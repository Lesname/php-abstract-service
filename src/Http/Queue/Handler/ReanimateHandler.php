<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Queue\Handler;

use LessQueue\Queue;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use LessDocumentor\Route\Attribute\DocInput;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use LessDocumentor\Route\Attribute\DocHttpResponse;
use LessAbstractService\Http\Queue\Handler\Parameters\ReanimateParameters;
use LessAbstractService\Http\Resource\Handler\Helper\HydrateParametersHelper;

#[DocInput(ReanimateParameters::class)]
#[DocHttpResponse(code: 204)]
final class ReanimateHandler implements RequestHandlerInterface
{
    use HydrateParametersHelper;

    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly Queue $queue,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $this->hydrateParameters($request, ReanimateParameters::class);

        $this->queue->reanimate($parameters->id, $parameters->until);

        return $this->responseFactory->createResponse(204);
    }
}
