<?php

namespace App\Presenters;

use Nette;
use Luracast\Restler\Restler;


class ApiPresenter extends Nette\Application\UI\Presenter
{
	public function renderDefault() {
		$r = new Restler();
		$r->addAPIClass('App\Api\Api');
		$r->handle();
	}

	public function renderDoc() {

	}
}
