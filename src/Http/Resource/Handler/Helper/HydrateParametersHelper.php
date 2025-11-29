<?php

declare(strict_types=1);

namespace LesAbstractService\Http\Resource\Handler\Helper;

use LesHydrator\Hydrator;
use LesValueObject\ValueObject;
use LesHydrator\ReflectionHydrator;
use Psr\Http\Message\ServerRequestInterface;

trait HydrateParametersHelper
{
    /**
     * @param class-string<T> $parametersClass
     *
     * @return T
     *
     * @template T of ValueObject
     */
    protected function hydrateParameters(ServerRequestInterface $request, string $parametersClass)
    {
        $parameters = $request->getParsedBody();
        assert(is_array($parameters));

        return $this->getHydrator()->hydrate($parametersClass, $parameters);
    }

    protected function getHydrator(): Hydrator
    {
        return new ReflectionHydrator();
    }
}
