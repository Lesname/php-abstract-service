<?php
declare(strict_types=1);

namespace LesAbstractService\Mezzio\Router\Route;

use LesDomain\Event\Event;
use LesValidator\Validator;
use LesValueObject\ValueObject;
use LesResource\Model\ResourceModel;
use Psr\Http\Server\RequestHandlerInterface;
use LesResource\Repository\ResourceRepository;
use LesDocumentor\Route\Document\Property\Category;
use LesHttp\Middleware\Condition\Constraint\ConditionConstraint;
use LesAbstractService\Http\Resource\Handler\CreateEventRouteHandler;
use LesAbstractService\Http\Resource\Handler\UpdateEventRouteHandler;
use LesAbstractService\Http\Resource\Handler\ResultQueryRouteHandler;
use LesAbstractService\Http\Resource\Handler\ResultsQueryRouteHandler;
use LesHttp\Middleware\Authorization\Constraint\AuthorizationConstraint;
use LesAbstractService\Http\Resource\ConditionConstraint\ExistsResourceConditionConstraint;

/**
 * @psalm-immutable
 */
final class RpcRouteBuilder
{
    /** @var class-string<ResourceRepository<ResourceModel>>|null */
    private ?string $resourceRepository;

    /** @var class-string|null */
    private ?string $proxyClass;

    /** @var class-string<Validator>|null */
    private ?string $validator;

    /** @var class-string<ValueObject>|null */
    private ?string $input;

    /** @var array<class-string<ConditionConstraint>> */
    private array $conditions;

    /** @var array<string, mixed> */
    private array $extraOptions;

    /**
     * @param non-empty-string $resourceName
     * @param non-empty-array<class-string<AuthorizationConstraint>> $authorizations
     */
    public function __construct(
        public readonly string $resourceName,
        private array $authorizations,
    ) {
        $this->resourceRepository = null;
        $this->proxyClass = null;
        $this->validator = null;
        $this->input = null;
        $this->conditions = [];
        $this->extraOptions = [];
    }

    public function withExtraOption(string $key, mixed $value): self
    {
        $clone = clone $this;
        // @phpstan-ignore property.readOnlyByPhpDocAssignNotInConstructor
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
        // @phpstan-ignore property.readOnlyByPhpDocAssignNotInConstructor
        $clone->authorizations = $authorizations;

        return $clone;
    }

    /**
     * @param class-string<AuthorizationConstraint> $authorization
     */
    public function withAddedAuthorization(string $authorization): self
    {
        return $this->withAuthorizations(
            [
                ...$this->authorizations,
                $authorization,
            ],
        );
    }

    /**
     * @param class-string<ConditionConstraint> $condition
     */
    public function withCondition(string $condition): self
    {
        return $this->withConditions([$condition]);
    }

    /**
     * @param array<class-string<ConditionConstraint>> $conditions
     */
    public function withConditions(array $conditions): self
    {
        $clone = clone $this;
        // @phpstan-ignore property.readOnlyByPhpDocAssignNotInConstructor
        $clone->conditions = $conditions;

        return $clone;
    }

    /**
     * @param class-string<ConditionConstraint> $condition
     */
    public function withAddedCondition(string $condition): self
    {
        return $this->withConditions(
            [
                ...$this->conditions,
                $condition,
            ],
        );
    }

    /**
     * @template T of ResourceModel
     *
     * @param class-string<ResourceRepository<T>> $resourceRepository
     */
    public function withResourceRepository(string $resourceRepository): self
    {
        $clone = clone $this;
        // @phpstan-ignore property.readOnlyByPhpDocAssignNotInConstructor
        $clone->resourceRepository = $resourceRepository;
        // @phpstan-ignore property.readOnlyByPhpDocAssignNotInConstructor
        $clone->proxyClass = $resourceRepository;

        return $clone;
    }

    /**
     * @param class-string $proxyClass
     */
    public function withProxyClass(string $proxyClass): self
    {
        $clone = clone $this;
        // @phpstan-ignore property.readOnlyByPhpDocAssignNotInConstructor
        $clone->proxyClass = $proxyClass;

        return $clone;
    }

    /**
     * @param class-string<Validator> $validator
     */
    public function withValidator(string $validator): self
    {
        $clone = clone $this;
        // @phpstan-ignore property.readOnlyByPhpDocAssignNotInConstructor
        $clone->validator = $validator;

        return $clone;
    }

    /**
     * @param class-string<ValueObject> $input
     */
    public function withInput(string $input): self
    {
        $clone = clone $this;
        // @phpstan-ignore property.readOnlyByPhpDocAssignNotInConstructor
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
            ->withAddedCondition(ExistsResourceConditionConstraint::class)
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
            ->withExtraOption('event', $event)
            ->withExtraOption('input', $event)
            ->buildRoute(
                $method,
                Category::Command,
                $handler,
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
            ->withExtraOption(
                'proxy',
                [
                    'class' => $this->proxyClass,
                    'method' => $method,
                ],
            )
            ->buildRoute(
                $method,
                Category::Query,
                $handler,
            );
    }

    /**
     * @param class-string<RequestHandlerInterface> $handler
     * @param array<string, mixed> $baseRoute
     *
     * @return iterable<string, array<mixed>>
     */
    public function buildRoute(string $method, Category $type, string $handler, array $baseRoute = []): iterable
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

        foreach (['resourceRepository', 'validator', 'input', 'conditions'] as $key) {
            if ($this->{$key}) {
                $route[$key] = $this->{$key};
            }
        }

        yield "POST:/{$this->resourceName}.{$method}" => $route;
    }
}
