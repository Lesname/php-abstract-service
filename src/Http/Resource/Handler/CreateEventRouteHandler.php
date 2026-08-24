<?php

declare(strict_types=1);

namespace LesAbstractService\Http\Resource\Handler;

use Override;
use LesHydrator\Hydrator;
use LesDomain\Event\Event;
use LesDomain\Event\Store\Store;
use LesAbstractService\Clock\Clock;
use Psr\Http\Message\ServerRequestInterface;
use LesValueObject\String\Exception\TooLong;
use LesValueObject\String\Exception\TooShort;
use LesDocumentor\Route\Attribute\DocHttpResponse;
use LesDocumentor\Route\Attribute\DocInputProvided;
use LesValueObject\String\Format\Exception\NotFormat;
use LesDomain\Identifier\Generator\IdentifierGenerator;
use LesHttp\Middleware\Route\Handler\Response\HandleResponse;
use LesHttp\Middleware\Route\Handler\Response\CreatedHandleResponse;
use LesAbstractService\Http\Resource\Handler\Response\CreatedResponse;

#[DocInputProvided(['id', 'occurredOn', 'headers'])]
#[DocHttpResponse(CreatedResponse::class, 201)]
final class CreateEventRouteHandler extends AbstractEventRouteHandler
{
    /**
     * @psalm-pure
     */
    public function __construct(
        private readonly IdentifierGenerator $identifierGenerator,
        private readonly string $projectName,
        Hydrator $hydrator,
        Store $store,
        Clock $clock,
    ) {
        parent::__construct($hydrator, $store, $clock);
    }

    /**
     * @psalm-mutation-free
     */
    #[Override]
    protected function createResponse(ServerRequestInterface $request, Event $event): HandleResponse
    {
        assert(isset($event->id));

        return new CreatedHandleResponse(
            [
                'type' => "{$this->projectName}.{$event->target}",
                'id' => $event->id,
            ],
        );
    }

    /**
     * @return array<mixed>
     *
     * @throws TooLong
     * @throws TooShort
     * @throws NotFormat
     */
    #[Override]
    protected function getEventData(ServerRequestInterface $request): array
    {
        $data = parent::getEventData($request);

        $data['id'] = $this->identifierGenerator->generate();

        return $data;
    }
}
