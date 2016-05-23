<?php

namespace Automation\Client\Command;

use chobie\Jira\Api;
use chobie\Jira\Issue;
use chobie\Jira\Issues\Walker;
use Coyl\Git\Exception\BranchNotFoundException;
use Coyl\Git\Git;
use Coyl\Git\GitRepo;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class TestReviewTasksCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('git:branches:test-review')
            ->addArgument("tasks", InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Tasks to test');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $git = new GitRepo(realpath('.'));
        /** @var string[] $tasks */
        $tasks = $input->getArgument('tasks');
        /** @var Api $api */
        $api = $this->getContainer()->get('jira_api_rest_client');

        $walker = new Walker($api);
        $query = "status = 'REVIEW'";
        if ($tasks) {
            $query .= 'AND key IN ("' . implode('","', $tasks) . '")';
        } else {
            $query .= 'AND assignee=currentUser()';
        }
        $output->writeln('query ' . $query);
        $walker->push($query);
        /** @var \chobie\Jira\Issue $issue */
        $git->fetch();
        foreach ($walker as $issue) {
            try {
                $output->writeln('Checking out branch ' . $issue->getKey());
                $git->checkout($issue->getKey());
                $output->writeln('Pulling');
                $git->pull("origin", $issue->getKey());
                $output->writeln('Testing');
                $git->getConsole()->runCommand('phpunit');
            } catch (BranchNotFoundException $e) {
                $output->writeln($e->getMessage()." ".$e->getPrevious()->getMessage());
                break;
            } catch (\Exception $e) {
                $output->writeln($e->getMessage());
                break;
            }
            $output->writeln('Tests OK!');
        }
    }
}