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

function template_callback_PrivateTopics_boards()
{
	global $context, $txt;

	if (!empty($context['boards']))
	{
		echo '
						<dt>
							<strong>', $txt['PrivateTopics_boards'], ':</strong>
						</dt>
						<dd>
							<fieldset id="visible_boards" style="width: 95%;">
								<legend>', $txt['PrivateTopics_select_boards'], '</legend>';
		foreach ($context['boards'] as $board)
			echo '
								<div style="margin-left: ', $board['child_level'], 'em;"><input type="checkbox" name="PrivateTopics_boards[]" id="PrivateTopics_boards_', $board['id'], '" value="', $board['id'], '" ', $board['selected'] ? ' checked="checked"' : '', ' class="input_check" /> <label for="PrivateTopics_boards_', $board['id'], '">', $board['name'], '</label></div>';

		echo '
								<br />
								<input type="checkbox" id="checkall_check" class="input_check" onclick="invertAll(this, this.form, \'PrivateTopics_boards\');" /> <label for="checkall_check"><em>', $txt['check_all'], '</em></label>
							</fieldset>
						</dd>';
	}
}