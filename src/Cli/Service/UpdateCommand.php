<?php
declare(strict_types=1);

namespace LesAbstractService\Cli\Service;

use Override;
use LesQueue\Queue;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class UpdateCommand extends Command
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly Queue $queue,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->queue->countProcessing() > 0) {
            $output->writeln('Active queue processing cannot update during');

            return self::FAILURE;
        }

        exec('git pull --strategy-option=theirs');

        exec('/usr/local/bin/composer install --no-dev --optimize-autoloader --prefer-dist');

        if (!file_exists('config/local')) {
            $output->writeln('Look into local config usage');
        }

        if (file_exists('config/autoload')) {
            $output->writeln('<comment>Autoload config available</comment>');
        }

        if (file_exists('config/development.config.php')) {
            $output->writeln('<comment>Beware development config active</comment>');
        } else {
            $files = glob('config/cache*.php');

            if ($files === false || count($files) === 0) {
                $output->writeln('<comment>Beware no cache config active</comment>');
            } else {
                foreach ($files as $file) {
                    unlink($file);
                }
            }
        }

        exec('git fetch --depth 1');
        exec('git reflog expire --expire=all --all');
        exec('git tag -l | xargs git tag -d');
        exec('git gc --prune=all');

        $this->cache->clear();

        exec("./vendor/bin/laminas documentor.write", $execOutput, $resultCode);

        $output->writeln("Documentor write result code: {$resultCode}");

        return self::SUCCESS;
    }
}
