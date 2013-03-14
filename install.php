<?php

/**
 * install.php
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

	if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
		require_once(dirname(__FILE__) . '/SSI.php');

	elseif (!defined('SMF'))
		exit('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');

	global $smcFunc, $context, $db_prefix, $sourcedir, $maintenance;

	db_extend('packages');

	if (empty($context['uninstalling']))
	{
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
					'name' => 'user',
					'type' => 'int',
					'size' => 10,
					'null' => false
				),
			),
			'indexes' => array(
				array(
					'type' => 'primary',
					'columns' => array('topic_id', 'user')
				),
			),
			'if_exists' => 'ignore',
			'error' => 'fatal',
			'parameters' => array(),
		);

		/* Installing */
		$existing_tables = array();
		foreach ($tables as $table)
			$existing_tables[$table['table_name']] = $smcFunc['db_create_table']($table['table_name'], $table['columns'], $table['indexes'], $table['parameters'], $table['if_exists'], $table['error']);
		
		foreach ($existing_tables as $table => $exists)
			// Probably upgrading?
			if ($exists && $table == '{db_prefix}private_topics')
			{
				$addCol = $smcFunc['db_add_column'](
					'{db_prefix}messages', 
					array(
						'name' => 'user',
						'type' => 'int',
						'size' => 10,
						'null' => false
					),
					array(),
					'ignore'
				);
				// Fingers crossed, I think in SQLite won't work...
				$smcFunc['db_remove_index']('{db_prefix}private_topics', 'topic_id');
				$smcFunc['db_add_index'](
					'{db_prefix}private_topics',
					array(
						'type' => 'primary',
						'columns' => array('topic_id', 'user')
					)
				);

				// If the column already exists, let's do another couple of checks
				if (!$addCol)
				{
					require_once($sourcedir . '/Subs-Admin.php');

					$table_name = str_replace('{db_prefix}', $db_prefix, '{db_prefix}private_topics');
					$columns = $smcFunc['db_list_columns']($table_name, false);
					foreach ($columns as $column)
						// That is, we are upgrading, so: maintenance mode until you run the maintenance
						if ($column == 'users')
						{
							// Let's remember the current maintenance status
							updateSettingsFile(array('original_maintenance' => $maintenance));
							// Let's set miantenance 1 until the db isn't updated
							updateSettingsFile(array('maintenance' => 1));
						}
				}
			}
	}