<?php

namespace smCore\smWiki\Controllers;

use smCore\smWiki\Parser, smCore\smWiki\Storage, smCore\Settings;

class Create extends AbstractWikiController
{
	public function __construct($module)
	{
		parent::__construct($module);
		parent::__construct('create');
	}
	
	/**
	 * 
	 */
	public function createMethod()
	{
		echo $this->_page_name;
		return $this->_render();
	}
}