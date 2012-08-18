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

class History implements \ArrayAccess, \Iterator, \Countable
{
	/**
	 *
	 * @var array An array of page objects
	 */
	protected $_pages = array();
	
	/**
	 * 
	 * @param type $page_name
	 * @param type $offset
	 * @param type $limit
	 * @param type $order
	 */
	public function __construct($page_name, $offset = 0, $limit = 10, $order = 'desc')
	{
		// @todo
	}
	
	/**
	 * @param mixed $offset 
	 * @return bool Whether or not $offset exixts.
	 */
	public function offsetExists($offset)
	{
		return isset($this->_pages[$offset]);
	}

	/**
	 * @param mixed $offset The key you want to find the value of.
	 * @return mixed The value of key $offset.
	 */
	public function offsetGet($offset)
	{
		return $this->_pages[$offset];
	}

	/**
	 * 
	 * @param type $offset The key to set the value of.
	 * @param type $value The value to set.
	 */
	public function offsetSet($offset, $value)
	{
		// just use our existing function
		$this->_pages[$offset] = $value;
	}

	/**
	 * @param type $offset The key to unset.
	 */
	public function offsetUnset($offset)
	{
		unset($this->_pages[$offset]);
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