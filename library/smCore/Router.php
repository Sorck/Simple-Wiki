<?php

/**
 * smCore Router Class
 *
 * @package smCore
 * @author smCore Dev Team
 * @license MPL 1.1
 * @version 1.0 Alpha
 *
 * The contents of this file are subject to the Mozilla Public License Version 1.1
 * (the "License"); you may not use this package except in compliance with the
 * License. You may obtain a copy of the License at http://www.mozilla.org/MPL/
 *
 * The Original Code is smCore.
 *
 * The Initial Developer of the Original Code is the smCore project.
 *
 * Portions created by the Initial Developer are Copyright (C) 2011
 * the Initial Developer. All Rights Reserved.
 */

namespace smCore;

class Router
{
	protected static $_routes = null;
	protected static $_matches = array();

	public function __construct()
	{
		if (self::$_routes === null)
			$this->_loadRoutes();
	}

	/**
	 * Try to match a path to one of our routes. A literal match is tried first, after which we attempt
	 * to find a regex that fits.
	 *
	 * @param string $path The path to find a route for.
	 * @return mixed An array of route data if one was found, false otherwise.
	 *
	 * @access public
	 */
	public function match($path)
	{
		// Normalize the path - we don't want to miss a match because of a stray slash.
		$path = trim($path, '/?');

		// These aren't real routes - cut them off early.
		if (strpos($path, 'themes') === 0 || strpos($path, 'resources') === 0)
		{
			// @todo: send a 404
			return false;
		}
		else if (strpos($path, 'cache') === 0 || strpos($path, 'library') === 0 || strpos($path, 'languages') === 0)
		{
			// @todo: send a 403
			return false;
		}

		if (empty($path))
		{
			if (!empty(self::$_routes['default']))
				return self::$_routes['default'];
		}
		else if (array_key_exists($path, self::$_routes['literal']))
		{
			return self::$_routes['literal'][$path];
		}
		else if (!empty(self::$_routes['regex']))
		{
			foreach (self::$_routes['regex'] as $route)
			{
				if (preg_match('~^' . $route['match'] . '$~i', $path, $matches))
				{
					$this->_matches = $matches;

					return $route;
				}
			}
		}

		return false;
	}

	public function getMatch($name)
	{
		if (array_key_exists($name, $this->_matches))
			return $this->_matches[$name];

		return null;
	}

	/**
	 * Load the routes from the cache, or load each module's config data and feed the routes to _addRoutes().
	 *
	 * @access protected
	 */
	protected function _loadRoutes()
	{
		if ((self::$_routes = Application::get('cache')->load('core_routes')) !== false)
			return;

		// We don't want to do a regex match if we don't have to
		self::$_routes = array(
			'default' => array(),
			'literal' => array(),
			'regex' => array(),
		);

		$modules = Application::get('modules');
		$identifiers = $modules->getIdentifiers();

		foreach ($identifiers as $id)
		{
			$config = $modules->getModuleConfig($id);

			// Doesn't have any routes… which is weird, but okay.
			if (empty($config['routes']))
				continue;

			self::_addRoutes($config['routes'], $id);
		}

		// @todo: Use app constants so we don't have to remember different tags. Application::DEPENDENCY_MODULE_REGISTRY = '...';
		Application::get('cache')->save(self::$_routes, 'core_routes', array('dependency_module_registry'));
	}

	/**
	 * Test each route's "match" value to see if it's a literal or a regex, and put them in
	 * the appropriate category. Route method names cannot match any of the method names
	 * defined in smCore\Module\Controller, or we'd get unexpected results.
	 *
	 * @param array $routes An array of config route data
	 * @param string $identifier The module identifier for these routes
	 *
	 * @access protected
	 */
	protected function _addRoutes(array $routes, $identifier)
	{
		// You're not allowed to give your methods the names of generic Controller class methods.
		static $disallowedMethodNames = null;

		if ($disallowedMethodNames === null)
			$disallowedMethodNames = get_class_methods('\smCore\Module\Controller');

		/* @todo: use these?
		array(
			// Reserved words, PHP will choke on them anyways
			'__CLASS__',
			'__DIR__',
			'__FILE__',
			'__FUNCTION__',
			'__halt_compiler',
			'__LINE__',
			'__METHOD__',
			'__NAMESPACE__',
			'abstract',
			'and',
			'array',
			'as',
			'break',
			'case',
			'catch',
			'class',
			'clone',
			'const',
			'continue',
			'declare',
			'default',
			'die',
			'do',
			'echo',
			'else',
			'elseif',
			'empty',
			'enddeclare',
			'endfor',
			'endforeach',
			'endif',
			'endswitch',
			'endwhile',
			'eval',
			'exit',
			'extends',
			'final',
			'for',
			'foreach',
			'function',
			'global',
			'goto',
			'if',
			'implements',
			'include_once',
			'include',
			'instanceof',
			'interface',
			'isset',
			'list',
			'namespace',
			'new',
			'or',
			'print',
			'private',
			'protected',
			'public',
			'require_once',
			'require',
			'return',
			'static',
			'switch',
			'throw',
			'try',
			'unset',
			'use',
			'var',
			'while',
			'xor',
			// Magic method names, don't allow these as route methods
			'__construct',
			'__destruct',
			'__call',
			'__callStatic',
			'__sleep',
			'__wakeup',
			'__get',
			'__set',
			'__isset',
			'__unset',
			'__toString',
			'__invoke',
			'__set_state',
			'__clone',
		);
		*/

		foreach ($routes as $name => $route)
		{
			$method = !empty($route['method']) ? $route['method'] : $name;

			// @todo: throw an Exception
			if (in_array($method, $disallowedMethodNames))
				continue;

			if (!is_array($route['match']))
				$route['match'] = array($route['match']);

			// @todo: clean the regexes?
			foreach ($route['match'] as $match)
			{
				$type = 'strict';
				$match = trim($match, '/');

				// If either of these characters is in the route, it has to be a regex
				if (strpos($match, '(') !== false || strpos($match, '[') !== false)
				{
					$type = 'regex';
					$match = str_replace('/', '\\/', $match);

					// Test for a valid regex... @todo: throw an Exception?
					if (preg_match('/' . $match . '/', '') === false)
						continue;

					self::$_routes['regex'][] = array(
						'match' => $match,
						'module' => $identifier,
						'controller' => $route['controller'],
						'method' => $method,
					);
				}
				else if (strpos($match, ':') !== false)
				{
					$type = 'regex';
					$match = preg_replace('/:([^\/]+)/', '(?<$1>[^/]+)', $match);

					self::$_routes['regex'][] = array(
						'match' => $match,
						'module' => $identifier,
						'controller' => $route['controller'],
						'method' => $method,
					);
				}
				else if (empty($match))
				{
					// Storing the route as the key enables us to use array_key_exists
					self::$_routes['default'] = array(
						'module' => $identifier,
						'controller' => $route['controller'],
						'method' => $method,
					);
				}
				else
				{
					// Storing the route as the key enables us to use array_key_exists
					self::$_routes['literal'][$match] = array(
						'module' => $identifier,
						'controller' => $route['controller'],
						'method' => $method,
					);
				}
			}
		}
	}
}