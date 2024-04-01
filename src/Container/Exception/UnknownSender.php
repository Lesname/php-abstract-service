<?php
declare(strict_types=1);

namespace LessAbstractService\Container\Exception;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @psalm-immutable
 *
 * @psalm-suppress MutableDependency
 */
final class UnknownSender extends Exception implements NotFoundExceptionInterface
{
    public function __construct(public readonly string $id)
    {
        parent::__construct("Uknown sender for '{$id}'");
    }
}
