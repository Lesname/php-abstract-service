<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Queue\Handler\Parameters;

use LessQueue\Job\Property\Identifier;
use LessValueObject\Number\Int\Date\Timestamp;
use LessValueObject\Composite\AbstractCompositeValueObject;

/**
 * @psalm-immutable
 */
final class ReanimateParameters extends AbstractCompositeValueObject
{
    public function __construct(
        public readonly Identifier $id,
        public readonly ?Timestamp $until,
    ) {}
}
