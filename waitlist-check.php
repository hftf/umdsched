<!DOCTYPE html>
<html>
<head>
<title>Waitlist data updater</title>
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
    foreach ($_POST['sched1'] as $request) {
        preg_match('#^\s*(([A-Z]{4})([^\s]*?))(\s(\d+))?\s*$#si', $request, $request_array);
        if (count($request_array) >= 3) {
            $requests[] = array('dept' => $request_array[1], 'sec' => @$request_array[5]);
            $wheres[] = '(dept = "' . $request_array[2] . '"' . (!empty($request_array[3]) ? ' AND course_number LIKE "' . $request_array[3] . '%"' : '') . (isset($request_array[5]) ? ' AND section LIKE "' . $request_array[5] . '%"' : '') . ')';
        }
    }

    include 'umd-api.php';
    $umd_api = new umd_api;
    
    if (empty($requests))
        echo 'Error: Invalid information entered.';
    else {
        include "../inc/db.php";
        mysql_select_db("umd_waitlist");
        mysql_query("SET NAMES 'utf8'");
        $query = 'SELECT * FROM waitlist_samples WHERE year = "' . date('Y') . '" AND term = "' . $umd_api->get_term() . '" AND (' . implode(" OR \n", $wheres) . ') ORDER BY year, term, dept, course_number, section, datetime';
        $result = mysql_query($query);
        
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
                echo '<li>At ' . date('d M Y, H:i:s', strtotime($sample['datetime'])) . ': ' . $section['section']['status'] . '</li>';
            echo '</dd>';
        }
        echo '</dl>';
    }
}

?>
<form id="waitlist-update" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<h3>Instructions</h3>
<ol>
<li>Enter at least one course (or section).</li>
<li>Click the "Update waitlist data" button.</li>
</ol>
<p>Format: <code>DEPT123</code> or <code>DEPT123 0101</code>, where <code>DEPT123</code> is the department code and course number, and <code>0101</code> is the section number. You may enter multiple sections separated by commas.</p>
<p><input type="text" class="tagger" name="sched1[]" id="sched1" style="padding: 3px;" /><br style="clear: both;" /></p>
<p class="buttons"><button id="waitlist-check-button" name="waitlist-check-button"><img src="inc/img/silk/page_white_lightning.png" /> Check waitlist data</button></p>
</form>
</body>
</html>