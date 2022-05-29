<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Handler\Event;

use JsonException;
use LessDocumentor\Route\Attribute\DocHttpResponse;
use LessDocumentor\Route\Attribute\DocInputProvided;
use LessDomain\Event\Event;
use LessDomain\Event\Store\Store;
use LessDomain\Identifier\IdentifierService;
use LessHydrator\Hydrator;
use LessAbstractService\Http\Handler\Event\Response\CreatedResponse;
use LessValueObject\Number\Exception\MaxOutBounds;
use LessValueObject\Number\Exception\MinOutBounds;
use LessValueObject\Number\Exception\PrecisionOutBounds;
use LessValueObject\String\Exception\TooLong;
use LessValueObject\String\Exception\TooShort;
use LessValueObject\String\Format\Exception\NotFormat;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

#[DocInputProvided(['id', 'occurredOn', 'headers'])]
#[DocHttpResponse(CreatedResponse::class, 201)]
final class CreateEventRouteHandler extends AbstractEventRouteHandler
{
    /**
     * @param ResponseFactoryInterface $responseFactory
     * @param StreamFactoryInterface $streamFactory
     * @param IdentifierService $identifierService
     * @param Hydrator $hydrator
     * @param Store $store
     * @param array<mixed> $routes
     */
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly IdentifierService $identifierService,
        Hydrator $hydrator,
        Store $store,
        array $routes,
    ) {
        parent::__construct($hydrator, $store, $routes);
    }

    /**
     * @throws JsonException
     *
     * @psalm-suppress NoInterfaceProperties false positive
     */
    protected function createResponse(ServerRequestInterface $request, Event $event): ResponseInterface
    {
        assert(isset($event->id));

        $body = $this->streamFactory->createStream(
            json_encode(
                ['id' => $event->id],
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
     * @throws MaxOutBounds
     * @throws MinOutBounds
     * @throws PrecisionOutBounds
     * @throws TooLong
     * @throws TooShort
     * @throws NotFormat
     *
     * @return array<mixed>
     */
    protected function getEventData(ServerRequestInterface $request): array
    {
        $data = parent::getEventData($request);

        $data['id'] = $this->identifierService->generate();

        return $data;
    }
}
