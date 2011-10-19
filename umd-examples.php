<?php

echo '<form method="get" action="' . $_SERVER['PHP_SELF'] . '">
<label for="year">Year:</label> <select id="year" name="year"><option value="2011" selected="selected">2011</option><option value="2012">2012</option></select>
<label for="term">Term:</label> <select id="term" name="term"><option value="08" selected="selected" style="font-weight: bold;">Fall</option><option value="12">Winter</option><option value="01" style="font-weight: bold;">Spring</option><option value="06">Summer</option></select>
<label for="dept">Department or course:</label> <input type="text" id="dept" name="dept" value="' . @$_GET['dept'] . '" />
<label for="sec">Section:</label> <input type="text" id="sec" name="sec" value="' . @$_GET['sec'] . '" />
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
$courses = $umdapi->get_schedule(@$_GET['year'],@$_GET['term'],@$_GET['dept'],@$_GET['sec']);
echo'<pre>';print_r($courses);echo'</pre>';

/*
//Get map categories
$categories = $umdapi->get_map();

//get buildings
$buildings = $umdapi->get_maps('academic');
*/
?>