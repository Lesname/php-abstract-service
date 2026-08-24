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
use Psr\Http\Message\ServerRequestInterface;
use LesValueObject\String\Exception\TooLong;
use LesValueObject\String\Exception\TooShort;
use LesHttp\Router\Route\Exception\OptionNotSet;
use LesHttp\Middleware\Route\Handler\RouteHandler;
use LesValueObject\String\Format\Exception\NotFormat;
use LesHttp\Middleware\Route\Handler\Response\HandleResponse;

abstract class AbstractEventRouteHandler implements RouteHandler
{
    /**
     * @psalm-impure
     */
    abstract protected function createResponse(ServerRequestInterface $request, Event $event): HandleResponse;

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
    public function handle(ServerRequestInterface $request, Route $route): HandleResponse
    {
        $event = $this->makeEvent($request, $route);
        $this->store->persist($event);

        return $this->createResponse($request, $event);
    }

    /**
     * @throws NotFormat
     * @throws OptionNotSet
     * @throws TooLong
     * @throws TooShort
     */
    protected function makeEvent(ServerRequestInterface $request, Route $route): Event
    {
        return $this->hydrator->hydrate(
            $this->getEventClass($request, $route),
            $this->getEventData($request),
        );
    }

    /**
     * @return class-string<Event>
     *
     * @throws OptionNotSet
     *
     * @psalm-mutation-free
     */
    protected function getEventClass(ServerRequestInterface $request, Route $route): string
    {
        $event = $route->getOption('event');

        if (!is_string($event) || !is_subclass_of($event, Event::class)) {
            throw new RuntimeException();
        }

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
