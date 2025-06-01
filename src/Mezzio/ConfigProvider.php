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
use LesDomain\Event\Store\DbalStore;
use LesHttp\Router\RpcRouterFactory;
use LesCache\Redis\RedisCacheFactory;
use LesToken\Codec\TokenCodecFactory;
use LesAbstractService\Container\Mail;
use LesDocumentor\Route\RouteDocumentor;
use LesDomain\Event\Publisher\Publisher;
use LesDatabase\Factory\ConnectionFactory;
use LesHttp\Middleware\Cors\CorsMiddleware;
use Symfony\Component\Translation\Translator;
use LesDocumentor\Route\LesRouteDocumentor;
use LesHttp\Middleware\Route\RouterMiddleware;
use LesHttp\Middleware\Locale\LocaleMiddleware;
use LesHttp\Middleware\Route\NoRouteMiddleware;
use Laminas\Stratigility\Middleware\ErrorHandler;
use LesAbstractService\Factory\Logger\HubFactory;
use LesDocumentor\Route\Document\Property\Method;
use LesHttp\Middleware\Cors\CorsMiddlewareFactory;
use LesDocumentor\Route\Input\RouteInputDocumentor;
use LesHttp\Middleware\Throttle\ThrottleMiddleware;
use Symfony\Contracts\Translation\TranslatorInterface;
use LesHttp\Middleware\Condition\ConditionMiddleware;
use LesAbstractService\Factory\Logger\MonologFactory;
use LesHttp\Middleware\Analytics\AnalyticsMiddleware;
use LesHttp\Middleware\Locale\LocaleMiddlewareFactory;
use LesDomain\Identifier\Generator\IdentifierGenerator;
use LesHttp\Middleware\Validation\ValidationMiddleware;
use LesAbstractService\Http;
use LesDocumentor\Route\Input\MezzioRouteInputDocumentor;
use LesAbstractService\Factory\Queue\RabbitMqQueueFactory;
use LesHttp\Middleware\Throttle\ThrottleMiddlewareFactory;
use LesDomain\Event\Publisher\FiberSubscriptionsPublisher;
use LesAbstractService\Factory\Container\ReflectionFactory;
use LesAbstractService\Mezzio\Router\Route\RpcRouteBuilder;
use LesDomain\Identifier\Generator\Uuid6IdentifierGenerator;
use LesHttp\Middleware\Analytics\AnalyticsMiddlewareFactory;
use LesHttp\Middleware\Authorization\AuthorizationMiddleware;
use LesHttp\Middleware\Authentication\AuthenticationMiddleware;
use LesAbstractService\Factory\Symfony\Translator\TranslatorFactory;
use LesAbstractService\Factory\Logger\SentryMonologDelegatorFactory;
use LesDomain\Event\Publisher\AbstractSubscriptionsPublisherFactory;
use LesHttp\Middleware\Authentication\AuthenticationMiddlewareFactory;
use LesHttp\Middleware\Authorization\Constraint\NoOneAuthorizationConstraint;
use LesHttp\Middleware\Authorization\Constraint\GuestAuthorizationConstraint;
use LesHttp\Middleware\Authorization\Constraint\AnyOneAuthorizationConstraint;
use LesHttp\Middleware\Authorization\Constraint\AnyIdentityAuthorizationConstraint;
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
                    RouteInputDocumentor::class => MezzioRouteInputDocumentor::class,

                    Router::class => RpcRouter::class,

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

                    LesRouteDocumentor::class => LesRouteDocumentor::class,
                    MezzioRouteInputDocumentor::class => MezzioRouteInputDocumentor::class,

                    AnyIdentityAuthorizationConstraint::class => AnyIdentityAuthorizationConstraint::class,
                    AnyOneAuthorizationConstraint::class => AnyOneAuthorizationConstraint::class,
                    GuestAuthorizationConstraint::class => GuestAuthorizationConstraint::class,
                    NoOneAuthorizationConstraint::class => NoOneAuthorizationConstraint::class,

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

                    FiberSubscriptionsPublisher::class => AbstractSubscriptionsPublisherFactory::class,

                    AuthenticationMiddleware::class => AuthenticationMiddlewareFactory::class,
                    AnalyticsMiddleware::class => AnalyticsMiddlewareFactory::class,
                    ThrottleMiddleware::class => ThrottleMiddlewareFactory::class,
                    CorsMiddleware::class => CorsMiddlewareFactory::class,
                    ValidationMiddleware::class => ReflectionFactory::class,
                    AuthorizationMiddleware::class => ReflectionFactory::class,
                    ConditionMiddleware::class => ReflectionFactory::class,
                    RouterMiddleware::class => ReflectionFactory::class,
                    NoRouteMiddleware::class => ReflectionFactory::class,

                    Http\Queue\Handler\DeleteHandler::class => ReflectionFactory::class,
                    Http\Queue\Handler\ReanimateHandler::class => ReflectionFactory::class,
                    Http\Queue\Handler\GetStatsHandler::class => ReflectionFactory::class,

                    Http\Resource\Handler\CreateEventRouteHandler::class => ReflectionFactory::class,
                    Http\Resource\Handler\UpdateEventRouteHandler::class => ReflectionFactory::class,

                    Http\Resource\Handler\ResultsQueryRouteHandler::class => ReflectionFactory::class,
                    Http\Resource\Handler\ResultQueryRouteHandler::class => ReflectionFactory::class,

                    RpcRouter::class => RpcRouterFactory::class,

                    Http\Resource\ConditionConstraint\ExistsResourceConditionConstraint::class => ReflectionFactory::class,

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

                    LocaleMiddleware::class => LocaleMiddlewareFactory::class,
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
        $builder = (new RpcRouteBuilder('queue', [HasGrantPermissionAuthorization::class]))
            ->withProxyClass(Queue\Queue::class)
            ->withExtraOption('document', false);

        yield from $builder->buildResultQueryRoute('countProcessing');
        yield from $builder->buildResultQueryRoute('countProcessable');
        yield from $builder->buildResultQueryRoute('countBuried');
        yield from $builder->buildResultsQueryRoute('getBuried');
        yield from $builder->buildRouteV2(Method::Query, 'getStats', Http\Queue\Handler\GetStatsHandler::class);

        yield from $builder->buildRouteV2(Method::Patch, 'reanimate', Http\Queue\Handler\ReanimateHandler::class);
        yield from $builder->buildRouteV2(Method::Delete, 'delete', Http\Queue\Handler\DeleteHandler::class);
    }
}
