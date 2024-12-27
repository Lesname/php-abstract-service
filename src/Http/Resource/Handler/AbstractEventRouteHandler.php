<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Resource\Handler;

use LessHydrator\Hydrator;
use LessDomain\Event\Event;
use LessDomain\Event\Store\Store;
use LessDomain\Event\Property\Headers;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use LessValueObject\String\Exception\TooLong;
use LessValueObject\String\Exception\TooShort;
use LessValueObject\Number\Exception\MaxOutBounds;
use LessValueObject\Number\Exception\MinOutBounds;
use LessValueObject\Number\Int\Date\MilliTimestamp;
use LessValueObject\Number\Exception\NotMultipleOf;
use LessValueObject\String\Format\Exception\NotFormat;

abstract class AbstractEventRouteHandler implements RequestHandlerInterface
{
    abstract protected function createResponse(ServerRequestInterface $request, Event $event): ResponseInterface;

    /**
     * @param array<mixed> $routes
     */
    public function __construct(
        private readonly Hydrator $hydrator,
        private readonly Store $store,
        private readonly array $routes,
    ) {}

    /**
     * @throws MaxOutBounds
     * @throws MinOutBounds
     * @throws NotFormat
     * @throws NotMultipleOf
     * @throws TooLong
     * @throws TooShort
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $event = $this->makeEvent($request);
        $this->store->persist($event);

        return $this->createResponse($request, $event);
    }

    /**
     * @throws MaxOutBounds
     * @throws MinOutBounds
     * @throws NotFormat
     * @throws NotMultipleOf
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
     */
    protected function getEventClass(ServerRequestInterface $request): string
    {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();
        $key = "{$method}:{$path}";

        assert(isset($this->routes[$key]));
        $route = $this->routes[$key];

        assert(is_array($route));
        assert(is_string($route['event']));
        assert(is_subclass_of($route['event'], Event::class));

        return $route['event'];
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
    protected function getEventData(ServerRequestInterface $request): array
    {
        $data = $request->getParsedBody();
        assert(is_array($data));

        $data['occurredOn'] = MilliTimestamp::now();
        $data['headers'] = Headers::fromRequest($request);

        return $data;
    }
}
