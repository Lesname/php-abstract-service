<?php

declare(strict_types=1);

namespace LesAbstractService\Http\Resource\Handler;

use Override;
use LesHydrator\Hydrator;
use LesDomain\Event\Event;
use LesDomain\Event\Store\Store;
use LesAbstractService\Clock\Clock;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use LesDocumentor\Route\Attribute\DocHttpResponse;
use LesDocumentor\Route\Attribute\DocInputProvided;

#[DocInputProvided(['occurredOn', 'headers'])]
#[DocHttpResponse(code: 204)]
final class UpdateEventRouteHandler extends AbstractEventRouteHandler
{
    /**
     * @psalm-pure
     */
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        Hydrator $hydrator,
        Store $store,
        Clock $clock,
    ) {
        parent::__construct($hydrator, $store, $clock);
    }

    /**
     * @psalm-impure
     */
    #[Override]
    protected function createResponse(ServerRequestInterface $request, Event $event): ResponseInterface
    {
        return $this->responseFactory->createResponse(204);
    }
}
