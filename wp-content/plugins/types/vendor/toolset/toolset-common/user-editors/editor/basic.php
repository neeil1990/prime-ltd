<?php

/**
 * Editor class for the Basic editor (CodeMirror).
 *
 * Handles all the functionality needed to allow the Basic editor (CodeMirror) to work with Content Template editing.
 *
 * @since 2.5.0
 */

class Toolset_User_Editors_Editor_Basic
	extends Toolset_User_Editors_Editor_Abstract {

	protected $id = 'basic';
	protected $name = 'HTML';

	public function required_plugin_active() {
		return true;
	}

	public function run() {

	}
}