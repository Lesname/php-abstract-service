<?php
declare(strict_types=1);

namespace LesAbstractService\Mezzio;

use Monolog\Logger;
use RuntimeException;
use Sentry\State\Hub;
use LesQueue as Queue;
use LesHydrator\Hydrator;
use LesHttp\Router\Router;
use LesAbstractService\Cli;
use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Connection;
use LesHttp\Router\RpcRouter;
use Sentry\State\HubInterface;
use LesCache\Redis\RedisCache;
use LesToken\Codec\TokenCodec;
use LesDomain\Event\Store\Store;
use Psr\SimpleCache\CacheInterface;
use LesValidator\TranslationHelper;
use LesHydrator\ReflectionHydrator;
use LesAbstractService\Application;
use LesDomain\Event\Store\DbalStore;
use LesHttp\Router\RpcRouterFactory;
use LesCache\Redis\RedisCacheFactory;
use LesToken\Codec\TokenCodecFactory;
use LesAbstractService\Container\Mail;
use LesHttp\Handler\MiddlewarePipeline;
use LesDocumentor\Route\RouteDocumentor;
use LesDomain\Event\Publisher\Publisher;
use LesDatabase\Factory\ConnectionFactory;
use Psr\Http\Server\RequestHandlerInterface;
use LesHttp\Middleware\Input\TrimMiddleware;
use LesHttp\Handler\MiddlewarePipelineFactory;
use LesHttp\Middleware\Response\CorsMiddleware;
use LesHttp\Middleware\Route\DispatchMiddleware;
use LesHttp\Middleware\Input\ValidationMiddleware;
use LesHttp\Middleware\Input\Decode\JsonMiddleware;
use LesHttp\Middleware\Response\CorsMiddlewareFactory;
use LesDocumentor\Route\Input\LesRouteInputDocumentor;
use LesHttp\Middleware\Response\CatchExceptionMiddleware;
use Symfony\Component\Translation\Translator;
use LesDocumentor\Route\LesRouteDocumentor;
use LesHttp\Middleware\Route\RouterMiddleware;
use LesHttp\Middleware\Locale\LocaleMiddleware;
use LesHttp\Middleware\Route\NoRouteMiddleware;
use Laminas\Stratigility\Middleware\ErrorHandler;
use LesAbstractService\Factory\Logger\HubFactory;
use LesDocumentor\Route\Document\Property\Method;
use LesHttp\Middleware\AccessControl\Condition\ConditionMiddleware;
use LesHttp\Middleware\AccessControl\Authorization\AuthorizationMiddleware;
use LesDocumentor\Route\Input\RouteInputDocumentor;
use LesHttp\Middleware\AccessControl\Throttle\ThrottleMiddleware;
use LesHttp\Middleware\AccessControl\Throttle\ThrottleMiddlewareFactory;
use Symfony\Contracts\Translation\TranslatorInterface;
use LesAbstractService\Factory\Logger\MonologFactory;
use LesHttp\Middleware\Analytics\AnalyticsMiddleware;
use LesHttp\Middleware\Locale\LocaleMiddlewareFactory;
use LesDomain\Identifier\Generator\IdentifierGenerator;
use LesAbstractService\Http;
use LesDocumentor\Route\Input\MezzioRouteInputDocumentor;
use LesAbstractService\Factory\Queue\RabbitMqQueueFactory;
use LesHttp\Middleware\AccessControl\Authentication\AuthenticationMiddleware;
use LesDomain\Event\Publisher\FiberSubscriptionsPublisher;
use LesAbstractService\Factory\Container\ReflectionFactory;
use LesDomain\Identifier\Generator\Uuid6IdentifierGenerator;
use LesHttp\Middleware\Analytics\AnalyticsMiddlewareFactory;
use LesHttp\Middleware\AccessControl\Authentication\AuthenticationMiddlewareFactory;
use LesAbstractService\Factory\Symfony\Translator\TranslatorFactory;
use LesAbstractService\Factory\Logger\SentryMonologDelegatorFactory;
use LesDomain\Event\Publisher\AbstractSubscriptionsPublisherFactory;
use LesHttp\Middleware\AccessControl\Authorization\Constraint\GuestAuthorizationConstraint;
use LesHttp\Middleware\AccessControl\Authorization\Constraint\AnyOneAuthorizationConstraint;
use LesHttp\Middleware\AccessControl\Authorization\Constraint\AnyIdentityAuthorizationConstraint;
use LesHttp\Middleware\AccessControl\Authorization\Constraint\NoOneAuthorizationConstraint;
use LesAbstractService\Middleware\Authorization\Constraint as AuthorizationConstraint;
use LesAbstractService\Permission\Http\AuthorizationConstraint\HasGrantPermissionAuthorization;

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
            'dependencies' => [
                'aliases' => [
                    CacheInterface::class => RedisCache::class,

                    Hydrator::class => ReflectionHydrator::class,

                    Store::class => DbalStore::class,

                    Queue\Queue::class => Queue\DbalQueue::class,

                    Publisher::class => FiberSubscriptionsPublisher::class,

                    IdentifierGenerator::class => Uuid6IdentifierGenerator::class,

                    RouteDocumentor::class => LesRouteDocumentor::class,
                    RouteInputDocumentor::class => LesRouteInputDocumentor::class,

                    Router::class => RpcRouter::class,

                    TranslatorInterface::class => Translator::class,

                    LoggerInterface::class => Logger::class,
                    HubInterface::class => Hub::class,

                    RequestHandlerInterface::class => MiddlewarePipeline::class,
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

                    LesRouteDocumentor::class => LesRouteDocumentor::class,
                    /** @phpstan-ignore-next-line  */
                    MezzioRouteInputDocumentor::class => MezzioRouteInputDocumentor::class,
                    LesRouteInputDocumentor::class => LesRouteInputDocumentor::class,

                    AnyIdentityAuthorizationConstraint::class => AnyIdentityAuthorizationConstraint::class,
                    AnyOneAuthorizationConstraint::class => AnyOneAuthorizationConstraint::class,
                    GuestAuthorizationConstraint::class => GuestAuthorizationConstraint::class,
                    NoOneAuthorizationConstraint::class => NoOneAuthorizationConstraint::class,

                    TrimMiddleware::class => TrimMiddleware::class,

                    AuthorizationConstraint\Account\AnyAccountAuthorizationConstraint::class => AuthorizationConstraint\Account\AnyAccountAuthorizationConstraint::class,
                    AuthorizationConstraint\Consumer\AnyConsumerAuthorizationConstraint::class => AuthorizationConstraint\Consumer\AnyConsumerAuthorizationConstraint::class,
                    AuthorizationConstraint\Producer\AnyProducerAuthorizationConstraint::class => AuthorizationConstraint\Producer\AnyProducerAuthorizationConstraint::class,
                ],
                'factories' => [
                    Application::class => ReflectionFactory::class,

                    Mail\TemplateContainer::class => Mail\TemplateContainerFactory::class,
                    Mail\SenderContainer::class => Mail\SenderContainerFactory::class,

                    RedisCache::class => RedisCacheFactory::class,

                    Connection::class => ConnectionFactory::class,

                    DbalStore::class => ReflectionFactory::class,

                    Queue\RabbitMqQueue::class => RabbitMqQueueFactory::class,
                    Queue\DbalQueue::class => ReflectionFactory::class,

                    FiberSubscriptionsPublisher::class => AbstractSubscriptionsPublisherFactory::class,

                    MiddlewarePipeline::class => MiddlewarePipelineFactory::class,

                    JsonMiddleware::class => ReflectionFactory::class,
                    AuthenticationMiddleware::class => AuthenticationMiddlewareFactory::class,
                    AuthorizationMiddleware::class => ReflectionFactory::class,
                    ConditionMiddleware::class => ReflectionFactory::class,
                    ThrottleMiddleware::class => ThrottleMiddlewareFactory::class,
                    AnalyticsMiddleware::class => AnalyticsMiddlewareFactory::class,
                    ValidationMiddleware::class => ReflectionFactory::class,
                    LocaleMiddleware::class => LocaleMiddlewareFactory::class,
                    CatchExceptionMiddleware::class => ReflectionFactory::class,
                    CorsMiddleware::class => CorsMiddlewareFactory::class,
                    DispatchMiddleware::class => ReflectionFactory::class,
                    NoRouteMiddleware::class => ReflectionFactory::class,
                    RouterMiddleware::class => ReflectionFactory::class,

                    Http\Queue\Handler\DeleteHandler::class => ReflectionFactory::class,
                    Http\Queue\Handler\ReanimateHandler::class => ReflectionFactory::class,
                    Http\Queue\Handler\GetStatsHandler::class => ReflectionFactory::class,

                    Http\Resource\Handler\CreateEventRouteHandler::class => Http\Resource\Handler\CreateEventRouteHandlerFactory::class,
                    Http\Resource\Handler\UpdateEventRouteHandler::class => ReflectionFactory::class,

                    Http\Resource\Handler\ResultsQueryRouteHandler::class => ReflectionFactory::class,
                    Http\Resource\Handler\ResultQueryRouteHandler::class => ReflectionFactory::class,

                    RpcRouter::class => RpcRouterFactory::class,

                    /** @phpstan-ignore classConstant.deprecatedClass */
                    Http\Resource\ConditionConstraint\ExistsResourceConditionConstraint::class => ReflectionFactory::class,
                    Http\Resource\ConditionConstraint\ExistsConditionConstraint::class => ReflectionFactory::class,
                    Http\Resource\ConditionConstraint\VersionConditionConstraint::class => ReflectionFactory::class,

                    Cli\Cache\ClearCommand::class => ReflectionFactory::class,

                    Cli\Documentor\WriteCommand::class => Cli\Documentor\WriteCommandFactory::class,

                    Cli\Queue\CountProcessableCommand::class => ReflectionFactory::class,
                    Cli\Queue\CountProcessingCommand::class => ReflectionFactory::class,
                    Cli\Queue\ProcessCommand::class => Cli\Queue\ProcessCommandFactory::class,
                    Cli\Queue\QuitCommand::class => ReflectionFactory::class,

                    Cli\Service\CleanUpCommand::class => ReflectionFactory::class,
                    Cli\Service\UpdateCommand::class => ReflectionFactory::class,

                    Logger::class => MonologFactory::class,
                    Hub::class => HubFactory::class,

                    TokenCodec::class => TokenCodecFactory::class,

                    Translator::class => TranslatorFactory::class,
                ],
            ],
            'laminas-cli' => [
                'commands' => [
                    'cache.clear' => Cli\Cache\ClearCommand::class,

                    'documentor.write' => Cli\Documentor\WriteCommand::class,

                    'queue.countProcessable' => Cli\Queue\CountProcessableCommand::class,
                    'queue.countProcessing' => Cli\Queue\CountProcessingCommand::class,
                    'queue.process' => Cli\Queue\ProcessCommand::class,
                    'queue.quit' => Cli\Queue\QuitCommand::class,

                    'service.update' => Cli\Service\UpdateCommand::class,
                    'service.cleanUp' => Cli\Service\CleanUpCommand::class,
                ],
            ],
            'routes' => [
                ...$this->composeQueueRoutes(),
            ],
            LocaleMiddleware::class => [
                'defaultLocale' => 'nl_NL',
                'allowedLocales' => [
                    'nl_NL',
                    'en_US',
                ],
            ],
            'cors' => [
                'methods' => [
                    Method::Post->value,
                    Method::Put->value,
                    Method::Patch->value,
                    Method::Query->value,
                    Method::Delete->value,
                ],
                'headers' => [
                    'Accept-Language',
                    'Authorization',
                    'Content-Type',
                    'If-Match',
                    'x-build',
                ],
                'maxAge' => 3_600,
            ],
            MiddlewarePipeline::class => [
                CatchExceptionMiddleware::class,
                CorsMiddleware::class,
                AuthenticationMiddleware::class,
                AnalyticsMiddleware::class,
                ThrottleMiddleware::class,
                RouterMiddleware::class,
                NoRouteMiddleware::class,
                JsonMiddleware::class,
                TrimMiddleware::class,
                LocaleMiddleware::class,
                ValidationMiddleware::class,
                AuthorizationMiddleware::class,
                ConditionMiddleware::class,
                DispatchMiddleware::class,
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
    private function composeQueueRoutes(): iterable
    {
        $builder = (new Http\Route\RpcRouteBuilder('queue', [HasGrantPermissionAuthorization::class]))
            ->withProxyClass(Queue\Queue::class)
            ->withExtraOption('document', false);

        yield from $builder->buildResultQueryRoute('countProcessing');
        yield from $builder->buildResultQueryRoute('countProcessable');
        yield from $builder->buildResultQueryRoute('countBuried');
        yield from $builder->buildResultsQueryRoute('getBuried');
        yield from $builder->buildRoute(Method::Query, 'getStats', Http\Queue\Handler\GetStatsHandler::class);

        yield from $builder->buildRoute(Method::Patch, 'reanimate', Http\Queue\Handler\ReanimateHandler::class);
        yield from $builder->buildRoute(Method::Delete, 'delete', Http\Queue\Handler\DeleteHandler::class);
    }
}
