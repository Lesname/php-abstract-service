<?php
declare(strict_types=1);

namespace LessAbstractService\Permission\Event;

use LessDomain\Event\Property\Headers;
use LessDomain\Event\AbstractAggregateEvent;
use LessDomain\Event\Helper\EventActionHelper;
use LessValueObject\Number\Int\Date\MilliTimestamp;
use LessValueObject\String\Format\Resource\Identifier;
use LessAbstractService\Permission\Model\Attributes\Flags;

/**
 * @psalm-immutable
 */
final class UpdatedEvent extends AbstractAggregateEvent
{
    use EventActionHelper;
    use PermissionEvent;

    public function __construct(
        Identifier $id,
        public readonly Flags $flags,
        MilliTimestamp $occurredOn,
        Headers $headers,
    ) {
        parent::__construct($id, $occurredOn, $headers);
    }
}
