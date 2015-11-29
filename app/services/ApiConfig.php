<?php

namespace App\Services;

class ApiConfig
{
	public $options;

	public function __construct($options)
	{
		$this->options = new \stdClass();
		foreach ($options as $key => $value)
		{
			$this->options->$key = $value;
		}
	}
}