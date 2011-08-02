<?php

echo '<form method="get" action="' . $_SERVER['PHP_SELF'] . '">
<label for="dept">Department or course:</label> <input type="text" id="dept" name="dept" value="' . @$_GET['dept'] . '" />
<label for="dept">Section:</label> <input type="text" id="sec" name="sec" value="' . @$_GET['sec'] . '" />
<input type="submit" value="Submit" /></form>';


//Grab API
include('umd-api.php');

//init API wrapper
$umdapi = new umd_api;

/*
//list departments
$departments = $umdapi->get_schedule();
foreach ($departments as $department)
    echo $department->code . ': ' . $department->name . '<br />';
*/

//get course schedule for fall 2010
//$courses = $umdapi->get_schedule('2010','08','hebr');

//Get Course Schedule for current term
$courses = $umdapi->get_schedule(null,null,@$_GET['dept'],@$_GET['sec']);
echo'<pre>';print_r($courses);echo'</pre>';

/*
//Get map categories
$categories = $umdapi->get_map();

//get buildings
$buildings = $umdapi->get_maps('academic');
*/
?>