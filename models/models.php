<?php

class College {
    var $name;
    var $url;
    
    function __construct($name, $url) {
        $this->name = $name;
        $this->url = $url;
    }
}

class Department {
    var $name;
    var $url;
    var $code;
    var $college;
    
    var $courses;
    
    function __construct($code, $name, $courses = null, $url = null, $college_name = null, $college_url = null) {
        $this->url = $url;
        $this->code = $code;
        $this->name = $name;
        $this->college = new College($college_name, $college_url);
        $this->courses = $courses;
    }
}

class Course {
    var $dept;
    var $number;
    var $title;
    var $desc;
    var $url;
    var $credits;
    
    var $sections;
    
    function __construct($dept, $number, $title, $desc = null, $url, $credits, $sections = null) {
        $this->dept = $dept;
        $this->number = $number;
        $this->title = $title;
        $this->credits = $credits;
        $this->url = $url;
        $this->sections = $sections;
    }
}

class Instructor {
    var $name;
    var $url;
    
    function __construct($name, $url) {
        $this->name = $name;
        $this->url = $url;
    }
}

class Status {
    var $seats;
    var $open;
    var $waitlist;
    var $orig_string;
    
    function __construct($seats, $open, $waitlist, $orig_string = null) {
        $this->seats = $seats;
        $this->open = $open;
        $this->waitlist = $waitlist;
        $this->orig_string = $orig_string;
    }
}

class Section {
    var $number;
    var $crn;
    var $status;
    var $url;
    
    var $instructors;
    var $meetings;
    
    function __construct($number, $crn, $status, $url, $instructor_name, $instructor_url, $meetings = null) {
        $this->number = $number;
        $this->crn = $crn;
        $this->status = $status;
        $this->url = $url;
        // TODO: Add support for multiple instructors
        $this->instructors = array(new Instructor($instructor_name, $instructor_url));
        $this->meetings = $meetings;
    }
}

class Location {
    var $bldg;
    var $room;
    var $url;
    
    function __construct($bldg, $room, $url) {
        $this->bldg = $bldg;
        $this->room = $room;
        $this->url = $url;
    }
}

class Meeting {
    var $days;
    var $time_start;
    var $time_end;
    var $type;
    var $location;
    
    function __construct($days, $time_start, $time_end, $location_url, $location_bldg, $location_room, $type) {
        $this->days = $days;
        $this->time_start = $time_start;
        $this->time_end = $time_end;
        $this->location = new Location($location_bldg, $location_room, $location_url);
        $this->type = $type;
    }
}