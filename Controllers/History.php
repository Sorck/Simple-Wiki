<?php

namespace smCore\smWiki\Controllers;

use smCore\smWiki\Storage, smCore\Settings, smCore\Application, smCore\Exception;

/**
 * @todo this class might need to be a little different as we might be accepting two page names
 */
class History extends AbstractWikiController
{
	/**
	 *
	 * @var type 
	 */
	protected $_history = null;
	
	/**
	 * @param \smCore\Module $module
	 */
	public function __construct(\smCore\Module $module)
	{
		parent::__construct($module);
	}
	
	/**
	 * 
	 */
	public function historyMethod()
	{
		$get = Application::get('input')->get;
		// are we searching for a revision?
		// @todo maybe this should be moved to /wiki/revision/id1/?diff=id2
		// or /wiki/diff/id1/id2
		// or /wiki/diff?a=id1;b=id2
		if(isset($get['revision'])) {
			if(isset($get['revision2']))
			{
				parent::__construct('history');
				throw new Exception('smwiki.history.not_implemented');
			}
			else
			{
				// redirect
				Application::get('response')->redirect('wiki/revision/' . (int) $get['revision']);
				throw new Exception('smwiki.history.malformed_request');
			}
		}
		else
		{
			// @todo Check the GET cage for limit and offset and order values
			$get = Application::get('input')->get;
			$offset = isset($get['page']) ? (int) $get['page']*10 : 0;
			$limit = isset($get['limit']) && (int) $get['limit'] > 0 ? (int) $get['limit'] : 10;
			$order = isset($get['order']) && in_array($get['order'], array('asc', 'desc')) ? strtolower($get['order']) : 'desc';
			// this loads some basic page info
			parent::__construct('history', false);
			// now get a History storage
			$this->_history = new Storage\History($this->_page_name, $offset, $limit, $order);
			return $this->_render('wiki_history', array('history' => $this->_history, 'crumbs' => array(
				new Storage\Menu('Wiki', '', 'wiki/', false),
				new Storage\Menu($this->_history['name'], '', 'wiki/'.$this->_history['urlname'], false),
				new Storage\Menu('History', '', 'wiki/history/'.$this->_history['urlname'], true),
			)));
		}
	}
}