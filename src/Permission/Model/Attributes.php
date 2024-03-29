<?php
declare(strict_types=1);

namespace LessAbstractService\Permission\Model;

use LessValueObject\Composite\ForeignReference;
use LessAbstractService\Permission\Model\Attributes\Flags;
use LessValueObject\Composite\AbstractCompositeValueObject;

/**
 * @psalm-immutable
 */
final class Attributes extends AbstractCompositeValueObject
{
    public function __construct(
        public readonly ForeignReference $identity,
        public readonly Flags $flags,
    ) {}
}
