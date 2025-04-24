<?php
declare(strict_types=1);

namespace LesAbstractService\Permission\Event\Listener;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use LesDomain\Event\Listener\Listener;
use LesAbstractService\Permission\Event;
use LesDomain\Event\AbstractAggregateEvent;
use LesDomain\Event\Listener\Helper\DelegateActionListenerHelper;
use LesDatabase\Query\Builder\Applier\Values\InsertValuesApplier;
use LesDatabase\Query\Builder\Applier\Values\UpdateValuesApplier;
use LesDatabase\Query\Builder\Applier\Resource\UpdateResourceApplier;

final class DbalListener implements Listener
{
    use DelegateActionListenerHelper;

    public function __construct(private readonly Connection $connection)
    {}

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
            ->apply($this->connection->createQueryBuilder())
            ->insert('permission')
            ->executeStatement();
    }

    /**
     * @throws Exception
     */
    protected function handleUpdated(Event\UpdatedEvent $event): void
    {
        UpdateValuesApplier
            ::forValues(
                [
                    'flags_grant' => $event->flags->grant,
                    'flags_read' => $event->flags->read,
                    'flags_create' => $event->flags->create,
                    'flags_update' => $event->flags->update,
                ],
            )
            ->apply($this->createUpdateBuilder($event))
            ->executeStatement();
    }

    private function createUpdateBuilder(AbstractAggregateEvent $event): QueryBuilder
    {
        $builder = $this->connection->createQueryBuilder();
        $builder->update('permission');
        UpdateResourceApplier::fromEvent($event)->apply($builder);

        return $builder;
    }
}
