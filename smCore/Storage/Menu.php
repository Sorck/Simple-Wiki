<?php

/**
 * smWiki Menu Storage Class
 *
 * This deals with a Menu object - a Wiki data construct. It is designed to make
 * organising the wiki page menu easier. It lets us easily configure the menu,
 * add new menu items, etc.
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

use smCore\Application, smCore\Settings;

class Menu implements \ArrayAccess, \Countable, \Iterator
{
	/**
	 * @var array Contains numerous Menu elements.
	 */
	protected $_children = array();
	/**
	 * @var array Contains data about this menu.
	 */
	protected $_data = array();
	/**
	 * @var array General Menu data registry.
	 */
	protected static $_registry = array();
	
	/**
	 * Sets a Menu registry key.
	 * @param type $key The key to set.
	 * @param type $value The value to set.
	 */
	public static function set($key, $value)
	{
		self::$_registry[$key] = $value;
	}
	
	/**
	 * Retrieve a registry value.
	 * @param type $key The key to get from the Menu registry.
	 * @return mixed The return value.
	 */
	public static function get($key)
	{
		return self::$_registry[$key];
	}
	
	/**
	 * Constructs a Menu object
	 * 
	 * @param string $name Language string for the button name.
	 * @param string $icon
	 * @param string $href The URI to link to 
	 * @param bool $active Whether this is the currently active link or not.
	 */
	public function __construct($name, $icon = false, $href = null, $active = false)
	{
		$this->_data = array(
			'name' => $name,
			'icon' => $icon,
			'active' => $active,
			'href' => str_replace('{page_name}', Menu::get('urlname'), substr($href,0,57) === 'http://' || substr($href,0,7)==='mailto:' ? $href : Settings::URL . $href),
		);
	}

	public function count() {
		return count($this->_children);
	}

	public function current() {
		return current($this->_children);
	}

	public function key() {
		return key($this->_children);
	}

	public function next() {
		return next($this->_children);
	}

	public function offsetExists($offset) {
		if(is_int($offset))
		{
			return isset($this->_children[$offset]);
		}
		else
		{
			return isset($this->_data[$offset]);
		}
	}

	public function offsetGet($offset) {
		if(is_int($offset))
		{
			return $this->_children[$offset];
		}
		else
		{
			return $this->_data[$offset];
		}
	}

	public function offsetSet($offset, $value) {
		if(is_int($offset) || !$offset)
		{
			$offset = $offset?: count($this->_children);
			if($value instanceof Menu)
			{
				$this->_children[$offset] = $value;
				$this->_data['data_' . $value] = $this->_children[$offset];
			}
			else
			{
				$this->_childred[$offset] = new Menu($value);
				// this makes it easy to access these methods again...
				$this->_data['data_' . $value] = $this->_children[$offset];
			}
		}
		else
		{
			$this->_data[$offset] = $value;
		}
	}

	public function offsetUnset($offset) {
		if(is_int($offset))
		{
			unset($this->_children[$offset]);
		}
		else
		{
			unset($this->_data[$offset]);
		}
	}

	public function rewind() {
		reset($this->_children);
	}

	public function valid() {
		return current($this->_children) !== false;
	}
	
	public function __toString() {
		return $this->_data['name'];
	}
}