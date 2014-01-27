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

function PrivateTopics_doSave($topic, $users)
{
	global $smcFunc;

	/* Clean the cache for this topic */
	cache_put_data('PrivateTopics_'. $topic, '', 240);

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}topics
		SET private_users = {string:users}
		WHERE id_topic = {int:id}',
		array(
			'users' => PrivateTopics_encode($users),
		)
	);
}

function PrivateTopics_getUsers($topic = 0)
{
	global $smcFunc;

	if (empty($topic))
		return false;

	/* Use the cache when possible */
	if (($return = cache_get_data('PrivateTopics_'. $topic, 240)) == null)
	{
		$result = $smcFunc['db_query']('', '
			SELECT private_users
			FROM {db_prefix}topics
			WHERE topic_id = {int:topic}',
			array(
				'topic' => $topic,
			)
		);

		list ($users) = $smcFunc['db_fetch_row']($result);

		$users = !empty($users) ? PrivateTopics_decode($users) : array();

		$request = $smcFunc['db_query']('', '
			SELECT id_member, real_name
			FROM {db_prefix}members
			WHERE id_member IN({array_int:users})',
			array(
				'users' => $users,
			)
		);

		$return = array();
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$return[$row['id_member']] = $row['real_name'];

		$smcFunc['db_free_result']($request);

		/* Cache this beauty */
		cache_put_data('PrivateTopics_'. $topic, $return, 240);
	}

	return $return;
}

function PrivateTopics_checkBoards($board)
{
	if (empty($board))
		return false;

	if (!empty($modSettings['PrivateTopics_boards']))
		$check = array_values(PrivateTopics_decode($modSettings['PrivateTopics_boards']));

	// No boards means the mod cannot work properly so return false.
	else
		return false;

	// Return a boolean, true if the mod is enable on this board.
	return (isset($check[$board]));
}

function PrivateTopics_encode($string)
{
	// No json? use serialize.
	$call = function_exists('json_encode') ? 'json_encode' : 'serialize';

	return $call($string);
}

function PrivateTopics_decode($string)
{
	// No json? use serialize.
	if (function_exists('json_decode'))
		return json_decode($string, true);

	else
		return unserialize($string);
}

function PrivateTopics_admin(&$admin_areas)
{
	$admin_areas['config']['areas']['privatetopics'] = array(
		'label' => $txt['PrivateTopics_title'],
		'file' => 'PrivateTopics.php',
		'function' => 'wrapperhandler',
		'icon' => 'posts.gif',
		'subsections' => array(
			'basic' => array($txt['PrivateTopics_settings'])
		),
	);
}

function PrivateTopics_handler($return_config = false)
{
	global $scripturl, $context, $sourcedir;

	/* I can has Adminz? */
	isAllowedTo('admin_forum');

	require_once($sourcedir . '/ManageSettings.php');

	$context['page_title'] = $txt['PrivateTopics_titles'];

	$subActions = array(
		'basic' => 'PrivateTopics_settings'
	);

	loadGeneralSettingParameters($subActions, 'basic');

	// Load up all the tabs...
	$context[$context['admin_menu_name']]['tab_data'] = array(
		'title' => $txt['PrivateTopics_titles'],
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

	/* I can has Adminz? */
	isAllowedTo('admin_forum');

	require_once($sourcedir . '/ManageServer.php');
	loadTemplate('PrivateTopics');
	loadLanguage('ManageMembers');

	$selected_board = PrivateTopics_decode(!empty($modSettings['PrivateTopics_boards']) ? $modSettings['PrivateTopics_boards'] : '');
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
		array('check', 'PrivateTopics_enable', 'subtext' => $txt['PrivateTopics_enable_sub')),
		array('callback', 'PrivateTopics_boards', 'subtext' => $txt['PrivateTopics_boards_sub')),
		array('text', 'PrivateTopics_boardindex_message', 'size' => 70, 'subtext' => $txt['PrivateTopics_boardindex_message_sub']),

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

		/* Clean the boards var, we only want integers and nothing else! */
		if (!empty($_POST['PrivateTopics_boards']))
		{
			$save_board = array();

			foreach ($_POST['PrivateTopics_boards'] as $key => $value)
				if (isset($context['boards'][$value]))
					$save_board[] = $value;

			updateSettings(array('PrivateTopics_boards' => PrivateTopics_encode($save_board)));
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
	$permissionList['membergroup']['can_always_see_private_topics'] = array(false, 'PrivateTopics_per_classic', 'PrivateTopics_per_simple');
}
