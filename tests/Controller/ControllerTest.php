<?php

declare(strict_types=1);

require_once __DIR__ . '/../fixtures/SomeController.php';

/**
 * @author  Philipp Steingrebe <philipp@steingrebe.de>
 */


class ControllerTest extends \PHPUnit\Framework\TestCase
{
    public function testController()
    {
        $app = new Everest\App\App();
        $app->value('foo', 'foo');
        $app->value('bar', 'bar');

        $app->controller('SomeController', [SomeController::class]);

        $controller = $app->container()['SomeControllerController'];
        $this->assertEquals('foo', $controller->getFoo());
        $this->assertEquals(['foo', 'bar'], $controller->getFooAndBar());
    }
}
