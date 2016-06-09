<?php

namespace Automation\Server\HookActions;

use Automation\Server\ActionResult;
use Automation\Server\GitHelper;
use Automation\Server\HookActionInterface;
use Coyl\Git\DTO\Reference;
use Coyl\Git\GitRepo;

class GitConflictMarkersCheck implements HookActionInterface
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

    public function process(Reference $ref, $oldRevision, $newRevision)
    {
        $diff = $this->git->getDiffForAllFiles($newRevision, $oldRevision);
        $diff = explode("\n", $diff);
        $diff = array_filter( $diff, function ($el) {
                return strpos($el, '-') !== 0;
            });
        foreach ($diff as $line){
            $mark1 = strpos($line, '<<<<' . '<<< ');
            $mark2 = strpos($line, '>>>' . '>>>> ');
            if (false !== $mark1 || false !== $mark2) {
                return new ActionResult('Conflict markers found', true);
            }
        }
        return new ActionResult('No conflinct markers');
    }
}
