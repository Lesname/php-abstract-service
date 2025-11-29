<?php

declare(strict_types=1);

namespace LesAbstractService\Permission\Repository\Exception;

use LesResource\Repository\Exception\NoResource;

/**
 * @psalm-immutable
 */
interface NoPermission extends NoResource
{
}
