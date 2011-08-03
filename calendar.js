$(document).ready(function() {
    $('#sched1').addTag(document.getElementById('ophir').innerText);

    var $calendar = $('#calendar');
    var id = 10;

    $calendar.weekCalendar({
        displayOddEven: true,
        timeslotHeight: 7,
        timeslotsPerHour: 12,
        allowCalEventOverlap: true,
        overlapEventsSeparate: true,
        timeFormat: 'g:i',
        timeSeparator: ' &ndash; ',
        firstDayOfWeek: 0,
        businessHours: {start: 8, end: 18, limitDisplay: true},
        minDate: Date.sun(),
        maxDate: Date.sat(),
        daysToShow: 7,
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
            var activeSched_id = 'sched1';
            var activeSched_sections = $('input[name="' + activeSched_id + '[]"][type="hidden"]').map(function(i, input) {
                var components = input.value.split(' ');
                if (components.length == 2)
                    return { 'dept': components[0], 'sec': components[1] };
            });
            
            return { 'model': 'course', 'format': 'events', 'data': activeSched_sections };
        },
        data: 'umd-rest.php',
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
        $('<div></div>').html('<textarea cols="34" rows="4">' + $('input[name="sched1[]"][type="hidden"]').map(function(i, input) { return input.value; }).toArray().join(',').replaceList({' ':'&nbsp;',',':', '}) + '</textarea>').dialog({
            title: "Serialize schedule",
            modal: true,
        });
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
    $(".sample_schedules dd a").click(function() {
        $('#sched1').data('list').find('li').click();
        $("#sched1").addTag($(this).html());
        $('#refresh_calendar_button').click();
        return false;
    });
    
    $('#grubber-link').click(function() {
        $('<div></div>').load('sched-grubber.html #sched-grubber').dialog({ title: "Schedule grubber", width: 690, height: 380 });
        return false;
    });


});