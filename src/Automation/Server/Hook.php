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

    /**
     * @param string         $oldRevision
     * @param string         $newRevision
     * @param Reference|null $ref
     *
     * @return HookResult
     */
    public function process($oldRevision, $newRevision, Reference $ref = null)
    {
        $result = new HookResult();
        foreach ($this->actions as $action) {
            $result->addResult($action->process($oldRevision, $newRevision, $ref));
        }

        return $result;
    }


}
