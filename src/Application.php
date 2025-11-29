<?php

declare(strict_types=1);

namespace LesAbstractService;

use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Application
{
    public function __construct(private readonly RequestHandlerInterface $handler)
    {
    }

    public function run(): void
    {
        $this->handle(ServerRequestFactory::createFromGlobals());
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
