<?php

namespace smCore\smWiki\Controllers;

use smCore\Application, smCore\Module\Controller, smCore\smWiki\Storage, smCore\Settings;

abstract class AbstractWikiController extends Controller
{
	protected $_page_name = null;
	protected $_page = null;
	
	/**
	 * 
	 * @param type $route
	 */
	public function __construct($route = '', $load_page = true)
	{
		if($route instanceof \smCore\Module)
		{
			parent::__construct($route);
		}
		else
		{
			$route = 'wiki' . ($route ? '/' . $route : '');
			// get our page name
			$this->_page_name = $this->_getPathData($route);
			if($load_page)
			{
				// load our page storage
				$this->_page = new Storage\Page($this->_page_name);
			}
		}
	}
	
	/**
	 * This makes rendering of wiki templates better as it auto-supplies some $context data
	 * 
	 * @param type $template
	 * @param type $context
	 */
	protected function _render($template = "wiki_page", $context = array())
	{
		return $this->_getParentModule()->render($template, array_merge(array(
			'page' => $this->_page,
			// @todo Get this data from the database...
			'wiki_menu_data' => array(
				array(
					'title' => 'Home',
					'link' => Settings::URL . '/wiki/Main_Page',
				)
			)), $context));
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