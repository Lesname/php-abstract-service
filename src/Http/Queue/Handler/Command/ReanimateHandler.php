<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Queue\Handler\Command;

use LessAbstractService\Http\Queue\Handler\Command\Parameters\ReanimateParameters;
use LessAbstractService\Http\Resource\Handler\AbstractParametersHandler;
use LessDocumentor\Route\Attribute\DocHttpResponse;
use LessDocumentor\Route\Attribute\DocInput;
use LessHydrator\Hydrator;
use LessQueue\Queue;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[DocInput(ReanimateParameters::class)]
#[DocHttpResponse(code: 204)]
final class ReanimateHandler extends AbstractParametersHandler
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly Queue $queue,
        Hydrator $hydrator,
    ) {
        parent::__construct($hydrator);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $this->getParameters($request, ReanimateParameters::class);

        $this->queue->reanimate($parameters->id, $parameters->until);

        return $this
            ->responseFactory
            ->createResponse(204);
    }
}
