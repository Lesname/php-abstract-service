<?php
declare(strict_types=1);

namespace LesAbstractService\Http\Resource\Handler;

use Override;
use RuntimeException;
use LesHydrator\Hydrator;
use LesDomain\Event\Event;
use LesDomain\Event\Store\Store;
use LesDomain\Event\Property\Headers;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use LesValueObject\String\Exception\TooLong;
use LesValueObject\String\Exception\TooShort;
use LesValueObject\Number\Exception\MaxOutBounds;
use LesValueObject\Number\Exception\MinOutBounds;
use LesDocumentor\Route\Document\Property\Method;
use LesValueObject\Number\Int\Date\MilliTimestamp;
use LesValueObject\Number\Exception\NotMultipleOf;
use LesValueObject\String\Format\Exception\NotFormat;

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
    #[Override]
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
        $route = $this->getRoute($request);

        assert(is_string($route['event']));
        assert(is_subclass_of($route['event'], Event::class));

        return $route['event'];
    }

    /**
     * @return array<mixed>
     */
    private function getRoute(ServerRequestInterface $request): array
    {
        $method = strtolower($request->getMethod());
        $key = "{$method}:{$request->getUri()->getPath()}";

        if (isset($this->routes[$key])) {
            $route = $this->routes[$key];
            assert(is_array($route));

            return $route;
        }

        if ($method === Method::Post->value) {
            $tryMethods = [
                Method::Query->value,
                Method::Delete->value,
                Method::Patch->value,
                Method::Put->value,
            ];

            foreach ($tryMethods as $tryMethod) {
                $key = "{$tryMethod}:{$request->getUri()->getPath()}";

                if (isset($this->routes[$key])) {
                    $route = $this->routes[$key];
                    assert(is_array($route));

                    return $route;
                }
            }
        }

        throw new RuntimeException();
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
