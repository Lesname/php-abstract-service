<?php
declare(strict_types=1);

namespace LessAbstractService\Permission\Event\Listener;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use LessDomain\Event\Listener\Listener;
use LessAbstractService\Permission\Event;
use LessDomain\Event\AbstractAggregateEvent;
use LessDomain\Event\Listener\Helper\DelegateActionListenerHelper;
use LessDatabase\Query\Builder\Applier\Values\InsertValuesApplier;
use LessDatabase\Query\Builder\Applier\Values\UpdateValuesApplier;
use LessDatabase\Query\Builder\Applier\Resource\UpdateResourceApplier;

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
                    'flags_read' => $event->flags->write || $event->flags->read,
                    'flags_write' => $event->flags->write,
                    'activity_last' => $event->getOccuredOn(),
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
                    'flags_read' => $event->flags->write || $event->flags->read,
                    'flags_write' => $event->flags->write,
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
