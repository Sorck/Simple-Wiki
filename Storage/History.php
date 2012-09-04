<?php

/**
 * smWiki History Storage Class
 *
 * This deals with a History object - a Wiki data construct. It is designed so that
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
 * Portions created by the Initial Developer are Copyright (C) 2012
 * the Initial Developer. All Rights Reserved.
 */

namespace smCore\smWiki\Storage;

use smCore\smWiki\Storage, smCore\Exception, smCore\Application, smCore\Module;

class History extends \smCore\Module\Storage implements \ArrayAccess, \Iterator, \Countable
{
	/**
	 * @var array An array of page objects
	 */
	protected $_pages = array();
	/**
	 * @var array A secondary array of data.
	 */
	protected $_data = array();
	/**
	 * @var smCore\smWiki\Storage\Page
	 */
	protected $_orig_page;
	
	/**
	 * Create a History storage object.
	 * 
	 * @param string $page_name The urlname of a wiki page
	 * @param int $offset The offset for getting the history data
	 * @param int $limit The maximum number of pages to return.
	 * @param string $order Order revisions in asc or desc order
	 * @throws smCore\Exception
	 */
	public function __construct($page_name, $offset = 0, $limit = 10, $order = 'desc')
	{
		// sort out dependancy injection...
		if($page_name instanceof Application && $offset instanceof Module)
		{
			parent::__construct($page_name, $offset);
		}
		// verify our variables
		// Are we ordering by ascending or descending?
		if(!in_array(strtolower($order), array('asc', 'desc')))
		{
			throw new Exception(array('smwiki.storage.history.invalid', 'order', $order));
		}
		// Are out offset and limit integers?
		if(!is_int($limit))
		{
			// this basically checks that it's a number...
			if((string) (int) $limit == (string) $limit)
			{
				$limit = (int) $limit;
			}
			// ah well, it's still not a number so lets throw a coplaint...
			else
			{
				throw new Exception(array('smwiki.storage.history.invalid', 'limit', $limit));
			}
		}
		if(!is_int($offset))
		{
			// another number check...
			if((string) (int) $offset == (string) $offset)
			{
				$offset = (int) $offset;
			}
			// what is it with us not being provided numbers?
			else
			{
				throw new Exception(array('smwiki.storage.history.invalid', 'offset', $offset));
			}
		}
		
		// now off to trying to get the right data
		try
		{
			// make sure this page exists
			$this->_orig_page = $this->_data = new Storage\Page($page_name);
		}
		// it doesn't exist?! We need to report this...
		catch(Exception $e)
		{
			throw new Exception('smwiki.storage.history.noexist');
		}
		
		// grab our database connection
		$db = Application::get('db');
		// now query for the pages we need...
		$res = $db->query('SELECT *
			FROM {db_prefix}wiki_content
			WHERE name = {text:name}
			ORDER BY id_revision ' . strtolower($order) . '
			LIMIT ' . $offset . ',' . $limit . '', array(
				'name' => $this->_orig_page['name'],
				'offset' => $offset,
				'limit' => $limit,
			));
		// now fetch the pages :)
		while($row = $res->fetch())
		{
			// add a Page storage...
			$this->_pages[] = new Storage\Page($row);
		}
		// if no pages have been defined then lets throw an exception
		if(!isset($this->_pages[0]))
		{
			throw new Exception('smwiki.storage.history.empty_range');
		}
		// @todo Might need to check for things like page moves, deletes etc
	}
	
	/**
	 * @param mixed $offset 
	 * @return bool Whether or not $offset exixts.
	 */
	public function offsetExists($offset)
	{
		if(is_int($offset))
		{
			return isset($this->_pages[$offset]);
		}
		else
		{
			return isset($this->_data[$offset]);
		}
	}

	/**
	 * @param mixed $offset The key you want to find the value of.
	 * @return mixed The value of key $offset.
	 */
	public function offsetGet($offset)
	{
		if(is_int($offset))
		{
			return $this->_pages[$offset];
		}
		else
		{
			return $this->_data[$offset];
		}
	}

	/**
	 * 
	 * @param type $offset The key to set the value of.
	 * @param type $value The value to set.
	 */
	public function offsetSet($offset, $value)
	{
		if($value instanceof Storage\Page && is_int($offset))
		{
			$this->_pages[$offset] = $value;
		}
		else
		{
			$this->_data[$offset] = $value;
		}
	}

	/**
	 * @param type $offset The key to unset.
	 */
	public function offsetUnset($offset)
	{
		if(is_int($offset))
		{
			unset($this->_pages[$offset]);
		}
		else
		{
			unset($this->_data[$offset]);
		}
	}
	
	public function rewind()
	{
		reset($this->_pages);
	}
	
	public function current()
	{
		return current($this->_pages);
	}
	
	public function key()
	{
		return key($this->_pages);
	}
	
	public function next()
	{
		return next($this->_pages);
	}
	
	public function valid()
	{
		return current($this->_pages) !== false;
	}
	
	public function count()
	{
		return count($this->_pages);
	}
}