<?php

declare(strict_types=1);

namespace LesAbstractService\Http\Resource\ConditionConstraint;

use Override;
use RuntimeException;
use LesHttp\Router\Route\Route;
use LesResource\Model\ResourceModel;
use Psr\Container\ContainerInterface;
use LesValueObject\String\Exception\TooLong;
use Psr\Http\Message\ServerRequestInterface;
use LesValueObject\String\Exception\TooShort;
use Psr\Container\ContainerExceptionInterface;
use LesResource\Repository\ResourceRepository;
use LesHttp\Router\Route\Exception\OptionNotSet;
use LesResource\Repository\Exception\NoResource;
use LesValueObject\String\Format\Exception\NotFormat;
use LesValueObject\String\Format\Resource\Identifier;
use LesHttp\Middleware\AccessControl\Condition\Constraint\ConditionConstraint;
use LesHttp\Middleware\AccessControl\Condition\Constraint\Result\ConditionConstraintResult;
use LesHttp\Middleware\AccessControl\Condition\Constraint\Result\SatisfiedConditionConstraintResult;
use LesHttp\Middleware\AccessControl\Condition\Constraint\Result\UnsatisfiedConditionConstraintResult;

final class VersionConditionConstraint implements ConditionConstraint
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFormat
     * @throws OptionNotSet
     * @throws TooLong
     * @throws TooShort
     * @throws NoResource
     */
    #[Override]
    public function satisfies(ServerRequestInterface $request): ConditionConstraintResult
    {
        $ifMatch = $request->getHeaderLine('If-Match');

        if (ctype_digit($ifMatch)) {
            $body = $request->getParsedBody();
            assert(is_array($body));
            assert(is_string($body['id']));

            $currentVersion = $this->getResourceRepository($request)->getCurrentVersion(new Identifier($body['id']));

            if ((int)$ifMatch !== $currentVersion) {
                return UnsatisfiedConditionConstraintResult::constraint(
                    'resource.versionMismatch',
                    [
                        'id' => $body['id'],
                        'versionExpected' => $ifMatch,
                        'versionActual' => $currentVersion,
                    ],
                );
            }
        }

        return new SatisfiedConditionConstraintResult();
    }

    /**
     * @return ResourceRepository<ResourceModel>
     *
     * @throws OptionNotSet
     * @throws ContainerExceptionInterface
     */
    protected function getResourceRepository(ServerRequestInterface $request): ResourceRepository
    {
        $route = $request->getAttribute('route');

        if (!$route instanceof Route) {
            throw new RuntimeException();
        }

        $resourceRepository = $route->getOption('resourceRepository');
        assert(is_string($resourceRepository));

        $resourceRepository = $this->container->get($resourceRepository);
        assert($resourceRepository instanceof ResourceRepository);

        return $resourceRepository;
    }
}
