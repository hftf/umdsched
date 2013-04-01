/*String.prototype.chunk = function(n) {
    if (typeof n == 'undefined') n = 2;
    return this.match(RegExp('.{1,'+n+'}', 'g'));
};
String.prototype.replaceList = function(list) {
    var str = String(this);
    for (var c in list)
        str = str.replace(new RegExp(c, 'g'), list[c]);
    return str;
};
function explodeDays(str) {
    var list = {'M': 'Mo', 'W': 'We', 'F': 'Fr'};
    return str.replaceList(list).chunk(2);
}*/
/*function sectionToEvents(section, course) {
    var events = [];

    for (var m = 0; m < section.meetings.length; m ++) {
        var meeting = section.meetings[m],
            meeting_days = explodeDays(meeting.days),
            meeting_events = [];

        for (var i = 0; i < meeting_days.length; i ++)
            meeting_events.push({
                id:       section.crn + '-' + meeting_days[i],
                start:    Date.parse(meeting_days[i] + ' ' + meeting.time_start),
                end:      Date.parse(meeting_days[i] + ' ' + meeting.time_end),
                title:    course.title,
                readOnly: true,
            });

        events = events.concat(meeting_events);
    }
    return events;
}*/
/*function callAPI(dept, sec) {
    var jqXHR = $.get('umd-rest.php', { model: 'course', format: 'events', data: { 'dept': dept, 'sec': sec }}, function(dept, textStatus, jqXHR) {
        // Assuming only one result
        if (dept != null && !$.isArray(dept)) {
            var events = sectionToEvents(dept.courses[0].sections[0], dept.courses[0]);
        }
    }, 'json');
}*/