<?php

declare(strict_types=1);

namespace LesAbstractService\Permission\Model;

use LesValueObject\Composite\ForeignReference;
use LesAbstractService\Permission\Model\Attributes\Flags;
use LesValueObject\Composite\AbstractCompositeValueObject;

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
