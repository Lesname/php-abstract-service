<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Resource\Handler;

use LessHydrator\Hydrator;
use LessValueObject\ValueObject;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @deprecated use HydrateParametersHelper
 */
abstract class AbstractParametersHandler implements RequestHandlerInterface
{
    public function __construct(protected readonly Hydrator $hydrator)
    {}

    /**
     * @param ServerRequestInterface $request
     * @param class-string<T> $classParameters
     *
     * @return T
     *
     * @template T of ValueObject
     */
    protected function getParameters(ServerRequestInterface $request, string $classParameters): ValueObject
    {
        $body = $request->getParsedBody();
        assert(is_array($body));

        return $this->hydrator->hydrate($classParameters, $body);
    }
}
