<?php

/**
 * 
 */

namespace smCore\smWiki\Models;

use smCore\Application, smCore\smWiki\Storage\PageFactory, smCore\Exception, smCore\smWiki\Library\WikiArray;

abstract class WikiPage extends WikiArray
{
	/**
	 * @var Application The smCore application object.
	 */
	protected $_app;
	/**
	 * @var PageFactory The factory we can load data from.
	 */
	protected $_factory;
	/**
	 *
	 * @var array WikiPage data.
	 */
	protected $_data = array();

	/**
	 * Initialises the namespaced page.
	 * 
	 * @param \smCore\Application $app
	 * @param \smCore\smWiki\Storage\PageFactory $factory
	 */
	public function __construct(Application $app, PageFactory $factory)
	{
		$this->_app = $app;
		$this->_factory = $factory;
		$this['crumbs'] = array(
			array(
				'name' => 'Wiki',
				'url' => array('wiki'),
				'active' => true,
			),
		);
	}
	
	/**
	 * You can replace this function to customise the wiki breadcrumb. :-)
	 */
	protected function _crumb()
	{return;
		$ns = str_replace('smCore\\smWiki\\Models\\Namespaces\\Wiki', '', get_class($this));
		if($ns !== 'View')
		{
			$this['crumbs'][] = array(
				'name' => 'Page',
				'url' => '',
			);
		}
	}
	
	abstract public function load($page_name);
	
	final protected function _GetPage($page_name)
	{
		$this->_factory->LoadPageData($page_name);
	}

	/*public function count()
	{
		return count($this->_data);
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

	public function offsetExists($offset)
	{
		return isset($this->_data[$offset]);
	}

	public function offsetGet($offset)
	{
		if(!isset($this->_data[$offset]))
		{
			throw new Exception(array('smwiki.namespaces.offset_noexist', $offset));
		}
		return $this->_data[$offset];
	}

	public function offsetSet($offset, $value)
	{
		if(is_null($offset))
		{
			$this->_data[] = $value;
		}
		else
		{
			$this->_data[$offset] = $value;
		}
	}

	public function offsetUnset($offset)
	{
		unset($this->_data[$offset]);
	}

	public function rewind()
	{
		reset($this->_data);
	}

	public function valid()
	{
		return !current($this->_data);
	}*/
}