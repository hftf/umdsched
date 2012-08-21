<?php

// -- Some constants
                  // According to http://www.provost.umd.edu/calendar/
$terms = array(   // First day of classes, last day of classes
  '201108' => array('start' => '20110831', 'end' => '20111213'),
//'201112' => array('start' => '20120103', 'end' => '20120123'),
  '201201' => array('start' => '20120125', 'end' => '20120510'),
//'201206' => 'Not sure how summer terms work...',
  '201208' => array('start' => '20120829', 'end' => '20121211'),
//'201212' => array('start' => '20130102', 'end' => '20130122'),
  '201301' => array('start' => '20130123', 'end' => '20130509'),
);
$seasons = array('01' => 'Spring', '06' => 'Summer', '08' => 'Fall', '12' => 'Winter');
$dayabbrs = array('SU' => 'Sun', 'MO' => 'Mon', 'TU' => 'Tue', 'WE' => 'Wed', 'TH' => 'Thu', 'FR' => 'Fri', 'SS' => 'Sat');


// -- Get inputs and plug into API

include 'umd-api.php';

$umd_api = new umd_api;

$data = json_decode($_POST['export_schedule_data']);
$year = isset($data -> year) ? $data -> year : date('Y');
$term = isset($data -> term) ? $data -> term : $umd_api -> get_term();
$sections = $data -> data;

$vevents = $umd_api -> get_schedules($year, $term, $sections, 'ics');


// -- Generate iCalendar

require_once 'inc/php/iCalcreator.class.php';

$calname = 'My ' . $seasons[$term] . ' ' . $year . ' course schedule';
$caldesc = 'Automatically generated at http://ophir.li/umd/calendar.html. If you found this useful, share it with your friends!';
$config = array(
  'unique_id' => 'ophir.li',
  'filename' => 'umdsched' . $year . $term . '-' . intval(microtime(true)) . '.ics',
);
$vcalendar = new vcalendar($config);
$vcalendar -> setProperty('METHOD', 'PUBLISH');
$vcalendar -> setProperty('X-WR-CALNAME', $calname);
$vcalendar -> setProperty('X-WR-CALDESC', $caldesc);
$vcalendar -> setProperty('X-WR-RELCALID', 'DE43623F-F2D6-408B-AD1B-408F4102C25A');
$vcalendar -> setProperty('X-WR-TIMEZONE', 'America/New_York');

$term_start = $terms[$year . $term]['start']; // Cache
$term_end   = $terms[$year . $term]['end']; // Cache

foreach ($vevents as $vevent) {
  $vevent_ = & $vcalendar->newComponent('vevent');
  $vevent_ -> setProperty('UID', $vevent['id']);
  
  $first_day = find_first_day($term_start, $vevent['byday']);
  
  $vevent_ -> setProperty('DTSTART', $first_day . 'T' . $vevent['tstart']);
  $vevent_ -> setProperty('DTEND',   $first_day . 'T' . $vevent['tend']);
  
  $bydays = array();
  foreach (explode(',', $vevent['byday']) as $d) $bydays[] = array('DAY' => $d);
  $vevent_ -> setProperty('RRULE', array(
    'FREQ' => $vevent['freq'],
    'INTERVAL' => $vevent['interval'],
    'UNTIL' => $term_end . 'T235960',
    'BYDAY' => $bydays,
  ));
 
  $vevent_ -> setProperty('SUMMARY', $vevent['summary']);
  $vevent_ -> setProperty('URL', $vevent['url']);
  $vevent_ -> setProperty('DESCRIPTION', $vevent['description'], array('ALTREP' => $vevent['description_altrep']));
  $vevent_ -> setProperty('LOCATION', $vevent['location'], array('ALTREP' => $vevent['location_altrep']));
  $vevent_ -> setProperty('ORGANIZER', $vevent['organizer_url'], array('CN' => $vevent['organizer_cn']));

  
}

function find_first_day($term_start, $byday) {
  global $dayabbrs;
  
  $days = explode(',', $byday);
  $first_day = '';
  
  foreach ($days as $i => $day) {
    $ymd = date('Ymd', strtotime('next ' . $dayabbrs[$day], strtotime($term_start)));
    if ($ymd >= $term_start && $ymd < $first_day || $first_day == '')
      $first_day = $ymd;
  }
      
  return $first_day;
}

$vcalendar -> returnCalendar();

?>