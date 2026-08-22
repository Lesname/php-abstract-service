<?php

declare(strict_types=1);

namespace LesAbstractService\Mezzio\ConfigProvider\Provider;

use LesQueue as Queue;
use LesAbstractService\Cli;
use LesAbstractService\Http;
use LesDocumentor\Route\Document\Property\Method;
use LesHttp\Middleware\AccessControl\Authorization\Constraint\AuthorizationConstraint;
use LesAbstractService\Permission\Http\AuthorizationConstraint\HasGrantPermissionAuthorization;

/**
 * @deprecated use QueueProvider
 */
final class QueueRoutesProvider
{
    /**
     * @psalm-pure
     */
    public function __construct(private readonly Http\Route\RpcRouteBuilder $baseRpcRouteBuilder)
    {}

    /**
     * @param non-empty-array<class-string<AuthorizationConstraint>> $authorizations
     *
     * @psalm-pure
     */
    public static function withAuthorizations(array $authorizations): self
    {
        return new self(new Http\Route\RpcRouteBuilder('queue', $authorizations));
    }

    /**
     * @psalm-pure
     */
    public static function withGrantPermission(): self
    {
        return self::withAuthorizations([HasGrantPermissionAuthorization::class]);
    }

    /**
     * @return array<string, mixed>
     *
     * @psalm-impure
     */
    public function __invoke(): array
    {
        $builder = $this
            ->baseRpcRouteBuilder
            ->withProxyClass(Queue\Queue::class)
            ->withExtraOption('document', false);

        return [
            'routes' => [
                ...$builder->buildResultQueryRoute('countProcessing'),
                ...$builder->buildResultQueryRoute('countProcessable'),
                ...$builder->buildResultQueryRoute('countBuried'),
                ...$builder->buildResultsQueryRoute('getBuried'),

                ...$builder->buildRoute(Method::Query, 'getStats', Http\Queue\Handler\GetStatsHandler::class),
                ...$builder->buildRoute(Method::Patch, 'reanimate', Http\Queue\Handler\ReanimateHandler::class),
                ...$builder->buildRoute(Method::Delete, 'delete', Http\Queue\Handler\DeleteHandler::class),
            ],
        ];
    }
}
