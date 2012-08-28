<?php

include 'models/models.php';
include 'str-utils.php';

class umd_api {
    public function __construct() {
        //configuration settings
        $this->user_agent = 'Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_0 like Mac OS X; en-us) AppleWebKit/532.9 (KHTML, like Gecko) Version/4.0.5 Mobile/8A293 Safari/6531.22.7';
        $this->maps_base = 'http://www.umd.edu/CampusMaps/bld_detail.cfm';
        $this->schedule_base = 'http://www.sis.umd.edu/bin/soc';
    }

    public function get_url($url) {
        //prefer the WP HTTP API to allow for caching and user agent spoofing, fall back if necessary
        if ( function_exists( 'wp_remote_get') )
            $data = wp_remote_retrieve_body( wp_remote_get($url, array('user-agent' => $this->user_agent) ) );
        else
            $data = file_get_contents($url);

        return $data;

        //parse the XML into a PHP object
        $xml = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);

        return $xml;
    }

    public function get_map($category = 'categories') {
        return $this->get_url($this->maps_base . $category . '.xml');
    }

    public function get_schedule($year = null, $term = null, $dept = null, $sec = null) {
        //if no year was given, assume the current year
        if (!$year)
            $year = date('Y');

        //if no term is given, calculate the current term
        if (!$term)
            $term = $this->get_term();

        //form URL and call
        $url = $this->schedule_base . '?term=' . $year . $term . '&crs=' . $dept . '&sec=' . $sec;
        $html = $this->get_url($url);

        if (!$dept) {
            // Extract info from HTML
            $html_start = '<font color=maroon size=+1><b>Departments</b></font><BR><BR><table>';
            $html_start_pos = strpos($html, $html_start) + strlen($html_start);
            $html_end = '</table';
            $html = substr($html, $html_start_pos , strpos($html, $html_end, $html_start_pos) - $html_start_pos);

            $depts = array();
            preg_match_all('#(?<=&crs=[A-Z]{4}>)([A-Z]{4})</a> (.*?)<br>#si', $html, $depts_array);
            for ($i = 0; $i < count($depts_array[0]); $i ++) {
                $depts[$i] = new Department($depts_array[1][$i], $depts_array[2][$i]);
            }
            
            return $depts;
        }
        else {
            // Extract info from HTML
            $html_start = "</table>\n<hr size=1>\n</center>";
            $html_start_pos = strpos($html, $html_start) + strlen($html_start);
            $html_end = "<hr size=1>\n<center>\n<table>";
            $html = substr($html, $html_start_pos , strpos($html, $html_end, $html_start_pos) - $html_start_pos);
            
            if (strpos($html, "' Not Found") > -1 || strpos($html, 'Choose from  a category below:') > -1)
                return null;
            
            $intro_end = '</h2>';
            $intro_end_pos = strpos($html, $intro_end) + strlen($intro_end);
            $intro_html = substr($html, 0, $intro_end_pos);
            preg_match('#(<a href = "(.*?)">)?\n<h2>([A-Z]{4}) (.*?)(</a>)? \(\n<a href = "(.*?)">\n(.*?)</a>\)#', $intro_html, $intro_array);
            
            $course_delimiter = '<font face="arial,helvetica" size=-1>' . "\n";
            $courses_start_pos = strpos($html, $course_delimiter, $intro_end_pos) + strlen($course_delimiter);
            $html = substr($html, $courses_start_pos, strlen($html) - $courses_start_pos);
            $courses_html = explode($course_delimiter, $html);
            
            $courses = array();
            foreach ($courses_html as $course_html) {
                //echo "<hr>\n[", $course_html, ']';
                
                $course_intro_end = "</font>\n<br>\n";
                $course_intro_end_pos = strpos($course_html, $course_intro_end) + strlen($course_intro_end);
                $course_intro_html = substr($course_html, 0, $course_intro_end_pos);
                preg_match('#<b>([A-Z]{4})([^\s]+?) ?</b>.*?\n<b>(.*?);</b>\n<b> ?\((.*?) credits?\)</b>\n#si', $course_intro_html, $course_intro_array);
                
                $course_url = $this->schedule_base . '?term=' . $year . $term . '&crs=' . $course_intro_array[1] . $course_intro_array[2];
                
                $section_delimiter = "<dl>"; // "*" appears directly after
                $section_delimiter_pos = strpos($course_html, $section_delimiter, $course_intro_end_pos);
                
                $sections = array();
                if ($section_delimiter_pos > -1) {
                    $sections_start_pos = $section_delimiter_pos + strlen($section_delimiter);
                    $course_html = substr($course_html, $sections_start_pos, strlen($course_html) - $sections_start_pos);
                    $sections_html = explode($section_delimiter, $course_html);
                    
                    foreach ($sections_html as $section_html) {
                        $section_intro_end = "Books</a>\n";
                        $section_intro_end_pos = strpos($section_html, $section_intro_end) + strlen($section_intro_end);
                        $section_intro_html = substr($section_html, 0, $section_intro_end_pos);
                        preg_match('#\n(.+)\((.+)\)\n(.*?) \((.*?)\)#si', $section_intro_html, $section_intro_array);
                        
                        $section_url = $course_url . '&sec=' . $section_intro_array[1];
                        preg_match('#(FULL: )?Seats=(\d+), Open=(\d+), Waitlist=(\d+)#si', $section_intro_array[4], $status_array);
                        $status = new Status($status_array[2], $status_array[3], $status_array[4], $section_intro_array[4]);
                        // TODO: Add support for multiple instructors
                        preg_match('#(<a href = "(.*?)">\s*)?(.*?)(</a>)?$#si', $section_intro_array[3], $instructor_array);
                        
                        $meeting_delimiter = '<dd>';
                        $meeting_delimiter_pos = strpos($section_html, $meeting_delimiter, $section_intro_end_pos);
                        
                        $meetings = array();
                        if ($meeting_delimiter_pos > -1) {
                            $meetings_start_pos = $meeting_delimiter_pos + strlen($meeting_delimiter);
                            $section_html = substr($section_html, $meetings_start_pos, strlen($section_html) - $meetings_start_pos);
                            $meetings_html = explode($meeting_delimiter, $section_html);
                            
                            foreach ($meetings_html as $meeting_html) {
                                preg_match('#([A-Za-z]*?)\.*? ?([0-9amp:]{6,7})- ?([0-9amp:]{6,7}) \((.*?)\) ?(.*?)</dd>#', $meeting_html, $meeting_array);
                                if (!empty($meeting_array)) {
                                    preg_match('#<a href="(.*?)">([A-Z]+?)</a> (.*?)$#si', $meeting_array[4], $location);
                                    if (empty($location))
                                        $location = array(1 => null, $meeting_array[4], null);
                                    $meeting = new Meeting($meeting_array[1], $meeting_array[2], $meeting_array[3], $location[1], $location[2], $location[3], $meeting_array[5]);
                                    $meetings[] = $meeting;
                                }
                            }
                        }
                        
                        $section = new Section($section_intro_array[1], $section_intro_array[2], $status, $section_url, $instructor_array[3], $instructor_array[2], $meetings);
                        $sections[] = $section;
                    }
                }

                $course = new Course($course_intro_array[1], $course_intro_array[2], str_replace("\n", ' ', $course_intro_array[3]), null, $course_url, $course_intro_array[4], $sections);
                $courses[] = $course;
            }

            $dept = new Department($intro_array[3], $intro_array[4], $courses, $intro_array[2], $intro_array[7], $intro_array[6]);
            return $dept;
        }
    }
    
    public function get_schedules($year, $term, $requests, $format) {
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
    }

    public function get_term() {
        //get the current month as 01-12
        $m = date('m');

        if ($m < 5)       return '01'; // JFMA
        else if ($m < 7)  return '06'; // MJ
        else if ($m < 12) return '08'; // JASON
        else              return '12'; // D
    }
}

?>