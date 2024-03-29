<?php
declare(strict_types=1);

namespace LessAbstractService\Event\Listener;

use LessDomain\Event\Event;
use LessDomain\Event\Listener\Listener;
use LessQueue\Job\Property\Name;
use LessQueue\Queue;

/**
 * @deprecated will be dropped
 */
final class HookPushListener implements Listener
{
    public function __construct(private readonly Queue $queue)
    {}

    public function handle(Event $event): void
    {
        if (!isset($event->id)) {
            return;
        }

        $this
            ->queue
            ->publish(
                new Name('hook:push'),
                [
                    'target' => $event->getTarget(),
                    'action' => $event->getAction(),
                    'id' => $event->id,
                ],
            );
    }
}
