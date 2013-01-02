<?php

namespace smCore\smWiki\Parsers;

use smCore\Application, smCore\Settings;

class WikiCode
{
	const WIKI_ITALICS = '\'\'';
	const WIKI_ITALICS_HTML_BEFORE = '<i>';
	const WIKI_ITALICS_HTML_AFTER = '</i>';
	protected $_classes = array(
	);
	
	protected $_open_tags = array();
	protected $_current_char = 0;
	
	/**
	 * 
	 */
	public function __construct()
	{
	}
	
	public function setClass($ele, $class)
	{
		$this->_classes[$ele] = $class;
	}
	
	/**
	 * Trys to turn the supplied string into a series of WikiCode tokens.
	 * 
	 * @param type $txt
	 */
	public function tokenize($text)
	{
		$this->_tokens = array();
		$position = 0;
		$current_token;
	}

	/**
	 * Performs the parsing of the provided text
	 * 
	 * @param string $text
	 * @return type
	 */
	public function parse($text)
	{
		$this->_current_char = 0;
		$this->_open_tags = array();
		$char_interest = str_split('\\*');
		$completed = '';
		$current_list = array();
		$limit = strlen($text);
		$i = 0;
		while($this->_current_char < $limit)
		{
			
			if((!isset($text[$this->_current_char]) || $text[$this->_current_char] !== '*') && !empty($current_list))
			{
				$completed .= '<ul' . (!isset($this->_classes['ul']) ?: ' class="'.$this->_classes['ul'].'"') . '>';
				foreach($current_list as $l)
				{
					$completed .= '<li>'.$l.'</li>';
				}
				$completed .= '</ul>';
				$current_list = array();
			}if($i > $limit)
				
				break;
			else
				$i ++;
			// is our current character one which we're interested in?
			switch($text[$this->_current_char])
			{
				// if it's escaping then ignore this and the next char
				case '\\':
					if(in_array($text[$this->_current_char+1], $char_interest))
					{
						// ignore this character then and just use the next char as a normal char
						$completed .= $text[$this->_current_char+1];
						$this->_current_char += 2;
					}
					else
					{
						$completed .= $text[$this->_current_char];
						$this->_current_char ++;
					}
					break;
				case '*':
					// open a list then
					if(empty($current_list))
					{
						// get data up to the end of our line
						$pos = strpos($text, "\n", $this->_current_char);
						$d = substr($text, $this->_current_char+1, $pos-$this->_current_char-1);
						$current_list[] = $d;
						$this->_current_char = $pos+1;
						#$completed .= $d;
					}
					else
					{
						$pos = strpos($text, "\n", $this->_current_char);
						$d = substr($text, $this->_current_char+1, $pos-$this->_current_char-1);
						$current_list[] = $d;
						$this->_current_char = $pos+1;/*$current_list[] = /*$completed .= $text[$this->_current_char];
						$this->_current_char ++;*/
					}
					break;
				default:
					$completed .= $text[$this->_current_char];
					$this->_current_char ++;
					break;
				
			}
		}
		return $completed;
	}
}