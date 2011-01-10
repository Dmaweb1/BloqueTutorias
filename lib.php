<?php
/**
 * Library functions for the tutorias block
 **/

//definittion of constants only if not defined before
if(!defined ('CALENDAR_DEFAULT_UPCOMING_LOOKAHEAD'))
{
    // These are read by the administration component to provide default values
    define('CALENDAR_DEFAULT_UPCOMING_LOOKAHEAD', 21);
    define('CALENDAR_DEFAULT_UPCOMING_MAXEVENTS', 10);
    define('CALENDAR_DEFAULT_STARTING_WEEKDAY',   1);
    // This is a packed bitfield: day X is "weekend" if $field & (1 << X) is true
    // Default value = 65 = 64 + 1 = 2^6 + 2^0 = Saturday & Sunday
    define('CALENDAR_DEFAULT_WEEKEND',            65);
}

// Fetch the correct values from admin settings/lang pack
// If no such settings found, use the above defaults
$firstday = isset($CFG->calendar_startwday) ? $CFG->calendar_startwday : get_string('firstdayofweek');
if(!defined('CALENDAR_STARTING_WEEKDAY')){
    if(!is_numeric($firstday)) {
        define ('CALENDAR_STARTING_WEEKDAY', CALENDAR_DEFAULT_STARTING_WEEKDAY);
    }
    else {
        define ('CALENDAR_STARTING_WEEKDAY', intval($firstday) % 7);
    }
}
if(!defined ('CALENDAR_WEEKEND'))
{
    define ('CALENDAR_WEEKEND', isset($CFG->calendar_weekend) ? intval($CFG->calendar_weekend) : CALENDAR_DEFAULT_WEEKEND);
    //define ('CALENDAR_URL', $CFG->wwwroot.'/calendar/');
    define ('CALENDAR_TF_24', '%H:%M');
    define ('CALENDAR_TF_12', '%I:%M %p');
}

$CALENDARDAYS = array('sunday','monday','tuesday','wednesday','thursday','friday','saturday');

function tutorias_calendar_top_controls($data) {
    global $CFG, $CALENDARDAYS, $THEME;
    $content = '';
    if(!isset($data['d'])) {
        $data['d'] = 1;
    }

     // Ensure course id passed if relevant
    // Required due to changes in view/lib.php mainly (calendar_session_vars())
    $courseid = '';
    if (!empty($data['id'])) {
        $courseid = '&amp;course='.$data['id'];
    }

    if(!checkdate($data['m'], $data['d'], $data['y'])) {
        $time = time();
    }
    else {
        $time = make_timestamp($data['y'], $data['m'], $data['d']);
    }
    $date = usergetdate($time);

    $data['m'] = $date['mon'];
    $data['y'] = $date['year'];
       
    list($prevmonth, $prevyear) = tutorias_calendar_sub_month($data['m'], $data['y']);
    list($nextmonth, $nextyear) = tutorias_calendar_add_month($data['m'], $data['y']);
    if($data['id']!=1){
        $nextlink = tutorias_calendar_get_link_next(get_string('monthnext', 'access'), 'view.php?id='.$data['id'].'&amp;', 0, $nextmonth, $nextyear, $accesshide=true);
        $prevlink = tutorias_calendar_get_link_previous(get_string('monthprev', 'access'), 'view.php?id='.$data['id'].'&amp;', 0, $prevmonth, $prevyear, true);
    }
    else
    {
        $nextlink = tutorias_calendar_get_link_next(get_string('monthnext', 'access'), 'index.php?', 0, $nextmonth, $nextyear, $accesshide=true);
        $prevlink = tutorias_calendar_get_link_previous(get_string('monthprev', 'access'), 'index.php?', 0, $prevmonth, $prevyear, true);
    }
    $link_months='"'.$CFG->wwwroot.'/blocks/tutorias/months.php?tut_y='.$data['y'].'&amp;instanceid='.$data['instanceid'].'&amp;courseid='.$data['id'].'"';
    $content .= "\n".'<div class="calendar-controls">'. $prevlink;
    $content .= '<span class="hide"> | </span><span class="current"><a href='. $link_months.'>'.userdate($time, get_string('strftimemonthyear')).'</a></span>';
    $content .= '<span class="hide"> | </span>'. $nextlink ."\n";
    $content .= "<span class=\"clearer\"><!-- --></span></div>\n";
    
    return $content;
}

function tutorias_calendar_get_mini($block_tutorias,$cal_month = false, $cal_year = false) {
    global $CFG, $USER, $COURSE;

    $display = new stdClass;
//var_dump($block_tutorias);
//    $display->minwday = get_user_preferences('calendar_startwday', CALENDAR_STARTING_WEEKDAY);    
    $display->minwday = $block_tutorias->config->dia_comienzo;
    $display->maxwday = $display->minwday + 6;

    $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);


    $content = '';

    if(!empty($cal_month) && !empty($cal_year)) {
        $thisdate = usergetdate(time()); // Date and time the user sees at his location
        if($cal_month == $thisdate['mon'] && $cal_year == $thisdate['year']) {
            // Navigated to this month
            $date = $thisdate;
            $display->thismonth = true;
        }
        else {
            // Navigated to other month, let's do a nice trick and save us a lot of work...
            if(!checkdate($cal_month, 1, $cal_year)) {
                $date = array('mday' => 1, 'mon' => $thisdate['mon'], 'year' => $thisdate['year']);
                $display->thismonth = true;
            }
            else {
                $date = array('mday' => 1, 'mon' => $cal_month, 'year' => $cal_year);
                $display->thismonth = false;
            }
        }
    }
    else {
        $date = usergetdate(time()); // Date and time the user sees at his location
        $display->thismonth = true;
    }

    // Fill in the variables we 're going to use, nice and tidy
    list($d, $m, $y) = array($date['mday'], $date['mon'], $date['year']); // This is what we want to display
    $display->maxdays = tutorias_calendar_days_in_month($m, $y);

    if (get_user_timezone_offset() < 99) {
        // We 'll keep these values as GMT here, and offset them when the time comes to query the db
        $display->tstart = gmmktime(0, 0, 0, $m, 1, $y); // This is GMT
        $display->tend = gmmktime(23, 59, 59, $m, $display->maxdays, $y); // GMT
    } else {
        // no timezone info specified
        $display->tstart = mktime(0, 0, 0, $m, 1, $y);
        $display->tend = mktime(23, 59, 59, $m, $display->maxdays, $y);
    }

    $startwday = dayofweek(1, $m, $y);

    // Align the starting weekday to fall in our display range
    // This is simple, not foolproof.
    if($startwday < $display->minwday) {
        $startwday += 7;
    }

    
    // We want to have easy access by day, since the display is on a per-day basis.
    // Arguments passed by reference.
    //calendar_events_by_day($events, $display->tstart, $eventsbyday, $durationbyday, $typesbyday);
    tutorias_events_by_day($events, $m, $y, $eventsbyday,$block_tutorias->instance->id);

    //Accessibility: added summary and <abbr> elements.
    ///global $CALENDARDAYS; appears to be broken.
    $days_title = array('sunday','monday','tuesday','wednesday','thursday','friday','saturday');

    $summary = get_string('calendarheading', 'calendar', userdate(make_timestamp($y, $m), get_string('strftimemonthyear')));
    $summary = get_string('tabledata', 'access', $summary);
    $content .= '<table class="minicalendar" summary="'.$summary.'">'; // Begin table
    $content .= '<tr class="weekdays">'; // Header row: day names

    // Print out the names of the weekdays
    $days = array('sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat');
    for($i = $display->minwday; $i <= $display->maxwday; ++$i) {
        // This uses the % operator to get the correct weekday no matter what shift we have
        // applied to the $display->minwday : $display->maxwday range from the default 0 : 6
        $content .= '<th scope="col"><abbr title="'. get_string($days_title[$i % 7], 'calendar') .'">'.
            get_string($days[$i % 7], 'calendar') ."</abbr></th>\n";
    }

    $content .= '</tr><tr>'; // End of day names; prepare for day numbers

    // For the table display. $week is the row; $dayweek is the column.
    $dayweek = $startwday;

    // Paddding (the first week may have blank days in the beginning)
    for($i = $display->minwday; $i < $startwday; ++$i) {
        $content .= '<td class="dayblank">&nbsp;</td>'."\n";
    }

    // Now display all the calendar
    for($day = 1; $day <= $display->maxdays; ++$day, ++$dayweek) {
        if($dayweek > $display->maxwday) {
            // We need to change week (table row)
            $content .= '</tr><tr>';
            $dayweek = $display->minwday;
        }

        // Reset vars
        $cell = '';
        if(CALENDAR_WEEKEND & (1 << ($dayweek % 7))) {
            // Weekend. This is true no matter what the exact range is.
            $class = 'weekend day';
        }
        else {
            // Normal working day.
            $class = 'day';
        }
        // Special visual fx if an event is defined
        if(isset($eventsbyday[$day])) {
       
           $date2=array("day" => $day,"month"=>$date["mon"],"year"=>$date["year"]);
       $dayhref = tutorias_get_event_link_href($CFG->wwwroot.'/blocks/tutorias/view.php?', $block_tutorias->instance->id,  $COURSE->id, null, null,$date2);

            // OverLib popup
            $popupcontent = '';
            foreach($eventsbyday[$day] as $eventid) {
                if (!isset($events[$eventid])) {
                    continue;
                }
                $event = $events[$eventid];
    
        if (!has_capability('block/tutorias:managetutory', $context) and (!$event->visible))
        {continue;}

        switch($event->type)
        {
            case 0:
                $popupicon = $CFG->wwwroot.'/blocks/tutorias/icons/tutorship.png';
                $popupalt  = get_string('individual', 'block_tutorias');    
            break;
                $popupicon = $CFG->wwwroot.'/blocks/tutorias/icons/group.gif';
                $popupalt  = get_string('group', 'block_tutorias');    
            break;
            case 2:
                $popupicon = $CFG->wwwroot.'/blocks/tutorias/icons/event.png';
                $popupalt  = get_string('event', 'block_tutorias');            
            break;
            case 3:
                $popupicon = $CFG->wwwroot.'/blocks/tutorias/icons/review.png';
                $popupalt  = get_string('review', 'block_tutorias');    
            break;
            default:
                $popupicon = $CFG->pixpath.'/c/user.gif';
                            $popupalt  = '';            
            break;
        }    
                $popupcontent .= '<div><img class="icon" src="'.$popupicon.'" alt="'.$popupalt.'" /><a href="'.$dayhref.'&amp;eventid='.$event->id.'">'.format_string($event->tutorshiptitle, true).'</a></div>';
            }
            //Accessibility: functionality moved to tutorias_calendar_get_popup.
            if($display->thismonth && $day == $d) {
                $popup = tutorias_calendar_get_popup(true, $events[$eventid]->starttime, $popupcontent);
            } else {
                $popup = tutorias_calendar_get_popup(false, $events[$eventid]->starttime, $popupcontent);
            }

// Class and cell content
        switch($event->type)
        {
        
        case 0:
             $class .= ' event_global';
            if($event->freepositions==0)
            {
                $class .= ' duration_global';
            }
        break;
        case 1:
             $class .= ' event_course';
             if($event->freepositions==0)
            {
                 $class .= ' duration_course';
            }
        break;
        case 2:
             $class .= ' event_group';
             if($event->freepositions==0)
            {
                 $class .= ' duration_group';
            }
        break;
        case 3:
             $class .= ' event_user';
             if($event->freepositions==0)
            {
                 $class .= ' duration_user';
            }
        break;

        }
            
            $cell = '<a href="'.$dayhref.'" '.$popup.'>'.$day.'</a>';
        }
        else {
            $cell = $day;
        }

     
        // If event has a class set then add it to the table day <td> tag
        // Note: only one colour for minicalendar
        if(isset($eventsbyday[$day])) {
            foreach($eventsbyday[$day] as $eventid) {
                if (!isset($events[$eventid])) {
                    continue;
                }
                $event = $events[$eventid];
                if (!empty($event->class)) {
                    $class .= ' '.$event->class;
                }
                break;
            }
        }

        // Special visual fx for today
        //Accessibility: hidden text for today, and popup.
        if($display->thismonth && $day == $d) {
            $class .= ' today';
            $today = get_string('today', 'calendar').' '.userdate(time(), get_string('strftimedayshort'));

            if(! isset($eventsbyday[$day])) {
                $class .= ' eventnone';
                $popup = tutorias_calendar_get_popup(true, false);
                $cell = '<a href="#" '.$popup.'>'.$day.'</a>';
            }
            $cell = get_accesshide($today.' ').$cell;
        }

        // Just display it
        if(!empty($class)) {
            $class = ' class="'.$class.'"';
        }
        $content .= '<td'.$class.'>'.$cell."</td>\n";
    }

    // Paddding (the last week may have blank days at the end)
    for($i = $dayweek; $i <= $display->maxwday; ++$i) {
        $content .= '<td class="dayblank">&nbsp;</td>';
    }
    $content .= '</tr>'; // Last row ends

    $content .= '</table>'; // Tabular display of days ends

    return $content;
}

/**
 * tutorias_calendar_get_popup, called at multiple points in from tutorias_calendar_get_mini.
 *        Copied and modified from tutorias_calendar_get_mini.
 * @uses OverLib popup.
 * @param $is_today bool, false except when called on the current day.
 * @param $event_timestart mixed, $events[$eventid]->timestart, OR false if there are no events.
 * @param $popupcontent string.
 * @return $popup string, contains onmousover and onmouseout events.
 */
function tutorias_calendar_get_popup($is_today, $event_timestart, $popupcontent='') {
    $popupcaption = '';
    if($is_today) {
        $popupcaption = get_string('today', 'calendar').' ';
    }
    if (false === $event_timestart) {
        $popupcaption .= userdate(time(), get_string('strftimedayshort'));
        $popupcontent = get_string('eventnone', 'calendar');

    } else {
        $popupcaption .= get_string('eventsfor', 'calendar', userdate($event_timestart, get_string('strftimedayshort')));
    }
    $popupcontent = str_replace("'", "\'", htmlspecialchars($popupcontent));
    $popupcaption = str_replace("'", "\'", htmlspecialchars($popupcaption));
    $popup = 'onmouseover="return overlib(\''.$popupcontent.'\', CAPTION, \''.$popupcaption.'\');" onmouseout="return nd();"';
    return $popup;
}

function tutorias_calendar_days_in_month($month, $year) {
   return intval(date('t', mktime(0, 0, 0, $month, 1, $year)));
}

function tutorias_calendar_sub_month($month, $year) {
    if($month == 1) {
        return array(12, $year - 1);
    }
    else {
        return array($month - 1, $year);
    }
}

function tutorias_calendar_add_month($month, $year) {
    if($month == 12) {
        return array(1, $year + 1);
    }
    else {
        return array($month + 1, $year);
    }
}


/**
 * Build and return a previous month HTML link, with an arrow.
 * @param string $text The text label.
 * @param string $linkbase The URL stub.
 * @param int $d $m $y Day of month, month and year numbers.
 * @param bool $accesshide Default visible, or hide from all except screenreaders.
 * @return string HTML string.
 */
function tutorias_calendar_get_link_previous($text, $linkbase, $d, $m, $y, $accesshide=false) {
    $href = tutorias_get_link_href($linkbase, $d, $m, $y);
    if(empty($href)) return $text;
    return link_arrow_left($text, $href, $accesshide, 'previous');
}

/**
 * Build and return a next month HTML link, with an arrow.
 * @param string $text The text label.
 * @param string $linkbase The URL stub.
 * @param int $d $m $y Day of month, month and year numbers.
 * @param bool $accesshide Default visible, or hide from all except screenreaders.
 * @return string HTML string.
 */
function tutorias_calendar_get_link_next($text, $linkbase, $d, $m, $y, $accesshide=false) {
    $href = tutorias_get_link_href($linkbase, $d, $m, $y);
    if(empty($href)) return $text;
    return link_arrow_right($text, $href, $accesshide, 'next');
}

/**
 * TODO document
 */
function tutorias_get_link_href($linkbase, $d, $m, $y) {
    if(empty($linkbase)) return '';
    $paramstr = '';
    if(!empty($d)) $paramstr .= '&amp;tut_d='.$d;
    if(!empty($m)) $paramstr .= '&amp;tut_m='.$m;
    if(!empty($y)) $paramstr .= '&amp;tut_y='.$y;
    if(!empty($paramstr)) $paramstr = substr($paramstr, 5);
    return $linkbase.$paramstr;
}
//idday  should be an array of day, month and year
function tutorias_get_event_link_href($linkbase, $instanceid, $courseid, $id, $idrep=null,$idday=null) {
    if(empty($linkbase)) return '';
    $paramstr = '';
    if(!empty($instanceid)) $paramstr .= '&amp;instanceid='.$instanceid;
    if(!empty($courseid)) $paramstr .= '&amp;courseid='.$courseid;
    if(!empty($id)) $paramstr .= '&amp;eventid='.$id;
    if(!empty($idrep)) $paramstr .= '&amp;repetitionid='.$idrep;
    if(!empty($idday)) $paramstr .= '&amp;dayid='.strtotime("$idday[day]-$idday[month]-$idday[year]");
    if(!empty($paramstr)) $paramstr = substr($paramstr, 5);
    return $linkbase.$paramstr;
}

function tutorias_get_paramm_vars($methot='GET')
{
    $vars='';    
    switch($methot)
    {
    case 'REQUEST':
        $aux= $_REQUEST;
    break;
    case 'POST':
        $aux= $_POST;
    break;
    case 'GET':
        $aux= $_GET;
    break;
    default:
        $aux= $_GET;
    break;    
    }    
    foreach($aux as $key => $value)
    {
        $vars.='&amp;'.$key.'='.$value;
    }
    return $vars;
}


function tutorias_filter_controls() {
    global $CFG, $SESSION, $USER;

    $id = optional_param( 'id',0,PARAM_INT );

    if($id<=1)
    {
    $courseurl='index.php?&amp;';
    }
    else
    {
        $courseurl='view.php?id='.$id.'&amp;';  
    }
 

    $getvars = '';

    $showindividual = optional_param('showindividual',1,PARAM_INT );
    $showgroup = optional_param('showgroup',1,PARAM_INT );
    $showevent = optional_param('showevent',1,PARAM_INT );
    $showreview = optional_param('showreview',1,PARAM_INT );
    $tut_m = optional_param('tut_m',null,PARAM_INT );
    $tut_y = optional_param('tut_y',null,PARAM_INT );

    if (!empty($tut_y)and !empty($tut_m)) {
        $getvars .= '&amp;tut_m='.$tut_m.'&amp;tut_y='.$tut_y ;
    }

    $content = '<div class="filters"><table style=" -moz-border-radius:4px; ">';

    $content .= '<tr>';
    if($showindividual) {
    $enlace=$courseurl.'showindividual=0'.'&amp;showgroup='.$showgroup.'&amp;showevent='.$showevent.'&amp;showreview='.$showreview.$getvars;
        $content .= '<td class="eventskey event_global" style="width: 11px;"><img src="'.$CFG->pixpath.'/t/hide.gif" class="iconsmall" alt="'.get_string('hide').'" title="'.get_string('hideindividual', 'block_tutorias').'" style="cursor:pointer" onclick="location.href='."'".$enlace."'".'"/></td>';
        $content .= '<td><a href="'.$enlace.'" title="'.get_string('hideindividual', 'block_tutorias').'">'.get_string('individual', 'block_tutorias').'</a></td>'."\n";
    } else {
    $enlace=$courseurl.'showindividual=1'.'&amp;showgroup='.$showgroup.'&amp;showevent='.$showevent.'&amp;showreview='.$showreview.$getvars;
        $content .= '<td style="width: 11px;"><img src="'.$CFG->pixpath.'/t/show.gif" class="iconsmall" alt="'.get_string('show').'" title="'.get_string('showindividual', 'block_tutorias').'" style="cursor:pointer" onclick="location.href='."'".$enlace."'".'" /></td>';
        $content .= '<td><a href="'.$enlace.'" title="'.get_string('showindividual', 'block_tutorias').'">'.get_string('individual', 'block_tutorias').'</a></td>'."\n";
    }
    if($showgroup) {
    $enlace=$courseurl.'showindividual='.$showindividual.'&amp;showgroup=0'.'&amp;showevent='.$showevent.'&amp;showreview='.$showreview.$getvars;
        $content .= '<td class="eventskey event_course" style="width: 11px;"><img src="'.$CFG->pixpath.'/t/hide.gif" class="iconsmall" alt="'.get_string('hide').'" title="'.get_string('hidegroup', 'block_tutorias').'" style="cursor:pointer" onclick="location.href='."'".$enlace."'".'" /></td>';
        $content .= '<td><a href="'.$enlace.'" title="'.get_string('hidegroup', 'block_tutorias').'">'.get_string('group', 'block_tutorias').'</a></td>'."\n";
    } else {
    $enlace=$courseurl.'showindividual='.$showindividual.'&amp;showgroup=1&amp;showevent='.$showevent.'&amp;showreview='.$showreview.$getvars;
        $content .= '<td style="width: 11px;"><img src="'.$CFG->pixpath.'/t/show.gif" class="iconsmall" alt="'.get_string('hide').'" title="'.get_string('showgroup', 'block_tutorias').'" style="cursor:pointer" onclick="location.href='."'".$enlace."'".'" /></td>';
        $content .= '<td><a href="'.$enlace.'" title="'.get_string('showgroup', 'block_tutorias').'">'.get_string('group', 'block_tutorias').'</a></td>'."\n";
    }

    $content .= "</tr>\n<tr>";
   if($showevent) {
       $enlace=$courseurl.'showindividual='.$showindividual.'&amp;showgroup='.$showgroup.'&amp;showevent=0'.'&amp;showreview='.$showreview.$getvars;
       $content .= '<td class="eventskey event_group" style="width: 11px;"><img src="'.$CFG->pixpath.'/t/hide.gif" class="iconsmall" alt="'.get_string('hide').'" title="'.get_string('hideevent', 'block_tutorias').'" style="cursor:pointer" onclick="location.href='."'".$enlace."'".'" /></td>';
       $content .= '<td><a href="'.$enlace.'" title="'.get_string('hideevent', 'block_tutorias').'">'.get_string('event', 'block_tutorias').'</a></td>'."\n";
   } else {
       $enlace=$courseurl.'showindividual='.$showindividual.'&amp;showgroup='.$showgroup.'&amp;showevent=1'.'&amp;showreview='.$showreview.$getvars;
       $content .= '<td style="width: 11px;"><img src="'.$CFG->pixpath.'/t/show.gif" class="iconsmall" alt="'.get_string('show').'" title="'.get_string('showevent', 'block_tutorias').'" style="cursor:pointer" onclick="location.href='."'".$enlace."'".'" /></td>';
       $content .= '<td><a href="'.$enlace.'" title="'.get_string('showevent', 'block_tutorias').'">'.get_string('event', 'block_tutorias').'</a></td>'."\n";
    }
        if($showreview) {
    $enlace=$courseurl.'showindividual='.$showindividual.'&amp;showgroup='.$showgroup.'&amp;showevent='.$showevent.'&amp;showreview=0'.$getvars;
            $content .= '<td class="eventskey event_user" style="width: 11px;"><img src="'.$CFG->pixpath.'/t/hide.gif" class="iconsmall" alt="'.get_string('hide').'" title="'.get_string('hidereview', 'block_tutorias').'" style="cursor:pointer" onclick="location.href='."'".$enlace."'".'" /></td>';
            $content .= '<td><a href="'.$enlace.'" title="'.get_string('hidereview', 'block_tutorias').'">'.get_string('review', 'block_tutorias').'</a></td>'."\n";
        } else {
    $enlace=$courseurl.'showindividual='.$showindividual.'&amp;showgroup='.$showgroup.'&amp;showevent='.$showevent.'&amp;showreview=1'.$getvars;
            $content .= '<td style="width: 11px;"><img src="'.$CFG->pixpath.'/t/show.gif" class="iconsmall" alt="'.get_string('show').'" title="'.get_string('showreview', 'block_tutorias').'" style="cursor:pointer" onclick="location.href='."'".$enlace."'".'" /></td>';
            $content .= '<td><a href="'.$enlace.'" title="'.get_string('showreview', 'block_tutorias').'">'.get_string('review', 'block_tutorias').'</a></td>'."\n";
        }

 $content .= "</tr>\n<tr>";
$content .= '<td class="full" style="width: 11px;"></td><td><a title="'.get_string('eventsfull_tooltip', 'block_tutorias').'">'.get_string('eventsfull', 'block_tutorias').'</a></td>'."\n";
    $content .= "</tr>\n</table>\n</div>";



    return $content;
    
}

//*****************************************************************************************//

/**
 * returns an array with the events given grouped by day, each component of the array wil be an array with the events of these day.    
 * @param array $events the events to short.
 * @param array &$eventsbyday events grouped and shorted by day.
 * @param int $m number of a month
 * @param int $y a year
 * @param int $instanceId the instance id of the block.
 */
function tutorias_events_by_day(&$events, $m, $y, &$eventsbyday,$instanceId)
{

    $showindividual = optional_param('showindividual',1,PARAM_INT );
    $showgroup = optional_param('showgroup',1,PARAM_INT );
    $showevent = optional_param('showevent',1,PARAM_INT );
    $showreview = optional_param('showreview',1,PARAM_INT );    

    $events=tutorias_get_events($instanceId,$m,$y);

    if(!$events){return;}
    foreach ($events as $event)
    {
        if( (($event->type==0)and($showindividual)) or (($event->type==1)and($showgroup)) or (($event->type==2)and($showevent)) or (($event->type==3)and($showreview)) )
        {
            $eventday=(int)date('d',$event->starttime);
            if((!isset($eventsbyday[$eventday])) or ($eventsbyday[$eventday] ==null))
            {
                $eventsbyday[$eventday]=array();
            }
        
            array_push($eventsbyday[$eventday],(int)$event->id);
        }

    }
}

/**
 * returns an array with the events of an instance of the block tutorias or all events of a instance in a specific month.
 * @param int $instanceId the instance id of the block.
 * @param int $month number of a month
 * @param int $year a year
 * @return array with the events.
 */
function tutorias_get_events($instanceId,$month=1,$year=0)
{

    $timeStart=strtotime($year.'-'.$month.'-1'); 
    if($year==0)
    {    
        $timeEnd=strtotime('2038-'.$month.'-1');
    }
    else
    {
        //$nextmonth=$month+1;
        $timeEnd=strtotime('+1 month',$timeStart);
    }
    $select= '(instanceid ='.$instanceId.') AND (starttime >='.$timeStart.') AND (starttime <='.$timeEnd.')';
    $events = get_records_select('block_tutorias', $select);

    return $events;
}

/**
 * returns an array with the events of an instance of the block tutorias or all events of a instance in a specific month.
 * @param int $instanceId the instance id of the block.
 * @param int $timespanday the day
 * @return array with the events.
 */
function tutorias_get_events_day($instanceId,$timespanday=0)
{
    if($timespanday==0)
    {
        $timeStart=strtotime(date("d-m-Y",time())); 
    }
    else
    {
        $timeStart=$timespanday;
    }
        $timeEnd=strtotime("+1 day",$timeStart); 
        
    $select= '(instanceid ='.$instanceId.') AND (starttime >='.$timeStart.') AND (starttime <='.$timeEnd.')';
    $events = get_records_select('block_tutorias', $select);

    return $events;
}


/**
 * returns an array with all the events of an instance of a user
 * @param int $instanceId the instance id of the block.
 * @param int $userid the id of the user
 * @return array with the events.
 */
function tutorias_get_events_user($instanceId,$userid)
{
    global $CFG;
    $prefix=$CFG->prefix;
    $events=array();

//we select first the tutorship where the user is teacher
    $select1= '(instanceid ='.$instanceId.') AND (teacherid ='.$userid.')';
    $events1 = get_records_select('block_tutorias', $select1);
    if($events1)
    {
        $events=array_merge($events,$events1);
    }
//we select second the tutorship where the user is student
    $select2= 'id in (select eventid from '.$prefix.'block_tutorias_students where (instanceid ='.$instanceId.') AND (studentid ='.$userid.'))';
    $events2=get_records_select('block_tutorias', $select2);
    if($events2)
    {
        $events=array_merge($events, $events2);
    }

    return $events;
}

/**
 * returns an array with the events of an instance of the block tutorias thath forms part of a specific repetition, include the primary event
 * @param int $instanceId the instance id of the block.
 * @param int $repetitionId
 * @return array with the events.
 */
function tutorias_get_events_repetitions($instanceId,$repetitionId)
{
    $events1 = Array(get_record('block_tutorias', 'id', $repetitionId));
    $select = '(instanceid ='.$instanceId.') AND (idrepetition ='.$repetitionId.')';
    $events2 = get_records_select('block_tutorias', $select,'starttime');
    $events=array_merge($events1,$events2);
    return $events;
}

/**
 * returns an object that represents a instance of a given block
 * @param int $instanceId the instance id of the block.
 * @return object that represents a instance of a given block.
 */
function tutorias_get_block($instanceId)
{
   if($block = get_record('block_instance', 'id', $instanceId))
   {
       if ($block->configdata != "")
       {
           $read = unserialize(base64_decode($block->configdata));
       $block->configdata=$read;
       }
       if($read = get_record('block', 'id', $block->blockid))
       {
           $block->name=$read->name;
       }  
    //$block->instance->id=$instanceId;
   }
   
   return $block;
}

/**
 * returns true if the tutorship  is finished
 * @param int $instanceId the instance id of the tutorship. 
 * @param int $eventId the id of the tutorship.
 * @return bool spam if the tutorship  is finished.
 */
function tutorias_get_tutorship_finished($instanceId,$eventId)
{
     $event = get_record('block_tutorias', 'id', $eventId);

    return ($event->starttime+$event->duration<time());
}

/**
 * returns true if the repetition  is finished
 * @param int $instanceId the instance id of the tutorship. 
 * @param int $repId the id of the repetition.
 * @return bool spam if the tutorship  is finished.
 */
function tutorias_get_repetition_finished($instanceId,$repId)
{
    $events_rep=tutorias_get_events_repetitions($instanceId,$repId);
     $event = array_pop($events_rep);
    return ($event->starttime+$event->duration<time());
}

/**
 * returns the starttime of the first event of a given repetition 
 * @param int $instanceId the instance id of the repetition. 
 * @param int $instanceId the instance id of the block.
 * @return time spam of the starttime of the first event.
 */
function tutorias_get_date_start_repetition($instanceId,$repetitionId)
{
     $event = get_record('block_tutorias', 'id', $repetitionId);
    if($event)
    {
        return $event->starttime;
    }
    else
    {
        return $event;
    } 
}

/**
 * returns the starttime of the last event of a given repetition 
 * @param int $instanceId the instance id of the repetition. 
 * @param int $instanceId the instance id of the block.
 * @return time spam of the starttime of the last event.
 */
function tutorias_get_date_end_repetition($instanceId,$repetitionId)
{
     $select = '(instanceid ='.$instanceId.') AND (idrepetition ='.$repetitionId.')';
    $events = get_records_select('block_tutorias', $select,'starttime');
        
    return array_pop($events)->starttime;
 
}



/**
 * returns the starttime of the last event of a given repetition 
 * @param int $instanceId the instance id of the repetition. 
 * @param int $instanceId the instance id of the block.
 * @return time spam of the starttime of the last event.
 */
function tutorias_is_first_event_rep($instanceId,$repetitionId)
{
     $select = '(instanceid ='.$instanceId.') AND (idrepetition ='.$repetitionId.')';
    $events = get_records_select('block_tutorias', $select);

    
    
    return ($events!=null);
 
}

/**
 * Get the time betwen two events of the same repetition.
 * @param int $instanceId the id of the instance. 
 * @param int $repetitionId the id of the repetition.
 * @return int, timespan
 */
function tutorias_get_time_betwn_rep($instanceId,$repetitionId)
{
    $select = '((instanceid ='.$instanceId.') AND (idrepetition ='.$repetitionId.')) OR ((instanceid ='.$instanceId.') AND (id ='.$repetitionId.')) ';
    $events = get_records_select('block_tutorias', $select,'starttime');


        $first_event=current($events);
    $second_event=next($events);

    $diference=date_diff($first_event->starttime, $second_event->starttime);

    return $diference;
}

/**
 * Get an array with the free positions in a tutorship
 * @param int $eventid the id of the tutorship. 
 * @return array,
 */
function tutorias_free_positions($eventId)
{

    $students = get_records('block_tutorias_students', 'eventid', $eventId);
    $event = get_record('block_tutorias', 'id', $eventId);

    $posiciones1=Array();
    $posiciones2=Array();
    $posiciones=Array();
    if($event->durationstudent!=0)
    {
        for($i=0;$i<$event->duration;$i=$i+$event->durationstudent)
        {
            array_push($posiciones1,$i);    
        }
        if(is_array($students))
        {
            foreach($students as $student)
            {
                $aux=$student->position*$event->durationstudent;
                array_push($posiciones2,$aux);    
            }
        }
        $posiciones=array_diff($posiciones1,$posiciones2);
    }
    else
    {
        array_push($posiciones,1);
    }
    
    return $posiciones;
}
/**
 * says if a tutorship is full, if returns true the tutorship it's not full, if returns false the tutorship is full
 * @param int $eventid the id of the tutorship. 
 * @return bool, if returns true the tutorship its not full, if returns false the tutorship is full
 */
function tutorias_not_complete($eventId)
{
    $event = get_record('block_tutorias', 'id', $eventId);
    return ($event->freepositions!=0);
}

/**
 * says if a tutorship is open, if returns true the tutorship is open, if returns false the tutorship close
 * @param int $eventid the id of the tutorship. 
 * @return bool, if returns true the tutorship its not full, if returns false the tutorship is full
 */
function tutorias_tutorship_open($eventId)
{
    $event = get_record('block_tutorias', 'id', $eventId);

    if($event->notaviablebefore==0)
    {return true;}
    else
    {return (($event->starttime-$event->notaviablebefore<time()));}
}

/**
 * get if the given position is free (nobody suscribed at these time) in the tutorship
 * @param int $eventid the id of the tutorship.
 * @param int $position the id of the position. 
 * @return bool if the given position is free
 */
function tutorias_position_free($eventId,$position)
{
    $students = get_record('block_tutorias_students', 'eventid', $eventId,'position',$position);
    
    return ($students==false);
}

/**
 * get if the student is suscribed to a tutorship
 * @param int $eventid the id of the tutorship.
 * @param int $studentId the id of the student. 
 * @return bool if the student is suscribed to a tutorship
 */
function tutorias_suscribed_tutorship($eventId,$studentId)
{
    return  record_exists('block_tutorias_students', 'eventid', $eventId,'studentid',$studentId);
}

/**
 * get if the tutorship has any student suscribed
 * @param int $eventid the id of the tutorship.
 * @return bool if the tutorship has any student suscribed
 */
function tutorias_students_suscribed_tutorship($eventId)
{

    $students = get_records('block_tutorias_students', 'eventid', $eventId);

    return !($students==false);
}

/**
 * get if the repetition of a tutorship has any student suscribed
 * @param int $eventid the id of the repetition.
 * @return bool if the repetition of a tutorship has any student suscribed
 */
function tutorias_students_suscribed_repetition_tutorship($eventId)
{
    $res=false;

    $arrelement = get_records('block_tutorias', 'id', $eventId);
    if(!$arrelement){return false;}
    $element=current($arrelement);
    if ($element->idrepetition==0)
    {$idrepetition=$element->id;}
    else
    {$idrepetition=$element->idrepetition;}

    $repetitions = get_records('block_tutorias', 'idrepetition',  $idrepetition);
    $firstelement = get_records('block_tutorias', 'id', $idrepetition);
    
    if(($repetitions!=false) and ($firstelement!= false))
    {
        $all=array_merge($repetitions ,$firstelement );
    }
    else
    {
        $all=$arrelement;
    }

    foreach ($all as $key => $value)
    {
           $students = get_records('block_tutorias_students', 'eventid', $value->id);
        $res=((!($students==false))or $res);
    }

    return $res;
}

    

/**
 * get if the next events of the repetition of a tutorship has any student suscribed
 * @param int $eventid the id of the event that form part of the repetition.
 * @return bool if the repetition of a tutorship has any student suscribed
 */
function tutorias_students_suscribed_repetition_fromnow($eventId)
{
    $res=false;

    $arrelement = get_records('block_tutorias', 'id', $eventId);
    if(!$arrelement){return false;}
    $element=current($arrelement);
    if ($element->idrepetition==0)
    {$idrepetition=$element->id;}
    else
    {$idrepetition=$element->idrepetition;}

    $repetitions = recordset_to_array(get_recordset_select('block_tutorias', "idrepetition = $idrepetition and starttime >= $element->starttime" ));
    //$repetitions = get_records('block_tutorias', 'idrepetition',  $idrepetition);
    $firstelement = get_records('block_tutorias', 'id', $element->id);
 
    if(($repetitions!=false) and ($firstelement!= false))
    {
        $all=array_merge($repetitions ,$firstelement);
    }
    else
    {
        $all=$arrelement;
    }

    foreach ($all as $key => $value)
    {
        $students = get_records('block_tutorias_students', 'eventid', $value->id);
        $res=((!($students==false))or $res);
    }

    return $res;
}

/**
 * get a string with the name of a user.
 * @param int $userid the id of the moodle user.
 * @return string return string with the name of a user.
 */
function tutorias_username($userid)
{
    $user_moodle=get_record('user', 'id', $userid);
    return get_string('username', 'block_tutorias', $user_moodle);  
}

/**
 * format and send an e-mail to the teacher of a event.
 * @param int $eventid the id of the tutorship.
 * @param object $support_user support user of the moodle-
 * @param string $subject subject of the email.
 * @param string $message body of the email.
 * @return bool return true if the procces is sucesfull.
 */
function tutorias_send_mail_teacher($eventId,$support_user, $subject, $message)
{
    global $CFG, $COURSE;
    $ret=true;
    $event = get_record('block_tutorias' ,'id',$eventId);

    $messagehtml = '<head>';
    foreach ($CFG->stylesheets as $stylesheet) {
        $messagehtml .= '<link rel="stylesheet" type="text/css" href="'.$stylesheet.'" />'."\n";
    }
    $messagehtml .= '</head>';
    $messagehtml .= "\n<body id=\"email\">\n\n";
    $messagehtml .= '<div class="navbar">'.
    '<a target="_blank" href="'.$CFG->wwwroot.'/course/view.php?id='.$COURSE->id.'">'.$COURSE->shortname.'</a> &raquo; '.'</div><br/>';
    $messagehtml .= $message;
    $messagehtml .= '</body>';
    
    $user_moodle=get_record('user', 'id', $event->teacherid);
    if (!$mailresult=email_to_user($user_moodle,$support_user, $subject, $message ,$messagehtml,'','',0)) 
    {
        add_to_log($courseid, 'upload','block_tutorias error sending mail', '','error sending mail to user $id='.$event->teacherid);
        echo"\n\t\t\tError sending e-mail to user id: $event->teacherid";
        $ret=false;
    }else
    {
        echo"\n\t\t\tE-mail sended to user id: $event->teacherid";
    }
    return $ret;
}

/**
 * format and send an e-mail to the teacher when student is subscribed to a tutorship.
 * @param int $eventid the id of the tutorship.
 * @param object $support_user support user of the moodle-
 * @param string $subject subject of the email.
 * @param string $message body of the email.
 * @return bool return true if the procces is sucesfull.
 */

function tutorias_send_mail_student_suscribed($eventId,$support_user, $subject, $message)
{
    global $CFG, $COURSE;
    $ret=true;
    $event = get_record('block_tutorias' ,'id',$eventId);

    $messagehtml = '<head>';
    foreach ($CFG->stylesheets as $stylesheet) {
        $messagehtml .= '<link rel="stylesheet" type="text/css" href="'.$stylesheet.'" />'."\n";
    }
    $messagehtml .= '</head>';
    $messagehtml .= "\n<body id=\"email\">\n\n";
    $messagehtml .= '<div class="navbar">'.
    '<a target="_blank" href="'.$CFG->wwwroot.'/course/view.php?id='.$COURSE->id.'">'.$COURSE->shortname.'</a> &raquo; '.'</div><br/>';
    $messagehtml .= $message;
    $messagehtml .= '</body>';
    
    $user_moodle=get_record('user', 'id', $event->teacherid);
    if (!$mailresult=email_to_user($user_moodle,$support_user, $subject, $message ,$messagehtml,'','',0)) 
    {
        add_to_log($courseid, 'upload','block_tutorias error sending mail', '','error sending mail to user $id='.$event->teacherid);
        $ret=false;
    }
    return $ret;
}

/**
 * format and send an e-mail to students subscribed to a tutorship.
 * @param int $eventid the id of the tutorship.
 * @param object $support_user support user of the moodle-
 * @param string $subject subject of the email.
 * @param string $message body of the email.
 * @return bool return true if the procces is sucesfull.
 */
function tutorias_send_mail_students_tutorship($eventId,$support_user, $subject, $message)
{
    global $CFG, $COURSE;
    $ret=true;
    $students=tutorias_get_students_tutorship($eventId);

    $messagehtml = '<head>';
    foreach ($CFG->stylesheets as $stylesheet) {
        $messagehtml .= '<link rel="stylesheet" type="text/css" href="'.$stylesheet.'" />'."\n";
    }
    $messagehtml .= '</head>';
    $messagehtml .= "\n<body id=\"email\">\n\n";
    $messagehtml .= '<div class="navbar">'.
    '<a target="_blank" href="'.$CFG->wwwroot.'/course/view.php?id='.$COURSE->id.'">'.$COURSE->shortname.'</a> &raquo; '.'</div><br/>';
    $messagehtml .= $message;
    $messagehtml .= '</body>';
    $ret=true;

    if ($students)
    {
        foreach($students as $student)
        {
            //$user_moodle=get_record('user', 'id', $student->studentid);
            if (!$mailresult=email_to_user($student,$support_user, $subject, $message ,$messagehtml,'','',0)) 
            {
                add_to_log($courseid, 'upload','block_tutorias error sending mail', '','error sending mail to user $id='.$student->id);
                $ret=false;
            }
        }
    }
    return $ret;
}
/**
 * get all the students suscribed to a tutorship
 * @param int $eventid the id of the tutorship.
 * @return object students suscribed to a tutorship
 */
function tutorias_get_students_tutorship($eventId,$order="")
{
    global $CFG;
    $prefix=$CFG->prefix;
    
    switch($order){
        case 'position':
            $sql="select ".$prefix."user.* from ".$prefix."user INNER JOIN ".$prefix."block_tutorias_students ON ".$prefix."user.id=".$prefix."block_tutorias_students.studentid where ".$prefix."block_tutorias_students.eventid = $eventId order by position";
            break;
        default:
            $sql="select * from ".$prefix."user where id in (select studentid from ".$prefix."block_tutorias_students where eventid = $eventId order by positon)";
    }

    $students=get_records_sql($sql);

    return $students;
}

/**
 * Get info about a student.
 * @param int $eventid the id of the tutorship.
 * @param int $instanceid the id of the tutorias block. 
 * @param int $studentid the id of the student. 
 * @return string,  
 */
function tutorias_get_info_student_subscrived($eventId, $instanceid,$studentid)
{
    $out="";
    $event=get_record('block_tutorias', 'id', $eventId);
    $positions=get_recordset_select('block_tutorias_students', 'eventid='. $eventId.' and studentid='.$studentid,'position');
    foreach($positions as $position)
    {
        $time=$event->starttime+($event->durationstudent*$position['position']);
        $time2=$time+$event->durationstudent;
        $out.=strftime(get_string('strftime_time', 'block_tutorias'),$time).'-'.strftime(get_string('strftime_time', 'block_tutorias'),$time2)." | " ;
    }
    return trim($out," |");
}

/**
 * prints a table with all the users in a event
 * @param int $eventid the id of the tutorship.
 * @param int $instanceid the id of the tutorias block. 
 * @param int $sskey the the sesion key. 
 * @return mixed,  
 */
function tutorias_draw_students_table($eventId, $instanceid,$sskey,$return=false,$returnto='')
    {
        global $COURSE; 
        global $cm;
        global $CFG;
        $output="";
            
        $prefix=$CFG->prefix;

        if($returnto=='')
        {
            $returnto=$CFG->wwwroot."/blocks/tutorias/view_students.php?courseid=".$COURSE->id."&instanceid=$instanceid&eventid=$eventId";
        }
//Select the students suscribed to a tutorship
        $sql="select * from ".$prefix."user where id in (select studentid from ".$prefix."block_tutorias_students where eventid = $eventId)";
        
        $students=get_records_sql($sql);
        $event=get_record('block_tutorias', 'id', $eventId);

        $output.="<CENTER><form action='".$CFG->wwwroot."/user/action_redir.php' method='post' id='participantsform'><div>
                    <input type='hidden' name='sesskey' value='".$sskey."' />
                    <input type='hidden' name='returnto' value='".$returnto."' />
                <table id='participants' class='flexible generaltable generalbox' cellspacing='0' cellpadding='5'>" ;
        $i=1;        
        if($event->type!='0')
        {
            $output.="<thead>
                     <tr>
                                 <th class='header c0' scope='col'>&#35;<div class='commands'></div></th>
                     <th class='header c1' scope='col'>".get_string('userpic')."<div class='commands'></div></th>
                     <th class='header c2' scope='col'>".get_string('studentname', 'block_tutorias')."/".get_string('lastname', 'block_tutorias')."<div class='commands'></div></th>
                    <th class='header c3' scope='col'>".get_string('comments', 'block_tutorias')."<div class='commands'></div></th>
                    <th class='header c4' scope='col'>".get_string('select', 'block_tutorias')."<div class='commands'></div></th>  
                     </tr>
                 </thead>
                 <tbody>";


            foreach($students as $key => $current_student)
            {    
                $position=get_record('block_tutorias_students', 'eventid', $eventId,'studentid',$key);        
                $url = '/user/view.php?id='. $current_student->id .'&amp;course='. $COURSE->id ;
                if ($i&1) 
                {
                    $output.="<TR class='r0'>";
                }
                else
                {
                    $output.="<TR class='r1'>";
                }
                $output.="<TD class='cell c0'> $i </TD>";
                $output.="<TD class='cell c1'>";
                $output.=print_user_picture($current_student, $COURSE->id,null,null,true);
                $output.= "</TD>"; 
                $output.="<TD class='cell c2'><strong><a href='".$CFG->wwwroot . $url."'>".$current_student->firstname ." ". $current_student->lastname."</a></strong></TD>";
                $output.="<TD class='cell c3'>".format_text($position->comments)."</TD>";
                $output.="<TD class='cell c4'><input type='checkbox' name=user".$current_student->id." /></TD>";
                $output.="</TR>";
                
                $i++;
            }
        }
        else
        {
            $output.="<thead>
                 <tr>
                             <th class='header c0' scope='col'>".get_string('hour', 'block_tutorias')."<div class='commands'></div></th>
                 <th class='header c1' scope='col'>".get_string('userpic')."<div class='commands'></div></th>
                 <th class='header c2' scope='col'>".get_string('studentname', 'block_tutorias')."/".get_string('lastname', 'block_tutorias')."<div class='commands'></div></th>
                 <th class='header c3' scope='col'>".get_string('comments', 'block_tutorias')."<div class='commands'></div></th> 
                 <th class='header c4' scope='col'>".get_string('select', 'block_tutorias')."<div class='commands'></div></th> 
                 </tr>
             </thead>
             <tbody>";
            
            $num=floor($event->duration/$event->durationstudent);

            $sql="select * from ".$prefix."user where id in (select studentid from ".$prefix."block_tutorias_students where eventid = $eventId and position =  -1)";
            $students1=get_records_sql($sql);
            if(is_array($students1))
            {
                foreach($students1 as $key => $current_student1)
                {    
                    $position=get_record('block_tutorias_students', 'eventid', $eventId,'studentid',$key);                
                    $url = '/user/view.php?id='. $current_student1->id .'&amp;course='. $COURSE->id ;
                    if ($i&1) 
                    {
                        $output.="<TR class='r0'>";
                    }
                    else
                    {
                        $output.="<TR class='r1'>";
                    }    
                    $output.="<TD class='cell c0'>".$i."</TD>";
                    $url = '/user/view.php?id='. $current_student1->id .'&amp;course='. $COURSE->id ;
                    $output.="<TD class='cell c1'>";
                    if($current_student1)
                    {    $output.=print_user_picture($current_student1, $COURSE->id,null,null,true);
                    }
                    $output.= "</TD>"; 
                    $output.="<TD class='cell c2'><strong><a href='".$CFG->wwwroot . $url."'>".$current_student1->firstname ." ". $current_student1->lastname."</a></strong></TD>";
                    $output.="<TD class='cell c3'>".format_text($position->comments)."</TD>";
                    $output.="<TD classc='4'><input type='checkbox' name=user".$current_student1->id." /></TD>";
                    $output.="</TR>";
                    $i++;
                }
            }
            for($i=0;$i<$num;$i++)
            {
                if ($i&1) 
                {
                    $output.="<TR class='r0'>";
                }
                else
                {
                    $output.="<TR class='r1'>";
                }

                $output.="<TD class='cell c0'>".strftime(get_string('strftime_time', 'block_tutorias'),$event->starttime+($i*$event->durationstudent))."</TD>";

                $position=get_record('block_tutorias_students', 'eventid', $eventId,'position',$i);
                if($position)
                {
                    $current_student2=$students[$position->studentid];
                    $url = '/user/view.php?id='. $current_student2->id .'&amp;course='. $COURSE->id ;
                    $firstname=$current_student2->firstname;
                    $lastname=$current_student2->lastname;
                    $coments=format_text($position->comments);
                    $currentstudent2Id=$current_student2->id;
                }
                else
                {
                    $current_student2=null;
                    $url=$firstname=$lastname=$coments=$currentstudent2Id="";

                }
    
                $output.="<TD class='cell c1'>";
                if($current_student2)
                {    $output.=print_user_picture($current_student2, $COURSE->id,null,null,true);
                }
                $output.= "</TD>"; 
                $output.="<TD class='cell c2'><strong><a href='".$CFG->wwwroot . $url."'>". $firstname." ".$lastname."</a></strong></TD>";
                $output.="<TD class='cell c3'>".$coments."</TD>";
                $output.="<TD class='cell c4'><input type='checkbox' name=user".$currentstudent2Id." /></TD>";
                $output.="</TR>";
            }

        }    
        
        $output.="</tbody></table>
        <input type='hidden' name='formaction' value='messageselect.php' />
        <input type='hidden' name='id' value='".$COURSE->id."' />
        <input type='button' onclick='checkall()' value='".get_string('selectall')."' />        
        <input type='button' onclick='checknone()' value='".get_string('deselectall')."' />
        <input type='submit' value='".get_string('sendmailselected', 'block_tutorias')."' />
        </form></CENTER>";
        
        if($return)
        {
            return $output;
        }
        else
        {
            echo $output;
        }
        
    }


/**
 * Gets potential group members for grouping
 * @param int $courseid The id of the course
 * @param int $roleid The role to select users from
 * @param string $orderby The colum to sort users by
 * @return array An array of the users
 */
function tutorias_get_potential_members($courseid, $roleid = null, $orderby = 'lastname,firstname') {
    global $CFG;

    $context = get_context_instance(CONTEXT_COURSE, $courseid);
    $sitecontext = get_context_instance(CONTEXT_SYSTEM);
    $rolenames = array();
    $avoidroles = array();

    if ($roles = get_roles_used_in_context($context, true)) {

        $canviewroles    = get_roles_with_capability('moodle/course:view', CAP_ALLOW, $context);
        $doanythingroles = get_roles_with_capability('moodle/site:doanything', CAP_ALLOW, $sitecontext);

        foreach ($roles as $role) {
            if (!isset($canviewroles[$role->id])) {   // Avoid this role (eg course creator)
                $avoidroles[] = $role->id;
                unset($roles[$role->id]);
                continue;
            }
            if (isset($doanythingroles[$role->id])) {   // Avoid this role (ie admin)
                $avoidroles[] = $role->id;
                unset($roles[$role->id]);
                continue;
            }
            $rolenames[$role->id] = strip_tags(role_get_name($role, $context));   // Used in menus etc later on
        }
    }

    $select = 'SELECT u.id, u.username, u.firstname, u.lastname, u.idnumber ';
    $from   = "FROM {$CFG->prefix}user u INNER JOIN
               {$CFG->prefix}role_assignments r on u.id=r.userid ";

    if ($avoidroles) {
        $adminroles = 'AND r.roleid NOT IN (';
        $adminroles .= implode(',', $avoidroles);
        $adminroles .= ')';
    } else {
        $adminroles = '';
    }

    // we are looking for all users with this role assigned in this context or higher
    if ($usercontexts = get_parent_contexts($context)) {
        $listofcontexts = '('.implode(',', $usercontexts).')';
    } else {
        $listofcontexts = '('.$sitecontext->id.')'; // must be site
    }

    if ($roleid) {
        $selectrole = " AND r.roleid = $roleid ";
    } else {
        $selectrole = " ";
    }

    $where  = "WHERE (r.contextid = $context->id OR r.contextid in $listofcontexts)
                     AND u.deleted = 0 $selectrole
                     AND u.username != 'guest'
                     $adminroles ";
    $order = "ORDER BY $orderby ";

    return(get_records_sql($select.$from.$where.$order));

}

/**
 * create a new record suscribing an student to a tutorship
 * @param int $instanceid the instance id of the block. 
 * @param int $userid the id of the student.
 * @param int $eventid the id of the tutorship. 
 * @param bool $validate Sets if the function increments the number of free positions in a tutorship after deleting a user from the tutorship
 * @return mixed, return true o false if the record is correct or has an error, if the tutorship is full returns 0.
 */
function tutorias_unsuscribe_student($instanceid,$studentid,$eventid,$validate=true)
{
    global $CFG;

    $event = get_record('block_tutorias', 'id', $eventid);    

    $prefix=$CFG->prefix;
    $sql="select * from ".$prefix."block_tutorias_students where (studentid=$studentid) and (eventid=$eventid)";

    $suscribes = get_records_sql($sql);

    $suscribe=current($suscribes);

    $ret=delete_records('block_tutorias_students' ,'studentid',$studentid,'eventid',$eventid);

    if($ret and $validate and $suscribe->position!=-1)
    {
        $event->freepositions=$event->freepositions+count($suscribes);
        update_record('block_tutorias',$event);
    }

    return $ret;
}
/**
 * delete all elements of the repetition of which forms part of the event, and the event. 
 * Or the event if it not form part of a repetition
 * @param int $eventid the id of the event. 
 * @param int $instanceid the instance of the event. 
 * @return bool returns the result of the operation.
 */
function tutorias_delete_repetition_elements($eventid,$instanceid)
{
    $event=get_record('block_tutorias', 'id', $eventid);
    $first_event_rep=tutorias_is_first_event_rep($instanceid,$event->id);

    if($event->idrepetition==0)
    {
        if(!$first_event_rep)
        {
            $res = tutorias_unsuscribe_students_repetition($idrep);
            if($res){$res = $res and (bool)delete_records('block_tutorias','id',$event->id);}
            return $res;
        }
        else
        {
            $idrep=$event->id;
        }    
    }
    else
    {    
        $idrep=$event->idrepetition;
    }
    
    //first we delete the students
    $res = tutorias_unsuscribe_students_repetition($idrep);
    //second we delete all the events of the repetition
    if($res){ $res = $res and (bool)delete_records('block_tutorias','idrepetition',$idrep);}
    //last we delete the first event of the repetition
    if($res){$res = $res and (bool)delete_records('block_tutorias','id',$idrep);}
    
    return $res;
}


/**
 * delete the suscription of the students to an event that forms part of the given repetition.
 * @param int $repetitionid the id of the repetition. 
 * @return bool returns the result of the operation.
 */
function tutorias_unsuscribe_students_repetition($repetitionid)
{
    global $CFG,$COURSE,$USER;
    $ret=true;

    $events = get_records('block_tutorias', 'idrepetition', $repetitionid);
    $events[] = get_record('block_tutorias', 'id', $repetitionid);

    foreach ($events as $event1) 
    {
        $res=delete_records('block_tutorias_students' ,'eventid',$event1->id);
        $ret=( $ret and (bool)$res);
    } 
    if($ret)
    {
        add_to_log($COURSE->id, 'block_tutorias', 'unsuscribed users from repetitionid='.$repetitionid, $CFG->wwwroot.'course/view.php?&id='.
            $COURSE->id, '',$COURSE->id, $USER->id);
    }

    return $ret;
}

/**
 * delete the suscription of the students to an event that forms part of the given repetition and the date of the event is greather than the eventid.
 * @param int $repetitionid the id of the repetition. 
 * @param int $eventid the id of the first event to unsuscribe. 
 * @return bool returns the result of the operation.
 */
function tutorias_unsuscribe_students_repetition_from_now($repetitionid,$eventid)
{
    global $CFG,$COURSE,$USER;
    $ret=true;

    $actualevent = get_record('block_tutorias', 'id', $eventid);

    $events = get_records_select('block_tutorias', "idrepetition = $repetitionid and starttime > $actualevent->starttime" );
    $events[] = $actualevent ;

    foreach ($events as $event1) 
    {
        $res=delete_records('block_tutorias_students' ,'eventid',$event1->id);
        $ret=( $ret and (bool)$res);
    } 
    if($ret)
    {
        add_to_log($COURSE->id, 'block_tutorias', 'unsuscribed users from repetitionid='.$repetitionid . 'and start date > '. $actualevent->starttime , $CFG->wwwroot.'course/view.php?&id='.
            $COURSE->id, '',$COURSE->id, $USER->id);
    }

    return $ret;
}

/**
 * delete the events that forms part of the given repetition and the date of the event is greather than the eventid.
 * @param int $repetitionid the id of the repetition. 
 * @param int $eventid the id of the first event to delete. 
 * @return bool returns the result of the operation.
 */
function tutorias_delete_repetition_from_now($repetitionid,$eventid)
{
    global $CFG,$COURSE,$USER;
    $ret=true;

    $actualevent = get_record('block_tutorias', 'id', $eventid);

    $ret = delete_records_select('block_tutorias', "idrepetition = $repetitionid and starttime > ".$actualevent->starttime );
    $res = delete_records('block_tutorias', 'id', $eventid);

    return $ret;
}

/**
 * create a new record suscribing an student to a tutorship
 * @param int $instanceid the instance id of the block. 
 * @param int $userid the id of the student.
 * @param int $multipletutorship -----
 * @param int $eventid the id of the tutorship. 
 * @param int $option the first option selected by the student.
 * @param int $comments the comments written by the student. 
 * @param int $option2 the second option selected by the student.
 * @param bool $validate Sets if the function validate that a tutorship is full or not.
 * @return mixed, return true o false if the record is correct or has an error, if the tutorship is full returns 0.
 */
function tutorias_suscribe_student($instanceid,$multipletutorship,$userid,$eventid,$option,$comments,$option2,$validate=false)
{
    $event = get_record('block_tutorias', 'id', $eventid);
    $numAdd=0;

    if($validate)
    {
        $num=$event->freepositions;
        
        if($multipletutorship){$val=2;}else{$val=1;}
        if(($num<$val)and($num!=1))
        {            
            return 0;
        }
    }
    

    //Añadimos el código para guardar los datos en la base de datos
    $tabla='block_tutorias_students';
    $result=true;
    $false=true;
    $error=false;


    $suscribe= new stdClass();

    $suscribe->instanceid = $instanceid;
    $suscribe->studentid = $userid;
    $suscribe->eventid = $eventid;
    if($event->type==0)
    {$suscribe->position = $option;}
    else
    {$suscribe->position =-1;}
    $suscribe->comments = $comments;

    $suscribe->timemodified = $suscribe->timecreated = time();
    if(($suscribe->position == -1) or tutorias_position_free($eventid, $suscribe->position) )
    {
        $result += insert_record($tabla, $suscribe);
        $numAdd++;
    }
    else
    {
        $error=true;
    }

    if($multipletutorship)
    {
    
        $suscribe2= new stdClass();

        $suscribe2->instanceid = $instanceid;
        $suscribe2->studentid = $userid;
        $suscribe2->eventid = $eventid;
        $suscribe2->position = $option2;

        $suscribe2->timemodified = $suscribe->timecreated = time();
        if(tutorias_position_free($eventid, $option2))
        {
            $result +=  insert_record($tabla, $suscribe2);
            $numAdd++;
        }
        else
        {
            $error=true;
        }
    }

    if($result and ($option>0) )
    {
        $ev= new stdClass();
        $ev->id=$eventid;
        $ev->freepositions=$event->freepositions-$numAdd;
        update_record('block_tutorias', $ev);
    }

    if($error)
    {return (-1);}
    else
    {return $result;}

}

/**
 * Compares two timestamps and returns array with differencies (year, month, day, hour, minute, second)
 *
 * @param int $d1 timestamp 1.
 * @param int $d2 timestamp 2.
 * @return array.
 */
function date_diff($d1, $d2){
/* compares two timestamps and returns array with differencies (year, month, day, hour, minute, second)
*/
  //check higher timestamp and switch if neccessary
  if ($d1 < $d2){
    $temp = $d2;
    $d2 = $d1;
    $d1 = $temp;
  }
  /*else {
    $temp = $d1; //temp can be used for day count if required
  }*/

  $temp=$d2;

  $d1 = date_parse(date("Y-m-d H:i:s",$d1));
  $d2 = date_parse(date("Y-m-d H:i:s",$d2));

  //seconds
  if ($d1['second'] >= $d2['second']){
    $diff['second'] = $d1['second'] - $d2['second'];
  }
  else {
    $d1['minute']--;
    $diff['second'] = 60-$d2['second']+$d1['second'];
  }
  //minutes
  if ($d1['minute'] >= $d2['minute']){
    $diff['minute'] = $d1['minute'] - $d2['minute'];
  }
  else {
    $d1['hour']--;
    $diff['minute'] = 60-$d2['minute']+$d1['minute'];
  }
  //hours
  if ($d1['hour'] >= $d2['hour']){
    $diff['hour'] = $d1['hour'] - $d2['hour'];
  }
  else {
    $d1['day']--;
    $diff['hour'] = 24-$d2['hour']+$d1['hour'];
  }
  //days
  if ($d1['day'] >= $d2['day']){
    $diff['day'] = $d1['day'] - $d2['day'];
  }
  else {
    $d1['month']--;
    $diff['day'] = date("t",$temp)-$d2['day']+$d1['day'];
  }
  //months
  if ($d1['month'] >= $d2['month']){
    $diff['month'] = $d1['month'] - $d2['month'];
  }
  else {
    $d1['year']--;
    $diff['month'] = 12-$d2['month']+$d1['month'];
  }
  //years
  $diff['year'] = $d1['year'] - $d2['year'];
 
  return $diff;   

}

/**
 * Check if a year is leap.
 *
 * @param int $year The year.
 * @return bool.
 */
function tutorias_is_leap($year=NULL) {
    return checkdate(2, 29, ($year==NULL)? date('Y'):$year); // devolvemos true si es bisiesto
}


/**
 * Function copied from dmllib and edited to update more than a record
 * Update a record in a table
 *
 * $dataobject is an object containing needed data
 * Relies on $dataobject having a variable "id" to
 * specify the record to update
 *
 * @uses $CFG
 * @uses $db
 * @param string $table The database table to be checked against.
 * @param object $dataobject An object with contents equal to fieldname=>fieldvalue. Must have an entry for 'id' to map to the table specified fieldnameid.
 * @param string $fieldnameid The name of the field that is used to update all the registers with these field equal to the vale in $dataobject->id
 * @return bool
 */
function tutorias_update_recordsV2($table, $dataobject, $fieldnameid, $sqlcondition) {

    //require_once("$CFG->libdir/dmllib.php");
    global $db, $CFG;

    // integer value in id propery required
    if (empty($dataobject->id)) {
        return false;
    }
    $dataobject->id = (int)$dataobject->id;

/// Check we are handling a proper dataobject
    if (is_array($dataobject)) {
        debugging('Warning. Wrong call to update_record(). $dataobject must be an object. array found instead', DEBUG_DEVELOPER);
        $dataobject = (object)$dataobject;
    }

/// Remove this record from record cache since it will change
    if (!empty($CFG->rcache)) { // no === here! breaks upgrade
        rcache_unset_table($table);
    }

/// Temporary hack as part of phasing out all access to obsolete user tables  XXX
    if (!empty($CFG->rolesactive)) {
        if (in_array($table, array('user_students', 'user_teachers', 'user_coursecreators', 'user_admins'))) {
            if (debugging()) { var_dump(debug_backtrace()); }
            error('This SQL relies on obsolete tables ('.$table.')!  Your code must be fixed by a developer.');
        }
    }

/// Begin DIRTY HACK
    if ($CFG->dbfamily == 'oracle') {
        oracle_dirty_hack($table, $dataobject); // Convert object to the correct "empty" values for Oracle DB
    }
/// End DIRTY HACK

/// Under Oracle, MSSQL and PostgreSQL we have our own update record process
/// detect all the clob/blob fields and delete them from the record being updated
/// saving them into $foundclobs and $foundblobs [$fieldname]->contents
/// They will be updated later
    if (($CFG->dbfamily == 'oracle' || $CFG->dbfamily == 'mssql' || $CFG->dbfamily == 'postgres')
      && !empty($dataobject->id)) {
    /// Detect lobs
        $foundclobs = array();
        $foundblobs = array();
        db_detect_lobs($table, $dataobject, $foundclobs, $foundblobs, true);
    }

/// Determine all the fields in the table
    if (!$columns = $db->MetaColumns($CFG->prefix . $table)) {
        return false;
    }
    $data = (array)$dataobject;

    if (defined('MDL_PERFDB')) { global $PERF ; $PERF->dbqueries++; };

/// Pull out data matching these fields
    $update = array();
    foreach ($columns as $column) {
        if ($column->name == 'id') {
            continue;
        }
        if (array_key_exists($column->name, $data)) {
            $key   = $column->name;
            $value = $data[$key];
            if (is_null($value)) {
                $update[] = "$key = NULL"; // previously NULLs were not updated
            } else if (is_bool($value)) {
                $value = (int)$value;
                $update[] = "$key = $value";   // lets keep pg happy, '' is not correct smallint MDL-13038
            } else {
                $update[] = "$key = '$value'"; // All incoming data is already quoted
            }
        }
    }

/// Only if we have fields to be updated (this will prevent both wrong updates +
/// updates of only LOBs in Oracle
    if ($update) {
        $query = "UPDATE {$CFG->prefix}{$table} SET ".implode(',', $update)." WHERE {$fieldnameid} = {$dataobject->id} AND $sqlcondition";
        if (!$rs = $db->Execute($query)) {
            debugging($db->ErrorMsg() .'<br /><br />'.s($query));
            if (!empty($CFG->dblogerror)) {
                $debug=array_shift(debug_backtrace());
                error_log("SQL ".$db->ErrorMsg()." in {$debug['file']} on line {$debug['line']}. STATEMENT:  $query");
            }
            return false;
        }
    }

/// Under Oracle, MSSQL and PostgreSQL, finally, update all the Clobs and Blobs present in the record
/// if we know we have some of them in the query
    if (($CFG->dbfamily == 'oracle' || $CFG->dbfamily == 'mssql' || $CFG->dbfamily == 'postgres') &&
        !empty($dataobject->id) &&
        (!empty($foundclobs) || !empty($foundblobs))) {
        if (!db_update_lobs($table, $fieldnameid.'='.$dataobject->id, $foundclobs, $foundblobs)) {
            return false; //Some error happened while updating LOBs
        }
    }

    return true;
}


/**
 * Function copied from dmllib and edited to update more than a record
 * Update a record in a table
 *
 * $dataobject is an object containing needed data
 * Relies on $dataobject having a variable "id" to
 * specify the record to update
 *
 * @uses $CFG
 * @uses $db
 * @param string $table The database table to be checked against.
 * @param object $dataobject An object with contents equal to fieldname=>fieldvalue. Must have an entry for 'id' to map to the table specified fieldnameid.
 * @param string $fieldnameid The name of the field that is used to update all the registers with these field equal to the vale in $dataobject->id
 * @return bool
 */
function tutorias_update_records($table, $dataobject, $fieldnameid) {

    //require_once("$CFG->libdir/dmllib.php");
    global $db, $CFG;

    // integer value in id propery required
    if (empty($dataobject->id)) {
        return false;
    }
    $dataobject->id = (int)$dataobject->id;

/// Check we are handling a proper dataobject
    if (is_array($dataobject)) {
        debugging('Warning. Wrong call to update_record(). $dataobject must be an object. array found instead', DEBUG_DEVELOPER);
        $dataobject = (object)$dataobject;
    }

/// Remove this record from record cache since it will change
    if (!empty($CFG->rcache)) { // no === here! breaks upgrade
        rcache_unset_table($table);
    }

/// Temporary hack as part of phasing out all access to obsolete user tables  XXX
    if (!empty($CFG->rolesactive)) {
        if (in_array($table, array('user_students', 'user_teachers', 'user_coursecreators', 'user_admins'))) {
            if (debugging()) { var_dump(debug_backtrace()); }
            error('This SQL relies on obsolete tables ('.$table.')!  Your code must be fixed by a developer.');
        }
    }

/// Begin DIRTY HACK
    if ($CFG->dbfamily == 'oracle') {
        oracle_dirty_hack($table, $dataobject); // Convert object to the correct "empty" values for Oracle DB
    }
/// End DIRTY HACK

/// Under Oracle, MSSQL and PostgreSQL we have our own update record process
/// detect all the clob/blob fields and delete them from the record being updated
/// saving them into $foundclobs and $foundblobs [$fieldname]->contents
/// They will be updated later
    if (($CFG->dbfamily == 'oracle' || $CFG->dbfamily == 'mssql' || $CFG->dbfamily == 'postgres')
      && !empty($dataobject->id)) {
    /// Detect lobs
        $foundclobs = array();
        $foundblobs = array();
        db_detect_lobs($table, $dataobject, $foundclobs, $foundblobs, true);
    }

/// Determine all the fields in the table
    if (!$columns = $db->MetaColumns($CFG->prefix . $table)) {
        return false;
    }
    $data = (array)$dataobject;

    if (defined('MDL_PERFDB')) { global $PERF ; $PERF->dbqueries++; };

/// Pull out data matching these fields
    $update = array();
    foreach ($columns as $column) {
        if ($column->name == 'id') {
            continue;
        }
        if (array_key_exists($column->name, $data)) {
            $key   = $column->name;
            $value = $data[$key];
            if (is_null($value)) {
                $update[] = "$key = NULL"; // previously NULLs were not updated
            } else if (is_bool($value)) {
                $value = (int)$value;
                $update[] = "$key = $value";   // lets keep pg happy, '' is not correct smallint MDL-13038
            } else {
                $update[] = "$key = '$value'"; // All incoming data is already quoted
            }
        }
    }

/// Only if we have fields to be updated (this will prevent both wrong updates +
/// updates of only LOBs in Oracle
    if ($update) {
        $query = "UPDATE {$CFG->prefix}{$table} SET ".implode(',', $update)." WHERE {$fieldnameid} = {$dataobject->id}";
        if (!$rs = $db->Execute($query)) {
            debugging($db->ErrorMsg() .'<br /><br />'.s($query));
            if (!empty($CFG->dblogerror)) {
                $debug=array_shift(debug_backtrace());
                error_log("SQL ".$db->ErrorMsg()." in {$debug['file']} on line {$debug['line']}. STATEMENT:  $query");
            }
            return false;
        }
    }

/// Under Oracle, MSSQL and PostgreSQL, finally, update all the Clobs and Blobs present in the record
/// if we know we have some of them in the query
    if (($CFG->dbfamily == 'oracle' || $CFG->dbfamily == 'mssql' || $CFG->dbfamily == 'postgres') &&
        !empty($dataobject->id) &&
        (!empty($foundclobs) || !empty($foundblobs))) {
        if (!db_update_lobs($table, $fieldnameid.'='.$dataobject->id, $foundclobs, $foundblobs)) {
            return false; //Some error happened while updating LOBs
        }
    }

    return true;
}

/**
 * Merge the cells especified in the given worksheet
 *
 * @param object $worksheet An worksheet object that will be modified.
 * @param int $first_row the number of the first row.
 * @param int $irst_col the number of the first row.
 * @param int $last_row the number of the first row.
 * @param int $last_col the number of the first row.
 * @param object $format An object with the format of the cell.
 */
function tutorias_merge_cells(&$worksheet,$first_row, $first_col, $last_row, $last_col,$format)
{
    for($i=$first_row;$i<=$last_row;$i++)
    {
        for($j=$first_col;$j<=$last_col;$j++)
        {
            $worksheet->write_blank($i,$j,$format);
        }
    }
    $worksheet->merge_cells($first_row, $first_col, $last_row, $last_col);
}

/**
 * Generate the type of a tutorship.
 *
 * @param object $event An object with the tutorship.
 * @return string The type of a tutorship.
 */
function tutorias_get_string_type_tutorship($event){
    switch ($event->type)
        {
            case 0:
                $type=get_string('individual', 'block_tutorias');
                break;
            case 1:
                $type=get_string('group', 'block_tutorias');
                break;
            case 2:
                $type=get_string('event', 'block_tutorias');
                break;
            case 3:
                $type=get_string('review', 'block_tutorias');
                break;
        }

    return $type;
}

/**
 * Generate a file in excel format with the info of a tutorship and the students suscribed to it.
 *
 * @uses $CFG
 * @param array $students The list with the students of the tutorship.
 * @param object $event An object with the tutorship.
 * @param string $title The title of the excel file.
 * @param string $path The path where the file is written.
 * @return string The name of the file.
 */
function tutorias_file_export($students, $event, $title, $path)
{
    global $CFG;
    require_once($CFG->dirroot.'/lib/excellib.class.php');

    $teacher=get_record('user', 'id', $event->teacherid);

    $num_students=count($students);
    $strfichero = get_string('filename','block_tutorias');
    $filename=$strfichero."_".$event->tutorshiptitle.".xls";
    /// Calculate file name
    $downloadfilename = clean_filename($filename);
    /// Creating a workbook
    $workbook = new MoodleExcelWorkbook($path.$downloadfilename);
    /// Sending HTTP headers
    $workbook->send($downloadfilename);
    /// Adding the worksheet
    $myxls =& $workbook->add_worksheet($strfichero);
    $myxls->set_column(0,0,5);
    $myxls->set_column(3,3,11);
    $myxls->set_column(4,6,16);
    $format= &$workbook->add_format();
    
    /// Print names of all the fields
    $format->set_bold();
    $format->set_size(14);
    $format->set_fg_color('red');
    $format->set_align('center');
    $format->set_border(1);
    $format->set_Align('merge');
    
    $myxls->write_string(0,0,$title, $format);
    tutorias_merge_cells($myxls,0,0,0,6,$format);
    
    $format2= &$workbook->add_format();
    $format2->set_size(14);
    $format2->set_fg_color('gray');
    $format2->set_align('center');
    $format2->set_bottom(1);
   
    $myxls->write_string(1,0,$event->tutorshiptitle, $format2);
    tutorias_merge_cells($myxls,1,0,1,6,$format2);
    
    $format3= &$workbook->add_format();
    $format3->set_bold();
    $myxls->write_string(2,0,get_string('teacher', 'block_tutorias').": ", $format3);
    $myxls->merge_cells(2,0,2,2); 
    $myxls->write_string(2,3,$teacher->firstname." ".$teacher->lastname);
    $myxls->merge_cells(2,3,2,6);
    $myxls->write_string(3,0,get_string('typetutorship', 'block_tutorias').": ", $format3);
    $myxls->merge_cells(3,0,3,2);
    $myxls->write_string(3,3,tutorias_get_string_type_tutorship($event));
    $myxls->merge_cells(3,3,3,6);
    $myxls->write_string(4,0,get_string('place', 'block_tutorias').": ", $format3);
    $myxls->merge_cells(4,0,4,2);
    $myxls->write_string(4,3,$event->place);
    $myxls->merge_cells(4,3,4,6); 
    $myxls->write_string(5,0,get_string('numstudents', 'block_tutorias').": ", $format3);
    $myxls->merge_cells(5,0,5,3);
    $myxls->write_string(5,4,$num_students);
    $myxls->merge_cells(5,4,5,6);
    $myxls->write_string(6,0,get_string('starttime',  'block_tutorias').": ", $format3);
    $myxls->merge_cells(6,0,6,2);
    $myxls->write_string(6,3,ucfirst(userdate($event->starttime, get_string('strftime_fulldate','block_tutorias'))));
    $myxls->merge_cells(6,3,6,6); 
    $myxls->write_string(7,0,get_string('hour',  'block_tutorias').": ", $format3);
    $myxls->merge_cells(7,0,7,2);
    $myxls->write_string(7,3,ucfirst(userdate($event->starttime, get_string('strftime_fulltime','block_tutorias'))));
    $myxls->merge_cells(7,3,7,6); 
    $myxls->write_string(8,0,get_string('intro', 'block_tutorias').": ", $format3);
    $myxls->merge_cells(8,0,8,2);
    $myxls->write_string(8,3,$event->intro);
    $myxls->merge_cells(8,3,8,6); 

    $format5= &$workbook->add_format();
    $format5->set_size(12);
    $format2->set_fg_color(31);
    $format5->set_align('center');
    $format5->set_bottom(1);
    $format5->set_top(1);
    $format5->set_fg_color(31);
    $myxls->write_string(9,0,get_string('students', 'block_tutorias').':', $format5);
    tutorias_merge_cells($myxls,9,0,9,6,$format5);
    $myxls->write_string(10,0,get_string('hour', 'block_tutorias'), $format3);
    $myxls->write_string(10,1,get_string('lastname', 'block_tutorias'), $format3);
    $myxls->write_string(10,3,get_string('name', 'block_tutorias'), $format3);
    $myxls->write_string(10,4,get_string('mail', 'block_tutorias'), $format3);
    $myxls->write_string(10,6,get_string('comments2', 'block_tutorias'), $format3);
    $myxls->merge_cells(10,1,10,2);
    $myxls->merge_cells(10,5,10,6);
//var_dump($students);
    $row=11;
    $counter=1;
    foreach ($students as $student)
    {
    $positions=get_recordset_select('block_tutorias_students', 'eventid='. $event->id.' and studentid='.$student->id,'position');
     //$position=get_record('block_tutorias_students', 'eventid', $event->id,'studentid',$student->id);
    if (isset($positions)){   
        foreach($positions as $position){
            if ($position['position']==-1)
            {
                $myxls->write_string($row,0,$counter);    

                $myxls->write_string($row,1,$student->lastname);
                $myxls->write_string($row,3,$student->firstname);
                $myxls->write_string($row,4,$student->email);
                $myxls->write_string($row,5,$position['comments']);
                $myxls->merge_cells($row,1,$row,2);
                $myxls->merge_cells($row,5,$row,6);
                $row++;
                 $counter++;
            }
        }
    }
    }

    foreach ($students as $student)
    {
    $positions=get_recordset_select('block_tutorias_students', 'eventid='. $event->id.' and studentid='.$student->id,'position');
     //$position=get_record('block_tutorias_students', 'eventid', $event->id,'studentid',$student->id);
    if (isset($positions)){   
        foreach($positions as $position){    
            if ($position['position']!=-1)
            {
                $myxls->write_string($row,0,strftime(get_string('strftime_time', 'block_tutorias'),$event->starttime+($position['position']*$event->durationstudent)));
    
                $myxls->write_string($row,1,$student->lastname);
                $myxls->write_string($row,3,$student->firstname);
                $myxls->write_string($row,4,$student->email);
                $myxls->write_string($row,5,$position['comments']);
                $myxls->merge_cells($row,1,$row,2);
                $myxls->merge_cells($row,5,$row,6);

                $row++;
            }
        }
       }
    }

    /// Close the workbook
    $workbook->close();
    //return "$CFG->wwwroot/mod/gruposlab/temp/".$downloadfilename;
    return $downloadfilename;
}

?>
