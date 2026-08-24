<?php

declare(strict_types=1);

namespace LesAbstractService;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Application
{
    /**
     * @psalm-pure
     */
    public function __construct(private readonly RequestHandlerInterface $handler)
    {
    }

    public function run(): void
    {
        $psr17Factory = new Psr17Factory();

        $creator = new ServerRequestCreator(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $psr17Factory
        );

        $this->handle($creator->fromGlobals());
    }

    public function handle(ServerRequestInterface $request): void
    {
        $this->emit($this->handler->handle($request));
    }

    private function emit(ResponseInterface $response): void
    {
        $this->emitHeaders($response);
        $this->emitStatusCode($response);
        $this->emitBody($response);
    }

    private function emitHeaders(ResponseInterface $response): void
    {
        foreach ($response->getHeaders() as $headerName => $headerValues) {
            assert(is_string($headerName));
            $name = ucwords($headerName, '-');

            foreach ($headerValues as $headerValue) {
                $header = sprintf('%s: %s', $name, $headerValue);

                header($header);
            }
        }
    }

    private function emitStatusCode(ResponseInterface $response): void
    {
        http_response_code($response->getStatusCode());
    }

    private function emitBody(ResponseInterface $response): void
    {
        echo $response->getBody();
    }
}
