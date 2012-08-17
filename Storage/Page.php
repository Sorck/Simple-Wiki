<?php

/**
 * smWiki Page Storage Class
 *
 * This deals with a Page object - a Wiki data construct. It is designed so that
 * we don't have to run database queries in our controllers. It also abstracts
 * some thing so we can change how we store data without changing the Wiki
 * controllers.
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
 * Portions created by the Initial Developer are Copyright (C) 2011
 * the Initial Developer. All Rights Reserved.
 */

namespace smCore\smWiki\Storage;

use smCore\Application;

class Page
{
	/**
	 * @var array Stores internal page data. Access using get method.
	 */
	protected $_data = array();
	/**
	 * @var array Which variables have been modified?
	 */
	protected $_modified = array();

	/**
	 * 
	 * @param mixed $identifier A string of the page's URLNAME or the ID of it's revision
	 */	
	public function __construct($identifier = null)
	{
		// grab our database connection
		$db = Application::get('db');
		
		// a null identifier means we're creating a page
		if($identifier === null)
		{
			$this->_data = array(
				'name' => null,
				'urlname' => null,
				'parsed_content' => null,
				'unparsed_content' => null,
				'revision' => null,
			);
		}
		// are we accessing by revision?
		elseif(is_integer($identifier))
		{		
			// now query the database for our existence
			$res = $db->query('SELECT *
				FROM {db_prefix}wiki_content
				WHERE id_revision = {int:id}', array(
					'id' => $identifier,
				));
		}
		// must be a generic page then
		elseif(is_string($identifier))
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
				throw new \Exception('smwiki.storage.identifier_noexist');
			// set out internal data structure
			$this->set(array(
				'name' => $row['realname'],
				'urlname' => $row['urlname'],
				'parsed_content' => $row['parsed_content'],
				'unparsed_content' => $row['unparsed_content'],
				'revision' => $row['id_revision']
			), false);
		}
		// you're kidding me? surely we haven't been provided some really dodgy $identifier...
		else
		{
			throw new \Excpetion('smwiki.storage.identifier_invalid');
		}
	}
	
	/**
	 * Saves the current page data as a new revision
	 */
	public function save()
	{
		
	}
	
	/**
	 * Sets wiki page data
	 * 
	 * @param type $data
	 * @param bool $_do_clean Do we want to clean the data? This is for internal storage class usage.
	 */
	public function set($data, $_do_clean = true)
	{
		foreach($data as $k => $v)
		{
			if($_do_clean)
			{
				// some things need special cleaning requirements
				switch($k)
				{
					case 'name':
						// make sure it's polished
						$this->_data['urlname'] = $this->_hrefMake($v);
						$this->_modified[$k] = true;
						break;
					// this one is just to make it easier to set the content...
					case 'content':
						$this->_data['unparsed_content'] = $v;
						// we really should clean this one out... HTML purify it?
						$this->_data['parsed_content'] = $v;
						// and we've modified them both...
						$this->_modifed += array(
							'parsed_content' => true,
							'unparsed_content' => true,
						);
						// continue because we don't want to change the content key
						continue;
						break;
				}
			}
			
			$this->_modified[$k] = true;
			$this->_data[$k] = $v;
		}
	}
	
	/**
	 * 
	 * @param string $name The key that you wish to access.
	 * @return mixed The contents of $name or null
	 */
	public function get($name)
	{
		return isset($this->_data[$name]) ? $this->_data[$name] : null;
	}
	
	/**
	 * Encodes a page name for a Wiki URL.
	 * 
	 * This is designed to encode a page name for use in a URL.
	 * It is only to be used when saving a page.
	 * 
	 * @param string $name
	 * @return string The encoded page name.
	 */
	protected function _hrefMake($name)
	{
		// clean out bad characters
		$name = str_replace(array(
			"\n", "\t", ' '
		), '_', $name);
		
		// now urlencode it
		$name = urlencode($name);
		
		// silly encryption of slashes needs undoing...
		return str_replace('%2F', '/', $name);
	}
}