<?php

namespace smCore\smWiki\Controllers;

use smCore\smWiki\Parser, smCore\smWiki\Storage, smCore\Settings;

class Protect extends AbstractWikiController
{
	public function __construct($module)
	{
		parent::__construct($module);
		parent::__construct('protect');
	}
	
	/**
	 * 
	 */
	public function protectMethod()
	{
		echo $this->_page_name;
		return $this->_render();
	}
}