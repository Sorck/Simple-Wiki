<?php

namespace smCore\smWiki\Controllers;

use smCore\Application, smCore\Module\Controller, smCore\smWiki\Parser, smCore\smWiki\Storage, smCore\Settings, smCore\smWiki\Parsers\WikiCode;

class Main extends AbstractWikiController
{
	public function wikiMainMethod()
	{
		Application::get('response')->redirect('wiki/Main_Page');
	}

	public function __construct($module)
	{
		parent::__construct($module);
		parent::__construct();
	}
	
	/**
	 * 
	 */
	public function wikiPageMethod()
	{
		return $this->_render();
	}
}