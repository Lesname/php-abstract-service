<?php
declare(strict_types=1);

namespace LessAbstractService\Cli\Service;

use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class UpdateCommand extends Command
{
    public function __construct(
        private readonly CacheInterface $cache
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        exec('git pull --strategy-option=theirs > /dev/null 2>&1');

        exec('~/bin/composer install --no-dev --optimize-autoloader --prefer-dist > /dev/null 2>&1');

        if (file_exists('config/cache.php')) {
            unlink('config/cache.php');
        }

        exec('git fetch --depth 1');
        exec('git reflog expire --expire=all --all');
        exec('git tag -l | xargs git tag -d');
        exec('git gc --prune=all');

        $this->cache->clear();

        exec("./vendor/bin/laminas documentor.write");

        return self::SUCCESS;
    }
}
