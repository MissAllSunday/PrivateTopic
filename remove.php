<?php

/**
 * remove.php
 *
 * @package Private Topics mod
 * @version 1.2
 * @author Jessica González <suki@missallsunday.com>
 * @copyright 2017 Jessica González
 * @license http://www.mozilla.org/MPL/ MPL 2.0
 *
 */


// The reason why I use a separate remove file is to allow a better, cleaner manual install/uninstall

	if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
		require_once(dirname(__FILE__) . '/SSI.php');

	elseif (!defined('SMF'))
		exit('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');

	// Everybody likes hooks!
	$hooks = array(
		'integrate_pre_include' => '$sourcedir/PrivateTopics.php',
		'integrate_load_permissions' => 'PrivateTopics_permissions',
		'integrate_admin_areas' => 'PrivateTopics_admin',
	);

	// Uninstall
	$call = 'remove_integration_function';

	foreach ($hooks as $hook => $function)
		$call($hook, $function);