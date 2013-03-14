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

global $txt;

/* Board and MessageIndex strings */
$txt['PrivateTopics_boardindex_message_default'] = 'Topic preview unavailable';

$txt['PrivateTopics_title'] = 'Private Topics';
$txt['PrivateTopics_settings'] = 'Settings';
$txt['PrivateTopics_panel_desc'] = 'From Here you can set up the Private Topics Settings';
$txt['PrivateTopics_enable'] = 'Enable the Private Topics mod';
$txt['PrivateTopics_enable_sub'] = 'This is the master setting, needs ot be on for the mod to work properly.';
$txt['PrivateTopics_boards'] = 'Boards where you want to use topics as private';
$txt['PrivateTopics_select_boards'] = 'Select the boards';
$txt['PrivateTopics_redirect'] = 'I\'m sorry, you aren\'t allowed to see this topic.';
$txt['PrivateTopics_redirect_message'] = 'This message belongs to a private topic.';
$txt['PrivateTopics_boardindex_message'] = 'Set a custom text for the Board and MessageIndex last topic cell';
$txt['PrivateTopics_boardindex_message_sub'] = 'When the last topic of a board marked as a private board is a private topic, the mod will hide that las topic from the board and MessageIndex and instead will put a text, you can set a custom text to appear on that place, if left empty, the mod will use the default text: '. $txt['PrivateTopics_boardindex_message_default'];
/* $txt['PrivateTopics_'] = ''; */

/* Permissions strings */
$txt['permissiongroup_simple_PrivateTopics_per_simple'] = 'Private Topic mod permissions';
$txt['permissiongroup_PrivateTopics_per_classic'] = 'Private Topic mod permissions';
$txt['permissionname_can_set_topic_as_private'] = 'Can set topics as private topics';
$txt['permissionname_can_always_see_private_topics'] = 'Can always see private topics';
$txt['permissionname_PrivateTopics_title'] = 'Private Topics';

/* Post strings */
$txt['PrivateTopics_post_message'] = 'Users allowed to see the topic:';
$txt['PrivateTopics_post_enable'] = 'Mark as private topic';

/* Send pm */
$txt['PrivateTopics_pm_message'] = 'Hello, %2$s has invited you to a private topic: %1$s';
$txt['PrivateTopics_pm_subject'] = 'Private Topic invitation';