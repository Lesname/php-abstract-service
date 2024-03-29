<?php
declare(strict_types=1);

namespace LessAbstractService\Permission\Repository\Exception;

use LessResource\Repository\Exception\NoResource;

/**
 * @psalm-immutable
 */
interface NoPermission extends NoResource
{
}
