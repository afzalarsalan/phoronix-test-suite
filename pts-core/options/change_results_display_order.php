<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

class change_results_display_order implements pts_option_interface
{
	public static function required_function_sets()
	{
		return array("merge");
	}
	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, "pts_find_result_file", "result_file", "No result file was found.")
		);

	}
	public static function run($args)
	{
		$result = $args["result_file"];

		$result_file = new pts_result_file($result);
		$result_file_identifiers = $result_file->get_system_identifiers();

		if(count($result_file_identifiers) < 2)
		{
			echo "\nThere are not multiple test runs in this result file.\n";
			return false;
		}

		$extract_selects = array();
		echo "\nEnter The New Order To Display The New Results, From Left To Right.\n";

		do
		{
			$extract_identifier = pts_text_select_menu("Select the test run to be showed next", $result_file_identifiers);
			array_push($extract_selects, new pts_result_merge_select($result, $extract_identifier));

			$old_identifiers = $result_file_identifiers;
			$result_file_identifiers = array();

			foreach($old_identifiers as $identifier)
			{
				if($identifier != $extract_identifier)
				{
					array_push($result_file_identifiers, $identifier);
				}
			}
		}
		while(count($result_file_identifiers) > 0);

		$ordered_result = pts_merge_test_results($extract_selects);
		pts_save_result($args[0] . "/composite.xml", $ordered_result);
		pts_set_assignment_next("PREV_SAVE_RESULTS_IDENTIFIER", $args[0]);
		pts_display_web_browser(SAVE_RESULTS_DIR . $args[0] . "/composite.xml");
	}
}

?>
