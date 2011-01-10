<?php
 /**
 * Show info about the tutorships.
 *
 * @author dma
 * @package blocktutorias
 */

// declare any globals we need to use
    global $CFG, $USER;

// include moodle API and any supplementary files/API
    require_once("../../config.php");
    require_once( $CFG->dirroot.'/blocks/tutorias/lib.php');
    require_once( $CFG->libdir.'/blocklib.php' );

// check for all required variables
    $instanceid = required_param('instanceid', PARAM_INT);
    $courseid = required_param('courseid',PARAM_INT);
    $id = optional_param('eventid',null, PARAM_INT );
    $idrep = optional_param('repetitionid',null, PARAM_INT );
    $idday = optional_param('dayid',null, PARAM_INT );
    $viewusertutorship = optional_param('viewusertutorship',0, PARAM_INT );
    $comemanage = optional_param('manage',0, PARAM_INT );

//Get the contex of the course
    $context = get_context_instance(CONTEXT_COURSE, $courseid);

// ensure we have a valid courseid and can load the associated course object
    if (! $course = get_record('course', 'id', $courseid) ) {
        print_error(get_string('invalidcourse', 'block_tutorias', $courseid));
    }

// ensure the user has access to this course
    require_login($course);

// ensure the user has appropriate permissions to access this area
    require_capability('block/tutorias:viewtutory', get_context_instance(CONTEXT_COURSE, $courseid));

// ensure we have a valid block_tutorias  id and we get the object
    if(!$block_tutorias = get_record('block_instance', 'id', $instanceid)){
       print_error(get_string('nopage','block_tutorias', $instanceid));
    }

    $block_tutorias = tutorias_get_block($instanceid);

//we get the title of the block
    if(($block_tutorias->configdata=="")or($block_tutorias->configdata->title==""))
    {
        $titulo=get_string('blockname', 'block_tutorias');
    }
    else
    {
        $titulo=$block_tutorias->configdata->title;
    }


    if($id)
    {
        //we get the event, and validate it 
        if(!$event = get_record('block_tutorias' ,'id',$id))
        {
           print_error(get_string('invalidevent','block_tutorias', $id));
        }
    $events=array($event);
    $title=get_string('event','block_tutorias').': '.$event->tutorshiptitle ;
    }
    elseif($idrep)
    {
           //we get the event and teacher, and validate it 
        if(!$events = tutorias_get_events_repetitions($instanceid,$idrep))
        {
           print_error(get_string('invalidrepetitionid','block_tutorias', $idrep));
        }
    $title=get_string('repetitionsof','block_tutorias').': '.current($events)->tutorshiptitle ;
    }
    elseif($idday)
    {
           //we get the events of the given day and validate it 
        if(!$events = tutorias_get_events_day($instanceid,$idday))
        {
           print_error(get_string('invalidevent','block_tutorias', $instanceid));
        }
    $title=$title=get_string('eventsofday','block_tutorias').': '. strftime(get_string('strftime_fulldate','block_tutorias'),$idday);
    }
    elseif($viewusertutorship==1)
    {
        if(!$events = tutorias_get_events_user($instanceid,$USER->id))
        {
          notice(get_string('noevents','block_tutorias', $instanceid));
        }

    $title=get_string('yourevents','block_tutorias');
    }
    elseif($instanceid)
    {
        if(!$events = tutorias_get_events($instanceid))
        {
          notice(get_string('noevents','block_tutorias', $instanceid));
        }

    $title=get_string('eventsof','block_tutorias').': '.$titulo;
    }   
    else
    {
        print_error(get_string('missingparameters','block_tutorias', $instanceid));
    }



// print the header and associated data
    $site = get_site();
    $navlinks = array();
    $navlinks[] = array('name' => $titulo, 'link' => $CFG->wwwroot.'/blocks/tutorias/view.php?&instanceid='.$instanceid.'&courseid='.$courseid, 'type' => 'activityinstance');
    if($comemanage==1)
    { $navlinks[] = array('name' => get_string('manage', 'block_tutorias'), 'link' => $CFG->wwwroot.'/blocks/tutorias/manage.php?&instanceid='.$instanceid.'&courseid='.$courseid, 'type' => 'activityinstance');}
    $navlinks[] = array('name' => $title, 'link' => null, 'type' => 'activityinstance');
    $navigation = build_navigation($navlinks);

     print_header(strip_tags($site->fullname), $site->fullname, $navigation, '', '<meta name="description" content="'. s(strip_tags($site->summary)) .'">', true, '', user_login_string($course, $USER));


//print the titles
    print_heading($titulo, 'center');

    echo '<center>';
    print_simple_box($title, 'center', '', '#eee');
    echo '</center>';

//Adap the object block_tutorias for the use with tutorias_calendar_get_mini
   $bloque->instance=$block_tutorias;
   $bloque->config=$block_tutorias->configdata;
//create and print the table with the months
   foreach($events as $event)
   {   
     $teacher=get_record('user', 'id', $event->teacherid);
    if (!has_capability('block/tutorias:managetutory', $context) and (!$event->visible))
    {continue;}


    $classes="box generalbox generalboxcontent boxaligncenter boxwidthwide";
    $output  = print_box_start($classes, '', true);
    $output .= stripslashes_safe("<H2>".$event->tutorshiptitle."</H2>");
    $output .= format_text("<B>".get_string('intro', 'block_tutorias').": </B><BR>".$event->intro."<BR>");
    $output .= stripslashes_safe("<B>".get_string('teacher', 'block_tutorias').": </B>".$teacher->firstname." ".$teacher->lastname."<BR>");
    $output .= stripslashes_safe("<B>".get_string('starttime',  'block_tutorias').": </B>". ucfirst(userdate($event->starttime, get_string('strftime_fulldate','block_tutorias')))."<BR>");
    $output .= stripslashes_safe("<B>".get_string('hour',  'block_tutorias').": </B>". ucfirst(userdate($event->starttime, get_string('strftime_fulltime','block_tutorias')))."<BR>");
    $output .=stripslashes_safe("<B>".get_string('typetutorship', 'block_tutorias').": </B>");
$type=tutorias_get_string_type_tutorship($event);
    $output .=$type."<BR>";
    $output .= stripslashes_safe("<B>".get_string('place',  'block_tutorias').": </B>". $event->place."<BR>");
        $output .= stripslashes_safe("<B>".get_string('visible',  'block_tutorias').": </B>". get_string('visible'.$event->visible,  'block_tutorias')."<BR>");
    $output .= stripslashes_safe("<B>".get_string('notaviablebefore',  'block_tutorias').": </B>". ucfirst(userdate($event->starttime-$event->notaviablebefore, get_string('strftime_fulldatetime','block_tutorias')))."<BR>");

    if(tutorias_suscribed_tutorship($event->id,$USER->id))
    {
        $subs_at=tutorias_get_info_student_subscrived($event->id, $instanceid,$USER->id);
        $output .= "<B>".get_string('susbcribed_at', 'block_tutorias')."</B>";
        $output .= $subs_at.'<BR>';
    }
    if($event->freepositions==-1)
    {
        $output .= stripslashes_safe("<B>".get_string('posfree',  'block_tutorias').": </B>". get_string('unlimited',  'block_tutorias')."<BR><br><BR>");
    }
    else
    {
        $output .= stripslashes_safe("<B>".get_string('posfree',  'block_tutorias').": </B>". $event->freepositions."<BR><br><BR>");
    }


    $first_event_rep=tutorias_is_first_event_rep($instanceid,$event->id);
//if the event is the first of a repetition his idrepetition is 0 and should be equal to his id.    
    if($first_event_rep)
    {$event->idrepetition=$event->id;}
//If the event has repetitions whe show the info
    if((($event->idrepetition!=0) and (!$idrep)) or ($first_event_rep))
    {
        $output .= print_box_start($classes, '', true);
        $output .= stripslashes_safe("<H3>".get_string('repetition', 'block_tutorias')."</H3>");
        if(!$first_event_rep)
        {
            $output .= stripslashes_safe("<B>".get_string('repetitionstart', 'block_tutorias').": </B>".ucfirst(userdate(tutorias_get_date_start_repetition($instanceid,$event->idrepetition), get_string('strftime_fulldate','block_tutorias')))."<BR>");
        }else
        {
            $output .="<B>".get_string('firstrep',  'block_tutorias')."</B><br>";
            $output .= stripslashes_safe("<B>".get_string('starttime',  'block_tutorias').": </B>". ucfirst(userdate($event->starttime, get_string('strftime_fulldate','block_tutorias')))."<BR>");
        }
                $output .= stripslashes_safe("<B>".get_string('repetitionend', 'block_tutorias').": </B>".ucfirst(userdate(tutorias_get_date_end_repetition($instanceid,$event->idrepetition), get_string('strftime_fulldate','block_tutorias')))."<BR>");

        $rep_time=tutorias_get_time_betwn_rep($instanceid,$event->idrepetition);

        if($rep_time["day"]!=0)
        {
            $output .= stripslashes_safe("<B>".get_string('repeateachday',  'block_tutorias',$rep_time["day"])."</B><BR>");
        }
        elseif($rep_time["month"]!=0)
        {
            $output .= stripslashes_safe("<B>".get_string('repeateachmonth',  'block_tutorias',$rep_time["month"])."</B><BR>");
        }
        elseif($rep_time["year"]!=0)
        {
            $output .= stripslashes_safe("<B>".get_string('repeateachyear',  'block_tutorias',$rep_time["year"])."</B><BR>");
        }


        $linkvie= $CFG->wwwroot.'/blocks/tutorias/view.php';
        $optionsvie=array();
        $optionsvie["courseid"]=$courseid;
        $optionsvie["instanceid"]= $instanceid;
        $optionsvie["repetitionid"]=$event->idrepetition;
        $optionsvie["manage"]=$comemanage;
        $labelvie=get_string('seerep', 'block_tutorias');
        $output .=print_single_button($linkvie, $optionsvie, $labelvie,'get', '_self', true, '',  false, '');
        
        if (has_capability('block/tutorias:managetutory', $context))
        {

            $linkedit= $CFG->wwwroot.'/blocks/tutorias/edit.php';
            $optionsedit=array();
            $optionsedit["courseid"]=$courseid;
            $optionsedit["instanceid"]= $instanceid;
            $optionsedit["eventid"]=$event->id;
            $optionsedit["editrepetition"]=1;
            $optionsedit["manage"]=$comemanage;
            $labeledit=get_string('editrep', 'block_tutorias');
            $output .=print_single_button($linkedit, $optionsedit, $labeledit,'get', '_self', true, '',  false, '');

            $linkedit= $CFG->wwwroot.'/blocks/tutorias/edit_from_now.php';
            $optionsedit=array();
            $optionsedit["courseid"]=$courseid;
            $optionsedit["instanceid"]= $instanceid;
            $optionsedit["eventid"]=$event->id;
            $optionsedit["editrepetition"]=1;
            $optionsedit["manage"]=$comemanage;
            $labeledit=get_string('edifromnow', 'block_tutorias');
            $output .=print_single_button($linkedit, $optionsedit, $labeledit,'get', '_self', true, '',  false, '');

            $linkdel= $CFG->wwwroot.'/blocks/tutorias/delete.php';
            $optionsdel=array();
            $optionsdel["courseid"]=$courseid;
            $optionsdel["instanceid"]= $instanceid;
            $optionsdel["eventid"]=$event->id;
            $optionsdel["deleterepetition"]=1;
            $optionsdel["manage"]=$comemanage;
            $labeldel=get_string('delrep', 'block_tutorias');
            $output .=print_single_button($linkdel, $optionsdel, $labeldel,'get', '_self', true, '',  false, '');

            $linkdel= $CFG->wwwroot.'/blocks/tutorias/delete_from_now.php';
            $optionsdel=array();
            $optionsdel["courseid"]=$courseid;
            $optionsdel["instanceid"]= $instanceid;
            $optionsdel["eventid"]=$event->id;
            $optionsdel["deleterepetition"]=1;
            $optionsdel["manage"]=$comemanage;
            $labeldel=get_string('delfromnow', 'block_tutorias');
            $output .=print_single_button($linkdel, $optionsdel, $labeldel,'get', '_self', true, '',  false, '');


        }

        $output .= print_box_end(true);
        $output .='<br>';
    }
    
    if (has_capability('block/tutorias:managetutory', $context))
    {
        $output .=print_container_start(false,"div_botones","",true);        
        $link= $CFG->wwwroot.'/blocks/tutorias/edit.php';
        $options=array();
        $options["courseid"]=$courseid;
        $options["instanceid"]= $instanceid;
        $options["eventid"]=$event->id;
        $options["manage"]=$comemanage;
        $label=get_string('editevent', 'block_tutorias');
        $output .=print_single_button($link, $options, $label,'get', '_self', true, '',  false, '');
        $output .=print_container_end(true);

        $output .=print_container_start(false,"div_botones","",true);
        $link= $CFG->wwwroot.'/blocks/tutorias/delete.php';
        $label=get_string('deleteevent', 'block_tutorias');
        $output .=print_single_button($link, $options, $label,'get', '_self', true, '',  false, '');
        $output .=print_container_end(true);
        if($event->type!=2)
        {
            $output .=print_container_start(false,"div_botones","",true);
            $link= $CFG->wwwroot.'/blocks/tutorias/view_students.php';
            $label=get_string('viewstudents', 'block_tutorias');
            $output .=print_single_button($link, $options, $label,'get', '_self', true, '',  false, '');
            $output .=print_container_end(true);
        }
    }
    if (has_capability('block/tutorias:suscribetutory', $context) and ($event->type!=2))
    {
        $output .= print_container_start(false,"div_botones","",true);    
    
        if(tutorias_suscribed_tutorship($event->id,$USER->id))
        {
            $link= $CFG->wwwroot.'/blocks/tutorias/unsuscribe.php';
            $label=get_string('unsuscribestudent', 'block_tutorias');
        }
        else
        {
            $link= $CFG->wwwroot.'/blocks/tutorias/suscribe.php';
            $label=get_string('suscribestudent', 'block_tutorias');

        }
        $options=array();
        $options["courseid"]=$courseid;
        $options["instanceid"]= $instanceid;
        $options["eventid"]=$event->id;
        $options["manage"]=$comemanage;
        if((tutorias_tutorship_open($event->id) and tutorias_not_complete($event->id))or tutorias_suscribed_tutorship($event->id,$USER->id))
        {
            $output .=print_single_button($link, $options, $label,'get', '_self', true, '',  false, '');
        }
        $output .=print_container_end(true);

    }
    $output .= print_box_end(true);
        echo $output;

    }
// print the footer
    print_footer();
?>
