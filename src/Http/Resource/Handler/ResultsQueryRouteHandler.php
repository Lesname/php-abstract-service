<?php
declare(strict_types=1);

namespace LesAbstractService\Http\Resource\Handler;

use Override;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use LesDocumentor\Type\Document\Wrapper\Attribute\DocTypeWrapper;
use LesDocumentor\Type\Document\Wrapper\ResultsTypeDocumentWrapper;

#[DocTypeWrapper(ResultsTypeDocumentWrapper::class)]
final class ResultsQueryRouteHandler extends AbstractQueryRouteHandler
{
    /**
     * @throws JsonException
     */
    #[Override]
    protected function makeResponse(mixed $output): ResponseInterface
    {
        assert(is_iterable($output));
        assert(is_countable($output));

        $stream = $this->streamFactory->createStream(
            json_encode(
                [
                    'results' => $output,
                    'meta' => [
                        'total' => count($output),
                    ],
                ],
                flags: JSON_THROW_ON_ERROR,
            ),
        );

        return $this
            ->responseFactory
            ->createResponse()
            ->withAddedHeader('content-type', 'application/json')
            ->withBody($stream);
    }
}
