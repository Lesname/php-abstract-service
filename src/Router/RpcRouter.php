<?php
declare(strict_types=1);

namespace LessAbstractService\Router;

use Laminas\Stratigility\Middleware\RequestHandlerMiddleware;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * @deprecated
 */
final class RpcRouter implements RouterInterface
{
    /**
     * @param ContainerInterface $container
     * @param array<mixed> $routes
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly array $routes,
    ) {}

    public function addRoute(Route $route): void
    {
        throw new RuntimeException();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function match(Request $request): RouteResult
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        if ($path === '') {
            throw new RuntimeException();
        }

        if (!isset($this->routes["{$method}:{$path}"])) {
            return RouteResult::fromRouteFailure(Route::HTTP_METHOD_ANY);
        }

        $route = $this->routes["{$method}:{$path}"];
        assert(is_array($route));
        assert(is_string($route['middleware']));

        $handler = $this->container->get($route['middleware']);
        assert($handler instanceof RequestHandlerInterface);

        return RouteResult::fromRoute(new Route($path, new RequestHandlerMiddleware($handler)));
    }

    /**
     * @param string $name
     * @param array<mixed> $substitutions
     * @param array<mixed> $options
     */
    public function generateUri(string $name, array $substitutions = [], array $options = []): string
    {
        throw new RuntimeException();
    }
}
