<?php
declare(strict_types=1);

namespace LesAbstractService\Permission\Repository\Exception;

use LesResource\Exception\AbstractException;
use LesValueObject\Composite\ForeignReference;

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
