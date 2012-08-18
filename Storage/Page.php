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

class Page implements \ArrayAccess, \Iterator, \Countable
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
	 * @var array Lazy load variables are stored here as they're not always needed.
	 */
	protected $_lazy = array(
		'total_revisions' => '_lazyTotalRevisions',
		'urlname' => '_lazyUrlname',
	);
	/**
	 * @var array A list of conversions between database collumn names and page collumn names.
	 */
	protected $_db_name_convert = array(
		'id_revision' => 'revision',
	);
	
	/**
	 * 
	 * @param string $name The name to convert to the database collumn name.
	 * @return string The database collumn name.
	 */
	protected function _nameToDBName($name)
	{
		// flip it so we can easily return what we need
		$flipped = array_flip($this->_db_name_convert);
		// if it exists in $flipped then the name is different, else the same
		return isset($flipped[$name]) ? $flipped[$name] : $name;
	}

	/**
	 * 
	 * @param mixed $identifier A string of the page's URLNAME or the ID of it's revision
	 */	
	public function __construct($identifier = null)
	{
		// grab our database connection
		$db = Application::get('db');
		$cache = Application::get('cache');
		
		// a null identifier means we're creating a page
		if($identifier === null)
		{
			// set to our defaults...
			$this->_setFromRow(array());
		}
		// are we accessing by revision?
		elseif(is_integer($identifier))
		{
			// try the cache
			if(false !== $_from_cache = $cache->load('wiki_page_revision_' . $identifier))
			{
				// merge the cache data into our page data
				$this->_setFromRow($_from_cache);
			}
			// ah well, back to the database
			else
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
				// put it in the cache...
				$cache->save('wiki_page_revision_' . $identifier, $row);
				// we're still here so we must be fine to set our data
				$this->_setFromRow($row);
			}
		}
		// must be a generic page then
		elseif(is_string($identifier))
		{
			// first try the cache
			if(false !== $_from_cache = $cache->load('wiki_page_name_' . $identifier))
			{
				// merge this into the cache then
				$this->_setFromRow($_from_cache);
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
				$this->_setFromRow($row);
			}
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
			'id_editor' => $row['id_editor'] ?: 0,
			'is_new' => $row['id_revision'] ? false : true,
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
		// build our insert query
		// we can only save if values have been modified
		if(empty($this->_modified))
		{
			return true;
		}
		// our known page data values
		$known = array(
			'parsed_content' => 'text',
			'unparsed_content' => 'text',
			//'urlname' => 'text',
			'name' => 'text',
			'id_editor' => 'int',
		);
		// we need to do these by lazy functions
		$toDoByLazy = array();
		// go through the modified values and see which need lazy updating and which don't
		foreach($this->_modified as $modified => $b)
		{
			if(isset($this->_lazy[$modified]))
			{
				$toDoByLazy[] = $modified;
				unset($this->_modified[$modified]);
			}
			// if it's also not known then we have issues
			if(!isset($known[$modified]) && $modified !== 'urlname')
			{
				throw new Exception(array('smwiki.storage.unknown_changed_key', $modified));
			}
		}
		// build our known database query
		$qry = 'INSERT INTO {db_prefix}wiki_content ';
		$colls = array();
		$values = array();
		$toPass = array();
		foreach($known AS $m => $n)
		{
			$colls[] = $this->_nameToDBName($m);
			$values[] = '{' . $n . ':' . $m . '}';
			$toPass[$m] = $this[$m];
		}
		// put everything together now
		$qry .= '(' . implode(',', $colls) .')' .
			'VALUES (' . implode(',', $values).')';
		// get our database connection
		$db = Application::get('db');
		// now run the query
		$db->query($qry, $toPass);
		// get our new id_revision
		$this['revision'] = $db->lastInsertId();
		// @todo update the wiki_urls table? NB should be done lazily now
		// do our lazy variable saving
		foreach($toDoByLazy as $lazy)
		{
			// this just looks confusing... but it works :P
			$this->{$this->_lazy[$lazy]}($lazy, $this->_data[$lazy]);
		}
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
			if(isset($this->_data[$k]) && $v === $this->_data[$k])
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
						// continue to next foreach value because we don't want to change the content key
						continue 2;
				}
			}
			// if it's a lazy variable then it can set itself...
			if(isset($this->_lazy[$k]))
			{
				$this->{$this->_lazy[$k]}();
			}
			else
			{
				// yep, we've modified this variable
				$this->_modified[$k] = true;
				// and remember to store the value
				$this->_data[$k] = $v;
			}
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
			$cache = Application::get('cache');
			// try a cache load
			if(false !== $_from_cache = $cache->load('wiki_page_' . $this['revision'] . '_lazy_' . $name))
			{
				$this->_data += $_from_cache;
			}
			else
			{
				// get all keys we have
				$keys = array();
				foreach($this->_data as $k => $v)
				{
					$keys[] = $k;
				}
				// this should call a setter for that variable (and perhaps others)
				$this->{$this->_lazy[$name]}();
				// now get our new keys
				$newdata = array();
				foreach($this->_data as $k => $v)
				{
					if(!in_array($k, $keys))
					{
						$newdata[$k] = $v;
					}
				}
				// cache the $newdata
				$cache->save('wiki_page_' . $this['revision'] . '_lazy_' . $name, $newdata);
				
			}
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
	 * Cleans the cache of data about this page.
	 * 
	 * @param bool $all_revisions Should only the current revision be cleaned or all revisions?
	 */
	public function flush($all_revisions = false)
	{
		// grab the cache
		$cache = Application::get('cache');
		// remove the generic cache of this page
		$cache->remove('wiki_page_name_' . $this['urlname']);
		// if we're doing them all then we have to do this tactfully...
		if($all_revisions === true)
		{
			$res = Application::get('db')->query('SELECT id_revision
				FROM {db_prefix}wiki_content
				WHERE name = {text:name}');
			while($row = $res->fetch())
			{
				$this->_flushRevision($row['id_revision']);
			}
		}
		else
		{
			$this->_flushRevision($this['revision']);
		}
	}
	
	/**
	 * Internal function to flush a particular wiki page revision from the cache.
	 * 
	 * @param mixed $id An int or array_int.
	 * @throws \smCore\Exception
	 */
	protected function _flushRevision($id)
	{
		// better be an integer...
		if(!is_int($id))
		{
			throw new Exception('smwiki.storage.flush.not_int_revision');
		}
		// get the cache
		$cache = Application::get('cache');
		// delete the plain old revision data
		$cache->remove('wiki_page_revision_' . $id);
		// now remove all lazy load cache data
		foreach($this->_lazy as $name => $method)
		{
			$cache->remove('wiki_page_' . $this['revision'] . '_lazy_' . $name);
		}
	}
	
	/**
	 * 
	 * @param type $key
	 * @param type $value
	 * @throws Exception
	 */
	protected function _lazyTotalRevisions($key = null, $value = null)
	{
		// are we saving it?
		if(!is_null($key))
		{
			// sorry but we can't change the total number of revisions
			if($key === 'total_revisions')
			{
				throw new Exception(array('smwiki.storage.lazy.cannot_set', $key));
			}
			else
			{
				// erm... why're we being told to set this? :-\
				throw new Exception(array('smwiki.storage.lazy.wrong_setter', $key));
			}
		}
		// must just be loading
		else
		{
			// count up the revisions
			$row = Application::get('db')->query('SELECT count(id_revision) AS cnt
				FROM {db_prefix}wiki_content
				WHERE name = {text:name}',
				array(
					'name' => $this->get('name'),
				))->fetch();
			// and now set the value
			$this->_data['total_revisions'] = $row['cnt'];
		}
	}
	
	/**
	 * 
	 * @param type $key
	 * @param type $value
	 */
	protected function _lazyUrlname($key = null, $value = null)
	{
		if(!is_null($key))
		{
			// we must be saving then
			Application::get('db')->query('REPLACE INTO {db_prefix}wiki_urls
				(urlname, realname, latest_revision)
				VALUES({text:urlname}, {text:realname}, {int:latest_revision})', array(
					'urlname' => $this['urlname'],
					'realname' => $this['name'],
					'latest_revision' => $this['revision'],
				));
		}
		else
		{
			// honestly, WTH are we being set here for?
			throw new Exception('smwiki.storage.lazy.not_implemented');
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

	/**
	 * @param mixed $offset 
	 * @return bool Whether or not $offset exixts.
	 */
	public function offsetExists($offset)
	{
		return isset($this->_data[$offset]);
	}

	/**
	 * @param mixed $offset The key you want to find the value of.
	 * @return mixed The value of key $offset.
	 */
	public function offsetGet($offset)
	{
		return $this->get($offset);
	}

	/**
	 * 
	 * @param type $offset The key to set the value of.
	 * @param type $value The value to set.
	 */
	public function offsetSet($offset, $value)
	{
		// just use our existing function
		$this->set(array($offset => $value));
	}

	/**
	 * @param type $offset The key to unset.
	 */
	public function offsetUnset($offset)
	{
		// if it already exists then yes, we have changed it's value
		if(isset($this->_data[$offset]))
		{
			$this->_modified[$offset] = true;
		}
		// null'ing it is better for us...
		$this->_data[$offset] = null;
	}
	
	public function rewind()
	{
		reset($this->_data);
	}
	
	public function current()
	{
		return current($this->_data);
	}
	
	public function key()
	{
		return key($this->_data);
	}
	
	public function next()
	{
		return next($this->_data);
	}
	
	public function valid()
	{
		return $this->current() !== false;
	}
	
	public function count()
	{
		return count($this->_data);
	}
}