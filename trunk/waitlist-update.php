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
    $wheres = array();
    $valid = false;
    foreach ($_POST['sched1'] as $request) {
        preg_match('#^\s*(([A-Z]{4})([^\s]*?))(\s(\d+))?\s*$#si', $request, $request_array);
        if (count($request_array) >= 3) {
            $valid = true;
            $requests[] = array('dept' => $request_array[1], 'sec' => @$request_array[5]);
            $wheres[] = '(a.dept = "' . $request_array[2] . '"' . (!empty($request_array[3]) ? ' AND a.course_number LIKE "' . $request_array[3] . '%"' : '') . (isset($request_array[5]) ? ' AND a.section LIKE "' . $request_array[5] . '%"' : '') . ')';
        }
    }

    include 'umd-api.php';
    $umd_api = new umd_api;

    include "../inc/db.php";
    mysql_select_db("umd_waitlist");
    mysql_query("SET NAMES 'utf8'");

    $added = array();
    $all = false;
    //GROUP BY year, term, dept, course_number, section ORDER BY year, term, dept, course_number, section, datetime DESC';
    $query = 'SELECT a.* FROM waitlist_samples a JOIN (SELECT *, MAX(datetime) AS max_datetime FROM waitlist_samples GROUP BY year,term,dept,course_number,section) b
ON a.year=b.year AND a.term=b.term AND a.dept=b.dept AND a.course_number=b.course_number AND a.section=b.section WHERE a.year = "' . date('Y') . '" AND a.term = "' . $umd_api->get_term() . '" AND a.datetime=b.max_datetime';
    if (isset($_GET['all']) || empty($requests))
        $all = true;
    else
        $query .= ' AND (' . implode(" OR \n", $wheres) . ')';

    $result = mysql_query($query);
    while ($section = mysql_fetch_assoc($result)) {
        $key = $section['year'] . $section['term'] . $section['dept'] . $section['course_number'] . $section['section'];
        $added[$key] = $section;
        if ($all && !isset($requests[$section['dept']]))
            $requests[$section['dept']] = array('dept' => $section['dept'], 'sec' => null);
    }
    $schedules = $umd_api->get_schedules(json_decode(json_encode($requests)), 'object');
    if (empty($schedules))
        echo 'Error: Invalid information entered.';
    else {
        $insert_query = 'INSERT INTO waitlist_samples (year, term, dept, course_number, section, status, seats, open, waitlist, remote_addr, datetime, last_checked) VALUES' . "\n";
        $inserts = array();
        $update_query = 'UPDATE waitlist_samples SET last_checked = NOW() WHERE id IN' . "\n";
        $ins = array();
        foreach ($schedules as $dept) {
            foreach ($dept->courses as $course) {
                foreach ($course->sections as $section) {
                    $year = date('Y');
                    $term = $umd_api->get_term();
                    $key = $year . $term . $course->dept . $course->number . $section->number;
                    //echo $added[$key]['id'] . ' ' . $key . '<br />' . $added[$key]['status'] . ' ' . $added[$key]['datetime'] . '<br/>' . $section->status->orig_string . '<br/><br/>';
                    if (!isset($added[$key]) || $section->status->orig_string != $added[$key]['status'])
                        $inserts[] = '("' . $year . '", "' . $term . '", "' . $course->dept . '", "' . $course->number . '", "' . $section->number . '", "' . $section->status->orig_string . '", "' . $section->status->seats . '", "' . $section->status->open . '", "' . $section->status->waitlist . '", "' . $_SERVER['REMOTE_ADDR'] . '", NOW(), NOW())';
                    else
                        $ins[] = $added[$key]['id'];
                }
            }
        }
        if (!empty($inserts)) {
            $insert_query .= implode(",\n", $inserts);
            //echo $insert_query;
            $result = mysql_query($insert_query);
            echo '<p>' . ((!$result) ? 'Processed insert unsuccessfully.<br />Query: ' . $insert_query . '<br />Error: ' . mysql_error() : 'Inserted waitlist data successfully!') . '</p>';
        }
        if (!empty($ins)) {
            $update_query .= '(' . implode(', ', $ins) . ')';
            //echo $update_query;
            $result = mysql_query($update_query);
            echo '<p>' . ((!$result) ? 'Processed update unsuccessfully.<br />Query: ' . $update_query . '<br />Error: ' . mysql_error() : 'Updated waitlist data successfully!') . '</p>';
        }
    }
}

?>
<form id="waitlist-update" action="<?php echo $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']; ?>" method="post">
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