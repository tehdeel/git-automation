<?php


namespace Automation\Server;


class ActionResult
{
    /** @var bool */
    protected $declineRevision;

    /** @var string */
    protected $message;

    /**
     * @param string $message
     * @param bool   $declineRevision
     */
    public function __construct($message, $declineRevision = false)
    {
        $this->message = $message;
        $this->declineRevision = $declineRevision;
    }

    /**
     * @return boolean
     */
    public function isDeclineRevision()
    {
        return $this->declineRevision;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
}
