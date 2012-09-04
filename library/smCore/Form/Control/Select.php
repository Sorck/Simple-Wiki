<?php

/**
 * smCore Select Form Control Class
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

namespace smCore\Form\Control;

use smCore\Form\Control;

class Select extends Control
{
	protected $_properties;
	protected $_options = array();

	public function __construct(array $properties = array())
	{
		$this->_properties = array_merge(array(
			'label' => '',
			'name' => '',
			'id' => '',
			'value' => '',
			'validation' => array(
				'required' => false,
			),
		), $properties);

		if (!empty($properties['options']))
		{
			$this->addOptions($properties['options']);
		}
	}

	public function addOption($value, $label)
	{
		$this->_options[$value] = $label;

		return $this;
	}

	public function addOptions(array $options)
	{
		foreach ($options as $value => $label)
		{
			$this->addOption($value, $label);
		}

		return $this;
	}

	public function getOptions()
	{
		return $this->_options;
	}

	public function getType()
	{
		return 'select';
	}
}