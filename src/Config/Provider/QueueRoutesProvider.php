<?php

declare(strict_types=1);

namespace LesAbstractService\Config\Provider;

use LesQueue\Queue;
use LesDocumentor\Route\Document\Property\Method;
use LesAbstractService\Http\Route\RpcRouteBuilder;
use LesAbstractService\Http\Queue\Handler\DeleteHandler;
use LesAbstractService\Http\Queue\Handler\GetStatsHandler;
use LesAbstractService\Factory\Container\ReflectionFactory;
use LesAbstractService\Http\Queue\Handler\ReanimateHandler;

final class QueueRoutesProvider
{
    public function __construct(private readonly RpcRouteBuilder $rpcRouteBuilder)
    {}

    /**
     * @return array<string, mixed>
     *
     * @psalm-mutation-free
     */
    public function __invoke(): array
    {
        return [
            'routes' => $this->routes(),
            'dependencies' => [
                'factories' => [
                    DeleteHandler::class => ReflectionFactory::class,
                    ReanimateHandler::class => ReflectionFactory::class,
                    GetStatsHandler::class => ReflectionFactory::class,
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<mixed>>
     *
     * @psalm-mutation-free
     */
    private function routes(): array
    {
        $builder = $this
            ->rpcRouteBuilder
            ->withProxyClass(Queue::class)
            ->withExtraOption('document', false);

        return [
            ...$builder->buildResultQueryRoute('countProcessing'),
            ...$builder->buildResultQueryRoute('countProcessable'),
            ...$builder->buildResultQueryRoute('countBuried'),
            ...$builder->buildResultsQueryRoute('getBuried'),

            ...$builder->buildRoute(Method::Query, 'getStats', GetStatsHandler::class),
            ...$builder->buildRoute(Method::Patch, 'reanimate', ReanimateHandler::class),
            ...$builder->buildRoute(Method::Delete, 'delete', DeleteHandler::class),
        ];
    }
}
