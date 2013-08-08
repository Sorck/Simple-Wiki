<?php

/**
 * smWiki View Namespace Class
 *
 * This deals with the View Namespace.
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

use smCore\smWiki\Models\WikiPage, smCore\Exception;

class Revision extends WikiPage
{
	/**
	 * 
	 * @param string $page_name Name of the page to load.
	 */
	public function load($page_name)
	{
		if((string) (int) $page_name !== (string) $page_name)
		{
			throw new Exception('smwiki.revision.not_valid');
		}
		// Load our page data
		try
		{
			$this['page'] = $this->_factory->LoadPageData((int) $page_name);
		}
		catch( Exception $e )
		{
			throw new Exception('smwiki.revision.not_found');
		}
		$this['crumbs'][] = array(
			array(
				'url' => array('wiki', $this['page']['urlname']),
				'name' => $this['page']['name'],
				'active' => true,
			)
		);
	}
	
	protected function _crumb() {
		
	}
}