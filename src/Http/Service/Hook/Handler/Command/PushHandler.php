<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Service\Hook\Handler\Command;

use LessAbstractClient\Requester\Requester;
use LessAbstractService\Http\Resource\Handler\AbstractParametersHandler;
use LessAbstractService\Http\Service\Hook\Handler\Command\Parameters\Push\Type;
use LessAbstractService\Http\Service\Hook\Handler\Command\Parameters\PushParameters;
use LessDocumentor\Route\Attribute\DocHttpResponse;
use LessDocumentor\Route\Attribute\DocInput;
use LessHydrator\Hydrator;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[DocInput(PushParameters::class)]
#[DocHttpResponse(code: 204)]
final class PushHandler extends AbstractParametersHandler
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly Requester $requester,
        Hydrator $hydrator,
    ) {
        parent::__construct($hydrator);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $this->getParameters($request, PushParameters::class);

        if ($parameters->type === Type::Verification) {
            $this->handleVerification($parameters);
        }

        return $this->responseFactory->createResponse(204);
    }

    private function handleVerification(PushParameters $parameters): void
    {
        if (isset($parameters->body['token']) && is_string($parameters->body['token'])) {
            $this
                ->requester
                ->post(
                    'subscriber.verify',
                    ['token' => $parameters->body['token']],
                );
        }
    }
}
