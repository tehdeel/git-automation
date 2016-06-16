<?php

namespace Automation\Server\Command;

use Automation\Server\Hook;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PrePushHookCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('git:pre-push')
            ->setDescription('Runs all pre-recieve hooks and returns a result');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commit = trim(fgets(STDIN));
        if ($commit) {
            list ($_, $localSha, $__, $remoteSha) = explode(' ', $commit);

            /** @var Hook $hook */
            $hook = $this->getContainer()->get('ga.hook.pre_push');
            $result = $hook->process($remoteSha, $localSha);
            $output->writeln($result->getFormatted());

            return (int) $result->isDeclineRevision();
        }

        return 0;
    }
}