<?php

namespace Automation\Server;

use Coyl\Git\DTO\Reference;

interface HookActionInterface
{

    /**
     * @param string         $oldRevision
     * @param string         $newRevision
     * @param Reference|null $ref
     *
     * @return mixed
     */
    public function process($oldRevision, $newRevision, Reference $ref = null);

}
