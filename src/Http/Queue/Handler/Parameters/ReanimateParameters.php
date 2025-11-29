<?php

declare(strict_types=1);

namespace LesAbstractService\Http\Queue\Handler\Parameters;

use LesQueue\Job\Property\Identifier;
use LesValueObject\Number\Int\Date\Timestamp;
use LesValueObject\Composite\AbstractCompositeValueObject;

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
