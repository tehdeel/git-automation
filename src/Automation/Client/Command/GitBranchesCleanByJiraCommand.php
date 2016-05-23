<?php

namespace Automation\Client\Command;

use Coyl\Git\GitRepo;
use chobie\Jira\Issues\Walker;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GitBranchesCleanByJiraCommand extends ContainerAwareCommand
{
    /** @var  GitRepo */
    protected $git;

    protected function configure()
    {
        $this
            ->setName('git:branches:clean-by-jira')
            ->setDescription('Deletes local branches if task is closed in Jira')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force branch deletion')
            ->addOption('remote', 'r', InputOption::VALUE_NONE, 'Remove remote branches as well');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->git = new GitRepo(realpath('.'));
        $api = $this->getContainer()->get('jira_api_rest_client');
        $force = $remote = false;
        if (!empty($input->getOption('force'))) {
            $output->writeln('Force deletion enabled');
            $force = true;
        }
        if (!empty($input->getOption('remote'))) {
            $output->writeln('Remote branches deletion enabled');
            $remote = true;
        }
        $permanentBranches = ['dev', 'master', 'facebook'];

        $branches = $this->git->branches();

        $branches = array_filter(
            array_map(
                function ($val) use ($permanentBranches) {
                    $val = trim($val);
                    if (in_array($val, $permanentBranches)) {
                        return false;
                    }

                    $val = preg_replace("/_.*/i", "", $val);
                    if (strpos($val, '-') > 1 && strpos($val, '-') < (strlen($val) - 1)) {
                        return $val;
                    }

                    return false;
                },
                $branches
            )
        );

        $walker = new Walker($api);
        $query = 'key IN ("' . implode('","', $branches) . '")';
        $walker->push($query);
        /** @var \chobie\Jira\Issue $issue */
        $deleted = [];
        foreach ($walker as $issue) {
            $closedStatuses = ["approved", "awaiting for check", "closed (won't fix)", "closed success"];
            if (in_array(strtolower($issue->getStatus()['name']), $closedStatuses)) {
                $output->writeln($issue->getKey() . ' is closed ');
                try {
                    $this->git->branchDelete($issue->getKey(), $force);
                    $deleted[] = $issue->getKey();
                } catch (\Exception $e) {
                }
            } else {
                $output->writeln($issue->getKey() . ' is ' . $issue->getStatus()['name']);
            }

        }
        if ($remote && !empty($deleted)) {
            $this->git->deleteRemoteBranches($deleted);
        }
    }

}
