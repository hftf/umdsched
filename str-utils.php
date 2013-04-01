<?php

function explodeDays($str) {
    $list = array('M' => 'Mo', 'W' => 'We', 'F' => 'Fr');
    return str_split(strtr($str, $list), 2);
}
function sectionToEvents($section, $course, $n = 0,  $year, $term) {
    $events = array();

    if ($section->meetings) {
        foreach ($section->meetings as $meeting) {
            $meeting_days = explodeDays($meeting->days);
            $meeting_events = array();

            foreach ($meeting_days as $meeting_day)
                $meeting_events[] = array(
                  //'id'       => $section->crn . '-' . $meeting_day,
                    'id'       => $course->dept . $course->number . '-' . $section->number . '-' . $meeting_day,
                  //'crn'      => $section->crn,
                    'n'        => $n,
                    'start'    => $meeting_day . ' ' . $meeting->time_start,
                    'end'      => $meeting_day . ' ' . $meeting->time_end,
                    'title'    => sectionDesc($meeting, $section, $course,  $year, $term),
                    'readOnly' => true,
                );

            $events = array_merge($events, $meeting_events);
        }
    }
    return $events;
}


function sectionDesc($meeting, $section, $course,  $year, $term) {
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
    $status = '<a href="waitlist-check.php?year=' . $year . '&amp;term=' . $term . '&amp;sched1=' . $course->dept . $course->number . '%20' . $section->number . '"><span class="status">' . $status . '</span></a>';

    $str = '<span class="course-section">' . $course_ . '&nbsp; <span class="section-num">' . $section_ . (($meeting->type) ? ' <span class="meeting-type">' . $meeting->type . '</span>' : '') . '</span></span><br />' .
           '<abbr class="course-title" title="' . $course->title . '">' . $course->title . '</abbr><br />' . 
           '<span class="location">' . $location . '</span> <span class="status">' . $status . '</span>';
    return $str;
}


function sectionTovEvents($section, $course) {
    $vevents = array();

    if ($section->meetings) {
        $i = 1;
        $ids = array();
        foreach ($section->meetings as $meeting) {
            //$id = $section->crn . '.' . $i . '-' . $meeting->days;
            $id = $course->dept . $course->number . '-' . $section->number . '.' . $i . '-' . $meeting->days;
            $ids[$i] = $id;
            $meeting_days = strtoupper(implode(',', explodeDays($meeting->days)));
            $meeting_events = array(
              'id'          => $id, /**/
              'tstart'      => date('His', strtotime($meeting->time_start)), /**/
              'tend'        => date('His', strtotime($meeting->time_end)), /**/
              
              'freq'        => 'WEEKLY',
              'interval'    => 1,
              'until'       => null, /**/
              'byday'       => $meeting_days,
              
              'summary'     => $course->dept . $course->number . ' – ' . $course->title,
              'url'         => $course->url,
              'description' => 'Section: ' . $section->number . ' ' . $meeting->type . "\n" . 'Instructor: ' . $section->instructors[0]->name,
              'description_altrep' => $section->url,
              'location'    => $meeting->location->bldg . ' ' . $meeting->location->room,
              'location_altrep' => $meeting->location->url,
              
              // No API support for multiple instructors yet
              'organizer_cn' => $section->instructors[0]->name,
              'organizer_url' => $section->instructors[0]->url,
              
              'ids'          => &$ids,
            );
            $vevents[] = $meeting_events;
            $i ++;
        }
    }
    return $vevents;
}

?>