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
        $path = $this->getContainer()->getParameter('kernel.root_dir') . '/var/lock';
        $name = strtolower(str_replace(['_', '/', '\\'], '', get_class($this)));
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
