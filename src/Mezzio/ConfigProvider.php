<?php
declare(strict_types=1);

namespace LessAbstractService\Mezzio;

use Doctrine\DBAL\Connection;
use LessAbstractService\Cli;
use LessAbstractService\Container\Factory\ReflectionFactory;
use LessAbstractService\Http\Handler\Event;
use LessAbstractService\Http\Handler\Query;
use LessAbstractService\Http\Prerequisite\Resource\ResourceExistsPrerequisite;
use LessAbstractService\Http\Prerequisite\Resource\ResourcePrerequisiteFactory;
use LessAbstractService\Middleware\Authorization\Constraint as AuthorizationConstraint;
use LessAbstractService\Queue\Worker;
use LessAbstractService\Router\RpcRouter;
use LessAbstractService\Router\RpcRouterFactory;
use LessAbstractService\Token;
use LessDatabase\Factory\ConnectionFactory;
use LessDocumentor\Route\Input\MezzioRouteInputDocumentor;
use LessDocumentor\Route\Input\RouteInputDocumentor;
use LessDocumentor\Route\MezzioRouteDocumentor;
use LessDocumentor\Route\RouteDocumentor;
use LessDomain\Event\Publisher\FifoPublisher;
use LessDomain\Event\Publisher\FifoPublisherFactory;
use LessDomain\Event\Publisher\Publisher;
use LessDomain\Event\Store\DbalStore;
use LessDomain\Event\Store\Store;
use LessDomain\Identifier\IdentifierService;
use LessDomain\Identifier\Uuid6IdentifierService;
use LessHttp\Middleware\Analytics\AnalyticsMiddleware;
use LessHttp\Middleware\Analytics\AnalyticsMiddlewareFactory;
use LessHttp\Middleware\Authentication\AuthenticationMiddleware;
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
use LessValidator\Builder\GenericValidatorBuilder;
use LessValidator\Builder\TypeDocumentValidatorBuilder;
use Mezzio\Router\RouterInterface;

final class ConfigProvider
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                'aliases' => [
                    Hydrator::class => ReflectionHydrator::class,

                    Store::class => DbalStore::class,

                    Queue::class => DbalQueue::class,

                    Publisher::class => FifoPublisher::class,

                    IdentifierService::class => Uuid6IdentifierService::class,

                    TypeDocumentValidatorBuilder::class => GenericValidatorBuilder::class,
                    RouteDocumentor::class => MezzioRouteDocumentor::class,
                    RouteInputDocumentor::class => MezzioRouteInputDocumentor::class,

                    RouterInterface::class => RpcRouter::class,

                    Token\TokenService::class => Token\JwTokenService::class,
                ],
                'invokables' => [
                    ReflectionHydrator::class => ReflectionHydrator::class,

                    Uuid6IdentifierService::class => Uuid6IdentifierService::class,

                    GenericValidatorBuilder::class => GenericValidatorBuilder::class,

                    MezzioRouteDocumentor::class => MezzioRouteDocumentor::class,
                    MezzioRouteInputDocumentor::class => MezzioRouteInputDocumentor::class,

                    AnyOneAuthorizationConstraint::class => AnyOneAuthorizationConstraint::class,

                    AuthorizationConstraint\Account\AnyAccountAuthorizationConstraint::class => AuthorizationConstraint\Account\AnyAccountAuthorizationConstraint::class,
                    AuthorizationConstraint\Consumer\AnyConsumerAuthorizationConstraint::class => AuthorizationConstraint\Consumer\AnyConsumerAuthorizationConstraint::class,
                    AuthorizationConstraint\Producer\AnyProducerAuthorizationConstraint::class => AuthorizationConstraint\Producer\AnyProducerAuthorizationConstraint::class,
                ],
                'factories' => [
                    Connection::class => ConnectionFactory::class,

                    DbalStore::class => ReflectionFactory::class,

                    DbalQueue::class => ReflectionFactory::class,

                    Token\JwTokenService::class => Token\JwTokenServiceFactory::class,

                    FifoPublisher::class => FifoPublisherFactory::class,

                    AuthenticationMiddleware::class => ReflectionFactory::class,
                    AnalyticsMiddleware::class => AnalyticsMiddlewareFactory::class,
                    ThrottleMiddleware::class => ThrottleMiddlewareFactory::class,
                    CorsMiddleware::class => CorsMiddlewareFactory::class,
                    ValidationMiddleware::class => ValidationMiddlewareFactory::class,
                    AuthorizationMiddleware::class => AuthorizationMiddlewareFactory::class,
                    PrerequisiteMiddleware::class => PrerequisiteMiddlewareFactory::class,

                    Event\CreateEventRouteHandler::class => Event\CreateEventRouteHandlerFactory::class,
                    Event\UpdateEventRouteHandler::class => Event\UpdateEventRouteHandlerFactory::class,

                    Query\ResultsQueryRouteHandler::class => Query\QueryRouteHandlerFactory::class,
                    Query\ResultQueryRouteHandler::class => Query\QueryRouteHandlerFactory::class,

                    RpcRouter::class => RpcRouterFactory::class,

                    ResourceExistsPrerequisite::class => ResourcePrerequisiteFactory::class,

                    Cli\Service\LoadAccountRolesCommand::class => ReflectionFactory::class,
                    Cli\Documentor\WriteCommand::class => Cli\Documentor\WriteCommandFactory::class,
                    Cli\Queue\ProcessCommand::class => Cli\Queue\ProcessCommandFactory::class,

                    Worker\Service\LoadAccountRolesWorker::class => ReflectionFactory::class,
                ],
            ],
            'laminas-cli' => [
                'commands' => [
                    'service.loadAccountRoles' => Cli\Service\LoadAccountRolesCommand::class,
                    'documentor.write' => Cli\Documentor\WriteCommand::class,
                    'queue.process' => Cli\Queue\ProcessCommand::class,
                ],
            ],
            'workers' => [
                'service:loadAccountRoles' => Worker\Service\LoadAccountRolesWorker::class,
            ],
        ];
    }
}
