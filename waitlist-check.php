<!DOCTYPE html>
<html>
<head>
<title>Waitlist data checker</title>
<link rel="stylesheet" type="text/css" href="inc/jquery-tagger/tagger.css" />
<link rel="stylesheet" type="text/css" href="buttons.css" />
<script type="text/javascript" src="sched-grubber.js"></script>
<script type="text/javascript" src="inc/jquery-week-calendar/libs/jquery-1.4.4.min.js"></script> 
<script type="text/javascript" src="inc/jquery-tagger/jquery.tagger.js"></script>
</head>
<body>
<?php

if (isset($_POST['waitlist-check-button'])) {
    $requests = array();
    $wheres = array();
    $valid = false;
    foreach ($_POST['sched1'] as $request) {
        preg_match('#^\s*(([A-Z]{4})([^\s]*?))(\s(\d+))?\s*$#si', $request, $request_array);
        if (count($request_array) >= 3) {
            $valid = true;
            $requests[] = array('dept' => $request_array[1], 'sec' => @$request_array[5]);
            $wheres[] = '(dept = "' . $request_array[2] . '"' . (!empty($request_array[3]) ? ' AND course_number LIKE "' . $request_array[3] . '%"' : '') . (isset($request_array[5]) ? ' AND section LIKE "' . $request_array[5] . '%"' : '') . ')';
        }
    }

    include "../inc/db.php";
    mysql_select_db("umd_waitlist");
    mysql_query("SET NAMES 'utf8'");

    include 'umd-api.php';
    $umd_api = new umd_api;
    
    $query = '';
    if (empty($requests)) {
        if (!$valid && !empty($_POST['sched1'][0]))
            echo 'Error: Invalid information entered.';
        else
            $query = 'select a.* from waitlist_samples a join (select distinct status, year,term,dept,course_number,section,datetime, count(distinct status) from waitlist_samples group by year,term,dept,course_number,section having count(distinct status)>1) b on a.year=b.year and a.term=b.term and a.dept=b.dept and a.course_number=b.course_number and a.section=b.section order by a.year,a.term,a.dept,a.course_number,a.section,a.datetime';
    }
    else {

        $query = 'SELECT * FROM waitlist_samples WHERE year = "' . date('Y') . '" AND term = "' . $umd_api->get_term() . '" AND (' . implode(" OR \n", $wheres) . ') ORDER BY year, term, dept, course_number, section, datetime';
    }
    if ($query) {
        $result = mysql_query($query);
        
        if (!mysql_num_rows($result))
            echo 'No results found.';
        else {
            $sections = array();
            while ($sample = mysql_fetch_assoc($result)) {
                $key = $sample['year'] . $sample['term'] . $sample['dept'] . $sample['course_number'] . $sample['section'];
                if (!isset($sections[$key]))
                    $sections[$key] = array('section' => $sample, 'samples' => array());
                $sections[$key]['samples'][] = $sample;
            }
            
            echo '<dl>';
            foreach ($sections as $section_key => $section) {
                echo '<dt><strong>' .  $section['section']['dept'] . $section['section']['course_number'] . ' ' . $section['section']['section'] . '</strong></dt><dd>';
                foreach ($section['samples'] as $sample)
                    echo 'At ' . date('d M Y, H:i:s', strtotime($sample['datetime'])) . ' the status of this section was: ' . $sample['status'] . '<br />';
                echo '</dd>';
            }
            echo '</dl>';
        }
    }
    echo '<hr />';
}

?>
<form id="waitlist-check" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<h3>Instructions</h3>
<ol>
<li>Enter at least one course (or section), or leave blank to return all sections with changed statuses.</li>
<li>Click the "Check waitlist data" button.</li>
</ol>
<p>Format: <code>DEPT123</code> or <code>DEPT123 0101</code>, where <code>DEPT123</code> is the department code and course number, and <code>0101</code> is the section number. You may enter multiple sections separated by commas.</p>
<p><input type="text" class="tagger" name="sched1[]" id="sched1" style="padding: 3px;" /><br style="clear: both;" /></p>
<p class="buttons"><button id="waitlist-check-button" name="waitlist-check-button"><img src="inc/img/silk/page_white_lightning.png" /> Check waitlist data</button></p>
</form>
</body>
</html>