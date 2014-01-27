<?php

/**
 * PrivateTopics.php
 *
 * @package Private Topics mod
 * @version 1.1
 * @author Jessica González <suki@missallsunday.com>
 * @copyright 2012, 2013, 2014 Jessica González
 * @license http://www.mozilla.org/MPL/ MPL 2.0
 *
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

function wrapperhandler(){PrivateTopics::handler();}

class PrivateTopics
{
	protected $_return;
	protected $_topic;
	protected $_users;
	protected $_request;
	protected $_board;
	public static $name = 'PrivateTopics';

	public function __construct(){}

	public function doSave($topic, $users)
	{
		global $smcFunc;

		if (empty($topic) || empty($users))
			return false;

		$this->_users = (array) $users;

		// Serialize the users
		$this->handleUsers();

		$smcFunc['db_insert']('replace',
			'{db_prefix}topics',
			array(
				'id_topic' => 'int',
				'users' => 'string'
			),
			array(
				$topic,
				$this->_users
			),
			array('id_topic')
		);
	}

	public function doUpdate($id)
	{
		global $smcFunc;

		/* Update the cache for this entry */
		cache_put_data(self::$name .':'. $id, '', 120);

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}private_topics
			WHERE topic_id = {int:topic_id}',
			array(
				'topic_id' => $id
			)
		);

		$this->doSave($id);
	}

	public function doGet()
	{
		global $smcFunc;

		$this->unsetRequest();

		/* Use the cache when possible */
		if (($this->_return = cache_get_data(self::$name .':'. $this->_topic, 120)) == null)
		{
			$this->_request = $smcFunc['db_query']('', '
				SELECT pt.users, pt.topic_id, mem.real_name
				FROM {db_prefix}private_topics as pt
					LEFT JOIN {db_prefix}members as mem ON (pt.users = mem.id_member)
				WHERE topic_id = {int:topic}',
				array(
					'topic' => $this->_topic,
				)
			);

			$temp = array();
			while ($row = $smcFunc['db_fetch_assoc']($this->_request))
				if (!empty($row['real_name']))
					$temp[$row['users']] = $row['real_name'];

			$smcFunc['db_free_result']($this->_request);

			if (!empty($temp))
				$this->_return = $temp;
			else
				$this->_return = false;

			/* Cache this beauty */
			cache_put_data(self::$name .':'. $this->_topic, $this->_return, 120);
		}

		return $this->_return;
	}

	// Don't go around the bush, this mod needs PHP 5.2, anyway, if you need to make a change do it here.
	public function encodeUsers()
	{
		return json_encode($this->_users);
	}

	public function decodeUsers()
	{
		return json_decode($this->_users);
	}

	public static function text($var)
	{
		global $txt;

		if (empty($var))
			return false;

		// Load the mod's language file.
		loadLanguage(self::$name);

		if (!empty($txt[self::$name .'_'. $var]))
			return $txt[self::$name .'_'. $var];

		else
			return false;
	}

	public static function enable($var)
	{
		global $modSettings;

		if (empty($var))
			return false;

		if (isset($modSettings[self::$name .'_'. $var]) && !empty($modSettings[self::$name .'_'. $var]))
			return true;

		else
			return false;
	}

	public static function setting($var)
	{
		if (empty($var))
			return false;

		global $modSettings;

		if (true == self::enable($var))
			return $modSettings[self::$name .'_'. $var];

		else
			return false;
	}

	public function doBoard($board)
	{
		if (empty($board))
			return false;

		$this->_board = false;
		$check = self::setting('boards');

		if (!empty($check))
			$array = unserialize($check);
		else
			$array = array();

		if (in_array($board, $array))
			$this->_board = $array;

		return $this->_board;
	}

	public function doPermissionsSet()
	{
		return allowedTo('can_set_topic_as_private');
	}

	public function doPermissionsSee()
	{
		return allowedTo('can_always_see_private_topics');
	}

	public static function admin(&$admin_areas)
	{
		$admin_areas['config']['areas']['privatetopics'] = array(
			'label' => self::text('title'),
			'file' => 'PrivateTopics.php',
			'function' => 'wrapperhandler',
			'icon' => 'posts.gif',
			'subsections' => array(
				'basic' => array(self::text('settings'))
			),
		);
	}

	public static function handler($return_config = false)
	{
		global $scripturl, $context, $sourcedir;

		/* I can has Adminz? */
		isAllowedTo('admin_forum');

		require_once($sourcedir . '/ManageSettings.php');

		$context['page_title'] = self::text('titles');

		$subActions = array(
			'basic' => 'PrivateTopics::settings'
		);

		loadGeneralSettingParameters($subActions, 'basic');

		// Load up all the tabs...
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => self::text('titles'),
			'description' => self::text('panel_desc'),
			'tabs' => array(
				'basic' => array()
			),
		);

		call_user_func($subActions[$_REQUEST['sa']]);
	}

	public static function settings($return_config = false)
	{
		global $txt, $scripturl, $context, $sourcedir, $smcFunc, $modSettings;

		/* I can has Adminz? */
		isAllowedTo('admin_forum');

		$tools = self::doTools();

		require_once($sourcedir . '/ManageServer.php');
		loadTemplate('PrivateTopics');
		loadLanguage('ManageMembers');

		$selected_board = unserialize(self::setting('boards') ? self::setting('boards') : serialize(array()));
		$context['boards'] = array();
		$result = $smcFunc['db_query']('', '
			SELECT id_board, name, child_level
			FROM {db_prefix}boards
			ORDER BY board_order',
			array(
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($result))
			$context['boards'][$row['id_board']] = array(
				'id' => $row['id_board'],
				'name' => $row['name'],
				'child_level' => $row['child_level'],
				'selected' => in_array($row['id_board'], $selected_board)
			);
		$smcFunc['db_free_result']($result);

		$config_vars = array(
			array('check', self::$name .'_enable', 'subtext' => self::text('enable_sub')),
			array('callback', self::$name .'_boards', 'subtext' => self::text('boards_sub')),
			array('text', self::$name .'_boardindex_message', 'size' => 70, 'subtext' => self::text('boardindex_message_sub')),

		);

		if ($return_config)
			return $config_vars;

		$context['post_url'] = $scripturl . '?action=admin;area=privatetopics;save';
		$context['settings_title'] = self::text('title');
		$context['page_title'] = self::text('title');
		$context['sub_template'] = 'show_settings';

		if (isset($_GET['save']))
		{
			checkSession();

			/* Clean the boards var, we only want integers and nothing else! */
			if (!empty($_POST['PrivateTopics_boards']))
			{
				$save_board = array();

				foreach ($_POST['PrivateTopics_boards'] as $key => $value)
					if (isset($context['boards'][$value]))
						$save_board[] = $value;

				updateSettings(array('PrivateTopics_boards' => serialize($save_board)));
			}

			saveDBSettings($config_vars);
			redirectexit('action=admin;area=privatetopics');
		}

		prepareDBSettingContext($config_vars);
	}

	public static function permissions(&$permissionGroups, &$permissionList)
	{
		$permissionGroups['membergroup']['simple'] = array('PrivateTopics_per_simple');
		$permissionGroups['membergroup']['classic'] = array('PrivateTopics_per_classic');
		$permissionList['membergroup']['can_set_topic_as_private'] = array(false, 'PrivateTopics_per_classic', 'PrivateTopics_per_simple');
		$permissionList['membergroup']['can_always_see_private_topics'] = array(false, 'PrivateTopics_per_classic', 'PrivateTopics_per_simple');
	}
}