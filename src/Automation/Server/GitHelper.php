<?php

namespace Automation\Server;

use Coyl\Git\Git;
use Coyl\Git\GitRepo;

class GitHelper
{
    /**
     * @var GitRepo
     */
    private $git;
    /**
     * @var string
     */
    private $defaultBranch;

    /**
     * GitHelper constructor.
     *
     * @param GitRepo $git
     * @param string  $defaultBranch
     */
    public function __construct(GitRepo $git, $defaultBranch = 'master')
    {
        $this->git = $git;
        $this->defaultBranch = $defaultBranch;
    }

    /**
     * @param string      $lastRevision
     * @param string|null $firstRevision
     * @param bool        $filesOnly
     *
     * @return string
     */
    public function getDiffForAllFiles($lastRevision, $firstRevision = null, $filesOnly = false)
    {
        if (in_array($firstRevision, [null, Git::ZERO_REVISION], true)) {
            $firstRevision = $this->defaultBranch;
        }
        $log = $this->git->log(sprintf("--no-merges %s..%s --pretty=format:'%%H'", $firstRevision, $lastRevision));
        $log = explode("\n", $log);
        $diff = '';
        if ($filesOnly) {
            $format = '--pretty=format: --name-status %s';
        } else {
            $format = '--pretty=format: %s';
        }
        foreach ($log as $hash) {
            $diff .= $this->git->show(sprintf($format, $hash)) . "\n";
        }

        return $diff;
    }

    /**
     * @param string      $lastRevision
     * @param string|null $firstRevision
     * @param bool        $excludeDeleted
     *
     * @return array
     */
    public function getChangedFiles($lastRevision, $firstRevision = null, $excludeDeleted = true)
    {
        $files = $this->getDiffForAllFiles($lastRevision, $firstRevision, true);
        $files = explode("\n", $files);
        if ($excludeDeleted) {
            $files = array_filter(
                $files,
                function ($el) {
                    return strpos($el, "D\t") !== 0;
                }
            );
        }

        return array_filter(
            array_map(
                function ($el) {
                    $bits = explode("\t", $el);

                    return isset($bits[1]) ? $bits[1] : $bits[0];
                },
                $files
            )
        );
    }

    public function getFileInRevision($file, $revision)
    {
        return $this->git->show(sprintf('%s:%s', $file, $revision));
    }
}
