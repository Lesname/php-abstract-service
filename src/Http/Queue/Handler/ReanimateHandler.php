<?php

declare(strict_types=1);

namespace LesAbstractService\Http\Queue\Handler;

use Override;
use LesQueue\Queue;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use LesDocumentor\Route\Attribute\DocInput;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use LesDocumentor\Route\Attribute\DocHttpResponse;
use LesAbstractService\Http\Queue\Handler\Parameters\ReanimateParameters;
use LesAbstractService\Http\Resource\Handler\Helper\HydrateParametersHelper;

#[DocInput(ReanimateParameters::class)]
#[DocHttpResponse(code: 204)]
final class ReanimateHandler implements RequestHandlerInterface
{
    use HydrateParametersHelper;

    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly Queue $queue,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $this->hydrateParameters($request, ReanimateParameters::class);

        $this->queue->reanimate($parameters->id, $parameters->until);

        return $this->responseFactory->createResponse(204);
    }
}
