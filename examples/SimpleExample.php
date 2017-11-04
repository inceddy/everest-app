<?php

require_once '../vendor/autoload.php';
use ieu\Http\Route;

class Controller {

	public function __construct($request)
	{
		$this->request = $request;
	}

	public function showAction()
	{
		echo 'Show';
	}

	public function listAction()
	{
		echo 'List';
	}
	
}

(new ieu\App())

	// Add some controll
	->controller('ActionController', ['Request', Controller::CLASS])

	// Config the routes
	->config(['RouterProvider', function($routerProvider){
		$routerProvider
			->addRoute(new Route('show'), ['ActionController', 'showAction'])
			->addRoute(new Route('list'), ['ActionController', 'listAction']);
	}])

	// Run the app
	->run();