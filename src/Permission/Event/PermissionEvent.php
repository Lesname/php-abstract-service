<?php

declare(strict_types=1);

namespace LesAbstractService\Permission\Event;

use LesDomain\Event\Property\Target;

/**
 * @psalm-immutable
 */
trait PermissionEvent
{
    public Target $target {
        get => new Target('permission');
    }
}
