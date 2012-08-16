<?php

namespace smCore\smWiki\Controllers;

use smCore\smWiki\Parser, smCore\smWiki\Storage, smCore\Settings;

class Remove extends AbstractWikiController
{
	public function __construct($module)
	{
		parent::__construct($module);
		parent::__construct('remove');
	}
	
	/**
	 * 
	 */
	public function removeMethod()
	{
		echo $this->_page_name;
		return $this->_render();
	}
}