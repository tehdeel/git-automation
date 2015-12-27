<?php

namespace Automation;

use Automation\Client\Command\GitBranchesCleanByJiraCommand;
use Automation\Client\Command\TestCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\Input\InputInterface;

class GitAutomationApp extends Application
{
    public function getName()
    {
        return 'Git Routines Automation';
    }

    public function getVersion()
    {
        return '0.1.0';
    }

}