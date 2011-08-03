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

if (isset($_POST['waitlist-update-button'])) {
    $requests = array();
    foreach ($_POST['sched1'] as $request) {
        preg_match('#^\s*(([A-Z]{4})([^\s]*?))(\s(\d{4}))?\s*$#si', $request, $request_array);
        if (count($request_array) >= 3)
            $requests[] = array('dept' => $request_array[1], 'sec' => @$request_array[5]);
    }

    include 'umd-api.php';
    $umd_api = new umd_api;
    $schedules = $umd_api->get_schedules(json_decode(json_encode($requests)), 'object');
    
    if (empty($schedules))
        echo 'Error: Invalid information entered.';
    else {
        include "../inc/db.php";
        mysql_select_db("umd_waitlist");
        mysql_query("SET NAMES 'utf8'");
        $query = 'INSERT INTO waitlist_samples (year, term, dept, course_number, section, status, seats, open, waitlist, remote_addr, datetime) VALUES' . "\n";
        $inserts = array();
        foreach ($schedules as $dept) {
            foreach ($dept->courses as $course) {
                foreach ($course->sections as $section) {
                    $inserts[] = '("' . date('Y') . '", "' . $umd_api->get_term() . '", "' . $course->dept . '", "' . $course->number . '", "' . $section->number . '", "' . $section->status->orig_string . '", "' . $section->status->seats . '", "' . $section->status->open . '", "' . $section->status->waitlist . '", "' . $_SERVER['REMOTE_ADDR'] . '", NOW())';
                }
            }
        }
        $query .= implode(",\n", $inserts);
        $result = mysql_query($query);
        if (!$result) echo 'Processed unsuccessfully.<br />Query: ' . $query . '<br />Error: ' . mysql_error();
        else echo 'Updated waitlist data successfully!';
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
<p class="buttons"><button id="waitlist-update-button" name="waitlist-update-button"><img src="inc/img/silk/page_white_lightning.png" /> Update waitlist data</button></p>
</form>
</body>
</html>