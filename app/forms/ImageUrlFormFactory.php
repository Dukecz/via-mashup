<?php

use Nette\Application\UI\Form;

class ImageUrlFormFactory extends BootstrapFormFactory
{
	/**
	 * @return Form
	 */
	public function create()
	{
		$form = new Form;

		$form->addText('url', 'Url to image:')
			->setAttribute('placeholder', 'http://www.sloanlongway.org/images/default-album/tank-181.jpg');
			//->setDefaultValue('http://www.sloanlongway.org/images/default-album/tank-181.jpg');
		$form->addSubmit('send', 'Send');

		self::bootstrapize($form);

		return $form;
	}

}