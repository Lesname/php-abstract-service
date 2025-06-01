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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use LesResource\Repository\Exception\NoResource;
use LesHttp\Router\Route\Exception\OptionNotSet;

abstract class AbstractQueryRouteHandler implements RequestHandlerInterface
{
    abstract protected function makeResponse(mixed $output): ResponseInterface;

    final public function __construct(
        protected readonly ResponseFactoryInterface $responseFactory,
        protected readonly StreamFactoryInterface $streamFactory,
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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws OptionNotSet
     */
    protected function callProxy(ServerRequestInterface $request): mixed
    {
        $route = $request->getAttribute('route');

        if (!$route instanceof Route) {
            throw new RuntimeException();
        }

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
