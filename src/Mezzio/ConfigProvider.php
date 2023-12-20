<?php
declare(strict_types=1);

namespace LessAbstractService\Mezzio;

use RuntimeException;
use Doctrine\DBAL\Connection;
use LessAbstractService\Cli;
use LessValidator\TranslationHelper;
use Symfony\Component\Translation\Translator;
use LessHttp\Middleware\Locale\LocaleMiddleware;
use Symfony\Contracts\Translation\TranslatorInterface;
use LessHttp\Middleware\Locale\LocaleMiddlewareFactory;
use LessAbstractService\Container\Factory\ReflectionFactory;
use LessAbstractService\Event\Listener\HookPushListener;
use LessAbstractService\Symfony\Translator\TranslatorFactory;
use LessAbstractService\Http\Queue\Handler\Command\DeleteHandler;
use LessAbstractService\Http\Queue\Handler\Command\ReanimateHandler;
use LessAbstractService\Http\Resource\Handler\Command\CreateEventRouteHandler;
use LessAbstractService\Http\Resource\Handler\Command\CreateEventRouteHandlerFactory;
use LessAbstractService\Http\Resource\Handler\Command\UpdateEventRouteHandler;
use LessAbstractService\Http\Resource\Handler\Command\UpdateEventRouteHandlerFactory;
use LessAbstractService\Http\Resource\Handler\Query\QueryRouteHandlerFactory;
use LessAbstractService\Http\Resource\Handler\Query\ResultQueryRouteHandler;
use LessAbstractService\Http\Resource\Handler\Query\ResultsQueryRouteHandler;
use LessAbstractService\Http\Resource\Prerequisite\ResourceExistsPrerequisite;
use LessAbstractService\Http\Resource\Prerequisite\ResourcePrerequisiteFactory;
use LessAbstractService\Http\Service\Hook\Handler\Command\PushHandler;
use LessAbstractService\Http\Service\Hook\Handler\Command\PushHandlerFactory;
use LessAbstractService\Logger\HubFactory;
use LessAbstractService\Logger\MonologFactory;
use LessAbstractService\Logger\SentryMonologDelegatorFactory;
use LessAbstractService\Middleware\Authorization\Constraint as AuthorizationConstraint;
use LessAbstractService\Queue\RabbitMqQueueFactory;
use LessAbstractService\Queue\Worker;
use LessAbstractService\Router\Route\RpcRouteBuilder;
use LessAbstractService\Router\RpcRouter;
use LessAbstractService\Router\RpcRouterFactory;
use LessCache\Redis\RedisCache;
use LessCache\Redis\RedisCacheFactory;
use LessDatabase\Factory\ConnectionFactory;
use LessDocumentor\Route\Document\Property\Category;
use LessDocumentor\Route\Input\MezzioRouteInputDocumentor;
use LessDocumentor\Route\Input\RouteInputDocumentor;
use LessDocumentor\Route\LessRouteDocumentor;
use LessDocumentor\Route\MezzioRouteDocumentor;
use LessDocumentor\Route\RouteDocumentor;
use LessDomain\Event\Publisher\FifoPublisher;
use LessDomain\Event\Publisher\FifoPublisherFactory;
use LessDomain\Event\Publisher\Publisher;
use LessDomain\Event\Store\DbalStore;
use LessDomain\Event\Store\Store;
use LessDomain\Identifier\Generator\IdentifierGenerator;
use LessDomain\Identifier\Generator\Uuid6IdentifierGenerator;
use LessHttp\Middleware\Analytics\AnalyticsMiddleware;
use LessHttp\Middleware\Analytics\AnalyticsMiddlewareFactory;
use LessHttp\Middleware\Authentication\AuthenticationMiddleware;
use LessHttp\Middleware\Authentication\AuthenticationMiddlewareFactory;
use LessHttp\Middleware\Authorization\AuthorizationMiddleware;
use LessHttp\Middleware\Authorization\AuthorizationMiddlewareFactory;
use LessHttp\Middleware\Authorization\Constraint\AnyOneAuthorizationConstraint;
use LessHttp\Middleware\Cors\CorsMiddleware;
use LessHttp\Middleware\Cors\CorsMiddlewareFactory;
use LessHttp\Middleware\Prerequisite\PrerequisiteMiddleware;
use LessHttp\Middleware\Prerequisite\PrerequisiteMiddlewareFactory;
use LessHttp\Middleware\Throttle\ThrottleMiddleware;
use LessHttp\Middleware\Throttle\ThrottleMiddlewareFactory;
use LessHttp\Middleware\Validation\ValidationMiddleware;
use LessHttp\Middleware\Validation\ValidationMiddlewareFactory;
use LessHydrator\Hydrator;
use LessHydrator\ReflectionHydrator;
use LessQueue as Queue;
use LessQueue\Worker\PingWorker;
use LessToken\Codec\TokenCodec;
use LessToken\Codec\TokenCodecFactory;
use LessValidator\Builder\GenericValidatorBuilder;
use LessValidator\Builder\TypeDocumentValidatorBuilder;
use Mezzio\Router\RouterInterface;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Sentry\State\Hub;
use Sentry\State\HubInterface;

final class ConfigProvider
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'translator' => $this->getTranslator(),
            'shared_by_default' => php_sapi_name() !== 'cli',
            PushHandler::class => [
                'eventQueueJobMap' => [
                    'account:registered' => 'service:loadAccountRole',
                    'account:roleChanged' => 'service:loadAccountRole',
                ],
            ],
            'dependencies' => [
                'aliases' => [
                    CacheInterface::class => RedisCache::class,

                    Hydrator::class => ReflectionHydrator::class,

                    Store::class => DbalStore::class,

                    Queue\Queue::class => Queue\DbalQueue::class,

                    Publisher::class => FifoPublisher::class,

                    IdentifierGenerator::class => Uuid6IdentifierGenerator::class,

                    TypeDocumentValidatorBuilder::class => GenericValidatorBuilder::class,
                    RouteDocumentor::class => LessRouteDocumentor::class,
                    RouteInputDocumentor::class => MezzioRouteInputDocumentor::class,

                    RouterInterface::class => RpcRouter::class,

                    TranslatorInterface::class => Translator::class,

                    LoggerInterface::class => Logger::class,
                    HubInterface::class => Hub::class,
                ],
                'delegators' => [
                    Logger::class => [
                        SentryMonologDelegatorFactory::class,
                    ],
                ],
                'invokables' => [
                    ReflectionHydrator::class => ReflectionHydrator::class,

                    Uuid6IdentifierGenerator::class => Uuid6IdentifierGenerator::class,

                    GenericValidatorBuilder::class => GenericValidatorBuilder::class,

                    LessRouteDocumentor::class => LessRouteDocumentor::class,
                    MezzioRouteInputDocumentor::class => MezzioRouteInputDocumentor::class,

                    AnyOneAuthorizationConstraint::class => AnyOneAuthorizationConstraint::class,

                    PingWorker::class => PingWorker::class,

                    AuthorizationConstraint\Account\AnyAccountAuthorizationConstraint::class => AuthorizationConstraint\Account\AnyAccountAuthorizationConstraint::class,
                    AuthorizationConstraint\Consumer\AnyConsumerAuthorizationConstraint::class => AuthorizationConstraint\Consumer\AnyConsumerAuthorizationConstraint::class,
                    AuthorizationConstraint\Producer\AnyProducerAuthorizationConstraint::class => AuthorizationConstraint\Producer\AnyProducerAuthorizationConstraint::class,
                ],
                'factories' => [
                    RedisCache::class => RedisCacheFactory::class,

                    Connection::class => ConnectionFactory::class,

                    DbalStore::class => ReflectionFactory::class,

                    Queue\RabbitMqQueue::class => RabbitMqQueueFactory::class,
                    Queue\DbalQueue::class => ReflectionFactory::class,

                    FifoPublisher::class => FifoPublisherFactory::class,

                    HookPushListener::class => ReflectionFactory::class,

                    AuthenticationMiddleware::class => AuthenticationMiddlewareFactory::class,
                    AnalyticsMiddleware::class => AnalyticsMiddlewareFactory::class,
                    ThrottleMiddleware::class => ThrottleMiddlewareFactory::class,
                    CorsMiddleware::class => CorsMiddlewareFactory::class,
                    ValidationMiddleware::class => ValidationMiddlewareFactory::class,
                    AuthorizationMiddleware::class => AuthorizationMiddlewareFactory::class,
                    PrerequisiteMiddleware::class => PrerequisiteMiddlewareFactory::class,

                    DeleteHandler::class => ReflectionFactory::class,
                    ReanimateHandler::class => ReflectionFactory::class,

                    CreateEventRouteHandler::class => CreateEventRouteHandlerFactory::class,
                    UpdateEventRouteHandler::class => UpdateEventRouteHandlerFactory::class,

                    ResultsQueryRouteHandler::class => QueryRouteHandlerFactory::class,
                    ResultQueryRouteHandler::class => QueryRouteHandlerFactory::class,

                    RpcRouter::class => RpcRouterFactory::class,

                    ResourceExistsPrerequisite::class =>
                        ResourcePrerequisiteFactory::class,

                    AuthorizationConstraint\Account\DeveloperAccountAuthorizationConstraint::class => ReflectionFactory::class,

                    PushHandler::class => PushHandlerFactory::class,

                    Cli\Documentor\WriteCommand::class => Cli\Documentor\WriteCommandFactory::class,

                    Cli\Queue\CountProcessableCommand::class => ReflectionFactory::class,
                    Cli\Queue\CountProcessingCommand::class => ReflectionFactory::class,
                    Cli\Queue\ProcessCommand::class => Cli\Queue\ProcessCommandFactory::class,
                    Cli\Queue\QuitCommand::class => ReflectionFactory::class,

                    Cli\Service\LoadAccountRolesCommand::class => ReflectionFactory::class,
                    Cli\Service\UpdateCommand::class => ReflectionFactory::class,

                    Worker\Service\LoadAccountRolesWorker::class => ReflectionFactory::class,
                    Worker\Service\LoadAccountRoleWorker::class => ReflectionFactory::class,

                    Worker\Hook\PushWorker::class => Worker\Hook\PushWorkerFactory::class,

                    Logger::class => MonologFactory::class,
                    Hub::class => HubFactory::class,

                    TokenCodec::class => TokenCodecFactory::class,

                    Translator::class => TranslatorFactory::class,

                    LocaleMiddleware::class => LocaleMiddlewareFactory::class,
                ],
            ],
            'laminas-cli' => [
                'commands' => [
                    'documentor.write' => Cli\Documentor\WriteCommand::class,

                    'queue.countProcessable' => Cli\Queue\CountProcessableCommand::class,
                    'queue.countProcessing' => Cli\Queue\CountProcessingCommand::class,
                    'queue.process' => Cli\Queue\ProcessCommand::class,
                    'queue.quit' => Cli\Queue\QuitCommand::class,

                    'service.loadAccountRoles' => Cli\Service\LoadAccountRolesCommand::class,
                    'service.update' => Cli\Service\UpdateCommand::class,
                ],
            ],
            'routes' => [
                ...$this->composeServiceHookRoutes(),
                ...$this->composeQueueRoutes(),
            ],
            'workers' => [
                'service:loadAccountRoles' => Worker\Service\LoadAccountRolesWorker::class,
                'service:loadAccountRole' => Worker\Service\LoadAccountRoleWorker::class,

                'hook:push' => Worker\Hook\PushWorker::class,

                'queue:ping' => PingWorker::class,
            ],
            LocaleMiddleware::class => [
                'defaultLocale' => 'nl_NL',
                'allowedLocales' => [
                    'nl_NL',
                    'en_US',
                ],
            ],
        ];
    }

    /**
     * @return array{defaultLocale: string, translation: array<string, array<string>>}
     */
    private function getTranslator(): array
    {
        $translator = [
            'defaultLocale' => 'nl_NL',
            'translation' => [],
        ];

        $libFiles = glob(TranslationHelper::getTranslationDirectory() . '/[a-z][a-z]_[A-Z][A-Z].php');

        if ($libFiles === false) {
            throw new RuntimeException();
        }

        foreach ($libFiles as $file) {
            if (!is_file($file) || !is_readable($file)) {
                continue;
            }

            $locale = pathinfo($file, PATHINFO_FILENAME);

            if (!isset($translator['translation'][$locale])) {
                $translator['translation'][$locale] = [];
            }

            $translator['translation'][$locale][] = $file;
        }

        return $translator;
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    private function composeServiceHookRoutes(): iterable
    {
        $builder = (new RpcRouteBuilder('service.hook', [AnyOneAuthorizationConstraint::class]))
            ->withExtraOption('document', false);

        yield from $builder->buildRoute('push', Category::Command, PushHandler::class);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    private function composeQueueRoutes(): iterable
    {
        $builder = (new RpcRouteBuilder('queue', [AuthorizationConstraint\Account\DeveloperAccountAuthorizationConstraint::class]))
            ->withProxyClass(Queue\Queue::class)
            ->withExtraOption('document', false);

        yield from $builder->buildResultQueryRoute('countProcessing');
        yield from $builder->buildResultQueryRoute('countProcessable');
        yield from $builder->buildResultQueryRoute('countBuried');
        yield from $builder->buildResultsQueryRoute('getBuried');

        yield from $builder->buildRoute('reanimate', Category::Command, ReanimateHandler::class);
        yield from $builder->buildRoute('delete', Category::Command, DeleteHandler::class);
    }
}
