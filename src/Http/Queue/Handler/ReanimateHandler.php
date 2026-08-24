<?php

declare(strict_types=1);

namespace LesAbstractService\Http\Queue\Handler;

use Override;
use LesQueue\Queue;
use LesHttp\Router\Route\Route;
use LesDocumentor\Route\Attribute\DocInput;
use Psr\Http\Message\ServerRequestInterface;
use LesDocumentor\Route\Attribute\DocHttpResponse;
use LesHttp\Middleware\Route\Handler\RouteHandler;
use LesHttp\Middleware\Route\Handler\Response\HandleResponse;
use LesHttp\Middleware\Route\Handler\Response\EmptyHandleResponse;
use LesAbstractService\Http\Queue\Handler\Parameters\ReanimateParameters;
use LesAbstractService\Http\Resource\Handler\Helper\HydrateParametersHelper;

#[DocInput(ReanimateParameters::class)]
#[DocHttpResponse(code: 204)]
final class ReanimateHandler implements RouteHandler
{
    use HydrateParametersHelper;

    /**
     * @psalm-pure
     */
    public function __construct(private readonly Queue $queue)
    {}

    #[Override]
    public function handle(ServerRequestInterface $request, Route $route): HandleResponse
    {
        $parameters = $this->hydrateParameters($request, ReanimateParameters::class);

        $this->queue->reanimate($parameters->id, $parameters->until);

        return new EmptyHandleResponse();
    }
}
