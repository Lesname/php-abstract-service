<?php
declare(strict_types=1);

namespace LessAbstractService\Container\Mail\Exception;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @psalm-immutable
 *
 * @psalm-suppress MutableDependency
 */
final class UnknownReference extends Exception implements NotFoundExceptionInterface
{
    public function __construct(public readonly string $id)
    {
        parent::__construct("Unknown reference for '{$id}'");
    }
}
