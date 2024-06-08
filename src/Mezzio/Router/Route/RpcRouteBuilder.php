<?php
declare(strict_types=1);

namespace LessAbstractService\Mezzio\Router\Route;

use LessDomain\Event\Event;
use LessValidator\Validator;
use LessValueObject\ValueObject;
use LessResource\Model\ResourceModel;
use Psr\Http\Server\RequestHandlerInterface;
use LessResource\Repository\ResourceRepository;
use LessDocumentor\Route\Document\Property\Category;
use LessHttp\Middleware\Condition\Constraint\ConditionConstraint;
use LessAbstractService\Http\Resource\Handler\CreateEventRouteHandler;
use LessAbstractService\Http\Resource\Handler\UpdateEventRouteHandler;
use LessAbstractService\Http\Resource\Handler\ResultQueryRouteHandler;
use LessAbstractService\Http\Resource\Handler\ResultsQueryRouteHandler;
use LessHttp\Middleware\Authorization\Constraint\AuthorizationConstraint;
use LessAbstractService\Http\Resource\ConditionConstraint\ExistsResourceConditionConstraint;

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
     * @var array<class-string<ConditionConstraint>>
     * @readonly
     */
    public array $conditions = [];

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
     * @param class-string<AuthorizationConstraint> $authorization
     */
    public function withAddedAuthorization(string $authorization): self
    {
        $clone = clone $this;
        $clone->authorizations[] = $authorization;

        return $clone;
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
        $clone->conditions = $conditions;

        return $clone;
    }

    /**
     * @param class-string<ConditionConstraint> $condition
     */
    public function withAddedCondition(string $condition): self
    {
        $clone = clone $this;
        $clone->conditions[] = $condition;

        return $clone;
    }

    /**
     * @template T of ResourceModel
     *
     * @param class-string<ResourceRepository<T>> $resourceRepository
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

        foreach (['resourceRepository', 'validator', 'input', 'conditions'] as $key) {
            if ($this->{$key}) {
                $route[$key] = $this->{$key};
            }
        }

        yield "POST:/{$this->resourceName}.{$method}" => $route;
    }
}
