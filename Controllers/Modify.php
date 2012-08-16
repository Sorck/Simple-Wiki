<?php

namespace smCore\smWiki\Controllers;

use smCore\smWiki\Parser, smCore\smWiki\Storage, smCore\Settings;

class Modify extends AbstractWikiController
{
	public function __construct($module)
	{
		parent::__construct($module);
		parent::__construct('modify');
	}
	
	/**
	 * 
	 */
	public function modifyMethod()
	{
		echo $this->_page_name;
		return $this->_render();
	}
}