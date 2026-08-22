<?php

declare(strict_types=1);

namespace LesAbstractService\Http\Resource\Handler;

use Override;
use RuntimeException;
use LesHydrator\Hydrator;
use LesDomain\Event\Event;
use LesHttp\Router\Route\Route;
use LesDomain\Event\Store\Store;
use LesAbstractService\Clock\Clock;
use LesDomain\Event\Property\Headers;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use LesValueObject\String\Exception\TooLong;
use LesValueObject\String\Exception\TooShort;
use LesHttp\Router\Route\Exception\OptionNotSet;
use LesValueObject\String\Format\Exception\NotFormat;

abstract class AbstractEventRouteHandler implements RequestHandlerInterface
{
    /**
     * @psalm-impure
     */
    abstract protected function createResponse(ServerRequestInterface $request, Event $event): ResponseInterface;

    /**
     * @psalm-pure
     */
    public function __construct(
        private readonly Hydrator $hydrator,
        private readonly Store $store,
        private readonly Clock $clock,
    ) {}

    /**
     * @throws NotFormat
     * @throws OptionNotSet
     * @throws TooLong
     * @throws TooShort
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $event = $this->makeEvent($request);
        $this->store->persist($event);

        return $this->createResponse($request, $event);
    }

    /**
     * @throws NotFormat
     * @throws OptionNotSet
     * @throws TooLong
     * @throws TooShort
     */
    protected function makeEvent(ServerRequestInterface $request): Event
    {
        return $this->hydrator->hydrate(
            $this->getEventClass($request),
            $this->getEventData($request),
        );
    }

    /**
     * @return class-string<Event>
     *
     * @throws OptionNotSet
     */
    protected function getEventClass(ServerRequestInterface $request): string
    {
        $route = $request->getAttribute('route');

        if (!$route instanceof Route) {
            throw new RuntimeException();
        }

        $event = $route->getOption('event');

        assert(is_string($event));
        assert(is_subclass_of($event, Event::class));

        return $event;
    }

    /**
     * @return array<mixed>
     *
     * @throws TooLong
     * @throws TooShort
     * @throws NotFormat
     */
    protected function getEventData(ServerRequestInterface $request): array
    {
        $data = $request->getParsedBody();
        assert(is_array($data));

        $data['occurredOn'] = $this->clock->milliTimestamp();
        $data['headers'] = Headers::fromRequest($request);

        return $data;
    }
}
