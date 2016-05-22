<?php

namespace Automation\Client\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\LockHandler;

abstract class AbstractMonopolyCommand extends ContainerAwareCommand
{
    public function run(InputInterface $input, OutputInterface $output)
    {
        $name = implode(
            '',
            array_map(
                function ($e) {
                    return strtolower(str_replace(['_', '/', '\\', '-', '.'], '', $e));
                },
                array_merge([get_class($this)], $input->getArguments())
            )
        );
        $path = sys_get_temp_dir() . '/lock';
        $fs = new Filesystem();
        if (!$fs->exists($path)) {
            $fs->mkdir($path);
        }
        $locker = new LockHandler($name, $path);

        if (!$locker->lock(false)) {
            $output->writeln('<error>Another instance of the command executed in Monopoly mode, ouitting</error>');
            exit(75);
        }

        try {
            return parent::run($input, $output);
        } finally {
            $locker->release();
        }
    }
}
