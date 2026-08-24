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
use LesHttp\Router\Route\Route;
use LesHttp\Response\ErrorResponse;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use LesResource\Repository\Exception\NoResource;
use LesHttp\Router\Route\Exception\OptionNotSet;
use LesHttp\Middleware\Route\Handler\RouteHandler;
use LesHttp\Middleware\Route\Handler\Response\HandleResponse;
use LesHttp\Middleware\Route\Handler\Response\DynamicErrorHandleResponse;

abstract class AbstractQueryRouteHandler implements RouteHandler
{
    /**
     * @psalm-impure
     */
    abstract protected function makeResponse(mixed $output): HandleResponse;

    /**
     * @psalm-pure
     */
    final public function __construct(
        protected readonly ContainerInterface $container,
        protected readonly Hydrator $hydrator,
    ) {}

    /**
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     * @throws OptionNotSet
     * @throws ReflectionException
     */
    #[Override]
    public function handle(ServerRequestInterface $request, Route $route): HandleResponse
    {
        try {
            return $this->makeResponse($this->callProxy($request, $route));
        } catch (NoResource) {
            return new DynamicErrorHandleResponse(
                404,
                new ErrorResponse(
                    'Request resource not found',
                    'resourceExists'
                ),
            );
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws OptionNotSet
     */
    protected function callProxy(ServerRequestInterface $request, Route $route): mixed
    {
        $proxy = $route->getOption('proxy');

        assert(is_array($proxy));
        assert(is_string($proxy['class']));
        assert(interface_exists($proxy['class']));
        assert(is_string($proxy['method']));

        $refMethod = new ReflectionMethod($proxy['class'], $proxy['method']);

        $parameters = $this->getParametersForMethod($refMethod, $request);

        $proxyClass = $this->container->get($proxy['class']);
        assert(is_object($proxyClass));

        return $proxyClass->{$proxy['method']}(...$parameters);
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
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

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
