<?php

declare(strict_types=1);

class SomeFactory
{
    public $injectedValue;

    public function someMethod($aValue)
    {
        return $aValue;
    }
}
