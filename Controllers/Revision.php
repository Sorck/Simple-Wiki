<?php

namespace smCore\smWiki\Controllers;

use smCore\smWiki\Storage, smCore\Exception;

class Revision extends AbstractWikiController
{
	public function __construct($module)
	{
		parent::__construct($module);
		parent::__construct('revision', false);
	}
	
	/**
	 * 
	 */
	public function revisionMethod()
	{
		if((string) (int) $this->_page_name !== (string) $this->_page_name)
		{
			throw new Exception('smwiki.revision.not_valid');
		}
		$this->_page = new Storage\Page((int) $this->_page_name);
		return $this->_render('wiki_revision');
	}
}