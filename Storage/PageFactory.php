<?php

/**
 * smWiki PageFactory Storage Class
 *
 * This returns a Page Namespace object.
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

namespace smCore\smWiki\Storage;

use smCore\Exception, smCore\Storage\Factory, smCore\smWiki\Models\Namespaces;

class PageFactory extends Factory
{
	protected static $_loaded = array();

	/**
	 * @param string $page_name
	 * @param string $namespace
	 * @throws Exception
	 */
	public function load($page_name, $namespace)
	{
		// Is the namespace valid?
		if(preg_match('/^[A-Za-z]+\z/', $namespace) !== 1)
		{
			// throw a namespace_malformed error
			throw new Exception('smwiki.storage.namespace_malformed');
		}
		$ns = '\\smCore\\smWiki\\Models\\Namespaces\\' . $namespace;
		// Does the namespace exist?
		if(!class_exists($ns))
		{
			// throw a namespace_noexist error
			throw new Exception(array('smwiki.storage.namespace_noexist', $ns));
		}
		
		// Is there a unique handler for this page/namsepace combination?
		if(preg_match('/^[A-Za-z][A-Za-z0-9_]+\z/', $page_name) === 1)
		{
			// first test for a full class
			if(class_exists($ns . '\\' . $page_name))
			{
				$ns .= '\\' . $page_name;
				$page = new $ns($this->_app, $this);
				$page->load($page_name);
				
				return $page;
			}
			elseif(method_exists($ns, $page_name))
			{
				// make sure we can definitely call it
				$reflection = new \ReflectionMethod($ns, $page_name);
				if(!$reflection->isStatic() && $reflection->isPublic() && !$reflection->isConstructor() && !$reflection->isDestructor())
				{
					// @todo implement a way to make sure we're actually calling a function
					throw new Exception('smwiki.storage.not_implemented');
				}
				else
				{
					// we can't let them access a non-accessible method
					throw new Exception('smwiki.storage.access_violation');
				}
			}
		}
		// @todo use namespace-specific factories?
		// Create our namesapced page
		$page = new $ns($this->_app, $this);
		
		$page->load($page_name);
		
		return $page;
	}
	
	/**
	 * @todo
	 * @param type $identifier
	 */
	public function LoadPageData($identifier)
	{
		// create a Page object
		#return new Page($identifier);
		// grab our database connection
		$db = $this->_app['db'];
		$cache = $this->_app['cache'];
		
		// a null identifier means we're creating a page
		if($identifier === null)
		{
			// set to our defaults...
			return array();
		}
		// an array means we're trying to initialise a page with that data
		elseif(is_array($identifier))
		{
			// @todo might need to check how good the array is...
			return new Page($identifier);
		}
		// are we accessing by revision?
		elseif(is_integer($identifier))
		{
			// try the cache
			if(false !== $_from_cache = $cache->load('wiki_page_revision_' . $identifier))
			{
				// merge the cache data into our page data
				return new Page($_from_cache);
			}
			// ah well, back to the database
			else
			{
				// now query the database for our existence
				$res = $db->query('SELECT *
					FROM {db_prefix}wiki_content AS c,
						{db_prefix}wiki_urls AS u
					WHERE id_revision = {int:id}
						AND u.realname = c.name
					LIMIT 0,1', array(
						'id' => $identifier,
					));
				// fetch the row
				$row = $res->fetch();
				// if this revision doesn't exist then throw an error
				if(!$row)
				{
					throw new Exception('smwiki.storage.identifier_noexist');
				}
				// put it in the cache...
				$cache->save('wiki_page_revision_' . $identifier, $row);
				// we're still here so we must be fine to set our data
				return new Page($row);
			}
		}
		// must be a generic page then
		elseif(is_string($identifier))
		{
			// first try the cache
			if(false !== $_from_cache = $cache->load('wiki_page_name_' . $identifier))
			{
				// merge this into the cache then
				return new Page($_from_cache);
			}
			// grr... off to the database then
			else
			{
				// query against our urlname database and page database
				$res = $db->query('SELECT *
					FROM {db_prefix}wiki_content AS c,
						{db_prefix}wiki_urls AS href
					WHERE href.urlname = {text:id}
						AND href.latest_revision = c.id_revision', array(
							'id' => $identifier,
						));
				$row = $res->fetch();
				// if nothing is returned then we need to moan about it...
				if(!$row)
				{
					throw new Exception('smwiki.storage.identifier_noexist');
				}
				// shove it into the cache
				$cache->save('wiki_page_name_' . $identifier, $row);
				// set out internal data structure
				return new Page($row);
			}
		}
		// you're kidding me? surely we haven't been provided some really dodgy $identifier...
		else
		{
			throw new Excpetion('smwiki.storage.identifier_invalid');
		}
	}
	
	/**
	 * @param \smCore\smWiki\Models\WikiNamespace $a Saves the provided page.
	 */
	public function SavePage(\smCore\smWiki\Models\WikiNamespace $a)
	{
		
	}
}