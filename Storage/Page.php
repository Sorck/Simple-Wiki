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

use smCore\Application, smCore\Exception;

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
	 * @var array Lazy load variables are stored here as they're not always needed.
	 */
	protected $_lazy = array();

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
			// set to our defaults...
			$this->_setFromRow(array());
		}
		// are we accessing by revision?
		elseif(is_integer($identifier))
		{		
			// now query the database for our existence
			$res = $db->query('SELECT *
				FROM {db_prefix}wiki_content
				WHERE id_revision = {int:id}
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
			// we're still here so we must be fine to set our data
			$this->_setFromRow($row);
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
			{
				throw new Exception('smwiki.storage.identifier_noexist');
			}
			// set out internal data structure
			$this->_setFromRow($row);
		}
		// you're kidding me? surely we haven't been provided some really dodgy $identifier...
		else
		{
			throw new Excpetion('smwiki.storage.identifier_invalid');
		}
	}
	
	/**
	 * Make a database row into a page object.
	 * 
	 * This function takes a wiki row and sets the Page object structure.
	 * Also loads some additional data about the page.
	 * 
	 * @param array $row A row from the wiki_content table.
	 */
	protected function _setFromRow($row)
	{
		// make sure we have an array
		if(!is_array($row))
		{
			// this is quite a major internal issue...
			throw new Exception('smwiki.storage.internal_error');
		}
		// get our current error reporting level
		$e = error_reporting();
		// surpress undefined index errors...
		error_reporting(~E_NOTICE);
		// merge the row data into our protected arra
		$this->_data += array(
			'name' => $row['realname'] ?: '',
			'urlname' => $row['urlname'] ?: '',
			'parsed_content' => $row['parsed_content'] ?: '',
			'unparsed_content' => $row['unparsed_content'] ?: '',
			'revision' => $row['id_revision'] ?: 0,
		);
		// revert error reporting level
		error_reporting($e);
	}
	
	/**
	 * Saves the current page data as a new revision
	 */
	public function save()
	{
		// @todo
	}
	
	/**
	 * Sets wiki page data
	 * 
	 * @param type $data An array of keys that you want to set to their corresponding value.
	 * @param bool $_do_clean Do we want to clean the data? This is for internal storage class usage.
	 */
	public function set($data, $_do_clean = true)
	{
		// cycle through all of the $data key/value pairs
		foreach($data as $k => $v)
		{
			// don't bother if it wouldn't change anything
			if(isset($data[$k]) && $v === $this->_data[$k])
			{
				continue;
			}
			// if this is being accessed from an external source then we'll be data cleaning
			if($_do_clean)
			{
				// some things need special cleaning requirements
				switch($k)
				{
					// if we're changing the name then we also need to change the urlname
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
			// yep, we've modified this variable
			$this->_modified[$k] = true;
			// and remember to store the value
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
		// if it exists then return it
		if(isset($this->_data[$name]))
		{
			return $this->_data[$name];
		}
		// is it supposed to be lazy-loaded?
		elseif(isset($this->_lazy[$name]))
		{
			// this should call a setter for that variable (and perhaps others)
			call_user_method($this->_lazy[$name], $this, $name);
			// now see if it exists again...
			if(isset($this->_data[$name]))
			{
				return $this->_data[$name];
			}
			else
			{
				// this shouldn't be happening so log a debug message
				// @todo log these issues
				// and just return a blank value
				return null;
			}
		}
		// meh... doesn't exist then :(
		else
		{
			return null;
		}
	}
	
	/**
	 * Encodes a page name for a Wiki URL.
	 * 
	 * This is designed to encode a page name for use in a URL.
	 * It is only to be used when saving a page.
	 * 
	 * @param string $name The non-encoded page name.
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