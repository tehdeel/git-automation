<?php


namespace Automation\Client\Command;


use chobie\Jira\Api;
use chobie\Jira\Issues\Walker;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
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
                $status = strtolower($issue->getStatus()['name']);
                $estimate = $issue->get('Original Estimate') / 3600;
                $times[$issue->getAssignee()['name']]['status'][$issue->getKey()] = $status;
                $times[$issue->getAssignee()['name']]['estimate'][$issue->getKey()] = $estimate;
                $times[$issue->getAssignee()['name']]['finished'][$issue->getKey()] = in_array($status, $this->finishedStatuses) ? $estimate : 0;
            }
        }
        $table = new Table($output);
        $columns = ['User', 'Estimate', 'Remaining'];
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $columns[] = 'Tasks';
            $columns[] = 'Times';
        }

        $table->setHeaders($columns);

        foreach ($times as $user => $time) {
            $table->addRow(new TableSeparator());
            $estimate = array_sum($time['estimate']);
            $remaining = $estimate - array_sum($time['finished']);
            $row = [$user, $estimate, $remaining];
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $formula = implode("\n", $time['estimate']);
                $tasks = array_map(function ($val) use ($time) {
                    return empty($time['status'][$val]) ? $val : $val . ' ' . $time['status'][$val];
                }, array_keys($time['estimate']));
                $row[] = implode("\n", $tasks);
                $row[] = $formula;
            }
            $table->addRow($row);
        }

        $table->render();
    }

}