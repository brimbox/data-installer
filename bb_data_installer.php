<?php
/* Data Installer */
/* Brimbox.com*/
/* Licence GNU v3 */
/*
@module_name=bb_data_installer;
@friendly_name = Data Installer;
@interface = bb_brimbox;
@module_type = 5;
@module_version = 1.3;
@maintain_state = No;
@description = This is a Module that can be used to upload a whole data_table.;
*/
?>
<?php
$main->check_permission("bb_brimbox",5);
?>
<?php
/* PRESERVE STATE */
$main->retrieve($con, $array_state);

//intitalize message array
$arr_messages = array();
//set PHP timeout to infinite
set_time_limit(0);

/* DATA INSTALL */
//truncate data_table, checking safety check box   
if ($main->button(1) && ($main->post('check_truncate', $module) == 1))
    {
	array_push($arr_messages, "Data Table has been truncated.");
    $query = "TRUNCATE TABLE data_table;";
    $main->query($con, $query);
    }
//safety check box not set
elseif ($main->button(1) && ($main->post('check_truncate', $module) <> 1))
	{		
	array_push($arr_messages, "Data Table has not been truncated. Please check confirm box.");	
	}

//disable date triggers	
if ($main->button(2))
    {  
    $query = "ALTER TABLE data_table DISABLE TRIGGER ts1_modify_date;";
    $main->query($con, $query);
    $query = "ALTER TABLE data_table DISABLE TRIGGER ts2_create_date;";
    $main->query($con, $query);
	array_push($arr_messages, "Date Triggers have been disabled.");
    }
	
//drop primary id sequence
if ($main->button(3))
    {
	array_push($arr_messages, "Primary Key Sequence on column id has been dropped.");
    $query = "DROP SEQUENCE IF EXISTS data_table_id_seq CASCADE;";
    $main->query($con, $query);
    } 

//post data to database	
if ($main->button(4))
    {
	if (!empty($_FILES[$main->name('data_file', $module)]["tmp_name"]))
		{        
		$handle = fopen($_FILES[$main->name('data_file', $module)]["tmp_name"],"r");
		}
    if ($handle)
		{
		$str = rtrim(fgets($handle));
		$insert_clause = str_replace("\t",",",$str);
		$arr_insert = explode("\t",$str);
		
		$arr_values = array();
		for ($i=1; $i<=count($arr_insert); $i++)
			{
			array_push($arr_values, "\$" . $i);   
			}
		$values_clause = implode(",", $arr_values);
		
		//number of rows in table
		while($str = fgets($handle))
			{
			//$str = iconv("Windows-1252", "UTF-8//TRANSLIT//IGNORE", rtrim($str));
			$str = str_replace("\\n", "\n", $str);
			$arr_query = explode("\t",$str);        
			
			$query = "INSERT INTO data_table (" . $insert_clause . ") " .
					 "VALUES (" . $values_clause . ");";
					 
			//echo $query . "<br>";
			$main->query_params($con, $query, $arr_query);        
			}
		array_push($arr_messages, "Data has been inserted into the Data Table.");
		}
	else
		{
		array_push($arr_messages, "Upload file has been not been specified.");	
		}
    }

//create and reinitialize sequence    
 if ($main->button(5))
    {       
    $query = "CREATE SEQUENCE data_table_id_seq;";
    $main->query($con, $query);
    $query = "ALTER TABLE data_table ALTER COLUMN id SET DEFAULT NEXTVAL('data_table_id_seq');";
    $main->query($con, $query);
    $query = "ALTER SEQUENCE  data_table_id_seq OWNED BY data_table.id;";
	$main->query($con, $query);
    $query = "SELECT setval('data_table_id_seq', (select max(id) + 1 from data_table));";
    $main->query($con, $query);
	
	array_push($arr_messages, "Primary Key Sequence on column id has been created and reset.");
    }

//enable date column triggers	
 if ($main->button(6))
    {       
    $query = "ALTER TABLE data_table ENABLE TRIGGER ts1_modify_date;";
    $main->query($con, $query);
    $query = "ALTER TABLE data_table ENABLE TRIGGER ts2_create_date;";
    $main->query($con, $query);
	
	array_push($arr_messages, "Date Triggers have been enabled.");
    }
/* END DATA INSTALL */
	
/* LIST INSTALL */
//empty list column
if ($main->button(7) && ($main->post('check_empty', $module) == 1))
	{
	$str_empty = str_repeat('0', 2000);
    $query = "UPDATE data_table SET list_string = B'" . $str_empty . "';";
    $main->query($con, $query);	
	array_push($arr_messages, "All Lists have been reset to empty.");
    }
elseif ($main->button(7) && ($main->post('check_empty', $module) <> 1))
	{		
	array_push($arr_messages, "Lists have not been reset to empty. Please check confirm box.");	
	}

//upload list files	
if ($main->button(8))
    {
    //list definitions
	if (!empty($_FILES[$main->name('list_defs', $module)]["tmp_name"]))
		{
		$handle = fopen($_FILES[$main->name('list_defs', $module)]["tmp_name"],"r");
		}
	if ($handle)
		{
		//skip header row
		$str = fgets($handle); 
		$arr_lists = array();
		while($str = fgets($handle))
			{
			$arr_row = explode("\t",rtrim($str));	
			$arr_lists[$arr_row[2]][$arr_row[0]]['name'] = $arr_row[1];
            $arr_lists[$arr_row[2]][$arr_row[0]]['description'] = $arr_row[3];
            $arr_lists[$arr_row[2]][$arr_row[0]]['archive'] = $arr_row[4];	
			}
		$main->update_json($con, $arr_lists, "bb_create_lists");
		array_push($arr_messages, "List Definitions have been brought into JSON Table.");
		}
	else
		{
		array_push($arr_messages, "Lists Definition file has not been specified.");	
		}
	//list data
	if (!empty($_FILES[$main->name('list_data', $module)]["tmp_name"]))
		{
		$handle = fopen($_FILES[$main->name('list_data', $module)]["tmp_name"],"r");
		}
	if ($handle)
		{
		//skip header row
		$str = fgets($handle);	
		while($str = fgets($handle))
			{
			$arr_params = explode("\t",rtrim($str));
			
			$query = "UPDATE data_table SET list_string = bb_list_set(list_string, $2) WHERE id = $1 AND row_type = $3;";
			$main->query_params($con, $query, $arr_params);      
			}
		array_push($arr_messages, "List Data has been brought in Data Table.");
		}
	else
		{
		array_push($arr_messages, "Lists Data file has not been specified.");	
		}
    }
/* END LIST INSTALL */

/* START REQUIRED FORM */
//title
echo "<p class=\"spaced bold larger\">Install Data</p>";

//messages
echo "<div class=\"spaced\">";
$main->echo_messages($arr_messages);
echo "</div>";

//multipart form
$main->echo_form_begin(array('type'=>"enctype=\"multipart/form-data\""));
$main->echo_module_vars($module);

//data install form objects
echo "<div class=\"spaced padded border\">";
echo "<p class=\"spaced bold larger\">Upload Data</p>";
$main->echo_button("truncate",array("label"=>"Trucate Existing Data Table", "class"=>"spaced", "number"=>1));
echo "<span class = \"spaced border rounded padded shaded\">";
echo "<label class=\"padded\">Confirm: </label>";
$main->echo_input("check_truncate", 1, array('type'=>'checkbox','input_class'=>'middle holderup'));
echo "</span><br>";
$main->echo_button("stop_triggers", array("label"=>"Disable Date Triggers", "class"=>"spaced", "number"=>2));
echo "<br>";
$main->echo_button("drop_sequence", array("label"=>"Drop Data Table Primary Id Sequence", "class"=>"spaced", "number"=>3));
echo "<br>";
echo "<label class=\"spaced\">Select Data Filename: </label>";
echo "<input class=\"spaced\" type=\"file\" name=\"data_file\" id=\"file\" />";
$main->echo_button("upload_file", array("label"=>"Upload Data File", "class"=>"spaced", "number"=>4));
echo "<br>";
$main->echo_button("create_sequence", array("label"=>"Create Data Table Primary Id Sequence", "class"=>"spaced", "number"=>5));
echo "<br>";
$main->echo_button("start_triggers", array("label"=>"Enable Date Triggers", "class"=>"spaced", "number"=>6));
echo "</div><br>";

//list install form objects
echo "<div class=\"spaced padded border\">";
echo "<p class=\"spaced bold larger\">Upload Lists</p>";
$main->echo_button("empty_lists", array("label"=>"Empty All Lists", "class"=>"spaced", "number"=>7));
echo "<span class = \"spaced border rounded padded shaded\">";
echo "<label class=\"padded\">Confirm: </label>";
$main->echo_input("check_empty", 1, array('type'=>'checkbox','input_class'=>'middle padded'));
echo "</span><br>";
echo "<label class=\"spaced\">Select List Data File: </label>";
echo "<input class=\"spaced\" type=\"file\" name=\"list_data\" id=\"file\" /><br>";
echo "<label class=\"spaced\">Select List Definition File: </label>";
echo "<input class=\"spaced\" type=\"file\" name=\"list_defs\" id=\"file\" /><br>";
$main->echo_button("upload_lists", array("label"=>"Upload List Files", "class"=>"spaced", "number"=>8));
echo "</div>";

$main->echo_state($array_state);
$main->echo_form_end();
/* END FORM */
?>
