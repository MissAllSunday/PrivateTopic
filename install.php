<?php

/**
 * install.php
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

	if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
		require_once(dirname(__FILE__) . '/SSI.php');

	elseif (!defined('SMF'))
		exit('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');

	global $smcFunc, $context;

	db_extend('packages');

	if (empty($context['uninstalling']))
	{
		/* @todo don't use a separate table for this, use the already existing topics table... */
		$tables[] = array(
			'table_name' => '{db_prefix}private_topics',
			'columns' => array(
				array(
					'name' => 'topic_id',
					'type' => 'int',
					'size' => 5,
					'null' => false
				),
				array(
					'name' => 'users',
					'type' => 'text',
					'size' => '',
					'default' => '',
				),
			),
			'indexes' => array(
				array(
					'type' => 'primary',
					'columns' => array('topic_id')
				),
			),
			'if_exists' => 'ignore',
			'error' => 'fatal',
			'parameters' => array(),
		);

		/* Installing */
		foreach ($tables as $table)
		$smcFunc['db_create_table']($table['table_name'], $table['columns'], $table['indexes'], $table['parameters'], $table['if_exists'], $table['error']);
	}