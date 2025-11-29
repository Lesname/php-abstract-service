<?php

declare(strict_types=1);

namespace LesAbstractService\Factory\Container\Exception;

use Exception;
use Throwable;

/**
 * @psalm-immutable
 */
final class FailedParameter extends Exception
{
    public function __construct(public readonly string $parameter, Throwable $previous)
    {
        parent::__construct("Failed to create parameter '{$parameter}'", previous: $previous);
    }
}
