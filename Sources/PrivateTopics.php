<?php

/**
 * PrivateTopics.php
 *
 * @package Private Topics mod
 * @version 1.2
 * @author Jessica González <suki@missallsunday.com>
 * @copyright 2017 Jessica González
 * @license http://www.mozilla.org/MPL/ MPL 2.0
 *
 */

class PrivateTopics
{
	protected $_cacheTime = 300;
	protected $_cacheName = __CLASS__ .'_';
	public $app = array();

	public function __construct()
	{
		loadLanguage('PrivateTopics');

		$this->app = array(
			'decode' => function($var){
				return empty($var) ? false : (is_array($var) ? array_map(function($value) {
					return explode(',', $value);
				}, $var) : explode(',', $value));
			},
			'encode' => function($var){
				return implode(',', $var);
			},
		);
	}

	public function doSave($topic, $data = array())
	{
		global $smcFunc;

		if (empty($topic) || empty($data))
			return false;

		// Clean the cache for this topic
		cache_put_data($this->_cacheName . $topic, null, $this->_cacheTime);

		// Get and merge the default groups.
		$data = array_merge($data, $this->getDefaultGroups());
		$data = array_unique($data);

		$smcFunc['db_query']('', '
			UPDATE {db_prefix}topics
			SET private_groups = {string:groups}, private_users = {string:users}
			WHERE id_topic = {int:id}',
			array(
				'groups' => $this->app['encode']($data),
				'users' => $this->getDefaultUsers(),
				'id' => $topic
			)
		);
	}

	public function checkMultipleTopics($topicIDs = array())
	{
		global $smcFunc, $user_info;

		if (empty($topicIDs))
			return false;

		// Work with arrays.
		$topicIDs = (array) $topicIDs;
		$topicIDs = array_unique($topicIDs);
		$return = $temp = array();

		$result = $smcFunc['db_query']('', '
			SELECT id_topic, private_users AS users, private_groups AS groups
			FROM {db_prefix}topics
			WHERE id_topic = IN({array_int:topics})',
			array(
				'topics' => $topicIDs,
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($result))
			$temp[$row['id_topic']] = $this->app['decode']($row);

		$smcFunc['db_free_result']($request);

		foreach ($topicIDs as $t)
			$return[$t] = (bool) (count(array_intersect(array_keys($temp[$t]['groups']), $user_info['groups'])) == 0);

		return $return;
	}

	public function getTopicInfo($topic = 0)
	{
		global $smcFunc;

		if (empty($topic))
			return array(
				'groups' => array(),
				'users' => array(),
			);

		// Use the cache when possible
		if (($return = cache_get_data($this->_cacheName . $topic, $this->_cacheTime)) == null)
		{
			$return = array();

			$result = $smcFunc['db_query']('', '
				SELECT private_users AS users, private_groups AS groups
				FROM {db_prefix}topics
				WHERE id_topic = {int:topic}',
				array(
					'topic' => $topic,
				)
			);

			$data = $smcFunc['db_fetch_assoc']($result);
			$data = array_filter($data);

			$smcFunc['db_free_result']($result);

			if (empty($data))
				return array(
				'groups' => array(),
				'users' => array(),
			);

			else
				$data = $this->app['decode']($data);

			// Query to get the users name
			if (!empty($data['users']))
			{
				$request = $smcFunc['db_query']('', '
					SELECT id_member, member_name
					FROM {db_prefix}members
					WHERE id_member IN({array_int:users})',
					$data
				);

				while ($row = $smcFunc['db_fetch_assoc']($request))
					$return['users'][$row['id_member']] = $row['member_name'];

				$smcFunc['db_free_result']($request);
			}

			// Get the groups names
			if (!empty($data['groups']))
			{
				$request = $smcFunc['db_query']('', '
					SELECT id_group, group_name
					FROM {db_prefix}membergroups
					WHERE id_group IN({array_int:groups})',
					$data
				);

				while ($row = $smcFunc['db_fetch_assoc']($request))
					$return['groups'][$row['id_group']] = $row['group_name'];

				$smcFunc['db_free_result']($request);
			}

			// Cache this beauty
			cache_put_data($this->_cacheName . $topic, $return, $this->_cacheTime);
		}


		// Need to declare the key even if its empty.
		if (!isset($return['groups']))
			$return['groups'] = array();

		if (!isset($return['users']))
			$return['users'] = array();

		return $return;
	}

	public function check($topic, $board = 0)
	{
		global $modSettings, $user_info;

		// No, you can't see nothing.
		if (empty($topic))
			return false;

		// First check, theres gotta be a topic ID and an admin can always see it all.
		if (!empty($user_info['is_admin']))
			return true;

		// Second check, boards. If the board wasn't found it means this mod doesn't apply to that board.
		if (!empty($board) && !$this->checkBoards($board))
			return true;

		$data = $this->getTopicInfo($topic);

		// Third check, if there are no groups it means the topic is visible for everyone.
		if (empty($data['groups']))
			return true;

		// Fourth check, membergroups.
		if (count(array_intersect(array_keys($data['groups']), $user_info['groups'])) != 0)
			return true;

		// Last chance, check the user's ID
		if (in_array($user_info['id'], array_keys($data['users'])))
			return true;

		// The user isn't allowed to see this topic.
		return false;
	}

	public function membergroups($ids = array())
	{
		global $smcFunc, $modSettings, $txt;

		$request = $smcFunc['db_query']('', '
			SELECT id_group, group_name
			FROM {db_prefix}membergroups
			WHERE id_group > {int:admin}
			'. ($ids ? 'AND id_group IN({array_int:ids})' : '') .'',
			array(
				'admin' => 1,
				'ids' => $ids,
			)
		);

		$return = array();

		while ($row = $smcFunc['db_fetch_assoc']($request))
			$return[$row['id_group']] = $row['group_name'];

		$smcFunc['db_free_result']($request);

		return $return;
	}

	public function getSelectedGroups()
	{
		global $modSettings;

		return !empty($modSettings['PrivateTopics_groups']) ? $this->membergroups(unserialize($modSettings['PrivateTopics_groups'])) : array();
	}

	public function getDefaultGroups()
	{
		global $modSettings;

		return !empty($modSettings['PrivateTopics_default_groups']) ? unserialize($modSettings['PrivateTopics_default_groups']) : array();
	}

	public function getDefaultUsers()
	{
		global $modSettings, $user_info;

		$data = !empty($modSettings['PrivateTopics_user_list']) ? $this->app['decode']($modSettings['PrivateTopics_user_list']) : array();

		$data[] = $user_info['id'];
		$data = array_unique($data);

		return $this->app['encode']($data);
	}

	public function getMessageIndexGroups($topic)
	{
		global $modSettings;

		if (empty($topic))
			return array();

		// First off, get the topic info.
		$info = $this->getTopicInfo($topic);

		// Get the list of not visible groups.
		$notVisible = !empty($modSettings['PrivateTopics_hide_groups']) ? unserialize($modSettings['PrivateTopics_hide_groups']) : array();

		// No groups? return the default groups list.
		if (empty($notVisible))
			return $info;

		// Remove the groups we don't want.
		foreach ($notVisible as $k => $g)
			if (!empty($info['groups'][$g]))
				unset($info['groups'][$g]);

		return $info;
	}

	public function checkBoards($board)
	{
		global $modSettings;

		if (empty($board))
			return false;

		if (!empty($modSettings['PrivateTopics_boards']))
			$check = json_decode($modSettings['PrivateTopics_boards'], true);

		// No boards means the mod cannot work properly so return false.
		else
			return false;

		// Return a boolean, true if the mod is enable on this board.
		return (in_array($board, $check));
	}

}

function PrivateTopics_admin(&$admin_areas)
{
	global $txt, $context;

	loadLanguage('PrivateTopics');

	if (!isset($context['PrivateTopic']))
		$context['PrivateTopic'] = new PrivateTopics();

	$admin_areas['config']['areas']['privatetopics'] = array(
		'label' => $txt['PrivateTopics_title'],
		'file' => 'PrivateTopics.php',
		'function' => 'PrivateTopics_handler',
		'icon' => 'posts.gif',
		'subsections' => array(
			'basic' => array($txt['PrivateTopics_settings'])
		),
	);
}

function PrivateTopics_handler($return_config = false)
{
	global $scripturl, $context, $sourcedir, $txt;

	//I can has Adminz?
	isAllowedTo('admin_forum');

	require_once($sourcedir . '/ManageSettings.php');

	$context['page_title'] = $txt['PrivateTopics_title'];

	$subActions = array(
		'basic' => 'PrivateTopics_settings'
	);

	loadGeneralSettingParameters($subActions, 'basic');

	// Load up all the tabs...
	$context[$context['admin_menu_name']]['tab_data'] = array(
		'title' => $txt['PrivateTopics_title'],
		'description' => $txt['PrivateTopics_panel_desc'],
		'tabs' => array(
			'basic' => array()
		),
	);

	$subActions[$_REQUEST['sa']]();
}

function PrivateTopics_settings($return_config = false)
{
	global $txt, $scripturl, $context, $sourcedir, $smcFunc, $modSettings;

	//I can has Adminz?
	isAllowedTo('admin_forum');

	require_once($sourcedir . '/ManageServer.php');
	loadTemplate('PrivateTopics');
	loadLanguage('ManageMembers');

	$selected_board = !empty($modSettings['PrivateTopics_boards']) ? json_decode($modSettings['PrivateTopics_boards'], true) : array();
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

	$groups = $context['PrivateTopic']->membergroups();

	$config_vars = array(
		array('callback', 'PrivateTopics_boards', 'subtext' => $txt['PrivateTopics_boards_sub']),
		array('permissions', 'can_set_topic_as_private', 0, $txt['permissionname_can_set_topic_as_private']),
		array('select', 'PrivateTopics_groups',
			$groups,
			'subtext' => $txt['PrivateTopics_groups_sub'],
			'multiple' => true,
		),
		array('select', 'PrivateTopics_default_groups',
			$groups,
			'subtext' => $txt['PrivateTopics_default_groups_sub'],
			'multiple' => true,
		),
		array('select', 'PrivateTopics_hide_groups',
			$groups,
			'subtext' => $txt['PrivateTopics_hide_groups_sub'],
			'multiple' => true,
		),
		array('text', 'PrivateTopics_user_list', 'size' => 50, 'subtext' => $txt['PrivateTopics_user_list_sub']),
	);

	if ($return_config)
		return $config_vars;

	$context['post_url'] = $scripturl . '?action=admin;area=privatetopics;save';
	$context['settings_title'] = $txt['PrivateTopics_title'];
	$context['page_title'] = $txt['PrivateTopics_title'];
	$context['sub_template'] = 'show_settings';

	if (isset($_GET['save']))
	{
		checkSession();

		//Clean the boards var, we only want integers and nothing else!
		if (!empty($_POST['PrivateTopics_boards']))
		{
			$save_board = array();

			foreach ($_POST['PrivateTopics_boards'] as $key => $value)
				if (isset($context['boards'][$value]))
					$save_board[] = $value;

			updateSettings(array('PrivateTopics_boards' => json_encode($save_board)));
		}

		saveDBSettings($config_vars);
		redirectexit('action=admin;area=privatetopics');
	}

	prepareDBSettingContext($config_vars);
}

function PrivateTopics_permissions(&$permissionGroups, &$permissionList)
{
	$permissionGroups['membergroup']['simple'] = array('PrivateTopics_per_simple');
	$permissionGroups['membergroup']['classic'] = array('PrivateTopics_per_classic');
	$permissionList['membergroup']['can_set_topic_as_private'] = array(false, 'PrivateTopics_per_classic', 'PrivateTopics_per_simple');
	// $permissionList['membergroup']['can_always_see_private_topics'] = array(false, 'PrivateTopics_per_classic', 'PrivateTopics_per_simple');
}
