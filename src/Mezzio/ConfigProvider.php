<?php
declare(strict_types=1);

namespace LessAbstractService\Mezzio;

use Monolog\Logger;
use RuntimeException;
use Sentry\State\Hub;
use LessQueue as Queue;
use LessHydrator\Hydrator;
use LessAbstractService\Cli;
use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Connection;
use Sentry\State\HubInterface;
use LessCache\Redis\RedisCache;
use LessToken\Codec\TokenCodec;
use LessQueue\Worker\PingWorker;
use LessDomain\Event\Store\Store;
use Mezzio\Router\RouterInterface;
use Psr\SimpleCache\CacheInterface;
use LessValidator\TranslationHelper;
use LessHydrator\ReflectionHydrator;
use LessAbstractService\Queue\Worker;
use LessDomain\Event\Store\DbalStore;
use LessCache\Redis\RedisCacheFactory;
use LessToken\Codec\TokenCodecFactory;
use LessDocumentor\Route\RouteDocumentor;
use LessDomain\Event\Publisher\Publisher;
use LessDatabase\Factory\ConnectionFactory;
use LessHttp\Middleware\Cors\CorsMiddleware;
use Symfony\Component\Translation\Translator;
use LessDocumentor\Route\LessRouteDocumentor;
use LessDomain\Event\Publisher\FifoPublisher;
use LessHttp\Middleware\Locale\LocaleMiddleware;
use LessAbstractService\Mezzio\Router\RpcRouter;
use Laminas\Stratigility\Middleware\ErrorHandler;
use LessAbstractService\Factory\Logger\HubFactory;
use LessValidator\Builder\GenericValidatorBuilder;
use LessHttp\Middleware\Cors\CorsMiddlewareFactory;
use LessDocumentor\Route\Document\Property\Category;
use LessDocumentor\Route\Input\RouteInputDocumentor;
use LessDomain\Event\Publisher\FifoPublisherFactory;
use LessHttp\Middleware\Throttle\ThrottleMiddleware;
use Symfony\Contracts\Translation\TranslatorInterface;
use LessHttp\Middleware\Condition\ConditionMiddleware;
use LessAbstractService\Factory\Logger\MonologFactory;
use LessHttp\Middleware\Analytics\AnalyticsMiddleware;
use LessAbstractService\Container\Mail;
use LessHttp\Middleware\Locale\LocaleMiddlewareFactory;
use LessAbstractService\Mezzio\Router\RpcRouterFactory;
use LessValidator\Builder\TypeDocumentValidatorBuilder;
use LessAbstractService\Event\Listener\HookPushListener;
use LessDomain\Identifier\Generator\IdentifierGenerator;
use LessHttp\Middleware\Validation\ValidationMiddleware;
use LessDocumentor\Route\Input\MezzioRouteInputDocumentor;
use LessAbstractService\Factory\Queue\RabbitMqQueueFactory;
use LessHttp\Middleware\Throttle\ThrottleMiddlewareFactory;
use LessAbstractService\Factory\Container\ReflectionFactory;
use LessAbstractService\Mezzio\Router\Route\RpcRouteBuilder;
use LessHttp\Middleware\Prerequisite\PrerequisiteMiddleware;
use LessHttp\Middleware\Condition\ConditionMiddlewareFactory;
use LessDomain\Identifier\Generator\Uuid6IdentifierGenerator;
use LessHttp\Middleware\Analytics\AnalyticsMiddlewareFactory;
use LessHttp\Middleware\Authorization\AuthorizationMiddleware;
use LessHttp\Middleware\Validation\ValidationMiddlewareFactory;
use LessHttp\Middleware\Authentication\AuthenticationMiddleware;
use LessAbstractService\Http\Queue\Handler\Command\DeleteHandler;
use LessHttp\Middleware\Prerequisite\PrerequisiteMiddlewareFactory;
use LessAbstractService\Http\Queue\Handler\Command\ReanimateHandler;
use LessAbstractService\Factory\Symfony\Translator\TranslatorFactory;
use LessAbstractService\Factory\Logger\SentryMonologDelegatorFactory;
use LessHttp\Middleware\Authorization\AuthorizationMiddlewareFactory;
use LessAbstractService\Http\Service\Hook\Handler\Command\PushHandler;
use LessHttp\Middleware\Authentication\AuthenticationMiddlewareFactory;
use LessAbstractService\Http\Resource\Handler\Query\ResultQueryRouteHandler;
use LessAbstractService\Http\Resource\Handler\Query\QueryRouteHandlerFactory;
use LessAbstractService\Http\Resource\Handler\Query\ResultsQueryRouteHandler;
use LessAbstractService\Http\Service\Hook\Handler\Command\PushHandlerFactory;
use LessAbstractService\Http\Resource\Handler\Command\CreateEventRouteHandler;
use LessHttp\Middleware\Authorization\Constraint\NoOneAuthorizationConstraint;
use LessHttp\Middleware\Authorization\Constraint\GuestAuthorizationConstraint;
use LessAbstractService\Http\Resource\Handler\Command\UpdateEventRouteHandler;
use LessAbstractService\Http\Resource\Prerequisite\ResourceExistsPrerequisite;
use LessAbstractService\Http\Resource\Prerequisite\ResourcePrerequisiteFactory;
use LessHttp\Middleware\Authorization\Constraint\AnyOneAuthorizationConstraint;
use LessHttp\Middleware\Authorization\Constraint\AnyIdentityAuthorizationConstraint;
use LessAbstractService\Http\Resource\Handler\Command\CreateEventRouteHandlerFactory;
use LessAbstractService\Http\Resource\Handler\Command\UpdateEventRouteHandlerFactory;
use LessAbstractService\Middleware\Authorization\Constraint as AuthorizationConstraint;
use LessAbstractService\Http\Resource\ConditionConstraint\ExistsResourceConditionConstraint;
use LessAbstractService\Permission\Http\AuthorizationConstraint\HasGrantPermissionAuthorization;
use LessAbstractService\Http\Resource\ConditionConstraint\ExistsResourceConditionConstraintFactory;

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
                    ErrorHandler::class => [
                        Listener\SentryErrorListenerDelegatorFactory::class,
                    ],
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

                    AnyIdentityAuthorizationConstraint::class => AnyIdentityAuthorizationConstraint::class,
                    AnyOneAuthorizationConstraint::class => AnyOneAuthorizationConstraint::class,
                    GuestAuthorizationConstraint::class => GuestAuthorizationConstraint::class,
                    NoOneAuthorizationConstraint::class => NoOneAuthorizationConstraint::class,

                    PingWorker::class => PingWorker::class,

                    AuthorizationConstraint\Account\AnyAccountAuthorizationConstraint::class => AuthorizationConstraint\Account\AnyAccountAuthorizationConstraint::class,
                    AuthorizationConstraint\Consumer\AnyConsumerAuthorizationConstraint::class => AuthorizationConstraint\Consumer\AnyConsumerAuthorizationConstraint::class,
                    AuthorizationConstraint\Producer\AnyProducerAuthorizationConstraint::class => AuthorizationConstraint\Producer\AnyProducerAuthorizationConstraint::class,
                ],
                'factories' => [
                    Mail\TemplateContainer::class => Mail\TemplateContainerFactory::class,
                    Mail\SenderContainer::class => Mail\SenderContainerFactory::class,

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
                    ConditionMiddleware::class => ConditionMiddlewareFactory::class,

                    DeleteHandler::class => ReflectionFactory::class,
                    ReanimateHandler::class => ReflectionFactory::class,

                    CreateEventRouteHandler::class => CreateEventRouteHandlerFactory::class,
                    UpdateEventRouteHandler::class => UpdateEventRouteHandlerFactory::class,

                    ResultsQueryRouteHandler::class => QueryRouteHandlerFactory::class,
                    ResultQueryRouteHandler::class => QueryRouteHandlerFactory::class,

                    RpcRouter::class => RpcRouterFactory::class,

                    ResourceExistsPrerequisite::class => ResourcePrerequisiteFactory::class,
                    ExistsResourceConditionConstraint::class => ExistsResourceConditionConstraintFactory::class,

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
            'translation' => [
                'nl_NL' => [
                    __DIR__ . '/../../docs/translations/nl_NL.php',
                ],
                'en_US' => [
                    __DIR__ . '/../../docs/translations/en_US.php',
                ],
            ],
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
        $builder = (new RpcRouteBuilder('queue', [HasGrantPermissionAuthorization::class]))
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
