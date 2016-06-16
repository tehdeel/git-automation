<?php


namespace Automation\Server\HookActions;


use Automation\Server\ActionResult;
use Automation\Server\GitHelper;
use Automation\Server\HookActionInterface;
use Coyl\Git\DTO\Reference;

class CodeSnifferCheck implements HookActionInterface
{
    /**
     * @var GitHelper
     */
    private $git;
    /**
     * @var string
     */
    private $vendor;

    /**
     * GitConflictMarkersCheck constructor.
     *
     * @param GitHelper $git
     * @param string    $rootDir
     */
    public function __construct(GitHelper $git, $rootDir)
    {
        $this->git = $git;
        $this->vendor = realpath(dirname($rootDir) . '/vendor/bin');
    }

    /**
     * @inheritdoc
     */
    public function process($oldRevision, $newRevision, Reference $ref = null)
    {
        $files = $this->git->getChangedFiles($newRevision, $oldRevision);
        $exec = sprintf('%s/phpcs %s 2>&1', $this->vendor, implode(' ', $files));
        exec($exec, $output, $code);
        if ($code === 0) {
            return new ActionResult('CodeSniffer check: OK');
        }

        return new ActionResult(sprintf("CodeSniffer check: ERROR\n%s", implode("\n", $output)), true);
    }

}