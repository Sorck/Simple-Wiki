<?php

namespace smCore\smWiki\Controllers;

use smCore\smWiki\Storage, smCore\Settings, smCore\Application, smCore\Exception;

/**
 * @todo this class might need to be a little different as we might be accepting two page names
 */
class History extends AbstractWikiController
{
	public function __construct($module)
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
				throw new Exception('smwiki.history.invalid_request');
			}
		}
		else
		{
			// this loads current page data
			parent::__construct('history');
			// now get a History storage
			$this->_history = new Storage\History($this->page_name);
			return $this->_render('wiki_history');
		}
	}
}