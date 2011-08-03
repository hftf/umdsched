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
    $location = $meeting->location->bldg . (($meeting->location->room) ? ' ' . $meeting->location->room : '');
    if ($meeting->location->url)
        $location = '<a href="' . $meeting->location->url . '">' . $location . '</a>';
    $course_ = $course->dept . $course->number;
    if ($course->url)
        $course_ = '<a class="course-dept-num" href="' . $course->url . '">' . $course_ . '</a>';
    $section_ = $section->number;
    if ($section->url)
        $section_ = '<a href="' . $section->url . '">' . $section_ . '</a>';
    $status = ($section->status->open != 0) ? ($section->status->seats - $section->status->open) . '/' . $section->status->seats : 'Full (' . $section->status->waitlist . ')';

    $str = '<span class="course-section">' . $course_ . '&nbsp; <span class="section-num">' . $section_ . (($meeting->type) ? ' <span class="meeting-type">' . $meeting->type . '</span>' : '') . '</span></span><br />' .
           '<abbr class="course-title" title="' . $course->title . '">' . $course->title . '</abbr><br />' . 
           '<span class="location">' . $location . '</span> <span class="status">' . $status . '</span>';
    return $str;
}

?>