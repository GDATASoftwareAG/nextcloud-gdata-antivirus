<?php

namespace OCA\GDataVaas;

use OCP\EventDispatcher\Event;

class ScanStateEvent extends Event
{
    private bool $state;
    
    public function __construct(bool $state)
    {
        parent::__construct();
        $this->state = $state;
    }
    
    public function getState(): bool
    {
        return $this->state;
    }
}