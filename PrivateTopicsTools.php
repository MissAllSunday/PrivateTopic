<?php

/**
 * PrivateTopics.english.php
 *
 * @package Private Topics mod
 * @version 1.0
 * @author Jessica González <missallsunday@simplemachines.org>
 * @copyright 2012, 2013 Jessica González
 * @license http://www.mozilla.org/MPL/ MPL 2.0
 *
 * @version 1.0
 */

/*
 * Version: MPL 2.0
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
 * If a copy of the MPL was not distributed with this file,
 * You can obtain one at http://mozilla.org/MPL/2.0/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 */

class PrivateTopicsTools
{
	/**
	 * @var object The unique instance of the class
	 * @access private
	 */
	private static $_instance;

	/**
	 * @var array An array containing all the settings founded by $this->extract()
	 * @see OharaTools::extract()
	 * @access protected
	 */
	protected $_settings = array();

	/**
	 * @var array An array containing all the txt strings founded by $this->extract()
	 * @see OharaTools::extract()
	 * @access protected
	 */
	protected $_text = array();

	/**
	 * @var string The name of your mod or some unique identifier, you should replace this with your own identifier/mod name
	 * @access protected
	 */
	protected $_name = 'PrivateTopics';

	/**
	 * @var string The pattern used to search the modsettings and txt arrays, should be: /identifier_/ this is defined with the value of $_name
	 * @access protected
	 */
	protected $_pattern;

	/**
	 * Initialize the extract() method and sets the pattern property using $_name's value.
	 *
	 * @access protected
	 * @return void
	 */
	protected function __construct()
	{
		/* Set the pattern property with $_name's value */
		$this->_pattern = '/'. $this->_name .'_/';

		/* Extract the requested values from the arrays */
		$this->extract();
	}

	/**
	 * Set's a unique instance for the class.
	 *
	 * @access public
	 * @return object
	 */
	public static function getInstance()
	{
		if (!self::$_instance)
			self::$_instance = new self();

		return self::$_instance;
	}

	/**
	 * Extracts the requested values form the $modSettings and txt arrays, sets $_text and $_settings with the founded data.
	 *
	 * @global array $modSettings SMF's modSettings variable
	 * @global array $txt SMF's text strings
	 * @access public
	 * @return void
	 */
	public function extract()
	{
		global $modSettings, $txt;

		/* Load the mod's language file */
		loadLanguage($this->_name);

		$this->_settings = $modSettings;

		$this->_text = $txt;
	}

	/**
	 * Return true if the param value do exists on the $_settings array, false otherwise.
	 *
	 * @param string the name of the key
	 * @access public
	 * @return bool
	 */
	public function enable($var)
	{
		if (!empty($this->_settings[$this->_name .'_'. $var]))
			return true;

		else
			return false;
	}

	/**
	 * Get the requested array element.
	 *
	 * @param string the key name for the requested element
	 * @access public
	 * @return mixed
	 */
	public function getSetting($var)
	{
		if (empty($var))
			return false;

		elseif (!empty($this->_settings[$this->_name .'_'. $var]))
			return $this->_settings[$this->_name .'_'. $var];

		else
			return false;
	}

	/**
	 * Get the requested array element.
	 *
	 * @param string the key name for the requested element
	 * @access public
	 * @return mixed
	 */
	public function getText($var)
	{
		if (!empty($this->_text[$this->_name .'_'. $var]))
			return $this->_text[$this->_name .'_'. $var];

		else
			return false;
	}
}