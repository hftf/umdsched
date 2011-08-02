<?php

function explodeDays($str) {
    $list = array('M' => 'Mo', 'W' => 'We', 'F' => 'Fr');
    return str_split(strtr($str, $list), 2);
}
function sectionToEvents($section, $course, $n = 0) {
    $events = array();

    if ($section->meetings) {
        foreach ($section->meetings as $meeting) {
            $meeting_days = explodeDays($meeting->days);
            $meeting_events = array();

            foreach ($meeting_days as $meeting_day)
                $meeting_events[] = array(
                    'id'       => $section->crn . '-' . $meeting_day,
                    'crn'      => $section->crn,
                    'n'        => $n,
                    'start'    => $meeting_day . ' ' . $meeting->time_start,
                    'end'      => $meeting_day . ' ' . $meeting->time_end,
                    'title'    => sectionDesc($meeting, $section, $course),
                    'readOnly' => true,
                );

            $events = array_merge($events, $meeting_events);
        }
    }
    return $events;
}


function sectionDesc($meeting, $section, $course) {
    $location = $meeting->location_bldg . (($meeting->location_room) ? ' ' . $meeting->location_room : '');
    if ($meeting->location_url)
        $location = '<a href="' . $meeting->location_url . '">' . $location . '</a>';

    $testudo_base_url = 'http://www.sis.umd.edu/bin/soc?term=201108';
    $course_url = $testudo_base_url . '&crs=' . $course->dept . $course->number;
    $section_url = $testudo_base_url . '&crs=' . $course->dept . $course->number . '&sec=' . $section->number;
    $str = '<span class="course-section"><a class="course-dept-num" href="' . $course_url . '">' . $course->dept . $course->number . '</a>&nbsp; <a class="section-num" href="' . $section_url . '">' . $section->number . (($meeting->type) ? ' <span class="meeting-type">' . $meeting->type . '</span>' : '') . '</a></span><br />' .
           '<abbr class="course-title" title="' . $course->title . '">' . $course->title . '</abbr><br />' . 
           '<span class="location">' . $location . '</span>';
    return $str;
}

?>