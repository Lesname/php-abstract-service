<?php
declare(strict_types=1);

namespace LesAbstractService\Http\Queue\Handler\Parameters;

use LesQueue\Job\Property\Identifier;
use LesValueObject\Composite\AbstractCompositeValueObject;

/**
 * @psalm-immutable
 */
final class DeleteParameters extends AbstractCompositeValueObject
{
    public function __construct(
        public readonly Identifier $id,
    ) {
    }
}
