<?php

declare(strict_types=1);

namespace LesAbstractService\Permission\Event\Listener;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;
use LesAbstractService\Permission\Event;
use LesDomain\Event\AbstractAggregateEvent;
use LesDatabase\Query\Builder\Applier\ChainApplier;
use LesDomain\Event\Listener\AbstractDbalDelegateListener;
use LesDatabase\Query\Builder\Applier\Values\InsertValuesApplier;
use LesDatabase\Query\Builder\Applier\Values\UpdateValuesApplier;
use LesDatabase\Query\Builder\Applier\Resource\UpdateResourceApplier;

/**
 * @deprecated no replacement
 */
final class DbalListener extends AbstractDbalDelegateListener
{
    /**
     * @throws Exception
     */
    protected function handleGranted(Event\GrantedEvent $event): void
    {
        InsertValuesApplier
            ::forValues(
                [
                    'id' => $event->id,
                    'identity_type' => $event->identity->type,
                    'identity_id' => $event->identity->id,
                    'flags_grant' => $event->flags->grant,
                    'flags_read' => $event->flags->read,
                    'flags_create' => $event->flags->create,
                    'flags_update' => $event->flags->update,
                    'activity_last' => $event->occurredOn,
                ],
            )
            ->apply($this->db->createQueryBuilder())
            ->insert('permission')
            ->executeStatement();
    }

    /**
     * @throws Exception
     */
    protected function handleUpdated(Event\UpdatedEvent $event): void
    {
        ChainApplier::chain(
            UpdateValuesApplier
                ::forValues(
                    [
                        'flags_grant' => $event->flags->grant,
                        'flags_read' => $event->flags->read,
                        'flags_create' => $event->flags->create,
                        'flags_update' => $event->flags->update,
                    ],
                ),
            UpdateResourceApplier::fromEvent($event),
        )
            ->apply($this->db->createQueryBuilder())
            ->update('permission')
            ->executeStatement();
    }
}
