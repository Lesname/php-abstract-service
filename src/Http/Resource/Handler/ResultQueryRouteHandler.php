<?php
declare(strict_types=1);

namespace LesAbstractService\Http\Resource\Handler;

use Override;
use JsonException;
use ReflectionException;
use LesHttp\Response\ErrorResponse;
use LesResource\Model\ResourceModel;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use LesResource\Repository\Exception\NoResource;
use LesDocumentor\Type\Document\Wrapper\Attribute\DocTypeWrapper;
use LesDocumentor\Type\Document\Wrapper\ResultTypeDocumentWrapper;

#[DocTypeWrapper(ResultTypeDocumentWrapper::class)]
final class ResultQueryRouteHandler extends AbstractQueryRouteHandler
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws JsonException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            return parent::handle($request);
        } catch (NoResource) {
            $stream = $this->streamFactory->createStream(
                json_encode(
                    new ErrorResponse(
                        'Request resource not found',
                        'resourceExists'
                    ),
                    flags: JSON_THROW_ON_ERROR
                ),
            );

            return $this
                ->responseFactory
                ->createResponse(404)
                ->withBody($stream);
        }
    }

    /**
     * @throws JsonException
     */
    #[Override]
    protected function makeResponse(mixed $output): ResponseInterface
    {
        assert($output instanceof ResourceModel);

        $stream = $this->streamFactory->createStream(
            json_encode(
                ['result' => $output],
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
