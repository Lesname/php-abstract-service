<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Handler\Query;

use JsonException;
use LessDocumentor\Type\Document\Wrapper\Attribute\DocTypeWrapper;
use LessDocumentor\Type\Document\Wrapper\ResultTypeDocumentWrapper;
use LessHttp\Response\ErrorResponse;
use LessResource\Model\ResourceModel;
use LessResource\Repository\Exception\NoResource;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionException;

#[DocTypeWrapper(ResultTypeDocumentWrapper::class)]
final class ResultQueryRouteHandler extends AbstractQueryRouteHandler
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws JsonException
     */
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
