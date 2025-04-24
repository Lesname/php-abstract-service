<?php
declare(strict_types=1);

namespace LesAbstractService\Factory\Container;

use Closure;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionNamedType;
use ReflectionException;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;

final class ReflectionFactory
{
    /**
     * @param ContainerInterface $container
     * @param ReflectionMethod $constructor
     *
     * @return iterable<mixed>
     *
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    private function getParameters(ContainerInterface $container, ReflectionMethod $constructor): iterable
    {
        foreach ($constructor->getParameters() as $parameter) {
            yield $this->getParameterDependency($container, $parameter);
        }
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    private function getParameterDependency(ContainerInterface $container, ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();
        assert($type instanceof ReflectionNamedType);
        assert($type->isBuiltin() === false);

        if ($type->getName() === ContainerInterface::class) {
            return $container;
        }

        try {
            return  $container->get($type->getName());
        } catch (NotFoundExceptionInterface $e) {
            if ($parameter->allowsNull()) {
                return null;
            }

            throw $e;
        }
    }

    /**
     * @param ContainerInterface $container
     * @param class-string<T> $name
     *
     * @return T
     *
     * @template T of object
     *
     * @throws ReflectionException
     */
    public function __invoke(ContainerInterface $container, string $name)
    {
        $reflection = new ReflectionClass($name);

        return $reflection->newLazyProxy($this->createFactory($reflection, $container));
    }

    /**
     * @param ReflectionClass<object> $class
     */
    private function createFactory(ReflectionClass $class, ContainerInterface $container): Closure
    {
        return function () use ($class, $container) {
            $constructor = $class->getConstructor();

            $parameters = $constructor
                ? $this->getParameters($container, $constructor)
                : [];

            return $class->newInstance(...$parameters);
        };
    }
}
