<?php
declare(strict_types=1);

namespace LesAbstractService;

use Mezzio\MiddlewareFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Laminas\Stratigility\MiddlewarePipeInterface;
use Laminas\HttpHandlerRunner\RequestHandlerRunnerInterface;

final class Application
{
    public function __construct(
        private readonly RequestHandlerRunnerInterface $runner,
        private readonly MiddlewareFactoryInterface $factory,
        private readonly MiddlewarePipeInterface $pipeline,
    ) {
    }

    public function run(): void
    {
        $this->runner->run();
    }

    /**
     * @param class-string<MiddlewareInterface>|MiddlewareInterface $middleware
     */
    public function pipe(MiddlewareInterface|string $middleware): void
    {
        $this->pipeline->pipe($this->factory->prepare($middleware));
    }
}
