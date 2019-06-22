<?php
/**
 * Created for lv-exports.
 * Datetime: 03.07.2018 14:41
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\External\Export\App\Commands;


use Leadvertex\External\Export\App\Components\DeferredRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BackgroundCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('app:background')
            ->setDescription('Run generate operation in background')
            ->addArgument('token', InputArgument::REQUIRED, 'Batch token');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tokensDir = __DIR__ . implode(DIRECTORY_SEPARATOR, ['runtime', 'tokens']);
        $handler = new DeferredRunner($tokensDir);
        $handler->run($input->getArgument('token'));
    }

}