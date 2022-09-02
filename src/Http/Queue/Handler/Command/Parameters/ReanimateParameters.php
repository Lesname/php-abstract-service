<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Queue\Handler\Command\Parameters;

use LessQueue\Job\Property\Identifier;
use LessValueObject\Composite\AbstractCompositeValueObject;
use LessValueObject\Number\Int\Date\Timestamp;

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
