<?php
declare(strict_types=1);

namespace LessAbstractServiceTest\Router\Route;

use LessAbstractService\Router\Route\RpcRouteBuilder;
use PHPUnit\Framework\TestCase;
use LessHttp\Middleware\Prerequisite\Constraint\PrerequisiteConstraint;
use LessHttp\Middleware\Authorization\Constraint\AuthorizationConstraint;
use LessHttp\Middleware\Authorization\Constraint\AnyOneAuthorizationConstraint;

/**
 * @covers \LessAbstractService\Router\Route\RpcRouteBuilder
 */
class RpcRouteBuilderTest extends TestCase
{
    public function testWithAddedAuthorization(): void
    {
        $baseConstraints = [AnyOneAuthorizationConstraint::class];
        $builder = new RpcRouteBuilder('foo', $baseConstraints);

        $withAdded = $builder->withAddedAuthorization(AuthorizationConstraint::class);

        self::assertSame($baseConstraints, $builder->authorizations);
        self::assertSame([...$baseConstraints, AuthorizationConstraint::class], $withAdded->authorizations);
        self::assertNotSame($builder, $withAdded);
    }

    public function testWithAddedPrerequisite(): void
    {
        $builder = new RpcRouteBuilder('foo', [AnyOneAuthorizationConstraint::class]);

        $withAdded = $builder->withAddedPrerequisite(PrerequisiteConstraint::class);

        self::assertSame([], $builder->prerequisites);
        self::assertSame([PrerequisiteConstraint::class], $withAdded->prerequisites);
        self::assertNotSame($builder, $withAdded);
    }
}
