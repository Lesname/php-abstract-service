<?php
declare(strict_types=1);

namespace LesAbstractService\Http\Resource\Handler;

use Override;
use JsonException;
use LesHydrator\Hydrator;
use LesDomain\Event\Event;
use LesDomain\Event\Store\Store;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use LesValueObject\String\Exception\TooLong;
use LesValueObject\String\Exception\TooShort;
use Psr\Http\Message\ResponseFactoryInterface;
use LesValueObject\Number\Exception\MaxOutBounds;
use LesValueObject\Number\Exception\MinOutBounds;
use LesValueObject\Number\Exception\NotMultipleOf;
use LesDocumentor\Route\Attribute\DocHttpResponse;
use LesDocumentor\Route\Attribute\DocInputProvided;
use LesValueObject\String\Format\Exception\NotFormat;
use LesDomain\Identifier\Generator\IdentifierGenerator;
use LesAbstractService\Http\Resource\Handler\Response\CreatedResponse;

#[DocInputProvided(['id', 'occurredOn', 'headers'])]
#[DocHttpResponse(CreatedResponse::class, 201)]
final class CreateEventRouteHandler extends AbstractEventRouteHandler
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly IdentifierGenerator $identifierGenerator,
        private readonly string $projectName,
        Hydrator $hydrator,
        Store $store,
    ) {
        parent::__construct($hydrator, $store);
    }

    /**
     * @throws JsonException
     */
    #[Override]
    protected function createResponse(ServerRequestInterface $request, Event $event): ResponseInterface
    {
        assert(isset($event->id));

        $body = $this->streamFactory->createStream(
            json_encode(
                [
                    'type' => "{$this->projectName}.{$event->target}",
                    'id' => $event->id,
                ],
                JSON_THROW_ON_ERROR,
            ),
        );

        return $this
            ->responseFactory
            ->createResponse(201)
            ->withBody($body)
            ->withAddedHeader('content-type', 'application/json');
    }

    /**
     * @return array<mixed>
     *
     * @throws MinOutBounds
     * @throws TooLong
     * @throws TooShort
     * @throws NotFormat
     * @throws NotMultipleOf
     * @throws MaxOutBounds
     */
    #[Override]
    protected function getEventData(ServerRequestInterface $request): array
    {
        $data = parent::getEventData($request);

        $data['id'] = $this->identifierGenerator->generate();

        return $data;
    }
}
