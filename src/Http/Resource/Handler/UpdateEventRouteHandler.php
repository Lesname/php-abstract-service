<?php

declare(strict_types=1);

namespace LesAbstractService\Http\Resource\Handler;

use Override;
use LesDomain\Event\Event;
use Psr\Http\Message\ServerRequestInterface;
use LesDocumentor\Route\Attribute\DocHttpResponse;
use LesDocumentor\Route\Attribute\DocInputProvided;
use LesHttp\Middleware\Route\Handler\Response\HandleResponse;
use LesHttp\Middleware\Route\Handler\Response\EmptyHandleResponse;

#[DocInputProvided(['occurredOn', 'headers'])]
#[DocHttpResponse(code: 204)]
final class UpdateEventRouteHandler extends AbstractEventRouteHandler
{
    /**
     * @psalm-pure
     */
    #[Override]
    protected function createResponse(ServerRequestInterface $request, Event $event): HandleResponse
    {
        // @phpstan-ignore possiblyImpure.new
        return new EmptyHandleResponse();
    }
}
