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
     * GitConflictMarkersCheck constructor.
     *
     * @param GitHelper $git
     */
    public function __construct(GitHelper $git)
    {
        $this->git = $git;
    }

    /**
     * @param Reference $ref
     * @param string    $oldRevision
     * @param string    $newRevision
     *
     * @return ActionResult
     */
    public function process(Reference $ref, $oldRevision, $newRevision)
    {
        $files = $this->git->getChangedFiles($newRevision, $oldRevision);
        $exec = sprintf('/opt/app/git-automation/vendor/bin/phpcs %s 2>&1', implode(' ', $files));
        exec($exec, $output, $code);
        if ($code === 0) {
            return new ActionResult('CodeSniffer check: OK');
        }

        return new ActionResult(sprintf("CodeSniffer check:\n%s", implode("\n", $output)), true);
    }

}