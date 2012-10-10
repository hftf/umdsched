<!DOCTYPE html>
<html>
<head>
<title>Credit streamgraph</title><!--
<link rel="stylesheet" type="text/css" href="inc/jquery-tagger/tagger.css" />
<link rel="stylesheet" type="text/css" href="buttons.css" />
<script type="text/javascript" src="sched-grubber.js"></script>
<script type="text/javascript" src="inc/jquery-week-calendar/libs/jquery-1.4.4.min.js"></script> 
<script type="text/javascript" src="inc/jquery-tagger/jquery.tagger.js"></script>-->
<script type="text/javascript" src="inc/grafico/prototype.js"></script>
<script type="text/javascript" src="inc/grafico/raphael.js"></script>
<script type="text/javascript" src="inc/grafico/grafico.base.js"></script>
<script type="text/javascript" src="inc/grafico/grafico.line.js"></script>
<script type="text/javascript">
grafico_options = {
    markers: "value",
    
    draw_axis: false,
    grid: true,
    show_horizontal_labels: true,
    show_vertical_labels: true,
    
    left_padding: 20,
    //plot_padding :      0,
    hover_text_color :  "#fff",
    background_color :  "#fff",
    
    
    stream_line_smoothing: "simple",
    stream_smart_insertion: true,
    stream_label_threshold: 0,
};
</script>
</head>
<body>
<?php

$credits = array(
  '201108' => 'MATH340 0101, PHYS272 0101, PHYS174 0102, CPSP118D 0102, CMSC250 0102, ASTR120 0101',
  '201112' => '',
  '201201' => 'MATH341 0101, PHYS273 0101, PHYS275 0101, CPSP119D 0101, CMSC216 0202, ASTR121 0101',
  '201206' => '',
  '201208' => 'PHYS374 0101, PHYS401 0101, PHYS276 0101, CMSC351 0101, CPSP218D 0101, ASTR310 0101',
  '201212' => 'LING240 0101',
  '201301' => 'PHYS411 0101, PHYS375 0101, LING311 0101, CMSC330 0204, CPSP239D 0101, ASTR320 0101',
  '201306' => '',
);


//if (isset($_POST['waitlist-update-button'])) {
    $time_start = microtime(1);
    $time_prev = $time_start;
    /*function since($desc) {
        global $time_start, $time_prev;
        $time_now = microtime(1);
        echo '<p>Since start: ' . number_format($time_now - $time_start, 4) . '; since previous: ' . number_format($time_now - $time_prev, 4) . ' &mdash; ' . $desc . '</p>';
        $time_prev = $time_now;
    }*/
    /*
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
    }*/

    //set_time_limit(69);
    include 'umd-api.php';
    $umd_api = new umd_api;
    since('After creating API instance');

add_streamgraph_script(retrieve_credits($credits) );
    
// Input: Sections by term
// Output: Credits by departmenet by term
function retrieve_credits($term_sections) {
    global $umd_api;

    // Build object
    $requests = array();
    $terms = array();
    
    foreach ($term_sections as $year_term => $dept_secs) {
        if (empty($dept_secs))
            continue;
        
        list($year, $term) = str_split($year_term, 4);
        $terms[$year_term] = 0;
        
        if ($dept_secs) {
            $dept_secs = explode(', ', $dept_secs);
            foreach ($dept_secs as $i => $dept_sec_str) {
                list($dept, $sec) = explode(' ', $dept_sec_str);
                $requests[] = (object) array('year' => $year, 'term' => $term, 'dept' => $dept, 'sec' => $sec);
            }
        }
    }
    
    // Query API
    $data = $umd_api->get_schedules_async(null, null, $requests, 'credits');
    
    // Populate credits
    $dept_credits = array();
    
    foreach ($data as $i) {
        $year_term = $i->data->year . $i->data->term;
        $department = $i->code;
        $course_num = $i->courses[0]->number;
        $credits = $i->courses[0]->credits;
        
        if (!isset($dept_credits[$department])) {
            $dept_credits[$department] = $terms;
            $dept_credits[$department]['_name'] = $i->name;
        }
        //$dept_credits[$department][$year_term][$course_num] = $credits;
        $dept_credits[$department][$year_term] += $credits;
    }
    
    return $dept_credits;
}
function add_streamgraph_script($dept_credits_) {
    $dept_credits = array();
    $dept_names = array();
    $terms_ = array();
    
    foreach($dept_credits_ as $dept => $terms) {
        $dept_names[$dept] = $dept; //$terms['_name'];
        unset($terms['_name']);
        
        if (empty($terms_))
            $terms_ = term_labels(array_keys($terms));
        $dept_credits[$dept] = array_values($terms);
    }
    
    $json_data = json_encode($dept_credits);
    $json_datalabels = json_encode($dept_names);
    $json_labels = json_encode($terms_);

    $str = '<div id="streamgraph" style="width: 700px; height: 400px;"></div>
    <script type="text/javascript">
    Event.observe(window, "load", function() {
        grafico_options.datalabels = ' . $json_datalabels . ';
        grafico_options.labels = ' . $json_labels . ';
        var streamgraph = new Grafico.StackGraph( $("streamgraph"), ' . $json_data . ', grafico_options );
    });
    </script>';
    echo $str;
}
function term_labels($year_terms) {
    foreach ($year_terms as $i => $term)
        $year_terms[$i] = season(substr($term, 4)) . "\n" . substr($term, 0, 4);
    return $year_terms;
}
function season($term) {
    switch ($term) {
        case '01': return 'Spring';
        case '06': return 'Summer';
        case '08': return 'Fall';
        case '12': return 'Winter';
    }
}

?>

<!--
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
-->
</body>
</html>
