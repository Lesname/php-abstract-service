<?php

declare(strict_types=1);

namespace LesAbstractService\Cli\Queue\Exception;

use Exception;
use LesAbstractService\Exception\AbstractServiceException;

/**
 * @psalm-immutable
 */
final class NoWorkerForJob extends Exception implements AbstractServiceException
{
    public function __construct(public readonly string $jobName)
    {
        parent::__construct("No worker found for job: {$this->jobName}");
    }
}
