<!DOCTYPE html>
<html>
<head>
<title>Waitlist data checker</title>
<link rel="stylesheet" type="text/css" href="inc/jquery-tagger/tagger.css" />
<link rel="stylesheet" type="text/css" href="buttons.css" />
<script type="text/javascript" src="sched-grubber.js"></script>
<script type="text/javascript" src="inc/jquery-week-calendar/libs/jquery-1.4.4.min.js"></script> 
<script type="text/javascript" src="inc/jquery-tagger/jquery.tagger.js"></script>
<script type="text/javascript" src="inc/js/jquery.sparkline.2.0.js"></script>
<script type="text/javascript">
$(document).ready(function() {
    $('.sparkline').sparkline('html', { chartRangeMin: 0, lineColor: 'hsla(120, 76%, 55%, 0.6)', fillColor: 'hsla(120, 76%, 55%, 0.3)', spotRadius: false, enableTagOptions: true, tagValuesAttribute: 'sparkcompositeValues', height: 40, width: 100,
      tooltipFormatter: function(sparkline, options, fields) {
        var d = new Date(fields.x * 1000);
        dstr = d.toMyISOString();
        return dstr + '<br /><span style="color: ' + fields.color + '">&#9679; </span>' + (window.lastSeats = fields.y) + ' seats';
      }
    });
    $('.sparkline').sparkline('html', { chartRangeMin: 0, lineColor: '#000', fillColor: false, spotRadius: false /* new */, enableTagOptions: true, composite: true, 
      tooltipFormatter: function(sparkline, options, fields) {
        var diff = window.lastSeats - fields.y,
            open = diff > 0 ? diff : 0;
            wait = diff < 0 ? -diff : 0;
        
        return '<br /><span style="color: ' + fields.color + '">&#9679; </span>' + open + ' open, ' + wait + ' waitlist';
      }
    });
});
Date.prototype.toMyISOString = function() {
  function pad(n) { return n < 10 ? '0' + n : n };
  var d = this;
  return d.getFullYear() + '-'
      + pad(d.getMonth() + 1) + '-'
      + pad(d.getDate()) + ' '
      + pad(d.getHours()) + ':'
      + pad(d.getMinutes()) + ':'
      + pad(d.getSeconds());
};
</script>
<style type="text/css">
body { font: 0.8em "lucida grande","lucida sans",sans-serif; }
.sparkline { display: inline-block; vertical-align: bottom; }
dd { font-size: 0.8em; margin-bottom: 1em; }
</style>
</head>
<body>
<?php

$year = (isset($_GET['year'])) ? $_GET['year'] : '2012';
$term = (isset($_GET['term'])) ? $_GET['term'] : '08';

$inputs = array();
if (isset($_POST['waitlist-check-button']))
    $inputs = $_POST['sched1'];
if (isset($_GET['sched1'])) {
    if (!is_array($_GET['sched1']) && is_string($_GET['sched1']))
        $_GET['sched1'] = preg_split('#\s*,\s*#', $_GET['sched1']);
    if (is_array($_GET['sched1']))
        $inputs = array_merge($inputs, $_GET['sched1']);
}

if (!empty($inputs)) {
    $requests = array();
    $wheres = array();
    $valid = false;
    foreach ($inputs as $request) {
        preg_match('#^\s*(([A-Z]{4})([^\s]*?))(\s(\d+))?\s*$#si', $request, $request_array);
        if (count($request_array) >= 3) {
            $valid = true;
            $requests[] = array('dept' => $request_array[1], 'sec' => @$request_array[5]);
            $wheres[] = '(dept = "' . $request_array[2] . '"' . (!empty($request_array[3]) ? ' AND course_number LIKE "' . $request_array[3] . '%"' : '') . (isset($request_array[5]) ? ' AND section LIKE "' . $request_array[5] . '%"' : '') . ')';
        }
    }

    include "inc/db.php";
    mysql_select_db("umd_waitlist");
    mysql_query("SET NAMES 'utf8'");

    include 'umd-api.php';
    $umd_api = new umd_api;
    
    $query = '';
    if (empty($requests)) {
        if (!$valid && !empty($_POST['sched1'][0]))
            echo 'Error: Invalid information entered.';
        else
            $query = 'select a.* from waitlist_samples a join (select distinct status, year,term,dept,course_number,section,datetime, count(distinct status) from waitlist_samples WHERE year = "' . $year . '" AND term = "' . $term . '" group by year,term,dept,course_number,section having count(distinct status)>1) b on a.year=b.year and a.term=b.term and a.dept=b.dept and a.course_number=b.course_number and a.section=b.section order by a.year,a.term,a.dept,a.course_number,a.section,a.datetime';
    }
    else {

        $query = 'SELECT * FROM waitlist_samples WHERE year = "' . $year . '" AND term = "' . $term . '" AND (' . implode(" OR \n", $wheres) . ') ORDER BY year, term, dept, course_number, section, datetime';
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
                    $sections[$key] = array('section' => $sample, 'samples' => array(), 'sparkline' => array(), 'ymax' => 0);
                $sections[$key]['samples'][] = $sample;
                $y = ($sample['seats'] - $sample['open'] + $sample['waitlist']);
                $max = max($sample['seats'], $y);
                if ($max > $sections[$key]['ymax'])
                    $sections[$key]['ymax'] = $max;
                $sections[$key]['sparkline'][] = strtotime($sample['datetime']) . ':' . $y;
                $sections[$key]['sparkline'][] = strtotime($sample['last_checked']) . ':' . $y;
                $sections[$key]['composite'][] = strtotime($sample['datetime']) . ':' . $sample['seats'];
                $sections[$key]['composite'][] = strtotime($sample['last_checked']) . ':' . $sample['seats'];
            }
            
            echo '<dl>';
            foreach ($sections as $section_key => $section) {
                $last_sample = $section['samples'][count($section['samples']) - 1];
                $section['sparkline'][] = time() . ':' . ($last_sample['seats'] - $last_sample['open'] + $last_sample['waitlist']);
                $section['composite'][] = time() . ':' . $last_sample['seats'];
                $sparkline = implode(',', $section['sparkline']);
                $composite = implode(',', $section['composite']);
                $url = section2url($section['section']);
                //sparknormalRangeMax="' . $section['section']['seats'] . '" 
                echo '<dt><strong><a href="' . $url . '">' .  $section['section']['dept'] . $section['section']['course_number'] . ' ' . $section['section']['section'] . '</a></strong> <span class="sparkline" sparkchartRangeMax="' . $section['ymax'] . '" values="' . $sparkline . '" sparkcompositeValues="' . $composite . '"></span></dt><dd><table>';
                foreach ($section['samples'] as $sample)
                    echo '<tr><td>' . $sample['datetime'] . '</td><td>' . $sample['last_checked'] . '</td><td>' . $sample['status'] . '</td></tr>';
                echo '</table></dd>';
            }
            echo '</dl>';
        }
    }
    echo '<hr />';
}

function section2url($section) {
    $base_url = 'http://www.sis.umd.edu/bin/soc?';
    return $base_url . 'term=' . $section['year'] . $section['term'] . '&crs=' . $section['dept'] . $section['course_number'] . '&sec=' . $section['section'];
}

?>
<form id="waitlist-check" action="<?php echo $_SERVER['PHP_SELF']; ?>?year=<?php echo $year; ?>&amp;term=<?php echo $term; ?>" method="post">
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
