<?php

declare(strict_types=1);

namespace LesAbstractService\Http\Resource\Handler;

use Override;
use LesHttp\Middleware\Route\Handler\Response\HandleResponse;
use LesDocumentor\Type\Document\Wrapper\Attribute\DocTypeWrapper;
use LesDocumentor\Type\Document\Wrapper\ResultTypeDocumentWrapper;
use LesHttp\Middleware\Route\Handler\Response\SuccessHandleResponse;

#[DocTypeWrapper(ResultTypeDocumentWrapper::class)]
final class ResultQueryRouteHandler extends AbstractQueryRouteHandler
{
    /**
     * @psalm-pure
     */
    #[Override]
    protected function makeResponse(mixed $output): HandleResponse
    {
        // @phpstan-ignore possiblyImpure.new
        return new SuccessHandleResponse(['result' => $output]);
    }
}
