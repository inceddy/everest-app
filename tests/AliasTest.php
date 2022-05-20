<?php


/**
 * @author  Philipp Steingrebe <philipp@steingrebe.de>
 */

use Everest\App\Alias;
use Everest\App\Session;
use Everest\App\Options;
use Everest\Http\Requests\ServerRequest;
use Everest\Http\Responses\Response;
use Everest\App\Alias\Options as OptionsAlias;
use Everest\App\Alias\Request as RequestAlias;
use Everest\App\Alias\Session as SessionAlias;

class AliasTest extends \PHPUnit\Framework\TestCase {

	public function setUp() : void
	{
		Alias::reset();
	}

	public function testOptionsAlias()
	{
		$app = new Everest\App\App;
		$app->options([
			'foo' => 'bar'
		]);

		$app->boot();
		$this->assertSame('bar', OptionsAlias::component()('foo'));
	}

	public function testMissingName()
	{
		$this->expectException(LogicException::class);

		$invalidAlias = new class extends Alias {};
		$invalidAlias::component();
	}

	public function testSwap()
	{
		$alias = new class extends Alias {
			protected static function getName() : string
			{
				return 'foo';
			}
		};

		$app = new Everest\App\App;
		$app->value('foo', 'bar');
		$app->boot();

		$this->assertSame('bar', $alias::component());

		// Swap
		$alias::swap('baz');
		$this->assertSame('baz', $alias::component());

	}

	public function testInvalidTypeSwap()
	{
		$this->expectException(InvalidArgumentException::class);

		$alias = new class extends Alias {
			protected static function getName() : string
			{
				return 'foo';
			}
		};

		$app = new Everest\App\App;
		$app->value('foo', 'bar');
		$app->boot();

		// Swap
		$alias::swap(1);
	}

	public function testInvalidClassSwap()
	{
		$this->expectException(InvalidArgumentException::class);

		$alias = new class extends Alias {
			protected static function getName() : string
			{
				return 'foo';
			}
		};

		$app = new Everest\App\App;
		$app->value('foo', new stdClass);
		$app->boot();

		// Swap
		$alias::swap(new ArrayObject);
	}

	public function testMagicMethodAccess()
	{
		$that = $this;
		$alias = new class extends Alias {
			protected static function getName() : string
			{
				return 'foo';
			}
		};

		$app = new Everest\App\App;
		$app->value('foo', new class {
			public function someMethod() {
				return 'bar';
			}
		});
		$app->boot();

		$this->assertSame('bar', $alias::someMethod());

	}

	public function testAppAliases()
	{
		$app = new Everest\App\App;
		$app->value('ErrorHandler', function(){
			return $this->createMock(Response::class);
		});
		$app->run(
			$this->createMock(ServerRequest::class)
		);

		$this->assertInstanceOf(Options::class, OptionsAlias::component());
		$this->assertInstanceOf(ServerRequest::class, RequestAlias::component());
		$this->assertInstanceOf(Session::class, SessionAlias::component());
	}
}