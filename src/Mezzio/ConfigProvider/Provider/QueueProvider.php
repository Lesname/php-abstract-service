<?php

declare(strict_types=1);

namespace LesAbstractService\Mezzio\ConfigProvider\Provider;

use LesQueue\Queue;
use LesQueue\DbalQueue;
use LesQueue\PgsqlQueue;
use LesAbstractService\Cli;
use LesQueue\RabbitMqQueue;
use LesAbstractService\Http;
use Symfony\Component\Console\Command\Command;
use LesDocumentor\Route\Document\Property\Method;
use LesAbstractService\Http\Route\RpcRouteBuilder;
use LesAbstractService\Factory\Queue\PgsqlQueueFactory;
use LesAbstractService\Factory\Queue\RabbitMqQueueFactory;
use LesAbstractService\Factory\Container\ReflectionFactory;

/**
 * @deprecated use QueueRoutesProvider and ConfigProvider from Queue lib
 */
final class QueueProvider
{
    /** @var class-string<Queue>|null */
    private ?string $useQueue = null;

    private ?RpcRouteBuilder $useRpcRouteBuilder = null;

    /**
     * @param class-string<Queue> $useQueue
     */
    public static function create(string $useQueue, ?RpcRouteBuilder $useRpcRouteBuilder = null): self
    {
        $provider = new self();
        $provider->useQueue = $useQueue;
        $provider->useRpcRouteBuilder = $useRpcRouteBuilder;

        return $provider;
    }

    /**
     * @param class-string<Queue> $queue
     *
     * @psalm-mutation-free
     */
    public function useQueue(string $queue): QueueProvider
    {
        return clone (
            $this,
            ['useQueue' => $queue],
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function useRpcRouteBuilder(RpcRouteBuilder $rpcRouteBuilder): QueueProvider
    {
        return clone (
            $this,
            ['useRpcRouteBuilder' => $rpcRouteBuilder],
        );
    }

    /**
     * @return array<string, mixed>
     *
     * @psalm-impure
     *
     * @psalm-suppress DeprecatedClass
     */
    public function __invoke(): array
    {
        $aliases = [];

        if ($this->useQueue) {
            $aliases[Queue::class] = $this->useQueue;
        }

        return [
            'routes' => $this->routes(),
            'dependencies' => [
                'aliases' => $aliases,
                'factories' => [
                    DbalQueue::class => ReflectionFactory::class,
                    RabbitMqQueue::class => RabbitMqQueueFactory::class,
                    PgsqlQueue::class => PgSqlQueueFactory::class,

                    Http\Queue\Handler\DeleteHandler::class => ReflectionFactory::class,
                    Http\Queue\Handler\ReanimateHandler::class => ReflectionFactory::class,
                    Http\Queue\Handler\GetStatsHandler::class => ReflectionFactory::class,

                    Cli\Queue\CountProcessableCommand::class => ReflectionFactory::class,
                    Cli\Queue\CountProcessingCommand::class => ReflectionFactory::class,
                    Cli\Queue\ProcessCommand::class => Cli\Queue\ProcessCommandFactory::class,
                    Cli\Queue\QuitCommand::class => ReflectionFactory::class,
                    Cli\Queue\ReanimateCommand::class => ReflectionFactory::class,
                ],
            ],
            'laminas-cli' => [
                'commands' => $this->cliCommands(),
            ],
        ];
    }

    /**
     * @return array<string, class-string<Command>>
     *
     * @psalm-pure
     */
    private function cliCommands(): array
    {
        return [
            'queue.countProcessable' => Cli\Queue\CountProcessableCommand::class,
            'queue.countProcessing' => Cli\Queue\CountProcessingCommand::class,
            'queue.process' => Cli\Queue\ProcessCommand::class,
            'queue.quit' => Cli\Queue\QuitCommand::class,
            'queue.reanimate' => Cli\Queue\ReanimateCommand::class,
        ];
    }

    /**
     * @return array<string, array<mixed>>
     *
     * @psalm-impure
     */
    private function routes(): array
    {
        if ($this->useRpcRouteBuilder === null) {
            return [];
        }

        $builder = $this
            ->useRpcRouteBuilder
            ->withProxyClass(Queue::class)
            ->withExtraOption('document', false);

        return [
            ...$builder->buildResultQueryRoute('countProcessing'),
            ...$builder->buildResultQueryRoute('countProcessable'),
            ...$builder->buildResultQueryRoute('countBuried'),
            ...$builder->buildResultsQueryRoute('getBuried'),

            ...$builder->buildRoute(Method::Query, 'getStats', Http\Queue\Handler\GetStatsHandler::class),
            ...$builder->buildRoute(Method::Patch, 'reanimate', Http\Queue\Handler\ReanimateHandler::class),
            ...$builder->buildRoute(Method::Delete, 'delete', Http\Queue\Handler\DeleteHandler::class),
        ];
    }
}
