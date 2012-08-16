<?php

namespace smCore\smWiki\Controllers;

use smCore\Application, smCore\Module\Controller, smCore\smWiki\Parser, smCore\smWiki\Storage, smCore\Settings, smCore\smWiki\Parsers\WikiCode;

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
		// gut out the path for our page name
		$urlname = $this->_getPathData();
		
		// load our storage
		$page = new Storage\Page($urlname);
		
		// test this...
		/*$w = new WikiCode;
		$w->setClass('ul', 'nav nav-list');
		$menu = $w->parse('* Home
* New Page');*/
		
		//Parsers\Factory::factory('BBC')->parse('[b][/b]');
		return $this->_getParentModule()->render('wiki_page', array(
			'wiki_name_page' => $page->get('name'),
			'wiki_content_page' => $page->get('parsed_content'),
			'wiki_name_page_href' => $page->get('urlname'),
			'wiki_page_revision' => $page->get('revision'),
			'wiki_menu_data' => array(
				array(
					'title' => 'Home',
					'link' => Settings::URL . '/wiki/Main_Page',
				)
			)));
	}
	/**
	 * @param string The base route we need to strip out (no preceeding or trailing slashes)
	 * 
	 * @return string The page name
	 */
	protected function _getPathData($route = 'wiki')
	{
		return substr(Application::get('request')->getPath(), strlen($route)+2);
	}
}