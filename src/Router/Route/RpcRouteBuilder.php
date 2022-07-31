<?php
declare(strict_types=1);

namespace LessAbstractService\Router\Route;

use LessDomain\Event\Event;
use LessHttp\Middleware\Authorization\Constraint\AuthorizationConstraint;
use LessHttp\Middleware\Prerequisite\Constraint\PrerequisiteConstraint;
use LessAbstractService\Http\Handler;
use LessAbstractService\Http\Prerequisite\Resource\ResourceExistsPrerequisite;
use LessResource\Model\ResourceModel;
use LessResource\Repository\ResourceRepository;
use LessValidator\Validator;
use LessValueObject\ValueObject;
use Psr\Http\Server\RequestHandlerInterface;

final class RpcRouteBuilder
{
    /** @var class-string<ResourceRepository<ResourceModel>>|null */
    private ?string $resourceRepository = null;

    /** @var class-string<Validator>|null */
    private ?string $validator = null;

    /** @var class-string<ValueObject>|null */
    private ?string $input = null;

    /** @var array<class-string<PrerequisiteConstraint>> */
    private array $prerequisites = [];

    /**
     * @param non-empty-string $resourceName
     * @param non-empty-array<class-string<AuthorizationConstraint>> $authorizations
     */
    public function __construct(
        private readonly string $resourceName,
        private array $authorizations,
    ) {}

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
     * @param array<class-string<PrerequisiteConstraint>> $prerequisites
     */
    public function withPrerequisites(array $prerequisites): self
    {
        $clone = clone $this;
        $clone->prerequisites = $prerequisites;

        return $clone;
    }

    /**
     * @param class-string<ResourceRepository<ResourceModel>> $resourceRepository
     *
     * @deprecated use withResourceRepository
     */
    public function withResourceService(string $resourceRepository): self
    {
        return $this->withResourceRepository($resourceRepository);
    }

    /**
     * @param class-string<ResourceRepository<ResourceModel>> $resourceRepository
     */
    public function withResourceRepository(string $resourceRepository): self
    {
        $clone = clone $this;
        $clone->resourceRepository = $resourceRepository;

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
    public function buildCreateEventRoute(string $method, string $event, string $handler = Handler\Event\CreateEventRouteHandler::class): iterable
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
    public function buildUpdateEventRoute(string $method, string $event, string $handler = Handler\Event\UpdateEventRouteHandler::class): iterable
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
                Type::Command,
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
        yield from $this->buildQueryRoute($method, Handler\Query\ResultQueryRouteHandler::class);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public function buildResultsQueryRoute(string $method): iterable
    {
        yield from $this->buildQueryRoute($method, Handler\Query\ResultsQueryRouteHandler::class);
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
                Type::Query,
                $handler,
                [
                    'proxy' => [
                        'class' => $this->resourceRepository,
                        'method' => $method,
                    ],
                ],
            );
    }

    /**
     * @param string $method
     * @param Type $type
     * @param class-string<RequestHandlerInterface> $handler
     * @param array<string, mixed> $baseRoute
     *
     * @return iterable<string, array<mixed>>
     *
     * @psalm-suppress MixedAssignment
     */
    public function buildRoute(string $method, Type | string $type, string $handler, array $baseRoute = []): iterable
    {
        $route = array_replace(
            $baseRoute,
            [
                'path' => "/{$this->resourceName}.{$method}",
                'authorizations' => $this->authorizations,
                'resource' => $this->resourceName,
                'middleware' => $handler,
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
