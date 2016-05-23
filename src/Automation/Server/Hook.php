<?php


namespace Automation\Server;


use Coyl\Git\DTO\Reference;

class Hook
{

    /** @var HookActionInterface[] */
    protected $actions = [];

    /**
     * Hook constructor.
     *
     * @param HookActionInterface[] $actions
     */
    public function __construct(array $actions)
    {
        array_filter(
            $actions,
            function ($action) {
                return ($action instanceof HookActionInterface);
            }
        );
        $this->actions = $actions;
    }

    public function process(Reference $ref, $oldRevision, $newRevision)
    {
        $result = new HookResult();
        foreach ($this->actions as $action) {
            $result->addResult($action->process($ref, $oldRevision, $newRevision));
        }
        return $result;
    }


}