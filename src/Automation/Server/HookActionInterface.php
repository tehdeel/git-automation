<?php

namespace Automation\Server;

use Coyl\Git\DTO\Reference;

interface HookActionInterface
{

    /**
     * @param Reference $ref
     * @param string    $oldRevision
     * @param string    $newRevision
     *
     * @return ActionResult
     */
    public function process(Reference $ref, $oldRevision, $newRevision);

}
