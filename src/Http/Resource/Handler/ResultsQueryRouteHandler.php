<?php

declare(strict_types=1);

namespace LesAbstractService\Http\Resource\Handler;

use Override;
use RuntimeException;
use LesHttp\Middleware\Route\Handler\Response\HandleResponse;
use LesDocumentor\Type\Document\Wrapper\Attribute\DocTypeWrapper;
use LesDocumentor\Type\Document\Wrapper\ResultsTypeDocumentWrapper;
use LesHttp\Middleware\Route\Handler\Response\SuccessHandleResponse;

#[DocTypeWrapper(ResultsTypeDocumentWrapper::class)]
final class ResultsQueryRouteHandler extends AbstractQueryRouteHandler
{
    /**
     * @psalm-pure
     */
    #[Override]
    protected function makeResponse(mixed $output): HandleResponse
    {
        if (!is_iterable($output) || !is_countable($output)) {
            throw new RuntimeException();
        }

        // @phpstan-ignore possiblyImpure.new
        return new SuccessHandleResponse(
            [
                'results' => $output,
                'meta' => [
                    'total' => count($output),
                ],
            ]
        );
    }
}
