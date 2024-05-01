<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Resource\Handler;

use LessHydrator\Hydrator;
use LessDomain\Event\Event;
use LessDomain\Event\Store\Store;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use LessDocumentor\Route\Attribute\DocHttpResponse;
use LessDocumentor\Route\Attribute\DocInputProvided;

#[DocInputProvided(['occurredOn', 'headers'])]
#[DocHttpResponse(code: 204)]
final class UpdateEventRouteHandler extends AbstractEventRouteHandler
{
    /**
     * @param ResponseFactoryInterface $responseFactory
     * @param Hydrator $hydrator
     * @param Store $store
     * @param array<mixed> $routes
     */
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        Hydrator $hydrator,
        Store $store,
        array $routes,
    ) {
        parent::__construct($hydrator, $store, $routes);
    }

    protected function createResponse(ServerRequestInterface $request, Event $event): ResponseInterface
    {
        return $this->responseFactory->createResponse(204);
    }
}
