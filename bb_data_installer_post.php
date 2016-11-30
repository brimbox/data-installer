<?php

/*
 * Copyright (C) Brimbox LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License Version 3 (GNU GPL v3)
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

/* NO HTML OUTPUT */
define ( 'BASE_CHECK', true );
// need DB_NAME from bb_config, must not have html output including blank spaces
include ("../../bb-config/bb_config.php"); // need DB_NAME

session_name ( DB_NAME );
session_start ();
session_regenerate_id ();

if (isset($_SESSION ['username']) && in_array($_SESSION ['userrole'], array("5_bb_brimbox"))) :
	
	// set by controller (index.php)
	$interface = $_SESSION ['interface'];
	$username = $_SESSION ['username'];
	$userrole = $_SESSION ['userrole'];
	$webpath = $_SESSION ['webpath'];
	$keeper = $_SESSION ['keeper'];
	$abspath = $_SESSION ['abspath'];
	
	// set by javascript submit form (bb_submit_form())
	$_SESSION ['button'] = $button = isset ( $_POST ['bb_button'] ) ? $_POST ['bb_button'] : 0;
	$_SESSION ['module'] = $module = isset ( $_POST ['bb_module'] ) ? $_POST ['bb_module'] : "";
	if ($_SESSION ['pretty_slugs'] == 1) {
		list ( , $slug ) = explode ( "_", $module, 2 );
		$_SESSION ['slug'] = $slug = str_replace ( "_", "-", $slug );
	} else {
		$_SESSION ['slug'] = $slug = $module;
	}
	$_SESSION ['submit'] = $submit = isset ( $_POST ['bb_submit'] ) ? $_POST ['bb_submit'] : "";
	
	/* SET UP WORK OBJECT AND POST STUFF */
	// objects are all daisy chained together
	// set up work from last object
	// contains bb_database class, extends bb_main
	// constants include -- some constants are used
	include_once ($abspath . "/bb-config/bb_constants.php");
	// include build class object
	if (file_exists ( $abspath . "/bb-extend/bb_include_main_class.php" ))
		include_once ($abspath . "/bb-extend/bb_include_main_class.php");
	else
		include_once ($abspath . "/bb-blocks/bb_include_main_class.php");
		
		// main object for hooks
	$main = new bb_main ();
	// need connection
	$con = $main->connect ();
	
	// load global arrays
	if (file_exists ( $abspath . "/bb-extend/bb_parse_globals.php" ))
		include_once ($abspath . "/bb-extend/bb_parse_globals.php");
	else
		include_once ($abspath . "/bb-blocks/bb_parse_globals.php");
		
		/* PRESERVE STATE */
	$POST = $_POST;
	
	$arr_state = $main->load ( $con, $submit );
	
	// intitalize message array
	$arr_messages = array ();
	// set PHP timeout to infinite
	set_time_limit ( 0 );
	
	/* DATA INSTALL */
	// truncate data_table, checking safety check box
	if ($main->button ( 1 ) && ($main->post ( 'check_truncate', $submit ) == 1)) {
		array_push ( $arr_messages, "Data Table has been truncated." );
		$query = "TRUNCATE TABLE data_table;";
		$main->query ( $con, $query );
	} elseif ($main->button ( 1 ) && ($main->post ( 'check_truncate', $submit ) != 1)) {
		// safety check box not set
		array_push ( $arr_messages, "Data Table has not been truncated. Please check confirm box." );
	}
	
	// disable date triggers
	if ($main->button ( 2 )) {
		$query = "ALTER TABLE data_table DISABLE TRIGGER ts1_modify_date;";
		$main->query ( $con, $query );
		$query = "ALTER TABLE data_table DISABLE TRIGGER ts2_create_date;";
		$main->query ( $con, $query );
		array_push ( $arr_messages, "Date Triggers have been disabled." );
	}
	
	// drop primary id sequence
	if ($main->button ( 3 )) {
		array_push ( $arr_messages, "Primary Key Sequence on column id has been dropped." );
		$query = "DROP SEQUENCE IF EXISTS data_table_id_seq CASCADE;";
		$main->query ( $con, $query );
	}
	
	// post data to database
	if ($main->button ( 4 )) {
		if (! empty ( $_FILES [$main->name ( 'data_file', $submit )] ["tmp_name"] )) {
			$handle = fopen ( $_FILES [$main->name ( 'data_file', $submit )] ["tmp_name"], "r" );
		}
		if ($handle) {
			$str = rtrim ( fgets ( $handle ) );
			$insert_clause = str_replace ( "\t", ",", $str );
			$arr_insert = explode ( "\t", $str );
			
			$arr_values = array ();
			for($i = 1; $i <= count ( $arr_insert ); $i ++) {
				array_push ( $arr_values, "\$" . $i );
			}
			$values_clause = implode ( ",", $arr_values );
			
			// number of rows in table
			while ( $str = fgets ( $handle ) ) {
				// $str = iconv("Windows-1252", "UTF-8//TRANSLIT//IGNORE", rtrim($str));
				$str = str_replace ( "\\n", "\n", $str );
				$arr_query = explode ( "\t", $str );
				
				$query = "INSERT INTO data_table (" . $insert_clause . ") " . "VALUES (" . $values_clause . ");";
				
				// echo $query . "<br>";
				$main->query_params ( $con, $query, $arr_query );
			}
			array_push ( $arr_messages, "Data has been inserted into the Data Table." );
		} else {
			array_push ( $arr_messages, "Upload file has been not been specified." );
		}
	}
	
	// create and reinitialize sequence
	if ($main->button ( 5 )) {
		$query = "CREATE SEQUENCE data_table_id_seq;";
		$main->query ( $con, $query );
		$query = "ALTER TABLE data_table ALTER COLUMN id SET DEFAULT NEXTVAL('data_table_id_seq');";
		$main->query ( $con, $query );
		$query = "ALTER SEQUENCE  data_table_id_seq OWNED BY data_table.id;";
		$main->query ( $con, $query );
		$query = "SELECT setval('data_table_id_seq', (select max(id) + 1 from data_table));";
		$main->query ( $con, $query );
		
		array_push ( $arr_messages, "Primary Key Sequence on column id has been created and reset." );
	}
	
	// enable date column triggers
	if ($main->button ( 6 )) {
		$query = "ALTER TABLE data_table ENABLE TRIGGER ts1_modify_date;";
		$main->query ( $con, $query );
		$query = "ALTER TABLE data_table ENABLE TRIGGER ts2_create_date;";
		$main->query ( $con, $query );
		
		array_push ( $arr_messages, "Date Triggers have been enabled." );
	}
	/* END DATA INSTALL */
	
	/* LIST INSTALL */
	// empty list column
	if ($main->button ( 7 ) && ($main->post ( 'check_empty', $submit ) == 1)) {
		$str_empty = str_repeat ( '0', 2000 );
		$query = "UPDATE data_table SET list_string = B'" . $str_empty . "';";
		$main->query ( $con, $query );
		array_push ( $arr_messages, "All Lists have been reset to empty." );
	} elseif ($main->button ( 7 ) && ($main->post ( 'check_empty', $submit ) != 1)) {
		array_push ( $arr_messages, "Lists have not been reset to empty. Please check confirm box." );
	}
	
	// upload list files
	if ($main->button ( 8 )) {
		// list definitions
		if (! empty ( $_FILES [$main->name ( 'list_defs', $submit )] ["tmp_name"] )) {
			$handle = fopen ( $_FILES [$main->name ( 'list_defs', $submit )] ["tmp_name"], "r" );
		}
		if ($handle) {
			// skip header row
			$str = fgets ( $handle );
			$arr_lists = array ();
			while ( $str = fgets ( $handle ) ) {
				$arr_row = explode ( "\t", rtrim ( $str ) );
				$arr_lists [$arr_row [2]] [$arr_row [0]] ['name'] = $arr_row [1];
				$arr_lists [$arr_row [2]] [$arr_row [0]] ['description'] = $arr_row [3];
				$arr_lists [$arr_row [2]] [$arr_row [0]] ['archive'] = $arr_row [4];
			}
			$main->update_json ( $con, $arr_lists, "bb_create_lists" );
			array_push ( $arr_messages, "List Definitions have been brought into JSON Table." );
		} else {
			array_push ( $arr_messages, "Lists Definition file has not been specified." );
		}
		// list data
		if (! empty ( $_FILES [$main->name ( 'list_data', $submit )] ["tmp_name"] )) {
			$handle = fopen ( $_FILES [$main->name ( 'list_data', $submit )] ["tmp_name"], "r" );
		}
		if ($handle) {
			// skip header row
			$str = fgets ( $handle );
			while ( $str = fgets ( $handle ) ) {
				$arr_params = explode ( "\t", rtrim ( $str ) );
				
				$query = "UPDATE data_table SET list_string = bb_list_set(list_string, $2) WHERE id = $1 AND row_type = $3;";
				$main->query_params ( $con, $query, $arr_params );
			}
			array_push ( $arr_messages, "List Data has been brought in Data Table." );
		} else {
			array_push ( $arr_messages, "Lists Data file has not been specified." );
		}
	}
	/* END LIST INSTALL */
	
	$arr_messages = array_unique ( $arr_messages );
	$main->set ( 'arr_messages', $arr_state, $arr_messages );
	
	// update state, back to db
	$main->update ( $con, $submit, $arr_state );
	
	$postdata = json_encode ( $POST );
	
	// set $_POST for $POST
	$query = "UPDATE state_table SET postdata = $1 WHERE id = " . $keeper . ";";
	pg_query_params($con, $query, array($postdata));
	
	// REDIRECT
	$index_path = "Location: " . $webpath . "/" . $slug;
	header ( $index_path );
	die ();

endif;

/* END FORM */
?>
