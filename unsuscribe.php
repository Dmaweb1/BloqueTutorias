 <?php

 /**
 * Unsuscribe the students from a tutorship.
 *
 * @author dma
 * @package blocktutorias
 */

require_once('../../config.php');
require_once( $CFG->dirroot.'/blocks/tutorias/lib.php');
require_once( $CFG->libdir.'/blocklib.php' );

global $CFG, $USER, $SITE;

$courseid = required_param('courseid',PARAM_INT);
$eventid = required_param('eventid', PARAM_INT );
$instanceid = required_param('instanceid', PARAM_INT);
$confirm = optional_param('confirm',0,PARAM_INT);
$comemanage = optional_param('manage',0, PARAM_INT );

$studentid =  $USER->id;

if (! $course = get_record('course', 'id', $courseid) ) {
error(get_string('invalidcourse', 'block_simplehtml'). $courseid);
}

require_login($course);
require_capability('block/tutorias:suscribetutory', get_context_instance(CONTEXT_COURSE, $courseid));

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
 
    if(!tutorias_suscribed_tutorship($eventid,$studentid))
    {
        error(get_string('invalidsucription','block_tutorias', $eventid));
    }
 //we get the event, and validate it
    if(!$event = get_record('block_tutorias' ,'id',$eventid))
    {
        error(get_string('invalidevent','block_tutorias', $eventid));
    }

// print the header and associated data
    $site = get_site();
    $navlinks = array();
    $navlinks[] = array('name' => $titulo, 'link' => $CFG->wwwroot.'/blocks/tutorias/view.php?&instanceid='.$instanceid.'&courseid='.$courseid, 'type' => 'activityinstance');
    if($comemanage==1)
        { $navlinks[] = array('name' => get_string('manage', 'block_tutorias'), 'link' => $CFG->wwwroot.'/blocks/tutorias/manage.php?&instanceid='.$instanceid.'&courseid='.$courseid, 'type' => 'activityinstance');}
    $navlinks[] = array('name' => $event->tutorshiptitle, 'link' => $CFG->wwwroot.'/blocks/tutorias/view.php?eventid='.$eventid.'&instanceid='.$instanceid.'&courseid='.$courseid .'&manage='.$comemanage, 'type' => 'activityinstance');
    $navlinks[] = array('name' => get_string('unsuscribeto', 'block_tutorias', $event->tutorshiptitle), 'link' => null, 'type' => 'activityinstance');
    $navigation = build_navigation($navlinks);

    print_header("$course->shortname: $titulo", $course->fullname, $navigation, '', '', true, '', user_login_string($course, $USER));


if(!$confirm){
    $optionsno = array('id'=>$courseid);
    $optionsno["manage"]=$comemanage;
    $optionsyes = array ('instanceid'=>$instanceid,'courseid'=>$courseid,'eventid'=>$eventid,'confirm'=>1, 'sesskey'=>sesskey());
    $optionsyes["manage"]=$comemanage;
    print_heading(get_string('confirunsuscribe', 'block_tutorias'));
    notice_yesno(get_string('unsuscribe', 'block_tutorias', $event->tutorshiptitle), 'unsuscribe.php',
                $CFG->wwwroot.'/course/view.php', $optionsyes, $optionsno, 'get', 'get');
} 
else {
    if (confirm_sesskey())
    {
        if (! tutorias_unsuscribe_student($instanceid,$studentid,$eventid)) {
            error(get_string('unsuscribeerror','block_tutoriasl',$id));
        }
    }
    else {
        error(get_string('sessionerror','block_tutorias'));
    }
    if($block_tutorias->configdata->alowunsubscribesendmail==1)
    {
        //we send an e-mail to the teacher
        $support_user=get_admin();
        $subject=get_string('unsuscribesubject', 'block_tutorias',$event->tutorshiptitle);
        $messagedata= new stdClass();
        $messagedata->name = tutorias_username($studentid);
        $messagedata->tutorship=$event->tutorshiptitle;
        $messagedata->body=$CFG->wwwroot.'/blocks/tutorias/view_students.php?eventid='.$event->id.'&courseid='.$course->id.'&instanceid='.$instanceid;                 
        $message=get_string('unsuscribemessage', 'block_tutorias', $messagedata);                
        tutorias_send_mail_student_suscribed($event->id,$support_user, $subject, $message);
    }

    echo "<br>";
    print_box(get_string('unsuscribeok', 'block_tutorias', $event->tutorshiptitle));
    add_to_log($eventid, 'block_tutorias', 'unsuscribe user from tutorship',$CFG->wwwroot.'/blocks/tutorias/view.php?eventid='.$eventid.'&courseid='.$course->id.'&instanceid='.$instanceid,'studentID='.$USER->id .'unsuscribed to eventID='.$eventid, $eventid, $USER->id);
    redirect("$CFG->wwwroot/course/view.php?id=$courseid");
}

print_footer();
?>
