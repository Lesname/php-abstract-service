<?php
declare(strict_types=1);

namespace LessAbstractService\Queue\Worker\Hook;

use LessDomain\Event\Property\Action;
use LessDomain\Event\Property\Target;
use LessHook\Producer\Service\ProducerService;
use LessQueue\Job\Job;
use LessQueue\Worker\Worker;
use LessValueObject\Composite\ForeignReference;
use LessValueObject\String\Format\Resource\Identifier;

final class PushWorker implements Worker
{
    public function __construct(
        private readonly ProducerService $producerService,
        private readonly ForeignReference $producer,
    ) {}

    public function process(Job $job): void
    {
        $data = $job->data;

        assert($data['target'] instanceof Target);
        assert($data['action'] instanceof Action);
        assert($data['id'] instanceof Identifier);

        $this
            ->producerService
            ->publish(
                $this->producer,
                $data['target']->getValue(),
                $data['action']->getValue(),
                $data['id'],
            );
    }
}
