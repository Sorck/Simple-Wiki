<?php

namespace smCore\smWiki\Controllers;

use smCore\smWiki\Parser, smCore\smWiki\Storage, smCore\Settings;

class Move extends AbstractWikiController
{
	public function __construct($module)
	{
		parent::__construct($module);
		parent::__construct('move');
	}
	
	/**
	 * 
	 */
	public function moveMethod()
	{
		echo $this->_page_name;
		return $this->_render();
	}
}