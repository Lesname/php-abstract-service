<?php
declare(strict_types=1);

namespace LessAbstractService\Permission\Config;

use LessDomain\Event\Publisher\FifoPublisherFactory;
use LessAbstractService\Permission\Event;
use LessAbstractService\Permission\Cli;
use LessAbstractService\Mezzio\Router\Route\RpcRouteBuilder;
use LessAbstractService\Permission\Repository;
use LessAbstractService\Factory\Container\ReflectionFactory;
use LessAbstractService\Permission\Http\Condition;
use LessAbstractService\Permission\Http\AuthorizationConstraint;

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
                    AuthorizationConstraint\HasWritePermissionAuthorization::class => ReflectionFactory::class,
                ],
            ],
            FifoPublisherFactory::CONFIG_KEY => [
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
                ...$developerRouteBuilder->buildResultsQueryRoute('getWithIdentity'),
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
