<?php

namespace Automation\Server;

class HookResult
{

    /**
     * @var ActionResult[]
     */
    private $actionResults;

    private $declineRevision = false;

    /**
     * HookResult constructor.
     *
     * @param ActionResult[] $actionResults
     */
    public function __construct(array $actionResults = null)
    {
        if (is_array($actionResults)) {
            foreach ($actionResults as $result) {
                $this->addResult($result);
            }
        }
    }

    /**
     * @return boolean
     */
    public function isDeclineRevision()
    {
        return $this->declineRevision;
    }

    /**
     * @param $result
     */
    public function addResult(ActionResult $result)
    {
        $this->actionResults[] = $result;
        if ($result->isDeclineRevision()) {
            $this->declineRevision = true;
        }
    }

    public function getFormatted()
    {
        $str = '';
        foreach ($this->actionResults as $result){
            $str .= "==========================\n";
            $str .= $result->getMessage();
        }
        return $str;
    }

}