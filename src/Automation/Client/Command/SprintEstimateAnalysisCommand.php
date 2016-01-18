<?php


namespace Automation\Client\Command;


use chobie\Jira\Api;
use chobie\Jira\Issues\Walker;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SprintEstimateAnalysisCommand extends ContainerAwareCommand
{

    protected $finishedStatuses = ['checking', 'closed success', 'closed unsuccess', 'done', 'testing'];

    protected function configure()
    {
        $this->setName('jira:sprint-analysis')->addArgument('assignees', InputArgument::IS_ARRAY, 'Usernames of assignees to analyse');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Api $api */
        $api = $this->getContainer()->get('git_automation.jira_api');
        $walker = new Walker($api);
        $query = 'sprint in openSprints ()';
        if (!empty($input->getArgument('assignees'))) {
            $query .= sprintf(' AND assignee IN (%s)', implode(', ', $input->getArgument('assignees')));
        }
        $query .= ' ORDER BY originalEstimate';
        $walker->push($query);
        $times = [];
        /** @var \chobie\Jira\Issue $issue */
        foreach ($walker as $issue) {
            if (!empty($issue->get('Original Estimate'))) {
                $estimate = $issue->get('Original Estimate') / 3600;
                $times[$issue->getAssignee()['name']]['estimate'][$issue->getKey()] = $estimate;
                $status = strtolower($issue->getStatus()['name']);
                $times[$issue->getAssignee()['name']]['finished'][$issue->getKey()] = in_array($status, $this->finishedStatuses) ? $estimate : 0;
            }
        }
        $table = new Table($output);
        $table->setHeaders(array('User', 'Estimate', 'Finished', 'Formula', 'Tasks'));
        foreach ($times as $user => $time) {
            $estimate = array_sum($time['estimate']);
            $remaining = $estimate - array_sum($time['finished']);
            $formula = implode(' + ', $time['estimate']);
            if ($remaining < $estimate) {
                $formula .= ' - ' . implode(' - ', $time['finished']);
            }
            $table->addRow([$user, $estimate, $remaining, $formula, implode(', ', array_keys($time['estimate']))]);
        }
        $table->render();
    }

}