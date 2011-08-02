<?php

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
                preg_match('#<b>([A-Z]{4})([^ ]+?) ?</b>.*?\n<b>(.*?);</b>\n<b> ?\((.*?) credits?\)</b>\n#si', $course_intro_html, $course_intro_array);
                
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
                        
                        $section = new Section($section_intro_array[1], $section_intro_array[2], $section_intro_array[4], str_replace("\n", ' ', $section_intro_array[3]), $meetings);
                        $sections[] = $section;
                    }
                }

                $course = new Course($course_intro_array[1], $course_intro_array[2], str_replace("\n", ' ', $course_intro_array[3]), $course_intro_array[4], $sections);
                $courses[] = $course;
            }

            $dept = new Department($intro_array[3], $intro_array[4], $courses, $intro_array[2], $intro_array[7], $intro_array[6]);
            return $dept;
        }
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

class Department {
    public $url;
    public $code;
    public $name;
    public $college_name; // Combine?
    public $college_url;
    
    public $courses;
    
    public function __construct($code, $name, $courses = null, $url = null, $college_name = null, $college_url = null) {
        $this->url = $url;
        $this->code = $code;
        $this->name = $name;
        $this->college_name = $college_name;
        $this->college_url = $college_url;
        $this->courses = $courses;
    }
}

class Course {
    public $dept;
    public $number;
    public $title;
    public $credits;
    
    public $sections;
    
    public function __construct($dept, $number, $title, $credits, $sections = null) {
        $this->dept = $dept;
        $this->number = $number;
        $this->title = $title;
        $this->credits = $credits;
        $this->sections = $sections;
    }
}

class Section {
    public $number;
    public $crn;
    public $status;
    public $instructor;
    //public $instructor_name; // Combine?
    //public $instructor_url;
    
    public $meetings;
    
    public function __construct($number, $crn, $status, $instructor, $meetings = null) {
        $this->number = $number;
        $this->crn = $crn;
        $this->status = $status;
        $this->instructor = $instructor;
        //$this->instructor_name = $instructor_name;
        //$this->instructor_url = $instructor_url;
        $this->meetings = $meetings;
    }
}

class Meeting {
    public $days;
    public $time_start;
    public $time_end;
    public $type;
    public $location_url; // Combine?
    public $location_bldg;
    public $location_room;
    
    public function __construct($days, $time_start, $time_end, $location_url, $location_bldg, $location_room, $type) {
        $this->days = $days;
        $this->time_start = $time_start;
        $this->time_end = $time_end;
        $this->location_url = $location_url;
        $this->location_bldg = $location_bldg;
        $this->location_room = $location_room;
        $this->type = $type;
    }
}
?>