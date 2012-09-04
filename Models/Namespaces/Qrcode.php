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

use smCore\smWiki\Models\WikiPage;

use smCore\Exception, \smCore\smWiki\Library, \smCore\Settings;

class Qrcode extends WikiPage
{
	/**
	 * 
	 */
	public function load($page_name)
	{
		try
		{
			$this['page'] = $this->_factory->LoadPageData($page_name);
			Library\QRcode::png($this->_app['settings']['url'] . '/wiki/' . $page_name);
		}
		catch(Exception $e)
		{
			Library\QRcode::png('Sorry but we cannot create a QRCode for a non-existant wiki page.');
		}
		// @todo catch QRcode specific errors
	}
}