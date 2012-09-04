<?php

namespace smCore\smWiki\Controllers;

use smCore\Application, smCore\Module\Controller, smCore\smWiki\Storage, smCore\Settings;

abstract class AbstractWikiController extends Controller
{
	protected $_page_name = null;
	protected $_page = null;
	protected $_route = null;
	public $menu = null;
	
	/**
	 * 
	 * @param type $route
	 *//*
	public function __construct(\smCore\Module $route, $load_page = true)
	{
		if($route instanceof \smCore\Module)
		{
			parent::__construct($route);
		}
		else
		{
			$this->_route = 'wiki' . ($route ? '/' . $route : '');
			$this->_route_name = $route;
			// get our page name
			$this->_page_name = $this->_getPathData($this->_route);
			if($load_page)
			{
				// load our page storage
				$this->_page = new Storage\Page($this->_page_name);
			}
			$this->initMenu();
		}
	}*/
		
	protected function _initPage()
	{
		// lets just hope we have been passed a valid identifier
	}
	
	public function initMenu()
	{
		if(!$this->menu)
		{
			Storage\Menu::set('urlname', $this->_page_name);
			$page_menu_data = new Storage\Menu('');
			$page_menu_data[] = new Storage\Menu('Permalink');
			$page_menu_data[] = new Storage\Menu('Page Toolbox', 'wrench');
			$page_menu_data[1][] = new Storage\Menu('Edit', 'pencil', '/wiki/edit/{page_name}');
			$page_menu_data[1][] = new Storage\Menu('Move', 'share-alt', '/wiki/move/{page_name}');
			$page_menu_data[1][] = new Storage\Menu('Protect', 'lock', '/wiki/protect/{page_name}');
			$page_menu_data[1][] = new Storage\Menu('Remove', 'trash', '/wiki/remove/{page_name}');
			$page_menu_data[1][] = new Storage\Menu('History', 'list', '/wiki/history/{page_name}');
			$page_menu_data['data_Page Toolbox']['data_Edit']['active'] = true;
			$page_menu_data[] = new Storage\Menu('Share Page', 'share');
			$page_menu_data[2][] = new Storage\Menu('QRCode', 'qrcode', '/wiki/qrcode/{page_name}');
			$page_menu_data[2][] = new Storage\Menu('E-Mail to friend', 'envelope', 'mailto:?subject=smWiki+-+' . $this->_page_name . '&body=' . urlencode(Settings::URL . '/' . $this->_route . '/' . $this->_page_name));
			$this->menu = $page_menu_data;
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
		return $this->_getParentModule()->render($template, array(
			'page' => $this->_page,
			'wiki' => array_merge(array(
				'page_subname' => $this->_route_name,
				'page_menu' => $this->menu,
				'page_title' => $this->_page['name'],
				'page_urlname' => $this->_page['urlname'],
				'crumbs' => array(),
			), $context),
			// @todo Get this data from the database...
			'wiki_menu_data' => array(
				array(
					'title' => 'Home',
					'link' => Settings::URL . '/wiki/Main_Page',
				),
			),
			'page_menu_data' => $this->menu));
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