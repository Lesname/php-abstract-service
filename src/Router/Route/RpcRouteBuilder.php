<?php
declare(strict_types=1);

namespace LessAbstractService\Router\Route;

use LessAbstractService\Http\Resource\Handler\Command\CreateEventRouteHandler;
use LessAbstractService\Http\Resource\Handler\Command\UpdateEventRouteHandler;
use LessAbstractService\Http\Resource\Handler\Query\ResultQueryRouteHandler;
use LessAbstractService\Http\Resource\Handler\Query\ResultsQueryRouteHandler;
use LessAbstractService\Http\Resource\Prerequisite\ResourceExistsPrerequisite;
use LessDocumentor\Route\Document\Property\Category;
use LessDomain\Event\Event;
use LessHttp\Middleware\Authorization\Constraint\AuthorizationConstraint;
use LessHttp\Middleware\Prerequisite\Constraint\PrerequisiteConstraint;
use LessResource\Model\ResourceModel;
use LessResource\Repository\ResourceRepository;
use LessValidator\Validator;
use LessValueObject\ValueObject;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @psalm-immutable
 */
final class RpcRouteBuilder
{
    /**
     * @var class-string<ResourceRepository<ResourceModel>>|null
     * @readonly
     */
    public ?string $resourceRepository = null;

    /**
     * @var class-string|null
     * @readonly
     */
    public ?string $proxyClass = null;

    /**
     * @var class-string<Validator>|null
     * @readonly
     */
    public ?string $validator = null;

    /**
     * @var class-string<ValueObject>|null
     * @readonly
     */
    public ?string $input = null;

    /**
     * @var array<class-string<PrerequisiteConstraint>>
     * @readonly
     *
     */
    public array $prerequisites = [];

    /**
     * @var array<string, mixed>
     * @readonly
     */
    public array $extraOptions = [];

    /**
     * @var non-empty-array<class-string<AuthorizationConstraint>>
     * @readonly
     */
    public array $authorizations;

    /**
     * @param non-empty-string $resourceName
     * @param non-empty-array<class-string<AuthorizationConstraint>> $authorizations
     */
    public function __construct(
        public readonly string $resourceName,
        array $authorizations,
    ) {
        $this->authorizations = $authorizations;
    }

    public function withExtraOption(string $key, mixed $value): self
    {
        $clone = clone $this;
        $clone->extraOptions[$key] = $value;

        return $clone;
    }

    /**
     * @param class-string<AuthorizationConstraint> $authorization
     */
    public function withAuthorization(string $authorization): self
    {
        return $this->withAuthorizations([$authorization]);
    }

    /**
     * @param non-empty-array<class-string<AuthorizationConstraint>> $authorizations
     */
    public function withAuthorizations(array $authorizations): self
    {
        $clone = clone $this;
        $clone->authorizations = $authorizations;

        return $clone;
    }

    /**
     * @param non-empty-array<class-string<AuthorizationConstraint>> $authorizations
     *
     * @deprecated use withAddedAuthorization
     */
    public function withAddedAuthorizations(array $authorizations): self
    {
        return $this->withAuthorizations([...$this->authorizations, ...$authorizations]);
    }

    /**
     * @param class-string<AuthorizationConstraint> $authorization
     */
    public function withAddedAuthorization(string $authorization): self
    {
        $clone = clone $this;
        $clone->authorizations[] = $authorization;

        return $clone;
    }

    /**
     * @param class-string<PrerequisiteConstraint> $prerequisite
     */
    public function withPrerequisite(string $prerequisite): self
    {
        return $this->withPrerequisites([$prerequisite]);
    }

    /**
     * @param array<class-string<PrerequisiteConstraint>> $prerequisites
     */
    public function withPrerequisites(array $prerequisites): self
    {
        $clone = clone $this;
        $clone->prerequisites = $prerequisites;

        return $clone;
    }

    /**
     * @param array<class-string<PrerequisiteConstraint>> $prerequisites
     *
     * @deprecated use withAddedPrerequisite
     */
    public function withAddedPrerequisites(array $prerequisites): self
    {
        return $this->withPrerequisites([...$this->prerequisites, ...$prerequisites]);
    }

    /**
     * @param class-string<PrerequisiteConstraint> $prerequisite
     */
    public function withAddedPrerequisite(string $prerequisite): self
    {
        $clone = clone $this;
        $clone->prerequisites[] = $prerequisite;

        return $clone;
    }

    /**
     * @param class-string<ResourceRepository<ResourceModel>> $resourceRepository
     */
    public function withResourceRepository(string $resourceRepository): self
    {
        $clone = clone $this;
        $clone->resourceRepository = $resourceRepository;
        $clone->proxyClass = $resourceRepository;

        return $clone;
    }

    /**
     * @param class-string $proxyClass
     */
    public function withProxyClass(string $proxyClass): self
    {
        $clone = clone $this;
        $clone->proxyClass = $proxyClass;

        return $clone;
    }

    /**
     * @param class-string<Validator> $validator
     */
    public function withValidator(string $validator): self
    {
        $clone = clone $this;
        $clone->validator = $validator;

        return $clone;
    }

    /**
     * @param class-string<ValueObject> $input
     */
    public function withInput(string $input): self
    {
        $clone = clone $this;
        $clone->input = $input;

        return $clone;
    }

    /**
     * @param string $method
     * @param class-string<Event> $event
     * @param class-string<RequestHandlerInterface> $handler
     *
     * @return iterable<string, array<mixed>>
     */
    public function buildCreateEventRoute(string $method, string $event, string $handler = CreateEventRouteHandler::class): iterable
    {
        yield from $this->buildEventRoute($method, $event, $handler);
    }

    /**
     * @param string $method
     * @param class-string<Event> $event
     * @param class-string<RequestHandlerInterface> $handler
     *
     * @return iterable<string, array<mixed>>
     */
    public function buildUpdateEventRoute(string $method, string $event, string $handler = UpdateEventRouteHandler::class): iterable
    {
        yield from $this
            ->withPrerequisites([ResourceExistsPrerequisite::class, ...$this->prerequisites])
            ->buildEventRoute($method, $event, $handler);
    }

    /**
     * @param string $method
     * @param class-string<Event> $event
     * @param class-string<RequestHandlerInterface> $handler
     *
     * @return iterable<string, array<mixed>>
     */
    public function buildEventRoute(string $method, string $event, string $handler): iterable
    {
        assert($this->resourceRepository !== null);

        yield from $this
            ->buildRoute(
                $method,
                Category::Command,
                $handler,
                [
                    'event' => $event,
                    'input' => $event,
                ],
            );
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public function buildResultQueryRoute(string $method): iterable
    {
        yield from $this->buildQueryRoute($method, ResultQueryRouteHandler::class);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public function buildResultsQueryRoute(string $method): iterable
    {
        yield from $this->buildQueryRoute($method, ResultsQueryRouteHandler::class);
    }

    /**
     * @param string $method
     * @param class-string<RequestHandlerInterface> $handler
     *
     * @return iterable<string, array<mixed>>
     */
    public function buildQueryRoute(string $method, string $handler): iterable
    {
        yield from $this
            ->buildRoute(
                $method,
                Category::Query,
                $handler,
                [
                    'proxy' => [
                        'class' => $this->proxyClass,
                        'method' => $method,
                    ],
                ],
            );
    }

    /**
     * @param class-string<RequestHandlerInterface> $handler
     * @param array<string, mixed> $baseRoute
     *
     * @return iterable<string, array<mixed>>
     */
    public function buildRoute(string $method, Category | string $type, string $handler, array $baseRoute = []): iterable
    {
        $route = array_replace(
            $baseRoute,
            $this->extraOptions,
            [
                'path' => "/{$this->resourceName}.{$method}",
                'authorizations' => $this->authorizations,
                'resource' => $this->resourceName,
                'middleware' => $handler,
                'category' => $type,
                'type' => $type,
            ],
        );

        foreach (['resourceRepository', 'validator', 'prerequisites', 'input'] as $key) {
            if ($this->{$key}) {
                $route[$key] = $this->{$key};
            }
        }

        yield "POST:/{$this->resourceName}.{$method}" => $route;
    }
}
