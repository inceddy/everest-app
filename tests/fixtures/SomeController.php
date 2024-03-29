<?php

declare(strict_types=1);

class SomeController extends Everest\App\Controller\AbstractContainerAwareController
{
    public function getFoo()
    {
        return $this->require('foo');
    }

    public function getFooAndBar()
    {
        return $this->require('foo', 'bar');
    }
}
