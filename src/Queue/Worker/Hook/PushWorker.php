<?php
declare(strict_types=1);

namespace LessAbstractService\Queue\Worker\Hook;

use LessHook\Producer\Service\ProducerService;
use LessQueue\Job\Job;
use LessQueue\Worker\Worker;
use LessValueObject\Composite\ForeignReference;

final class PushWorker implements Worker
{
    public function __construct(
        private readonly ProducerService $producerService,
        private readonly ForeignReference $producer,
    ) {}

    public function process(Job $job): void
    {
        $data = $job->getData();

        $this
            ->producerService
            ->publish(
                $this->producer,
                $data['target'],
                $data['action'],
                $data['id'],
            );
    }
}
