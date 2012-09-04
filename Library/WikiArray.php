<?php

/**
 * WikiArray
 */

namespace smCore\smWiki\Library;

use smCore\Exception;

class WikiArray implements \ArrayAccess, \Iterator, \Countable, \SeekableIterator, \RecursiveIterator
{
	protected $_data = array();
	
	public function __construct($data)
	{
		if(!is_Array($data) && !($data instanceof WikiArray))
		{
			throw new Exception();
		}
		foreach($data as $k => $v)
		{
			if(is_array($v))
			{
				$this->_data[$k] = new WikiArray($v);
			}
			else
			{
				$this->_data[$k] = $v;
			}
		}
	}
	
	public function count()
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
		if(is_array($value))
		{
			$value = new WikiArray($value);
		}
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
	}

	public function seek($index)
	{
		$this->rewind();
		$position = 0;
		while($position < $index && $this->valid())
		{
			$this->next();
			$position++;
		}
		if (!$this->valid())
		{
			throw new OutOfBoundsException('Invalid seek position');
		}
	}
	
	public function __toString()
	{
		$d = "[";
		foreach($this->_data as $k => $v)
		{
			$d .= $k . ' => ' . $v . ",\n";
		}
		$d .= "]";
		return $d;
	}

	public function getChildren()
	{
		return $this->_data[$this->current()];
	}

	public function hasChildren()
	{
		return $this->_data[$this->current()] instanceof WikiArray;
	}
}