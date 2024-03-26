<?php
declare(strict_types=1);

namespace LessAbstractService\Mezzio\Listener;

use Throwable;
use Sentry\State\Scope;
use Sentry\State\HubInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SentryErrorListener
{
    public function __construct(private readonly HubInterface $hub)
    {
    }

    public function __invoke(Throwable $error, ServerRequestInterface $request, ResponseInterface $response): void
    {
        $this->hub->withScope(
            function (Scope $scope) use ($error, $request): void {
                $scope->setExtra('reference', $request->getAttribute('reference'));
                $scope->setExtra('file', $error->getFile());
                $scope->setExtra('line', $error->getLine());
                $scope->setExtra('code', $error->getCode());

                $scope->setExtra('path', $request->getUri()->getPath());

                $this->hub->captureException($error);
            },
        );
    }
}
