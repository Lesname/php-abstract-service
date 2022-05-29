<?php
declare(strict_types=1);

namespace LessAbstractService\Container\Factory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

final class ReflectionFactory
{
    /**
     * @param ContainerInterface $container
     * @param ReflectionMethod|null $constructor
     *
     * @return iterable<object|null>
     *
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    private function getParameters(ContainerInterface $container, ?ReflectionMethod $constructor): iterable
    {
        if ($constructor instanceof ReflectionMethod) {
            foreach ($constructor->getParameters() as $parameter) {
                yield $this->getParameterDependency($container, $parameter);
            }
        }
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    private function getParameterDependency(ContainerInterface $container, ReflectionParameter $parameter): ?object
    {
        $type = $parameter->getType();
        assert($type instanceof ReflectionNamedType);
        assert($type->isBuiltin() === false);

        if ($type->getName() === ContainerInterface::class) {
            return $container;
        }

        try {
            $result = $container->get($type->getName());
        } catch (NotFoundExceptionInterface $e) {
            if ($parameter->allowsNull()) {
                return null;
            }

            throw $e;
        }

        assert(is_object($result));

        return $result;
    }

    /**
     * @param ContainerInterface $container
     * @param class-string<T> $name
     *
     * @return T
     *
     * @template T
     *
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     *
     * @psalm-suppress MixedMethodCall
     */
    public function __invoke(ContainerInterface $container, string $name)
    {
        assert(class_exists($name));

        $reflection = new ReflectionClass($name);
        $constructor = $reflection->getConstructor();

        return new $name(...$this->getParameters($container, $constructor));
    }
}
