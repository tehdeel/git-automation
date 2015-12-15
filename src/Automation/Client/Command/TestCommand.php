<?php


namespace Automation\Client\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:test');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dir = dirname(\Phar::running(false));
        if ($dir) $output->writeln('Phar mode ' . $dir);
        else $output->writeln('Simple mode ' . dirname(__DIR__));
        $output->writeln('__DIR__ ' . dirname(__DIR__));
        $output->writeln('Current dir ' . (realpath('.')));
    }

}