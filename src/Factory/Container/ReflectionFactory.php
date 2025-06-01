<?php
declare(strict_types=1);

namespace LesAbstractService\Factory\Container;

use Closure;
use Throwable;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionNamedType;
use ReflectionException;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use LesAbstractService\Factory\Container\Exception\FailedClass;
use LesAbstractService\Factory\Container\Exception\FailedParameter;

final class ReflectionFactory
{
    /**
     * @param ContainerInterface $container
     * @param ReflectionMethod $constructor
     *
     * @return iterable<mixed>
     *
     * @throws FailedParameter
     */
    private function getParameters(ContainerInterface $container, ReflectionMethod $constructor): iterable
    {
        foreach ($constructor->getParameters() as $parameter) {
            try {
                $dependency = $this->getParameterDependency($container, $parameter);
            } catch (Throwable $e) {
                throw new FailedParameter($parameter->getName(), $e);
            }

            yield $dependency;
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
        $factory = $this->createFactory($reflection, $container);

        return $reflection->newLazyProxy($factory);
    }

    /**
     * @param ReflectionClass<object> $class
     */
    private function createFactory(ReflectionClass $class, ContainerInterface $container): Closure
    {
        return function () use ($class, $container) {
            $constructor = $class->getConstructor();

            try {
                $parameters = $constructor
                    ? [...$this->getParameters($container, $constructor)]
                    : [];
            } catch (Throwable $e) {
                throw new FailedClass($class->getName(), $e);
            }


            return $class->newInstance(...$parameters);
        };
    }
}
