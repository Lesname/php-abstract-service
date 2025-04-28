<?php
declare(strict_types=1);

namespace LesAbstractService\Permission\Config;

use LesAbstractService\Permission\Event;
use LesAbstractService\Permission\Cli;
use LesAbstractService\Mezzio\Router\Route\RpcRouteBuilder;
use LesAbstractService\Permission\Repository;
use LesAbstractService\Factory\Container\ReflectionFactory;
use LesAbstractService\Permission\Http\Condition;
use LesAbstractService\Permission\Http\AuthorizationConstraint;
use LesDomain\Event\Publisher\AbstractSubscriptionsPublisherFactory;

final class ConfigProvider
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        $developerRouteBuilder = (new RpcRouteBuilder('permission', [AuthorizationConstraint\HasGrantPermissionAuthorization::class]))
            ->withResourceRepository(Repository\PermissionsRepository::class);

        return [
            'dependencies' => [
                'aliases' => [
                    Repository\PermissionsRepository::class => Repository\DbalPermissionsRepository::class,
                ],
                'factories' => [
                    Repository\DbalPermissionsRepository::class => Repository\DbalPermissionsRepositoryFactory::class,

                    Event\Listener\DbalListener::class => ReflectionFactory::class,

                    Condition\HasNoPermissionsCondition::class => ReflectionFactory::class,
                    Condition\HasPermissionsCondition::class => ReflectionFactory::class,

                    Cli\GrantCommand::class => ReflectionFactory::class,
                    Cli\UpdateCommand::class => ReflectionFactory::class,

                    AuthorizationConstraint\HasGrantPermissionAuthorization::class => ReflectionFactory::class,
                    AuthorizationConstraint\HasReadPermissionAuthorization::class => ReflectionFactory::class,
                    AuthorizationConstraint\HasCreatePermissionAuthorization::class => ReflectionFactory::class,
                    AuthorizationConstraint\HasUpdatePermissionAuthorization::class => ReflectionFactory::class,
                ],
            ],
            AbstractSubscriptionsPublisherFactory::CONFIG_KEY => [
                Event\Listener\DbalListener::class => [
                    Event\GrantedEvent::class,
                    Event\UpdatedEvent::class,
                ],
            ],
            'routes' => [
                ...$developerRouteBuilder
                    ->withCondition(Condition\HasNoPermissionsCondition::class)
                    ->buildCreateEventRoute('grant', Event\GrantedEvent::class),
                ...$developerRouteBuilder
                    ->withCondition(Condition\HasPermissionsCondition::class)
                    ->buildUpdateEventRoute('update', Event\UpdatedEvent::class),

                ...$developerRouteBuilder->buildResultQueryRoute('getWithId'),
                ...$developerRouteBuilder->buildResultQueryRoute('getWithIdentity'),
                ...$developerRouteBuilder->buildResultsQueryRoute('getWithFlags'),
                ...$developerRouteBuilder->buildResultsQueryRoute('getByLastActivity'),
            ],
            'laminas-cli' => [
                'commands' => [
                    'permission.grant' => Cli\GrantCommand::class,
                    'permission.update' => Cli\UpdateCommand::class,
                ],
            ],
        ];
    }
}
