<?php

/**
 * PrivateTopics.php
 *
 * @package Private Topics mod
 * @version 1.0
 * @author Jessica González <missallsunday@simplemachines.org>
 * @copyright 2012 Jessica González
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
	private $_return;
	private $_topic;
	private $_users;
	private $_request;
	private $_board;

	public function __construct($topic = false,  $users = false)
	{
		if (!empty($topic))
			$this->_topic = $topic;;

		if ($users)
			$this->_users = $users;

		$this->_return = array();
		$this->_request = null;
	}

	private function unsetRequest()
	{
		$this->_request = null;
	}

	public function doSave($topic)
	{
		global $smcFunc;

		$this->_topic = $topic;

		$smcFunc['db_insert']('replace',
			'{db_prefix}private_topics',
			array(
				'topic_id' => 'int',
				'users' => 'string'
			),
			array(
				$this->_topic,
				$this->_users
			),
			array('topic_id')
		);
	}

	public function doUpdate($params)
	{
		global $smcFunc;

		$smcFunc['db_query']('', '
			UPDATE {db_prefix}private_topics
			SET users = {string:users}
			WHERE topic_id = {int:topic_id}',
			array(
				'topic_id' => $this->_topic,
				'users' => $this->_users
			)
		);
	}

	public function doGet()
	{
		global $smcFunc;

		$this->unsetRequest();

		$this->_request = $smcFunc['db_query']('', '
			SELECT users, topic_id
			FROM {db_prefix}private_topics
			WHERE topic_id = {int:topic}
			LIMIT 1',
			array(
				'topic' => $this->_topic,
			)
		);

		$temp = $smcFunc['db_fetch_assoc']($this->_request);

		if (!empty($temp))
			$this->_return = explode(',', $temp['users']);

		else
			$this->_return = 'no';

		$smcFunc['db_free_result']($this->_request);

		return $this->_return;
	}

	public function doTools()
	{
		global $sourcedir;

		if(file_exists($sourcedir. '/PrivateTopicsTools.php'))
			require_once($sourcedir. '/PrivateTopicsTools.php');

		return PrivateTopicsTools::getInstance();
	}

	public function doBoard($board)
	{
		if (empty($board))
			return false;

		$this->_board = false;
		$temp = $this->doTools();
		$check = $temp->getSetting('boards');

		if (!empty($check))
			$array = explode(',', $check);

		else
			$array = array();

		if (in_array($board, $array))
			$this->_board = true;

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
			'basic' => 'PrivateTopics::settings'
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

	public static function settings($return_config = false)
	{
		global $txt, $scripturl, $context, $sourcedir;

		/* I can has Adminz? */
		isAllowedTo('admin_forum');

		$tools = self::doTools();

		require_once($sourcedir . '/ManageServer.php');

		$config_vars = array(
			array('check', 'PrivateTopics_enable', 'subtext' => $tools->getText('enable_sub')),
			array('text', 'PrivateTopics_boards', 'size' => 10, 'subtext' => $tools->getText('boards_sub')),

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
				$PrivateTopics_boards = explode(',', preg_replace('/[^0-9,]/', '', $_POST['PrivateTopics_boards']));

				foreach ($PrivateTopics_boards as $key => $value)
					if ($value == '')
						unset($PrivateTopics_boards[$key]);

				$_POST['PrivateTopics_boards'] = implode(',', $PrivateTopics_boards);
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