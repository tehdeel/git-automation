<?php


namespace Automation\Client\Command;


use chobie\Jira\Api;
use chobie\Jira\Issues\Walker;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SprintEstimateAnalysisCommand extends ContainerAwareCommand
{

    protected $finishedStatuses = ['checking', 'closed success', 'closed unsuccess', 'done', 'testing'];

    protected function configure()
    {
        $this->setName('jira:sprint-analysis')
             ->addArgument('assignees', InputArgument::IS_ARRAY, 'Usernames of assignees to analyse')
             ->addOption('sprint', 's', InputOption::VALUE_OPTIONAL, 'Name of sprint to analyze')
             ->addOption(
                 'excludeLabels',
                 'l',
                 InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                 'Exclude issues with the labels'
             )
             ->addOption(
                 'additionalDql',
                 'a',
                 InputOption::VALUE_OPTIONAL,
                 'arbitrary DQL; will be added with AND operator'
             );
    }

    private function getQuery(InputInterface $input)
    {
        if (!empty($input->getOption('sprint'))) {
            $query = sprintf('sprint = "%s"', $input->getOption('sprint'));
        } else {
            $query = 'sprint in openSprints()';
        }

        if (!empty($input->getOption('excludeLabels'))) {
            $query = sprintf(
                ' AND (labels NOT IN ("%s") OR labels is EMPTY)',
                implode('", "', $input->getOption('excludeLabels'))
            );
        }

        if (!empty($input->getArgument('assignees'))) {
            $query .= sprintf(' AND assignee IN (%s)', implode(', ', $input->getArgument('assignees')));
        }

        if (!empty($input->getOption('additionalDql'))) {
            $query .= sprintf(' AND %s', $input->getOption('additionalDql'));
        }

        $query .= ' ORDER BY originalEstimate';

        return $query;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Api $api */
        $api = $this->getContainer()->get('git_automation.jira_api');
        $walker = new Walker($api);
        $query = $this->getQuery($input);
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln(sprintf('Query: "%s"', $query));
        }
        $walker->push($query);
        $times = [];
        $count = 0;
        /** @var \chobie\Jira\Issue $issue */
        foreach ($walker as $issue) {
            if (empty($issue->get('Original Estimate'))) {
                continue;
            }
            $count ++;
            $status = strtolower($issue->getStatus()['name']);
            $estimate = $issue->get('Original Estimate') / 3600;
            $times[$issue->getAssignee()['name']]['status'][$issue->getKey()] = $status;
            $times[$issue->getAssignee()['name']]['estimate'][$issue->getKey()] = $estimate;
            $times[$issue->getAssignee()['name']]['finished'][$issue->getKey()] = in_array(
                $status,
                $this->finishedStatuses
            ) ? $estimate : 0;
        }
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln(sprintf('Got %d tasks with estimates for %d users', $count, count($times)));
        }
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
            $output->writeln(print_r($times, true));
        }

        $table = new Table($output);
        $columns = ['User', 'Estimate', 'Remaining', 'Tasks', 'Times'];
        $table->setHeaders($columns);

        $total = [];
        foreach ($times as $user => $time) {
            $table->addRow(new TableSeparator());
            $estimate = array_sum($time['estimate']);
            $remaining = $estimate - array_sum($time['finished']);
            $row = [$user, $estimate, $remaining];
            $formula = implode("\n", $time['estimate']);
            $tasks = array_map(
                function ($val) use ($time) {
                    return empty($time['status'][$val]) ? $val : $val . ' ' . $time['status'][$val];
                },
                array_keys($time['estimate'])
            );
            $row[] = implode("\n", $tasks);
            $row[] = $formula;
            $table->addRow($row);

            $total['estimate'][] = $estimate;
            $total['remaining'][] = $remaining;
            $total['tasks'][] = count($tasks);
        }

        if (count($total)) {
            $table->addRow(new TableSeparator());
            $total = [
                'Total',
                array_sum($total['estimate']),
                array_sum($total['remaining']),
                array_sum($total['tasks']),
                array_sum($total['estimate'])
            ];
            $table->addRow($total);
        }

        $table->render();
    }

}