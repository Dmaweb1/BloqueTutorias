<?php
 /**
 * Show the students suscribed to a tutorship.
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
    $id = required_param('eventid', PARAM_INT );
    $comemanage = optional_param('manage',0, PARAM_INT );
 
//Get the contex of the course
    $context = get_context_instance(CONTEXT_COURSE, $courseid);

// ensure we have a valid courseid and can load the associated course object
    if (! $course = get_record('course', 'id', $courseid) ) {
        error(get_string('invalidcourse', 'block_tutorias', $courseid));
    }

// ensure the user has access to this course
    require_login($course);

// ensure the user has appropriate permissions to access this area
    require_capability('block/tutorias:managetutory', get_context_instance(CONTEXT_COURSE, $courseid));

// ensure we have a valid block_tutorias  id and we get the object
    if(!$block_tutorias = get_record('block_instance', 'id', $instanceid)){
       error(get_string('nopage','block_tutorias', $instanceid));
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
           error(get_string('invalidevent','block_tutorias', $id));
        }
    $title=get_string('suscribedsto','block_tutorias',$event->tutorshiptitle);
    }

// print the header and associated data
    $site = get_site();
    $navlinks = array();
    $navlinks[] = array('name' => $titulo, 'link' => $CFG->wwwroot.'/blocks/tutorias/view.php?&instanceid='.$instanceid.'&courseid='.$courseid, 'type' => 'activityinstance');
    if($comemanage==1)
        { $navlinks[] = array('name' => get_string('manage', 'block_tutorias'), 'link' => $CFG->wwwroot.'/blocks/tutorias/manage.php?&instanceid='.$instanceid.'&courseid='.$courseid, 'type' => 'activityinstance');}
    $navlinks[] = array('name' => $event->tutorshiptitle, 'link' => $CFG->wwwroot.'/blocks/tutorias/view.php?eventid='.$id.'&instanceid='.$instanceid.'&courseid='.$courseid .'&manage='.$comemanage, 'type' => 'activityinstance');
    $navlinks[] = array('name' => get_string('participants', 'block_tutorias'), 'link' => null, 'type' => 'activityinstance');
    $navigation = build_navigation($navlinks);

    print_header("$course->shortname: $titulo", $course->fullname, $navigation, '', '', true, '', user_login_string($course, $USER));

//print the titles
    print_heading($titulo, 'center');

    echo '<center>';
    print_simple_box($title, 'center', '', '#eee');
    echo '</center>';

//Adap the object block_tutorias for the use with tutorias_calendar_get_mini
   $bloque->instance=$block_tutorias;
   $bloque->config=$block_tutorias->configdata;

   $output= tutorias_draw_students_table($id, $instanceid,sesskey(),true);
   $output.="<center>";
   $link= $CFG->wwwroot.'/blocks/tutorias/manage_students.php';
   $label=get_string('addremovestudents', 'block_tutorias');
   $options=array();
   $options["courseid"]=$courseid;
   $options["instanceid"]= $instanceid;
   $options["manage"]=$comemanage;
   $options["eventid"]=$event->id;
   $output.= print_single_button($link, $options, $label,'get', '_self', true, '',  false, '');

   $link2= '/blocks/tutorias/export.php?courseid='.$courseid.'&instanceid='.$instanceid.'&eventid='.$event->id;
   $label2=get_string('export', 'block_tutorias');
   $output.=button_to_popup_window($link2, 'export',$label2,$height=400, $width=500, $label2, null, true,$id=null, $class=null) ;

 
   $output.= "</center>";
   echo  $output;

// print the footer
    print_footer();
?>
