<?php

/**
 * PrivateTopics.template.php
 *
 * @package Private Topics mod
 * @version 1.2
 * @author Jessica González <suki@missallsunday.com>
 * @copyright 2017 Jessica González
 * @license http://www.mozilla.org/MPL/ MPL 2.0
 *
 */
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
