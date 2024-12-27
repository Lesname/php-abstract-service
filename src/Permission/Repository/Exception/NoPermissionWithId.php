<?php
declare(strict_types=1);

namespace LessAbstractService\Permission\Repository\Exception;

use LessResource\Repository\Exception\AbstractNoResourceWithId;

/**
 * @psalm-immutable
 */
final class NoPermissionWithId extends AbstractNoResourceWithId
{
}
