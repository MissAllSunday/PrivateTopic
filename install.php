<?php

/**
 * install.php
 *
 * @package Private Topics mod
 * @version 1.2
 * @author Jessica González <suki@missallsunday.com>
 * @copyright 2017 Jessica González
 * @license http://www.mozilla.org/MPL/ MPL 2.0
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
		$smcFunc['db_add_column'](
			'{db_prefix}topics',
			array(
				'name' => 'private_users',
					'type' => 'text',
					'size' => '',
					'default' => '',
			),
			array(),
			'update',
			null
		);

				$smcFunc['db_add_column'](
			'{db_prefix}topics',
			array(
				'name' => 'private_groups',
					'type' => 'text',
					'size' => '',
					'default' => '',
			),
			array(),
			'update',
			null
		);
	}
