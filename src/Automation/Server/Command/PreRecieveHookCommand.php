<?php

namespace Automation\Server\Command;

use Coyl\Git\DTO\Reference;
use Coyl\Git\GitRepo;
use Coyl\Git\RepoUtils;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PreRecieveHookCommand extends ContainerAwareCommand
{
    /** @var  GitRepo */
    protected $git;

    protected function configure()
    {
        $this
            ->setName('git:pre-recieve')
            ->setDescription('Runs all pre-recieve hooks and returns a result')
            ->addArgument('reference', InputArgument::REQUIRED, 'Reference')
            ->addArgument('old_revision', InputArgument::REQUIRED, 'Old revision')
            ->addArgument('new_revision', InputArgument::REQUIRED, 'New revision')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ref = RepoUtils::getReferenceInfo($input->getArgument('reference'));
        $oldRevision = $input->getArgument('old_revision');
        $newRevision = $input->getArgument('new_revision');

        $hook = $this->getContainer()->get('ga.hook.pre_recieve');
        
        $result = $hook->process($ref, $oldRevision, $newRevision);
        $output->writeln($result->getFormatted());
        return (int) $result->isDeclineRevision();
    }
}