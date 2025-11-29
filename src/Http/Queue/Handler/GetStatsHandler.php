<?php

declare(strict_types=1);

namespace LesAbstractService\Http\Queue\Handler;

use Override;
use JsonException;
use LesQueue\Queue;
use LesValueObject\Composite\Paginate;
use Psr\Http\Message\ResponseInterface;
use LesDocumentor\Route\Attribute\DocInput;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use LesValueObject\Number\Int\Paginate\Page;
use Psr\Http\Message\ResponseFactoryInterface;
use LesValueObject\Number\Int\Paginate\PerPage;
use LesValueObject\Number\Exception\MinOutBounds;
use LesValueObject\Number\Exception\MaxOutBounds;
use LesDocumentor\Route\Attribute\DocHttpResponse;
use LesValueObject\Number\Exception\NotMultipleOf;
use LesAbstractService\Http\Queue\Handler\Response\GetStatsResponse;
use LesAbstractService\Http\Queue\Handler\Parameters\GetStatsParameters;

#[DocHttpResponse(GetStatsResponse::class)]
#[DocInput(GetStatsParameters::class)]
final class GetStatsHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly Queue $queue,
    ) {}

    /**
     * @throws JsonException
     * @throws MaxOutBounds
     * @throws MinOutBounds
     * @throws NotMultipleOf
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = [
            'result' => [
                'processable' => $this->queue->countProcessable(),
                'processing' => $this->queue->countProcessing(),
                'buried' => $this
                    ->queue
                    ->getBuried(new Paginate(new PerPage(0), new Page(1)))
                    ->count(),
            ],
        ];

        $stream = $this->streamFactory->createStream(json_encode($data, flags: JSON_THROW_ON_ERROR));

        return $this
            ->responseFactory
            ->createResponse()
            ->withBody($stream)
            ->withAddedHeader('content-type', 'application/json');
    }
}
