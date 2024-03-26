<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Resource\Prerequisite;

use LessValueObject\String\Exception\TooLong;
use LessValueObject\String\Exception\TooShort;
use LessValueObject\String\Format\Exception\NotFormat;
use LessValueObject\String\Format\Resource\Identifier;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @psalm-suppress DeprecatedInterface
 */
final class ResourceExistsPrerequisite extends AbstractResourcePrerequisite
{
    /**
     * @throws TooLong
     * @throws TooShort
     * @throws NotFormat
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function isSatisfied(ServerRequestInterface $request): bool
    {
        $service = $this->getResourceRepository($request);

        $body = $request->getParsedBody();
        assert(is_array($body));
        assert(is_string($body['id']));

        return $service->exists(new Identifier($body['id']));
    }
}
