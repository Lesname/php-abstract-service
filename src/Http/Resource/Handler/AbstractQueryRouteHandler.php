<?php
declare(strict_types=1);

namespace LesAbstractService\Http\Resource\Handler;

use Override;
use JsonException;
use ReflectionMethod;
use RuntimeException;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use LesHydrator\Hydrator;
use LesValueObject\ValueObject;
use LesHttp\Response\ErrorResponse;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use LesResource\Repository\Exception\NoResource;
use LesDocumentor\Route\Document\Property\Method;

abstract class AbstractQueryRouteHandler implements RequestHandlerInterface
{
    abstract protected function makeResponse(mixed $output): ResponseInterface;

    /**
     * @param array<mixed> $routes
     */
    final public function __construct(
        protected readonly ResponseFactoryInterface $responseFactory,
        protected readonly StreamFactoryInterface $streamFactory,
        protected readonly ContainerInterface $container,
        protected readonly Hydrator $hydrator,
        protected readonly array $routes,
    ) {}

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws JsonException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            return $this->makeResponse($this->callProxy($request));
        } catch (NoResource) {
            $stream = $this->streamFactory->createStream(
                json_encode(
                    new ErrorResponse(
                        'Request resource not found',
                        'resourceExists'
                    ),
                    flags: JSON_THROW_ON_ERROR
                ),
            );

            return $this
                ->responseFactory
                ->createResponse(404)
                ->withBody($stream);
        }
    }

    /**
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function callProxy(ServerRequestInterface $request): mixed
    {
        $route = $this->getRoute($request);
        assert(is_array($route['proxy']));
        assert(is_string($route['proxy']['class']));
        assert(interface_exists($route['proxy']['class']));
        assert(is_string($route['proxy']['method']));

        $refMethod = new ReflectionMethod($route['proxy']['class'], $route['proxy']['method']);

        $parameters = $this->getParametersForMethod($refMethod, $request);

        $proxy = $this->container->get($route['proxy']['class']);
        assert(is_object($proxy));

        return $proxy->{$route['proxy']['method']}(...$parameters);
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
     * @return iterable<mixed>
     */
    protected function getParametersForMethod(ReflectionMethod $methodName, ServerRequestInterface $request): iterable
    {
        foreach ($methodName->getParameters() as $parameter) {
            yield $parameter->getName() => $this->getParameterValue($request, $parameter);
        }
    }

    protected function getParameterValue(ServerRequestInterface $request, ReflectionParameter $parameter): mixed
    {
        $body = $request->getParsedBody();
        assert(is_array($body));

        if (!isset($body[$parameter->getName()])) {
            if (!$parameter->allowsNull()) {
                throw new RuntimeException();
            }

            return null;
        }

        $value = $body[$parameter->getName()];
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            $typeName = $type->getName();

            assert(is_string($value) || is_int($value) || is_float($value) || is_array($value));
            assert(is_subclass_of($typeName, ValueObject::class));

            return $this->hydrator->hydrate($typeName, $value);
        }

        return $value;
    }
}
