<?php

namespace smCore\smWiki\Controllers;

use smCore\smWiki\Parser, smCore\smWiki\Storage, smCore\Settings;

/**
 * @todo this class might need to be a little different as we might be accepting two page names
 */
class History extends AbstractWikiController
{
	public function __construct($module)
	{
		parent::__construct($module);
		parent::__construct('history');
	}
	
	/**
	 * 
	 */
	public function historyMethod()
	{
		echo $this->_page_name;
		return $this->_render();
	}
}