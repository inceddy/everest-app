<?php

declare(strict_types=1);

class SomeService
{
    public $injectedValue;

    public function __construct($aValue)
    {
        $this->injectedValue = $aValue;
    }
}
