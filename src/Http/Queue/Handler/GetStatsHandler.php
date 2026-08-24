<?php

declare(strict_types=1);

namespace LesAbstractService\Http\Queue\Handler;

use Override;
use LesQueue\Queue;
use LesHttp\Router\Route\Route;
use LesValueObject\Composite\Paginate;
use LesDocumentor\Route\Attribute\DocInput;
use Psr\Http\Message\ServerRequestInterface;
use LesValueObject\Number\Int\Paginate\Page;
use LesValueObject\Number\Int\Paginate\PerPage;
use LesValueObject\Number\Exception\MinOutBounds;
use LesValueObject\Number\Exception\MaxOutBounds;
use LesDocumentor\Route\Attribute\DocHttpResponse;
use LesValueObject\Number\Exception\NotMultipleOf;
use LesHttp\Middleware\Route\Handler\RouteHandler;
use LesHttp\Middleware\Route\Handler\Response\HandleResponse;
use LesAbstractService\Http\Queue\Handler\Response\GetStatsResponse;
use LesHttp\Middleware\Route\Handler\Response\SuccessHandleResponse;
use LesAbstractService\Http\Queue\Handler\Parameters\GetStatsParameters;

#[DocHttpResponse(GetStatsResponse::class)]
#[DocInput(GetStatsParameters::class)]
final class GetStatsHandler implements RouteHandler
{
    /**
     * @psalm-pure
     */
    public function __construct(
        private readonly Queue $queue,
    ) {}

    /**
     * @throws MaxOutBounds
     * @throws MinOutBounds
     * @throws NotMultipleOf
     */
    #[Override]
    public function handle(ServerRequestInterface $request, Route $route): HandleResponse
    {
        return new SuccessHandleResponse(
            [
                'result' => [
                    'processable' => $this->queue->countProcessable(),
                    'processing' => $this->queue->countProcessing(),
                    'buried' => $this
                        ->queue
                        ->getBuried(new Paginate(new PerPage(0), new Page(1)))
                        ->count(),
                ],
            ]
        );
    }
}
