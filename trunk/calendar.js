$(document).ready(function() {
    $(sample_schedules).each(function(i, v) {
      //$('#sample_schedules').append('<dt>' + v.name + '</dt><dd><a href="#" id="sample_schedule_' + v.id + '">' + v.sched + '</a></dd>');
      $('#sample_schedules').append($('<li />').append($('<a href="#" id="sample_schedule_' + v.id + '" title="' + v.sched + '">' + v.name + '</a>').data('sched', v.sched)));
    });
    //$('#sched1').addTag(document.getElementById('sample_schedule_Ophir').text);
    
    var urlParams = initUrlParams(), loadSched = $('#sample_schedule_Ophir').data('sched');
    if (urlParams['s']) // comma-separated sections
      loadSched = urlParams['s'];
    else if (urlParams['v']) // comma-separated encoded sections
      loadSched = hash2secs(urlParams['v']);
    if (urlParams['y']) // year
        $('#year_select').val(urlParams['y']);
    if (urlParams['t']) // term
        $('#term_select').val(urlParams['t']);
    $('#sched1').addTag(loadSched);

    var $calendar = $('#calendar');
    var id = 10;

    $calendar.weekCalendar({
        displayOddEven: true,
        timeslotHeight: 7,
        timeslotsPerHour: 12,
        allowCalEventOverlap: true,
        overlapEventsSeparate: true,
        allowEventCreation: false,
        timeFormat: 'g:i',
        timeSeparator: ' &ndash; ',
        firstDayOfWeek: 0,
        businessHours: {start: 8, end: 22, limitDisplay: true},
        minDate: Date.mon(),
        maxDate: Date.fri(),
        firstDayOfWeek: 1,
        daysToShow: 5,
        buttons: false,
        //switchDisplay: {'1 day': 1, '3 next days': 3, 'work week': 5, 'full week': 7},
        title: function(daysToShow) {
            //return daysToShow == 1 ? '%date%' : '%start% - %end%';
            return 'My schedule';
        },
        getHeaderDate: function(date) {
            var dayName = this.useShortDayNames ? this.shortDays[date.getDay()] : this.longDays[date.getDay()];
            return dayName; // + (options.headerSeparator) + this._formatDate(date, options.dateFormat);
        },
        height: function($calendar) {
            return $(window).height() /*- $("h1").outerHeight()*/ - 1;
        },
        eventRender: function(calEvent, $event) {
            //var hue = (Math.pow(calEvent.crn, 2) + 270) % 360;
            var hue = (calEvent.n * 360 + 30) % 360;
            $event.css({'backgroundColor': 'hsla(' + hue + ', 100%, 65%, 0.6)', 'border': '1px solid hsla(' + hue + ', 80%, 45%, 0.6)' });
            $event.find('.wc-time').css({'backgroundColor': 'hsla(' + hue + ', 80%, 35%, 0.6)', 'border': '1px solid hsla(' + hue + ', 70%, 25%, 0.6)' });
            
            if (false && calEvent.end.getTime() < new Date().getTime()) {
                $event.css("backgroundColor", "#aaa");
                $event.find(".wc-time").css({
                    "backgroundColor": "#999",
                    "border": "1px solid #888"
                });
            }
        },
        draggable: function(calEvent, $event) {
            return calEvent.readOnly != true;
        },
        resizable: function(calEvent, $event) {
            return calEvent.readOnly != true;
        },
        eventNew: function(calEvent, $event) {
            var $dialogContent = $("#event_edit_container");
            resetForm($dialogContent);
            var startField = $dialogContent.find("select[name='start']").val(calEvent.start);
            var endField = $dialogContent.find("select[name='end']").val(calEvent.end);
            var titleField = $dialogContent.find("input[name='title']");
            var bodyField = $dialogContent.find("textarea[name='body']");


            $dialogContent.dialog({
                modal: true,
                title: "New Calendar Event",
                close: function() {
                    $dialogContent.dialog("destroy");
                    $dialogContent.hide();
                    $('#calendar').weekCalendar("removeUnsavedEvents");
                },
                buttons: {
                    save: function() {
                        calEvent.id = id;
                        id++;
                        calEvent.start = new Date(startField.val());
                        calEvent.end = new Date(endField.val());
                        calEvent.title = titleField.val();
                        calEvent.body = bodyField.val();

                        $calendar.weekCalendar("removeUnsavedEvents");
                        $calendar.weekCalendar("updateEvent", calEvent);
                        $dialogContent.dialog("close");
                    },
                    cancel: function() {
                        $dialogContent.dialog("close");
                    }
                }
            }).show();

            $dialogContent.find(".date_holder").text($calendar.weekCalendar("formatDate", calEvent.start));
            setupStartAndEndTimeFields(startField, endField, calEvent, $calendar.weekCalendar("getTimeslotTimes", calEvent.start));

        },
        eventDrop: function(calEvent, $event) {
          
        },
        eventResize: function(calEvent, $event) {
        },
        eventClick: function(calEvent, $event) {

            if (calEvent.readOnly) {
                return;
            }

            var $dialogContent = $("#event_edit_container");
            resetForm($dialogContent);
            var startField = $dialogContent.find("select[name='start']").val(calEvent.start);
            var endField = $dialogContent.find("select[name='end']").val(calEvent.end);
            var titleField = $dialogContent.find("input[name='title']").val(calEvent.title);
            var bodyField = $dialogContent.find("textarea[name='body']");
            bodyField.val(calEvent.body);

            $dialogContent.dialog({
                modal: true,
                title: "Edit - " + calEvent.title,
                close: function() {
                    $dialogContent.dialog("destroy");
                    $dialogContent.hide();
                    $('#calendar').weekCalendar("removeUnsavedEvents");
                },
                buttons: {
                    save: function() {

                        calEvent.start = new Date(startField.val());
                        calEvent.end = new Date(endField.val());
                        calEvent.title = titleField.val();
                        calEvent.body = bodyField.val();

                        $calendar.weekCalendar("updateEvent", calEvent);
                        $dialogContent.dialog("close");
                    },
                    "delete": function() {
                        $calendar.weekCalendar("removeEvent", calEvent.id);
                        $dialogContent.dialog("close");
                    },
                    cancel: function() {
                        $dialogContent.dialog("close");
                    }
                }
            }).show();

            var startField = $dialogContent.find("select[name='start']").val(calEvent.start);
            var endField = $dialogContent.find("select[name='end']").val(calEvent.end);
            $dialogContent.find(".date_holder").text($calendar.weekCalendar("formatDate", calEvent.start));
            setupStartAndEndTimeFields(startField, endField, calEvent, $calendar.weekCalendar("getTimeslotTimes", calEvent.start));
            $(window).resize().resize(); //fixes a bug in modal overlay size ??

        },
        eventMouseover: function(calEvent, $event) {
        },
        eventMouseout: function(calEvent, $event) {
        },
        noEvents: function() {

        },
        /*
        data: function(start, end, callback) {
            callback(getEventData());
        }
        */
        jsonOptions: function($calendar) {
            var options = getJsonOptions();
            options.model = 'course';
            options.format = 'events';
            return options;
        },
        data: 'umd-rest.php', // See line 1151 in jquery.weekcalendar.js for implementation
        calendarAfterLoad: function(calendar) {
            var events = calendar.weekCalendar('serializeEvents');
            var eventEnds = $(events).map(function(i, v) { var d = v.end; return Math.ceil(d.getHours()+d.getMinutes()/60); }).toArray();
            var lastEventEnd = Math.max.apply(Math, eventEnds);
            var lastBusinessHour = Math.min(18, lastEventEnd);
            this.businessHours.end = lastBusinessHour;
        },
    });

    function resetForm($dialogContent) {
        $dialogContent.find("input").val("");
        $dialogContent.find("textarea").val("");
    }


    /*
     * Sets up the start and end time fields in the calendar event
     * form for editing based on the calendar event being edited
     */
    function setupStartAndEndTimeFields($startTimeField, $endTimeField, calEvent, timeslotTimes) {

        $startTimeField.empty();
        $endTimeField.empty();

        for (var i = 0; i < timeslotTimes.length; i++) {
            var startTime = timeslotTimes[i].start;
            var endTime = timeslotTimes[i].end;
            var startSelected = "";
            if (startTime.getTime() === calEvent.start.getTime()) {
                startSelected = "selected=\"selected\"";
            }
            var endSelected = "";
            if (endTime.getTime() === calEvent.end.getTime()) {
                endSelected = "selected=\"selected\"";
            }
            $startTimeField.append("<option value=\"" + startTime + "\" " + startSelected + ">" + timeslotTimes[i].startFormatted + "</option>");
            $endTimeField.append("<option value=\"" + endTime + "\" " + endSelected + ">" + timeslotTimes[i].endFormatted + "</option>");

            $timestampsOfOptions.start[timeslotTimes[i].startFormatted] = startTime.getTime();
            $timestampsOfOptions.end[timeslotTimes[i].endFormatted] = endTime.getTime();

        }
        $endTimeOptions = $endTimeField.find("option");
        $startTimeField.trigger("change");
    }

    var $endTimeField = $("select[name='end']");
    var $endTimeOptions = $endTimeField.find("option");
    var $timestampsOfOptions = {start:[],end:[]};

    //reduces the end time options to be only after the start time options.
    $("select[name='start']").change(function() {
        var startTime = $timestampsOfOptions.start[$(this).find(":selected").text()];
        var currentEndTime = $endTimeField.find("option:selected").val();
        $endTimeField.html(
                $endTimeOptions.filter(function() {
                    return startTime < $timestampsOfOptions.end[$(this).text()];
                })
                );

        var endTimeSelected = false;
        $endTimeField.find("option").each(function() {
            if ($(this).val() === currentEndTime) {
                $(this).attr("selected", "selected");
                endTimeSelected = true;
                return false;
            }
        });

        if (!endTimeSelected) {
            //automatically select an end date 2 slots away.
            $endTimeField.find("option:eq(1)").attr("selected", "selected");
        }

    });


    var $edit_schedules = $("#edit_schedules");
    $('#refresh_calendar_button').click(function() {
        //$('<div id="refresh_dialog" style="text-align: center;"><br /><br /><img src="ajax-loader.gif" /></div>').dialog({ title: 'Loading&hellip;', modal: true });
        $calendar.weekCalendar('refresh');
    });
    $('#empty_schedule_button').click(function() {
        $('#sched1').data('list').find('li').click();
        $('#refresh_calendar_button').click();
    });
    $('#serialize_schedule_button').click(function() {
        var secs = $('input[name="sched1[]"][type="hidden"]').map(function(i, input) { return input.value; }).toArray().join(', ');
        var year = $('#year_select').val();
        var term = $('#term_select').val();
        var hash = secs2hash(secs), urlv = 'http://ophir.li/umd/calendar.html?y=' + year + '&t=' + term + '&v=' + hash;
        console.log(hash);
        $('<div id="serialize_dialog"></div>').html('<p><small class="new"><strong>NEW!</strong></small> Copy the URL below to easily share your schedule with friends:</p><textarea cols="45" rows="5">' + urlv + '</textarea><br /><p>Or copy your schedule in a concise comma-separated format:</p><textarea cols="45" rows="5">' + secs + '</textarea>').dialog({
            title: "Serialize schedule",
            modal: true,
            width: 380,
        });
    });
    $('#export_schedule_button').click(function() {
        var options = getJsonOptions();
        options.format = 'ics';
        $('#export_schedule_data').val(JSON.stringify(options));
    });
    $('#wrap_titles_button').click(function() {
        $(document.body).toggleClass('notitlewrap');
    });

    $("#edit_schedules_button").click(function() {
        $edit_schedules.dialog({
            title: "Edit schedules",
            width: 600,
            close: function() {
                $edit_schedules.dialog("destroy");
                $edit_schedules.hide();
                $calendar.weekCalendar('refresh');
            },
            buttons: {
                close: function() {
                    $edit_schedules.dialog("close");
                }
            }
        }).show();
    });
    $(".sample_schedules a").click(function() {
        resetScheduleData($(this).data('sched'));
        return false;
    });
    
    $('#grubber-link').click(function() {
        var $sched_grubber = $('<div id="sched-grubber-dialog"></div>');
        var parseUpdateButton = $('<button id="sched-parse-update-button""><img src="inc/img/silk/page_white_lightning.png">Parse and update schedule</button>').click(function() {
            var parsedschedule = parseInput(document.getElementById('sched-input').value);
            resetScheduleData(parsedschedule);
            $sched_grubber.dialog("close");
        });
        $sched_grubber.load('sched-grubber.html #sched-grubber', function() {
            $sched_grubber.find('#sched-parse-button').append(' only');
            $sched_grubber.find('.buttons').append(parseUpdateButton);
            $sched_grubber.find('li:eq(4)').text('Click the "Parse and update schedule" button.');
        });
        $sched_grubber.dialog({ title: "Schedule grubber", width: 690, height: 380 });
        return false;
    });

    function getJsonOptions() {
        var activeSched_id = 'sched1';
        var activeSched_sections = $('input[name="' + activeSched_id + '[]"][type="hidden"]').map(function(i, input) {
            var components = /\s*([A-Za-z]{4}(\d{3})[A-Za-z]?)\s+([A-Za-z\d]{4})\s*/.exec(input.value);
            if (components && components[1])
                return { 'dept': components[1], 'sec': components[3] };
                //return components[1] + ' ' + components[3];
        }).toArray();
        return { 'data': activeSched_sections, 'year': $('#year_select').val(), 'term': $('#term_select').val() };
    }
    function resetScheduleData(scheduledata) {
        $('#sched1').data('list').find('li').click();
        $("#sched1").addTag(scheduledata);
        $('#refresh_calendar_button').click();
    }
    
    function initUrlParams() {
        var urlParams = {},
            match,
            pl     = /\+/g,  // Regex for replacing addition symbol with a space
            search = /([^&=]+)=?([^&]*)/g,
            decode = function (s) { return decodeURIComponent(s.replace(pl, " ")); },
            query  = window.location.search.substring(1);

        while (match = search.exec(query))
            urlParams[decode(match[1])] = decode(match[2]);
        
        return urlParams;
    };
});
