<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Prerequisite\Resource;

use LessValueObject\String\Format\Resource\Identifier;
use Psr\Http\Message\ServerRequestInterface;

final class ResourceExistsPrerequisite extends AbstractResourcePrerequisite
{
    public function isSatisfied(ServerRequestInterface $request): bool
    {
        $service = $this->getResourceService($request);

        $body = $request->getParsedBody();
        assert(is_array($body));
        assert(is_string($body['id']));

        return $service->exists(new Identifier($body['id']));
    }
}
