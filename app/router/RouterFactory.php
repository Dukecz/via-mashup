<?php

namespace App;

use Nette;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;


class RouterFactory
{

	/**
	 * @return Nette\Application\IRouter
	 */
	public static function createRouter()
	{
		$router = new RouteList;
		$router[] = new Route('api/doc', 'Api:doc', Route::SECURED);
		$router[] = new Route('api[/<class>]', 'Api:default', Route::SECURED);
		$router[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:default', Route::SECURED);
		return $router;
	}

}
