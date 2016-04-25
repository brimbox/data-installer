<?php if (!defined('BASE_CHECK')) exit(); ?>
<?php

/*
 * Copyright (C) Brimbox LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License Version 3 (“GNU GPL v3”)
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU GPL v3 for more details.
 *
 * You should have received a copy of the GNU GPL v3 along with this program.
 * If not, see http://www.gnu.org/licenses/
 */
?>
<?php

/*
	@module_name=bb_data_installer;
	@friendly_name = Data Installer;
	@interface = bb_brimbox;
	@module_type = 5;
	@module_version = 2.0;
	@description = This is a Module that can be used to upload a whole data_table.;
 */
?>
<?php

$main->check_permission ( "5_bb_brimbox" );
?>
<?php

/* PRESERVE STATE */
$POST = $main->retrieve ( $con );

// get state from db
$arr_state = $main->load ( $con, $module );

$arr_messages = $main->process ( 'arr_messages', $module, $arr_state, array () );

/* START REQUIRED FORM */
// title
echo "<p class=\"spaced bold larger\">Install Data</p>";

// messages
echo "<div class=\"spaced\">";
$main->echo_messages ( $arr_messages );
echo "</div>";

// multipart form
$main->echo_form_begin ( array (
		'enctype' => "multipart/form-data" 
) );
$main->echo_module_vars ();

// data install form objects
echo "<div class=\"spaced padded border\">";
echo "<p class=\"spaced bold larger\">Upload Data</p>";
$main->echo_button ( "truncate", array (
		"label" => "Trucate Existing Data Table",
		"class" => "spaced",
		"number" => 1 
) );
echo "<span class = \"spaced border rounded padded shaded\">";
echo "<label class=\"padded\">Confirm: </label>";
$main->echo_input ( "check_truncate", 1, array (
		'type' => 'checkbox',
		'input_class' => 'middle holderup' 
) );
echo "</span><br>";
$main->echo_button ( "stop_triggers", array (
		"label" => "Disable Date Triggers",
		"class" => "spaced",
		"number" => 2 
) );
echo "<br>";
$main->echo_button ( "drop_sequence", array (
		"label" => "Drop Data Table Primary Id Sequence",
		"class" => "spaced",
		"number" => 3 
) );
echo "<br>";
echo "<label class=\"spaced\">Select Data Filename: </label>";
echo "<input class=\"spaced\" type=\"file\" name=\"data_file\" id=\"file\" />";
$main->echo_button ( "upload_file", array (
		"label" => "Upload Data File",
		"class" => "spaced",
		"number" => 4 
) );
echo "<br>";
$main->echo_button ( "create_sequence", array (
		"label" => "Create Data Table Primary Id Sequence",
		"class" => "spaced",
		"number" => 5 
) );
echo "<br>";
$main->echo_button ( "start_triggers", array (
		"label" => "Enable Date Triggers",
		"class" => "spaced",
		"number" => 6 
) );
echo "</div><br>";

// list install form objects
echo "<div class=\"spaced padded border\">";
echo "<p class=\"spaced bold larger\">Upload Lists</p>";
$main->echo_button ( "empty_lists", array (
		"label" => "Empty All Lists",
		"class" => "spaced",
		"number" => 7 
) );
echo "<span class = \"spaced border rounded padded shaded\">";
echo "<label class=\"padded\">Confirm: </label>";
$main->echo_input ( "check_empty", 1, array (
		'type' => 'checkbox',
		'input_class' => 'middle padded' 
) );
echo "</span><br>";
echo "<label class=\"spaced\">Select List Data File: </label>";
echo "<input class=\"spaced\" type=\"file\" name=\"list_data\" id=\"file\" /><br>";
echo "<label class=\"spaced\">Select List Definition File: </label>";
echo "<input class=\"spaced\" type=\"file\" name=\"list_defs\" id=\"file\" /><br>";
$main->echo_button ( "upload_lists", array (
		"label" => "Upload List Files",
		"class" => "spaced",
		"number" => 8 
) );
echo "</div>";

$main->echo_form_end ();
/* END FORM */
?>
