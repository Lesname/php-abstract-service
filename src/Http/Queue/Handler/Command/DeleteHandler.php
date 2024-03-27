<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Queue\Handler\Command;

use Psr\Http\Server\RequestHandlerInterface;
use LessAbstractService\Http\Resource\Handler\Helper\HydrateParametersHelper;
use LessAbstractService\Http\Queue\Handler\Command\Parameters\DeleteParameters;
use LessDocumentor\Route\Attribute\DocHttpResponse;
use LessDocumentor\Route\Attribute\DocInput;
use LessQueue\Queue;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[DocInput(DeleteParameters::class)]
#[DocHttpResponse(code: 204)]
final class DeleteHandler implements RequestHandlerInterface
{
    use HydrateParametersHelper;

    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly Queue $queue,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $this->hydrateParameters($request, DeleteParameters::class);
        $this->queue->delete($parameters->id);

        return $this->responseFactory->createResponse(204);
    }
}
