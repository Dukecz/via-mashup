<?php

use Nette\Application\UI\Form;

class ImageUrlFormFactory extends BootstrapFormFactory
{
	public function __construct(Nette\ComponentModel\IContainer $parent, $name)
	{
		parent::__construct($parent, $name);

		$this->create();
	}

	/**
	 * @return $this
	 */
	public function create()
	{
		$this->addText('imageUrl', 'Url to image:')
			->setAttribute('placeholder', 'http://www.sloanlongway.org/images/default-album/tank-181.jpg')
			->setRequired();
		$this->addSubmit('send', 'Send');

		self::bootstrapize($this);

		return $this;
	}

}