<?php

declare(strict_types=1);

use Everest\App\Options;

/**
 * @author  Philipp Steingrebe <philipp@steingrebe.de>
 */
class OptionsTest extends \PHPUnit\Framework\TestCase
{
    public function testCasting()
    {
        $this->assertInstanceOf(Options::class, Options::from([
            'a' => 1,
            'b' => 'foo',
        ]));
        $this->assertInstanceOf(Options::class, Options::from(__DIR__ . '/fixtures/SomeOptions.php'));
        $this->assertInstanceOf(Options::class, Options::from(__DIR__ . '/fixtures/SomeOptions.json'));
        $this->assertInstanceOf(Options::class, Options::from(__DIR__ . '/fixtures/SomeOptions.ini'));

        $this->assertInstanceOf(Options::class, Options::fromIniFile(__DIR__ . '/fixtures/SomeOptions.ini'));
        $this->assertInstanceOf(Options::class, Options::fromJsonFile(__DIR__ . '/fixtures/SomeOptions.json'));
    }

    public function testCastingWithUnknownFileExtension()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->assertInstanceOf(Options::class, Options::from(__DIR__ . '/fixtures/SomeOptions.unknown'));
    }

    public function testCastingWithMissingFileExtension()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->assertInstanceOf(Options::class, Options::from(__DIR__ . '/fixtures/SomeOptions'));
    }

    public function testAccess()
    {
        $obj = new stdClass();
        $obj->d = 20;

        $options = Options::from([
            'a' => [
                'b' => 1,
            ],
            'c' => $obj,
        ]);

        $this->assertSame([
            'b' => 1,
        ], $options('a'));
        $this->assertSame(1, $options('a.b'));
        $this->assertSame(20, $options('c.d'));
    }

    public function testAccessWithDefault()
    {
        $options = Options::from([]);
        $this->assertSame('foo', $options('some.path', 'foo'));
    }

    public function testMerge()
    {
        $optionsA = Options::from([
            'a' => [
                'b' => 1,
                'c' => 2,
            ],
            'd' => 10,
            'e' => [1, 2, 3],
        ]);

        $optionsB = Options::from([
            'a' => [
                'b' => 3,
            ],
            'd' => 20,
            'e' => [3, 4, 5],
        ]);

        $options = $optionsA->merge($optionsB);

        $this->assertSame(3, $options('a.b'));
        $this->assertSame(2, $options('a.c'));
        $this->assertSame(20, $options('d'));
        $this->assertSame([1, 2, 3, 4, 5], $options('e'));
    }

    public function testMergeWithNamespace()
    {
        $optionsA = Options::from([
            'ns_a' => [
                'a' => 20,
                'b' => 30,
            ],
        ]);

        $optionsB = Options::from([
            'a' => 40,
        ]);

        $options = $optionsA->merge($optionsB, 'ns_b')->merge($optionsB, 'ns_a');

        $this->assertEquals(40, $options('ns_a.a'));
        $this->assertEquals(30, $options('ns_a.b'));
        $this->assertEquals(40, $options('ns_b.a'));
    }
}
