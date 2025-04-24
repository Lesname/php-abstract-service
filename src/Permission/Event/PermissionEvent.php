<?php
declare(strict_types=1);

namespace LesAbstractService\Permission\Event;

use LesDomain\Event\Property\Target;

/**
 * @psalm-immutable
 */
trait PermissionEvent
{
    /**
     * @psalm-pure
     */
    public function getTarget(): Target
    {
        return new Target('permission');
    }
}
