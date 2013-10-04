<?php

include 'inc/php/parallelcurl.php';
include 'models/models.php';
include 'str-utils.php';

  $time_start = microtime(1);
  $time_prev = $time_start;

class umd_api {
    //configuration settings
    // I'm an iPhone! Wheeeeee
    public static $user_agent = 'Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_0 like Mac OS X; en-us) AppleWebKit/532.9 (KHTML, like Gecko) Version/4.0.5 Mobile/8A293 Safari/6531.22.7';
    public static $schedule_base = 'https://ntst.umd.edu/soc/';
        
    public function __construct() {
        $this->curl = new ParallelCurl(10); // max number of outstanding fetches
        $this->curlResults = array();
    }
    
    public static function callback_name($format) {
        switch ($format) {
            case 'events':  return 'callback_events';
            case 'ics':     return 'callback_ics';
            case 'credits': return 'callback_credits';
            case 'default':
            case 'object':
            case '':
            default:        return '';
        }
    }

    public function get_url($url) {
        return file_get_contents($url);
    }
    
    // Where processing_func runs parse_html in its body
    private function get_url_async($url, $callback, $data) {
        //since('Before requesting '.$data->i);
        $this->curl->startRequest($url, $callback, $data);
        //since('After requesting '.$data->i);
    }

    private static function build_url($year, $term, $dept, $sec,   $sections = false) {
        $url = self::$schedule_base . 'search?termId=' . $year . $term . '&courseId' . ($sections ? 's' : '') . '=' . strtoupper($dept) . '&sectionId=' . $sec . '&_classDays=on'; // The last part is mysterious; omitting it causes a 500
        return $url;
    }
    /*
    public function get_schedule($year = null, $term = null, $dept = null, $sec = null) {
        //if no year was given, assume the current year
        if (!$year)
            $year = date('Y');

        //if no term is given, calculate the current term
        if (!$term)
            $term = $this->get_term();

        //form URL and call
        $url = $this->build_url($year, $term, $dept, $sec);
        $html = $this->get_url($url);
        
        return self::parse_html($html, $dept);
    }
    */
    public function get_schedule_async($data, $callback) {
        $year = $data->year; $term = $data->term; $dept = $data->dept; $sec = $data->sec;
    
        //if no year was given, assume the current year
        if (!$year)
            $year = date('Y');

        //if no term is given, calculate the current term
        if (!$term)
            $term = $this->get_term();

        //form URL and call
        $url = $this->build_url($year, $term, $dept, $sec);
        //$data->sections_url = $this->build_url($year, $term, $dept, $sec,   true);
        $this->get_url_async($url, $callback, $data);
    }
    
    public function parse_html_async($html, $url, $curl_handle, $data) {
        //since('After returning ' . $data->i);
        $schedule = self::parse_html($html, $data);
        //since('After parsing ' . $data->i);
        
        $callback_name = self::callback_name($data->format);
        if ($callback_name)
            call_user_func(array($this, $callback_name), $schedule, $data);
        else
            $this->curlResults[$data->i] = $schedule;
    }

/*    private function xpath_class($simpleXMLElement, $class, $one = true, $text = false) {
        $array = $simpleXMLElement->xpath('//*[@class="' . $class . '"]');
        if ($one) {
            if ($text)
                return trim($array[0][0]);
            return $array[0];
        }
        return $array;
    }*/

    private function parse_html($html, $data) {
        if (!$data->dept) {
            // Extract info from HTML
            $html_start = '<div id="course-prefixes-page" class="row">';
            $html_start_pos = strpos($html, $html_start) + strlen($html_start);
            $html_end = "</div>\r\n\r\n<script>";
            $html = substr($html, $html_start_pos, strpos($html, $html_end, $html_start_pos) - $html_start_pos);

            $depts = array();
            preg_match_all('#(?<=two columns">)([A-Z]{4})</span>\s+<span class="prefix-name nine columns">(.*?)</span>#si', $html, $depts_array);
            for ($i = 0; $i < count($depts_array[0]); $i ++) {
                $depts[$i] = new Department($depts_array[1][$i], $depts_array[2][$i]);
            }
            
            return $depts;
        }
        else {
            // Extract info from HTML
            $html_start = '<div id="courses-page" class="row">';
            $html_start_pos = strpos($html, $html_start);// + strlen($html_start);
            //$html_end = "</div>\r\n\r\n<script";
            $html_end = "\r\n\r\n<script";
            $html = substr($html, $html_start_pos , strpos($html, $html_end, $html_start_pos) - $html_start_pos);
            $html = str_replace('title="Bookstore"', 'title="Bookstore" /', $html); // Invalid XHTML >:(
            $html = preg_replace('#(prefacing-course-text.*?)<P>#', '\1', $html); // Invalid XHTML >:(
            $html = preg_replace('#& #', '&amp; ', $html); // Invalid XHTML >:(
            
            if (strpos($html, "No courses matched your search filters above") > -1)
                return null;


            $xml = simplexml_load_string($html);
            
            $course_prefix_xml = $xml->div; //'course-prefix-container'
            $dept_code = trim($xml->div->div[0]->div->div[0]->span); //'course-prefix-abbr'
            $dept_name = trim($xml->div->div[0]->div->div[1]->span[0]); //'course-prefix-name'
            $dept_url = trim($xml->div->div[0]->div->div[1]->span[1]->a['href']); //'course-prefix-link'

            //$courses_xml = $xml->xpath('//*[@class="courses-container"]/*[@class="course"]');
            $courses_xml = $xml->div->div[1]->xpath('div[@class="course"]');
            $courses = array();
            foreach ($courses_xml as $course_html) {
                $course_id = trim($course_html->div->div[0]->div); //'course-id'
                preg_match('#^(?P<dept>[A-Z]{4})(?P<number>[^\s]+?)$#', $course_id, $course_id_split);
                $course_title = trim($course_html->div->div[1]->div->div[0]->div[0]->span); //'course-title'
                $course_credits = trim($course_html->div->div[1]->div->div[1]->div[0]->div->span[1]); //'course-min-credits'

                // Ignoring grading method, Gen ed and CORE categories
                // There is also a "course-text" class for things like purchasing books, lab fees;
                // sometimes it contains the description we expect to find in "approved-course-text"
                // and it can be fancied up with markup like <hr>
                //$course_desc = $course_html->xpath('div/div[2]/div[@class="approved-course-texts-container"]/div/div/div[@class="approved-course-text"]');
                //$course_desc = $course_html->div->div[1]->div[1]->xpath('div/div/div');
                $course_desc = $course_html->xpath('//div[@class="approved-course-text"]');
                $divs = array();
                foreach ($course_desc as $div) $divs[] = trim($div);
                $course_desc = implode(' ', $course_desc); // implode("\n\n", ...)
                $course_url = self::$schedule_base . '?term=' . $data->year . $data->term . '&course=' . $course_id_split['dept'] . $course_id_split['number'];

                // Can be <span class="individual-instruction-message">Contact department for information to register for this course.</span>
                //$sections_xml = $course_html->xpath('div/div[2]/div[4]/div/fieldset//*[@class="sections-container"]//*[@class="section"]');
                $this_sectiondata_xml = $this->sectiondata_xml->xpath('div[@id="' . $course_id . '"]');
                $sections_xml = $this_sectiondata_xml[0]->div->div->div;
                //$sections_xml = $a->div->div->div->div;
                $sections = array();
                foreach ($sections_xml as $section_html) {
                    $section_id = trim($section_html->div[0]->div->div[0]->span); //'section-id'
                    if ($data->sec && $data->sec != $section_id) // Since we can't get only one section from the same endpoint as getting all the sections
                        continue;

                    // Sadly, they got rid of crn

                    // Can be <span class="section-instructor">Instructor: TBA</span>
                    // //*[@class="section-instructors"]/*[@class="section-instructor"]
                    $instructors_arr = $section_html->div[0]->div->div[1]->span->span;
                    $instructors = array();
                    foreach ($instructors_arr as $span) {
                        if ($span->a) { // Not sure if instructor URLs are used anymore, but they are at least still present in older terms
                            $instructor_name = trim($span->a);
                            $instructor_url = trim($span->a['href']);
                        }
                        else {
                            $instructor_name = trim($span);
                            $instructor_url = null;
                        }
                        $instructors[] = new Instructor($instructor_name, $instructor_url);
                    }
                    $section_url = $course_url . '&section=' . $section_id; // Not sure about this anymore

                    $seats    = trim($section_html->div[0]->div->div[2]->div->span[1]->span[0]->span[1]); //'total-seats-count'
                    $open     = trim($section_html->div[0]->div->div[2]->div->span[1]->span[1]->span[1]); //'open-seats-count'
                    $waitlist = trim($section_html->div[0]->div->div[2]->div->span[1]->span[2]->span[1]); //'waitlist-count'
                    $status_str = ($open <= 0 ? 'FULL: ' : '') . "Seats=$seats, Open=$open, Waitlist=$waitlist";
                    $status = new Status($seats, $open, $waitlist, $status_str);
                    
                    //xpath('div/div[3]//*[@class="class-days-container"]//*[@class="row"]'); // Don't like the "row" here
                    $meetings_xml = $section_html->div[1]->div;
                    $meetings = array();
                    foreach ($meetings_xml as $meeting_html) {
                        // Can be <span class="section-days">TBA</span>, in which case the time start and time end are missing
                        // Can also be <div class="class-message">Contact department or instructor for details</div>
                        $meeting_days = trim($meeting_html->div[0]->span[0]); //'section-days'
                        $time_start   = trim($meeting_html->div[0]->span[1]); //'class-start-time'
                        $time_end     = trim($meeting_html->div[0]->span[2]); //'class-end-time'

                        // This is not always here; can be "Lab" "Discussion"
                        if ($meeting_html->div[2]) {
                            $meeting_type = trim($meeting_html->div[2]->span); //'class-type'
                        }

                        $location_url = trim($meeting_html->div[1]->span->a['href']); //'class-building'
                        $location_bldg = trim($meeting_html->div[1]->span->a->span); //'building-code'
                        $location_room = trim($meeting_html->div[1]->span->span); //'class-room'

                        $meeting = new Meeting($meeting_days, $time_start, $time_end, $location_url, $location_bldg, $location_room, $meeting_type);
                        $meetings[] = $meeting;
                    }
                    
                    $section = new Section($section_id, /*null,*/ $status, $section_url, $instructors, $meetings);
                    $sections[] = $section;
                }

                // note: didn't use course desc before redesign (was null)
                $course = new Course($course_id_split['dept'], $course_id_split['number'], $course_title, $course_desc, $course_url, $course_credits, $sections);
                $courses[] = $course;
            }

            $dept = new Department($dept_code, $dept_name, $courses, $dept_url); //, null, null);
            return $dept;
        }
    }
    
    public function get_schedules($year, $term, $requests, $format) {
        return $this->get_schedules_async($year, $term, $requests, $format);
        /*
        $schedules = array();
        if (empty($requests))
            return $schedules;
        
        foreach ($requests as $i => $request) {
            $year = isset($request->year) ? $request->year : ($year) ? $year : null;
            $term = isset($request->term) ? $request->term : ($term) ? $term : null;
            $dept = isset($request->dept) ? $request->dept : null;
            $sec  = isset($request->sec)  ? $request->sec  : null;
            
            $new_schedule = $this->get_schedule($year, $term, $dept, $sec);

            if ($format == 'events')
                $new_schedule = sectionToEvents($new_schedule->courses[0]->sections[0], $new_schedule->courses[0], $i / count($requests));
            else if ($format == 'ics')
                $new_schedule = sectionTovEvents($new_schedule->courses[0]->sections[0], $new_schedule->courses[0]);
            else
                $new_schedule = array($new_schedule);
            
            $schedules = array_merge($schedules, $new_schedule);
        }
        return $schedules;
        */
    }

    public function get_schedules_async($year, $term, $requests, $format = null) {
        if (empty($requests))
            return array();
        
        if (!$year)
            $year = date('Y');
        if (!$term)
            $term = $this->get_term();
        
        $n = count($requests);
        $depts = array();
        $sectiondata_url = self::$schedule_base . $year . $term . '/sections?';
        foreach ($requests as $i => $request) {
            if (!isset($request->year)) $request->year = ($year) ? $year : null;
            if (!isset($request->term)) $request->term = ($term) ? $term : null;
            if (!isset($request->dept)) $request->dept = null;
            if (!isset($request->sec))  $request->sec  = null;
            
            $request->i = $i;
            $request->n = $n;
            $request->format = $format;

            $dept = substr($request->dept, 0, 4);
            if (!isset($depts[$dept]))
                $depts[$dept] = count($depts);

            $sectiondata_url .= '&courseIds=' . strtoupper($request->dept);
        }

        //since('Before fetching sectiondata');
        $sectiondata_html = self::get_url($sectiondata_url);

        //since('After fetching sectiondata');
        $sectiondata_html = str_replace('title="Bookstore"', 'title="Bookstore" /', $sectiondata_html); // Invalid XHTML >:(
        $sectiondata_html = str_replace('<hr>', '<hr />', $sectiondata_html); // Invalid XHTML >:(
        $this->sectiondata_xml = simplexml_load_string($sectiondata_html);
        //since('After parsing sectiondata');
        
        foreach ($requests as $i => $request) {
            
            $dept = substr($request->dept, 0, 4);
            $request->di = $depts[$dept];
            $request->dn = count($depts);
            
            //since('Before sending ' . $i);
            $this->get_schedule_async($request, array($this, 'parse_html_async'));
            //since('After sending ' . $i);
        }
        
        //since('Waiting');
        $this->curl->finishAllRequests();
        //since('Done waiting');
        
        return $this->curlResults;
    }
    
    public function callback_events($schedule, $data) {
        $schedule = sectionToEvents($schedule->courses[0]->sections[0], $schedule->courses[0], $data->di/$data->dn, $data->year, $data->term); //, $data->i / $data->n);
        $this->curlResults = array_merge($this->curlResults, $schedule);
    }
    public function callback_ics($schedule, $data) {
        $schedule = sectionTovEvents($schedule->courses[0]->sections[0], $schedule->courses[0]);
        $this->curlResults = array_merge($this->curlResults, $schedule);
    }
    public function callback_credits($schedule, $data) {
        $schedule->data = $data;
        $this->curlResults[] = $schedule;
    }
    

    public static function get_term() {
        //get the current month as 01-12
        $m = date('m');

        if ($m < 5)       return '01'; // JFMA
        else if ($m < 7)  return '06'; // MJ
        else if ($m < 12) return '08'; // JASON
        else              return '12'; // D
    }
}

/*if (!function_exists('since')) {function //since($desc) {}}
if (!function_exists('since')) {function //since($desc) {
      global $time_start, $time_prev;
      $time_now = microtime(1);
      echo '<p>Since start: ' . number_format($time_now - $time_start, 4) . '; since previous: ' . number_format($time_now - $time_prev, 4) . ' &mdash; ' . $desc . '</p>';
      $time_prev = $time_now;
  } }*/
?>
