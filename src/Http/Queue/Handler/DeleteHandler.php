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
use LesAbstractService\Http\Queue\Handler\Parameters\DeleteParameters;
use LesAbstractService\Http\Resource\Handler\Helper\HydrateParametersHelper;

#[DocInput(DeleteParameters::class)]
#[DocHttpResponse(code: 204)]
final class DeleteHandler implements RouteHandler
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
        $parameters = $this->hydrateParameters($request, DeleteParameters::class);
        $this->queue->delete($parameters->id);

        return new EmptyHandleResponse();
    }
}
