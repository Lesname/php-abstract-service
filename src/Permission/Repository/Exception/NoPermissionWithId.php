<?php
declare(strict_types=1);

namespace LesAbstractService\Permission\Repository\Exception;

use LesResource\Repository\Exception\AbstractNoResourceWithId;

/**
 * @psalm-immutable
 */
final class NoPermissionWithId extends AbstractNoResourceWithId
{
}
