<?php
namespace Clickatell;
/**
 * Exception thrown when a problem occurs with the Clickatell API.
 */
class ClickatellException extends \Exception
{
    /**
     * Returns the 3-digit Clickatell error code.
     *
     * @return string
     */
    public function getClickatellErrorCode()
    {
        return sprintf('%03u', $this->getCode());
    }
}