<?php
declare(strict_types=1);

namespace LessAbstractService\Http\Queue\Handler\Command\Parameters;

use LessQueue\Job\Property\Identifier;
use LessValueObject\Composite\AbstractCompositeValueObject;

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
