<?php
declare(strict_types=1);

namespace LessAbstractService\Mezzio;

use Doctrine\DBAL\Connection;
use LessAbstractService\Cli;
use LessAbstractService\Container\Factory\ReflectionFactory;
use LessAbstractService\Event\Listener\HookPushListener;
use LessAbstractService\Http\Handler\Event;
use LessAbstractService\Http\Handler\Query;
use LessAbstractService\Http\Prerequisite\Resource\ResourceExistsPrerequisite;
use LessAbstractService\Http\Prerequisite\Resource\ResourcePrerequisiteFactory;
use LessAbstractService\Http\Resource\Handler\Command\CreateEventRouteHandler;
use LessAbstractService\Http\Resource\Handler\Command\CreateEventRouteHandlerFactory;
use LessAbstractService\Http\Resource\Handler\Command\UpdateEventRouteHandler;
use LessAbstractService\Http\Resource\Handler\Command\UpdateEventRouteHandlerFactory;
use LessAbstractService\Http\Resource\Handler\Query\QueryRouteHandlerFactory;
use LessAbstractService\Http\Resource\Handler\Query\ResultQueryRouteHandler;
use LessAbstractService\Http\Resource\Handler\Query\ResultsQueryRouteHandler;
use LessAbstractService\Http\Service\Hook\Handler\Command\PushHandler;
use LessAbstractService\Http\Service\Hook\Handler\Command\PushHandlerFactory;
use LessAbstractService\Logger\HubFactory;
use LessAbstractService\Logger\MonologFactory;
use LessAbstractService\Logger\SentryMonologDelegatorFactory;
use LessAbstractService\Middleware\Authorization\Constraint as AuthorizationConstraint;
use LessAbstractService\Queue\Worker;
use LessAbstractService\Router\RpcRouter;
use LessAbstractService\Router\RpcRouterFactory;
use LessDatabase\Factory\ConnectionFactory;
use LessDocumentor\Route\Document\Property\Category;
use LessDocumentor\Route\Input\MezzioRouteInputDocumentor;
use LessDocumentor\Route\Input\RouteInputDocumentor;
use LessDocumentor\Route\MezzioRouteDocumentor;
use LessDocumentor\Route\RouteDocumentor;
use LessDomain\Event\Publisher\FifoPublisher;
use LessDomain\Event\Publisher\FifoPublisherFactory;
use LessDomain\Event\Publisher\Publisher;
use LessDomain\Event\Store\DbalStore;
use LessDomain\Event\Store\Store;
use LessDomain\Identifier\Generator\IdentifierGenerator;
use LessDomain\Identifier\Generator\Uuid6IdentifierGenerator;
use LessDomain\Identifier\IdentifierService;
use LessDomain\Identifier\Uuid6IdentifierService;
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
use LessQueue\DbalQueue;
use LessQueue\Queue;
use LessQueue\Worker\PingWorker;
use LessToken\Codec\TokenCodec;
use LessToken\Codec\TokenCodecFactory;
use LessValidator\Builder\GenericValidatorBuilder;
use LessValidator\Builder\TypeDocumentValidatorBuilder;
use Mezzio\Router\RouterInterface;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
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
            PushHandler::class => [
                'eventQueueJobMap' => [
                    'account:registered' => 'service:loadAccountRole',
                    'account:roleChanged' => 'service:loadAccountRole',
                ],
            ],
            'dependencies' => [
                'aliases' => [
                    Hydrator::class => ReflectionHydrator::class,

                    Store::class => DbalStore::class,

                    Queue::class => DbalQueue::class,

                    Publisher::class => FifoPublisher::class,

                    IdentifierService::class => Uuid6IdentifierService::class,
                    IdentifierGenerator::class => Uuid6IdentifierGenerator::class,

                    TypeDocumentValidatorBuilder::class => GenericValidatorBuilder::class,
                    RouteDocumentor::class => MezzioRouteDocumentor::class,
                    RouteInputDocumentor::class => MezzioRouteInputDocumentor::class,

                    RouterInterface::class => RpcRouter::class,

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

                    Uuid6IdentifierService::class => Uuid6IdentifierService::class,
                    Uuid6IdentifierGenerator::class => Uuid6IdentifierGenerator::class,

                    GenericValidatorBuilder::class => GenericValidatorBuilder::class,

                    MezzioRouteDocumentor::class => MezzioRouteDocumentor::class,
                    MezzioRouteInputDocumentor::class => MezzioRouteInputDocumentor::class,

                    AnyOneAuthorizationConstraint::class => AnyOneAuthorizationConstraint::class,

                    PingWorker::class => PingWorker::class,

                    AuthorizationConstraint\Account\AnyAccountAuthorizationConstraint::class => AuthorizationConstraint\Account\AnyAccountAuthorizationConstraint::class,
                    AuthorizationConstraint\Consumer\AnyConsumerAuthorizationConstraint::class => AuthorizationConstraint\Consumer\AnyConsumerAuthorizationConstraint::class,
                    AuthorizationConstraint\Producer\AnyProducerAuthorizationConstraint::class => AuthorizationConstraint\Producer\AnyProducerAuthorizationConstraint::class,
                ],
                'factories' => [
                    Connection::class => ConnectionFactory::class,

                    DbalStore::class => ReflectionFactory::class,

                    DbalQueue::class => ReflectionFactory::class,

                    FifoPublisher::class => FifoPublisherFactory::class,

                    HookPushListener::class => ReflectionFactory::class,

                    AuthenticationMiddleware::class => AuthenticationMiddlewareFactory::class,
                    AnalyticsMiddleware::class => AnalyticsMiddlewareFactory::class,
                    ThrottleMiddleware::class => ThrottleMiddlewareFactory::class,
                    CorsMiddleware::class => CorsMiddlewareFactory::class,
                    ValidationMiddleware::class => ValidationMiddlewareFactory::class,
                    AuthorizationMiddleware::class => AuthorizationMiddlewareFactory::class,
                    PrerequisiteMiddleware::class => PrerequisiteMiddlewareFactory::class,

                    Event\CreateEventRouteHandler::class => Event\CreateEventRouteHandlerFactory::class,
                    Event\UpdateEventRouteHandler::class => Event\UpdateEventRouteHandlerFactory::class,

                    CreateEventRouteHandler::class => CreateEventRouteHandlerFactory::class,
                    UpdateEventRouteHandler::class => UpdateEventRouteHandlerFactory::class,

                    Query\ResultsQueryRouteHandler::class => Query\QueryRouteHandlerFactory::class,
                    Query\ResultQueryRouteHandler::class => Query\QueryRouteHandlerFactory::class,

                    ResultsQueryRouteHandler::class => QueryRouteHandlerFactory::class,
                    ResultQueryRouteHandler::class => QueryRouteHandlerFactory::class,

                    RpcRouter::class => RpcRouterFactory::class,

                    ResourceExistsPrerequisite::class => ResourcePrerequisiteFactory::class,

                    \LessAbstractService\Http\Resource\Prerequisite\ResourceExistsPrerequisite::class =>
                        \LessAbstractService\Http\Resource\Prerequisite\ResourcePrerequisiteFactory::class,

                    AuthorizationConstraint\Account\DeveloperAccountAuthorizationConstraint::class => ReflectionFactory::class,

                    PushHandler::class => PushHandlerFactory::class,

                    Cli\Documentor\WriteCommand::class => Cli\Documentor\WriteCommandFactory::class,
                    Cli\Queue\ProcessCommand::class => Cli\Queue\ProcessCommandFactory::class,
                    Cli\Service\LoadAccountRolesCommand::class => ReflectionFactory::class,
                    Cli\Service\UpdateCommand::class => ReflectionFactory::class,

                    Worker\Service\LoadAccountRolesWorker::class => ReflectionFactory::class,
                    Worker\Service\LoadAccountRoleWorker::class => ReflectionFactory::class,

                    Worker\Hook\PushWorker::class => Worker\Hook\PushWorkerFactory::class,

                    Logger::class => MonologFactory::class,
                    Hub::class => HubFactory::class,

                    TokenCodec::class => TokenCodecFactory::class,
                ],
            ],
            'laminas-cli' => [
                'commands' => [
                    'documentor.write' => Cli\Documentor\WriteCommand::class,
                    'queue.process' => Cli\Queue\ProcessCommand::class,
                    'service.loadAccountRoles' => Cli\Service\LoadAccountRolesCommand::class,
                    'service.update' => Cli\Service\UpdateCommand::class,
                ],
            ],
            'routes' => [
                'POST:/service.hook.push' => [
                    'path' => '/service.hook.push',
                    AuthorizationMiddlewareFactory::ROUTE_KEY => [AnyOneAuthorizationConstraint::class],
                    'resource' => 'service.hook',
                    'middleware' => PushHandler::class,
                    'type' => Category::Command,
                    'category' => Category::Command,
                ],
            ],
            'workers' => [
                'service:loadAccountRoles' => Worker\Service\LoadAccountRolesWorker::class,
                'service:loadAccountRole' => Worker\Service\LoadAccountRoleWorker::class,

                'hook:push' => Worker\Hook\PushWorker::class,

                'queue:ping' => PingWorker::class,
            ],
        ];
    }
}
