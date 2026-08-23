<?php

declare(strict_types=1);

namespace LesAbstractService\Http\Route;

use LesDomain\Event\Event;
use LesValidator\Validator;
use LesValueObject\ValueObject;
use LesResource\Model\ResourceModel;
use Psr\Http\Server\RequestHandlerInterface;
use LesResource\Repository\ResourceRepository;
use LesDocumentor\Route\Document\Property\Method;
use LesHttp\Middleware\Route\Handler\RouteHandler;
use LesAbstractService\Http\Resource\Handler\CreateEventRouteHandler;
use LesAbstractService\Http\Resource\Handler\UpdateEventRouteHandler;
use LesAbstractService\Http\Resource\Handler\ResultQueryRouteHandler;
use LesAbstractService\Http\Resource\Handler\ResultsQueryRouteHandler;
use LesHttp\Middleware\AccessControl\Condition\Constraint\ConditionConstraint;
use LesAbstractService\Http\Resource\ConditionConstraint\ExistsConditionConstraint;
use LesAbstractService\Http\Resource\ConditionConstraint\VersionConditionConstraint;
use LesHttp\Middleware\AccessControl\Authorization\Constraint\AuthorizationConstraint;
use LesHttp\Middleware\AccessControl\Authorization\Constraint\Chain\AuthorizationConstraintChain;

final class RpcRouteBuilder
{
    /** @var class-string<ResourceRepository<covariant ResourceModel>>|null */
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
     * @param non-empty-array<class-string<AuthorizationConstraint>|AuthorizationConstraint|AuthorizationConstraintChain> $authorizations
     *
     * @psalm-pure
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

    /**
     * @psalm-mutation-free
     */
    public function withExtraOption(string $key, mixed $value): self
    {
        return clone ($this, ['extraOptions' => $this->extraOptions + [$key => $value]]);
    }

    /**
     * @param class-string<AuthorizationConstraint>|AuthorizationConstraint|AuthorizationConstraintChain $authorization
     *
     * @psalm-mutation-free
     */
    public function withAuthorization(AuthorizationConstraint|AuthorizationConstraintChain|string $authorization): self
    {
        return $this->withAuthorizations([$authorization]);
    }

    /**
     * @param non-empty-array<class-string<AuthorizationConstraint>|AuthorizationConstraint|AuthorizationConstraintChain> $authorizations
     *
     * @psalm-mutation-free
     */
    public function withAuthorizations(array $authorizations): self
    {
        return clone(
            $this,
            ['authorizations' => $authorizations],
        );
    }

    /**
     * @param class-string<AuthorizationConstraint>|AuthorizationConstraint|AuthorizationConstraintChain $authorization
     *
     * @psalm-mutation-free
     */
    public function withAddedAuthorization(AuthorizationConstraint|AuthorizationConstraintChain|string $authorization): self
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
     *
     * @psalm-mutation-free
     */
    public function withCondition(string $condition): self
    {
        return $this->withConditions([$condition]);
    }

    /**
     * @param array<class-string<ConditionConstraint>> $conditions
     *
     * @psalm-mutation-free
     */
    public function withConditions(array $conditions): self
    {
        return clone(
            $this,
            ['conditions' => $conditions],
        );
    }

    /**
     * @param class-string<ConditionConstraint> $condition
     *
     * @psalm-mutation-free
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
     *
     * @psalm-mutation-free
     */
    public function withResourceRepository(string $resourceRepository): self
    {
        return clone(
            $this,
            [
                'resourceRepository' => $resourceRepository,
                'proxyClass' => $resourceRepository,
            ],
        );
    }

    /**
     * @param class-string $proxyClass
     *
     * @psalm-mutation-free
     */
    public function withProxyClass(string $proxyClass): self
    {
        return clone($this, ['proxyClass' => $proxyClass]);
    }

    /**
     * @param class-string<Validator> $validator
     *
     * @psalm-mutation-free
     */
    public function withValidator(string $validator): self
    {
        return clone ($this, ['validator' => $validator]);
    }

    /**
     * @param class-string<ValueObject> $input
     *
     * @psalm-mutation-free
     */
    public function withInput(string $input): self
    {
        return clone($this, ['input' => $input]);
    }

    /**
     * @param class-string<Event> $event
     * @param class-string<RequestHandlerInterface|RouteHandler> $handler
     *
     * @return iterable<string, array<mixed>>
     *
     * @psalm-impure
     */
    public function buildCreateEventRoute(string $action, string $event, string $handler = CreateEventRouteHandler::class): iterable
    {
        yield from $this->buildEventRoute(Method::Put, $action, $event, $handler);
    }

    /**
     * @param class-string<Event> $event
     * @param class-string<RequestHandlerInterface|RouteHandler> $handler
     *
     * @return iterable<string, array<mixed>>
     *
     * @psalm-impure
     */
    public function buildUpdateEventRoute(string $action, string $event, string $handler = UpdateEventRouteHandler::class): iterable
    {
        yield from $this
            ->withAddedCondition(ExistsConditionConstraint::class)
            ->withAddedCondition(VersionConditionConstraint::class)
            ->buildEventRoute(Method::Patch, $action, $event, $handler);
    }

    /**
     * @param class-string<Event> $event
     * @param class-string<RequestHandlerInterface|RouteHandler> $handler
     *
     * @return iterable<string, array<mixed>>
     *
     * @psalm-impure
     */
    public function buildEventRoute(Method $method, string $action, string $event, string $handler): iterable
    {
        assert($this->resourceRepository !== null);

        $builder = $this->input === null
            ? $this->withInput($event)
            : $this;

        yield from $builder
            ->withExtraOption('event', $event)
            ->buildRoute($method, $action, $handler);
    }

    /**
     * @return iterable<string, array<mixed>>
     *
     * @psalm-impure
     */
    public function buildResultQueryRoute(string $action): iterable
    {
        yield from $this->buildQueryRoute($action, ResultQueryRouteHandler::class);
    }

    /**
     * @return iterable<string, array<mixed>>
     *
     * @psalm-impure
     */
    public function buildResultsQueryRoute(string $method): iterable
    {
        yield from $this->buildQueryRoute($method, ResultsQueryRouteHandler::class);
    }

    /**
     * @param class-string<RequestHandlerInterface|RouteHandler> $handler
     *
     * @return iterable<string, array<mixed>>
     *
     * @psalm-impure
     */
    public function buildQueryRoute(string $action, string $handler): iterable
    {
        yield from $this
            ->withExtraOption(
                'proxy',
                [
                    'class' => $this->proxyClass,
                    'method' => $action,
                ],
            )
            ->buildRoute(
                Method::Query,
                $action,
                $handler,
            );
    }

    /**
     * @param class-string<RequestHandlerInterface|RouteHandler> $handler
     * @param array<string, mixed> $baseRoute
     *
     * @return iterable<string, array<mixed>>
     *
     * @psalm-mutation-free
     */
    public function buildRoute(Method $method, string $action, string $handler, array $baseRoute = []): iterable
    {
        $route = array_replace(
            $baseRoute,
            $this->extraOptions,
            [
                'path' => "/{$this->resourceName}.{$action}",
                'resourceRepository' => $this->resourceRepository,
                'authorizations' => $this->authorizations,
                'conditions' => $this->conditions,
                'resource' => $this->resourceName,
                'validator' => $this->validator,
                'middleware' => $handler,
                'handler' => $handler,
                'method' => $method->value,
                'input' => $this->input,
            ],
        );

        $route = array_filter($route, fn (mixed $value): bool => $value !== null);

        yield "{$method->value}:/{$this->resourceName}.{$action}" => $route;
    }
}
