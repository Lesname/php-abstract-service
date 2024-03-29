<?php
declare(strict_types=1);

namespace LessAbstractService\Permission\Repository\Parameter;

use LessValueObject\Composite\AbstractCompositeValueObject;

/**
 * @psalm-immutable
 */
final class Flags extends AbstractCompositeValueObject
{
    public function __construct(
        public readonly ?bool $grant,
        public readonly ?bool $read,
        public readonly ?bool $create,
        public readonly ?bool $update,
    ) {}
}
