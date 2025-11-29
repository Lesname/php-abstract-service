<?php

declare(strict_types=1);

namespace LesAbstractService\Http\Queue\Handler\Response;

use LesValueObject\Number\Int\Unsigned;
use LesValueObject\Composite\AbstractCompositeValueObject;

/**
 * @psalm-immutable
 */
final class GetStatsResponse extends AbstractCompositeValueObject
{
    public function __construct(
        public readonly Unsigned $processable,
        public readonly Unsigned $processing,
        public readonly Unsigned $buried,
    ) {}
}
