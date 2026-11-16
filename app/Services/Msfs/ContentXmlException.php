<?php

namespace App\Services\Msfs;

use RuntimeException;

class ContentXmlException extends RuntimeException
{
    public function __construct(string $message, public readonly string $reason)
    {
        parent::__construct($message);
    }
}
