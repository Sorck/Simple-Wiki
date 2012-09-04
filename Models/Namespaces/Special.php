<?php

/**
 * smWiki Special Namespace Class
 *
 * This deals with the Special Namespace.
 *
 * @package smWiki
 * @author smWiki Dev Team
 * @license MPL 1.1
 * @version 1.0 Alpha
 *
 * The contents of this file are subject to the Mozilla Public License Version 1.1
 * (the "License"); you may not use this package except in compliance with the
 * License. You may obtain a copy of the License at http://www.mozilla.org/MPL/
 *
 * The Original Code is smWiki.
 *
 * The Initial Developer of the Original Code is the smWiki project.
 *
 * Portions created by the Initial Developer are Copyright (C) 2012
 * the Initial Developer. All Rights Reserved.
 */

namespace smCore\smWiki\Models\Namespaces;

use smCore\smWiki\Models\WikiPage;

class Special extends WikiPage
{
	/**
	 * 
	 * @param string $page_name Name of the page to load.
	 */
	public function load($page_name)
	{
		// Load our page data
		$this['page'] = $this->_factory->LoadPageData($page_name);
	}
	
	/**
	 * 
	 * @param type $page_name
	 * @todo
	 */
	public function View_all($page_name)
	{
		
	}
	
	/**
	 * 
	 * @param type $page_name
	 */
	public function Recent_edits($page_name)
	{
		
	}
}