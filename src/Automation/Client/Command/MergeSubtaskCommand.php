<?php

namespace Automation\Client\Command;

use chobie\Jira\Api;
use chobie\Jira\Issue;
use Coyl\Git\Git;
use Coyl\Git\GitRepo;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MergeSubtaskCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('git:branches:merge-subtask')
            ->addArgument("branch", InputArgument::REQUIRED, 'Branch of subtask');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $git = new GitRepo(realpath('.'));
        $git->fetch();
        $branches = $git->branches(GitRepo::BRANCH_LIST_MODE_All);

        /** @var Api $api */
        $api = $this->getContainer()->get('jira_api_rest_client');

        $branchName = $input->getArgument('branch');
        if (!in_array($branchName, $branches)){
            $output->writeln(sprintf("<error>Branch %s not found</error>", $branchName));
        }

        $key = preg_replace('/_.*/i', '', $branchName);
        /** @var Api\Result $result */
        $result = $api->getIssue($key);
        /** @var Issue $issue */
        $issue = new Issue($result->getResult());
        if (isset($issue->getFields()['parent'])) {
            $parentKey = $issue->getFields()['parent']['key'];
        } else {
            $output->writeln(sprintf("Branch %s does not have a parent task", $key));
            return;
        }
        $git->checkout($branchName);
        $git->pull('origin', $branchName);
        if (!in_array($parentKey, $branches) && !in_array('remotes/origin/'.$parentKey, $branches)) {
            $git->branchNew($parentKey);
        }else{
            $git->checkout($parentKey);
        }

        $git->merge($branchName);
    }

}