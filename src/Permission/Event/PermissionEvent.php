<?php
declare(strict_types=1);

namespace LessAbstractService\Permission\Event;

use LessDomain\Event\Property\Target;

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
