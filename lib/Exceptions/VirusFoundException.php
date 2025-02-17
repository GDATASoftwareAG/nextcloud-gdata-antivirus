<?php

namespace OCA\GDataVaas\Exceptions;

use Exception;
use VaasSdk\VaasVerdict;

class VirusFoundException extends Exception
{
    public function __construct(VaasVerdict $verdict, string $fileName, int $fileId)
    {
        parent::__construct("Virus found in " . $fileName . "(" . $fileId . "): " . $verdict->detection, 415);
    }
}