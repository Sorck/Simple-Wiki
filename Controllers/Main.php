<?php

namespace smCore\smWiki\Controllers;

use smCore\Application, smCore\Module\Controller, smCore\Parsers;

class Main extends Controller
{
	public function wikiMainMethod()
	{
		Application::get('response')->redirect('wiki/Main_Page');
		$module = $this->_getParentModule();

		return $module->render('hello_world');
	}
	public function wikiPageMethod()
	{
		#Parsers\Factory::factory('BBC')->parse('[b][/b]');
		return $this->_getParentModule()->render('wiki_page', array('wiki_name_page' => 'Main Page', 'wiki_content_page' => '[h1]title[/h1]'."\n[b]bold[/b]"));
	}
}