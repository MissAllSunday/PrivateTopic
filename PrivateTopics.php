<?php

/**
 * PrivateTopics.php
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

function wrapperhandler(){PrivateTopics::handler();}

class PrivateTopics
{
	private $_allowed_users;
	private $_topic;
	private $_users;
	private $_board;
	public $tools;
	public static $name = 'PrivateTopics';

	public function __construct($topic = false)
	{
		if (!empty($topic))
			$this->_topic = $topic;

		$this->_allowed_users = array();
		$this->initTools();

	}

	public function allowedToSee($user, $board = false)
	{
		if ($this->doPermissionsSee())
			return true;

		$this->doGet();
		if ($board !== false)
		{
			$this->doBoard($board);

			if (!$this->_board)
				return true;
		}
		if (is_array($this->_allowed_users))
			return isset($this->_allowed_users[$user]);
		else
			return true;
	}

	public function allowedToMake($board)
	{
		$this->doBoard($board);

		return $this->_board && $this->doPermissionsSet();
	}

	public function getListAllowedUsers()
	{
		$this->doGet();
		unset($this->_allowed_users[-1]);

		return !empty($this->_allowed_users) ? $this->_allowed_users : array();
	}

	public function wasPrivate()
	{
		$this->doGet();
		return !empty($this->_allowed_users);
	}

	public function updateTopic($topic)
	{
		$this->_topic = $topic;
	}

	public function doSave($users)
	{
		global $smcFunc;

		if (empty($users))
			return;

		$save = array(array($this->_topic, -1));
		foreach ($users as $user)
			$save[] = array(
				$this->_topic,
				$user,
			);

		$smcFunc['db_insert']('replace',
			'{db_prefix}private_topics',
			array(
				'topic_id' => 'int',
				'user' => 'int'
			),
			$save,
			array('topic_id', 'user')
		);
	}

	public function doUpdate($ptusers)
	{
		global $smcFunc;

		/* Update the cache for this entry */
		cache_put_data(self::$name .':'. $this->_topic, '', 120);

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}private_topics
			WHERE topic_id = {int:topic_id}',
			array(
				'topic_id' => $this->_topic
			)
		);

		$this->doSave($ptusers);
	}

	protected function doGet()
	{
		global $smcFunc;

		/* Use the cache when possible */
		if (($this->_allowed_users = cache_get_data(self::$name .':'. $this->_topic, 120)) == null)
		{
			$request = $smcFunc['db_query']('', '
				SELECT pt.user, pt.topic_id, mem.real_name
				FROM {db_prefix}private_topics as pt
					LEFT JOIN {db_prefix}members as mem ON (pt.user = mem.id_member)
				WHERE topic_id = {int:topic}',
				array(
					'topic' => $this->_topic,
				)
			);

			$temp = array();
			while ($row = $smcFunc['db_fetch_assoc']($request))
			{
				if (!empty($row['real_name']))
					$temp[$row['user']] = $row['real_name'];
				elseif ($row['user'] == -1)
					$temp[$row['user']] = 'private';
			}

			$smcFunc['db_free_result']($request);

			if (!empty($temp))
				$this->_allowed_users = $temp;
			else
				$this->_allowed_users = false;

			/* Cache this beauty */
			cache_put_data(self::$name .':'. $this->_topic, $this->_allowed_users, 120);
		}
	}

	protected function initTools()
	{
		global $sourcedir;

		require_once($sourcedir. '/PrivateTopicsTools.php');

		$this->tools = PrivateTopicsTools::getInstance();
	}
	public static function doTools()
	{
		global $sourcedir;

		require_once($sourcedir. '/PrivateTopicsTools.php');

		return PrivateTopicsTools::getInstance();
	}

	protected function doBoard($board)
	{
		if (empty($board))
			return false;

		$this->_board = false;
		$temp = $this->doTools();
		$check = $this->tools->getSetting('boards');

		if (!empty($check))
			$array = unserialize($check);
		else
			$array = array();

		$this->_board = in_array($board, $array);
	}

	protected function doPermissionsSet()
	{
		return allowedTo('can_set_topic_as_private');
	}

	protected function doPermissionsSee()
	{
		return allowedTo('can_always_see_private_topics');
	}

	public static function admin(&$admin_areas)
	{
		$tools = self::doTools();

		$admin_areas['config']['areas']['privatetopics'] = array(
			'label' => $tools->getText('title'),
			'file' => 'PrivateTopics.php',
			'function' => 'wrapperhandler',
			'icon' => 'posts.gif',
			'subsections' => array(
				'basic' => array($tools->getText('settings'))
			),
		);
	}

	public static function handler($return_config = false)
	{
		global $scripturl, $context, $sourcedir;

		$tools = self::doTools();

		/* I can has Adminz? */
		isAllowedTo('admin_forum');

		require_once($sourcedir . '/ManageSettings.php');

		$context['page_title'] = $tools->getText('titles');

		$subActions = array(
			'basic' => 'PrivateTopics::settings',
			'maintenance' => 'PrivateTopics::maintenance'
		);

		loadGeneralSettingParameters($subActions, 'basic');

		// Load up all the tabs...
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $tools->getText('titles'),
			'description' => $tools->getText('panel_desc'),
			'tabs' => array(
				'basic' => array()
			),
		);

		call_user_func($subActions[$_REQUEST['sa']]);
	}

	public function doMaintenance()
	{
		global $smcFunc;
		$request = $smcFunc['db_query']('', '
			SELECT users
			FROM {db_prefix}private_topics
			WHERE topic_id = {int:topic}
			LIMIT 1',
			array(
				'topic' => $this->_topic,
			)
		);
		$result = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);
		$this->doUpdate(explode(',', $result['users']));
	}

	public static function maintenance($return_config = false)
	{
		global $smcFunc, $context, $maintenance, $modSettings, $db_prefix, $sourcedir, $txt;

		isAllowedTo('admin_forum');

		db_extend('packages');
		$table_name = str_replace('{db_prefix}', $db_prefix, '{db_prefix}private_topics');
		$columns = $smcFunc['db_list_columns']($table_name, false);
		loadTemplate('Admin');
		$increment = 100;
		$total = isset($_GET['total']) ? (int) $_GET['total'] : false;

		foreach ($columns as $column)
			// The column is there...let's do something
			if ($column == 'users')
			{
				if ($total === false)
				{
					$request = $smcFunc['db_query']('', '
						SELECT COUNT(*)
						FROM {db_prefix}private_topics
						WHERE users != {string:empty}',
						array(
							'empty' => ''
						)
					);
					list($total) = $smcFunc['db_fetch_row']($request);
					$smcFunc['db_free_result']($request);
				}
				$request = $smcFunc['db_query']('', '
					SELECT users, topic_id
					FROM {db_prefix}private_topics
					WHERE users != {string:empty}
					LIMIT {int:increment}',
					array(
						'empty' => '',
						'increment' => $increment
					)
				);
				if ($smcFunc['db_num_rows']($request) > 0)
				{
					while ($row = $smcFunc['db_fetch_assoc']($request))
					{
						$pt = new PrivateTopics($row['topic_id']);
						$pt->doMaintenance();
					}
					$smcFunc['db_free_result']($request);
					// we have to break
					$_GET['done'] = isset($_GET['done']) ? (int) $_GET['done'] : $increment;
					$context['continue_get_data'] = '?action=admin;area=privatetopics;sa=maintenance;total=' . $total . ';done=' . $_GET['done'] . ';' . $context['session_var'] . '=' . $context['session_id'];
					$context['page_title'] = $txt['not_done_title'];
					$context['continue_countdown'] = '2';
					$context['sub_template'] = 'not_done';
					$context['continue_post_data'] = '';
					$context['continue_percent'] = min(100, round($_GET['done'] / $total * 100));
					return;
				}
			}

		require_once($sourcedir . '/Subs-Admin.php');
		$smcFunc['db_remove_column']('{db_prefix}private_topics', 'users');
		updateSettingsFile(array('maintenance' => empty($modSettings['original_maintenance']) ? 0 : $modSettings['original_maintenance']));
		updateSettings(array('original_maintenance' => 0));
		redirectexit('action=admin;area=privatetopics');
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

		$selected_board = unserialize($tools->getSetting('boards') ? $tools->getSetting('boards') : serialize(array()));
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
			array('check', self::$name .'_enable', 'subtext' => $tools->getText('enable_sub')),
			array('callback', self::$name .'_boards', 'subtext' => $tools->getText('boards_sub')),
			array('text', self::$name .'_boardindex_message', 'size' => 70, 'subtext' => $tools->getText('boardindex_message_sub')),

		);

		if ($return_config)
			return $config_vars;

		$context['post_url'] = $scripturl . '?action=admin;area=privatetopics;save';
		$context['settings_title'] = $tools->getText('title');
		$context['page_title'] = $tools->getText('title');
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