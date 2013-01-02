<?php

namespace smCore\smWiki\Parsers;

class Diff
{
	public function __construct($a, $b)
	{
		// normalise any line breaks
		$a = $this->_normalize($a);
		$b = $this->_normalize($b);
		// break up the lines...
		$a = explode("\n", $a);
		$b = explode("\n", $b);
		// now compare them...
		$deletions = array_diff($a, $b);
		$additions = array_diff($b, $a);
		// get the next piece of data
		// @todo
	}
	protected function _normalize($var)
	{
		return str_replace(array("\r\n", "\r"), array("\n", "\n"), $var);
	}
}