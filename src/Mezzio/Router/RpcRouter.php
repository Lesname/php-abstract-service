<?php
declare(strict_types=1);

namespace LesAbstractService\Mezzio\Router;

use Override;
use LesDocumentor\Route\Document\Property\Method;
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

final class RpcRouter implements RouterInterface
{
    /**
     * @param array<string, array<mixed>> $routes
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly array $routes,
    ) {}

    #[Override]
    public function addRoute(Route $route): void
    {
        throw new RuntimeException();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Override]
    public function match(Request $request): RouteResult
    {
        $route = $this->findRoute($request);

        if ($route === null) {
            return RouteResult::fromRouteFailure(Route::HTTP_METHOD_ANY);
        }

        $path = $request->getUri()->getPath();
        assert($path !== '');

        assert(is_string($route['middleware']));

        $handler = $this->container->get($route['middleware']);
        assert($handler instanceof RequestHandlerInterface);

        return RouteResult::fromRoute(new Route($path, new RequestHandlerMiddleware($handler)));
    }

    /**
     * @return array<mixed>|null
     */
    private function findRoute(Request $request): ?array
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        if (isset($this->routes["{$method}:{$path}"])) {
            return $this->routes["{$method}:{$path}"];
        }

        if ($method === Method::Post->value) {
            $tryMethods = [
                Method::Query->value,
                Method::Delete->value,
                Method::Patch->value,
                Method::Put->value,
            ];

            foreach ($tryMethods as $tryMethod) {
                if (isset($this->routes["{$tryMethod}:{$path}"])) {
                    return $this->routes["{$tryMethod}:{$path}"];
                }
            }
        }

        return null;
    }

    /**
     * @param array<mixed> $substitutions
     * @param array<mixed> $options
     */
    #[Override]
    public function generateUri(string $name, array $substitutions = [], array $options = []): string
    {
        throw new RuntimeException();
    }
}
