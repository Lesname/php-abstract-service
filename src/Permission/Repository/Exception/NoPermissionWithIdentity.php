<?php
declare(strict_types=1);

namespace LessAbstractService\Permission\Repository\Exception;

use LessResource\Exception\AbstractException;
use LessValueObject\Composite\ForeignReference;

/**
 * @psalm-immutable
 */
final class NoPermissionWithIdentity extends AbstractException implements NoPermission
{
    public function __construct(ForeignReference $identity)
    {
        parent::__construct("No permission with identity '{$identity}'");
    }
}
