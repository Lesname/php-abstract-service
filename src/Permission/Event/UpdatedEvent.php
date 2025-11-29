<?php

declare(strict_types=1);

namespace LesAbstractService\Permission\Event;

use LesDomain\Event\Property\Action;
use LesDomain\Event\Property\Headers;
use LesDomain\Event\AbstractAggregateEvent;
use LesValueObject\Number\Int\Date\MilliTimestamp;
use LesValueObject\String\Format\Resource\Identifier;
use LesAbstractService\Permission\Model\Attributes\Flags;

/**
 * @psalm-immutable
 */
final class UpdatedEvent extends AbstractAggregateEvent
{
    use PermissionEvent;

    public Action $action {
        get => new Action('updated');
    }

    public function __construct(
        Identifier $id,
        public readonly Flags $flags,
        MilliTimestamp $occurredOn,
        Headers $headers,
    ) {
        parent::__construct($id, $occurredOn, $headers);
    }
}
