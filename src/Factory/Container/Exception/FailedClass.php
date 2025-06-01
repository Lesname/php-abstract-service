<?php
declare(strict_types=1);

namespace LesAbstractService\Factory\Container\Exception;

use Exception;
use Throwable;

final class FailedClass extends Exception
{
    public function __construct(public readonly string $class, Throwable $previous)
    {
        parent::__construct("Failed to create class '{$class}'", previous: $previous);
    }
}
