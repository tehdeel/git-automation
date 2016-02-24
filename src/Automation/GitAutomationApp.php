<?php

namespace Automation;

use Symfony\Bundle\FrameworkBundle\Console\Application;

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