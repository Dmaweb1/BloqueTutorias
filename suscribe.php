 <?php

 /**
 * Suscribe the students from a tutorship.
 *
 * @author dma
 * @package blocktutorias
 */

require_once( '../../config.php');
require_once( $CFG->dirroot.'/blocks/tutorias/lib.php');
require_once( $CFG->libdir.'/blocklib.php' );
require_once( $CFG->dirroot.'/blocks/tutorias/tutorias_suscribe_form.php');

global $CFG, $USER, $SITE;

$courseid = required_param('courseid',PARAM_INT);
$id = required_param('eventid', PARAM_INT );
$instanceid = required_param('instanceid', PARAM_INT);
$option = optional_param('option',0,PARAM_INT);
$comemanage = optional_param('manage',0, PARAM_INT );

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
    if(($block_tutorias->configdata->title=="")or($block_tutorias->configdata==""))
    {
        $titulo=get_string('blockname', 'block_tutorias');
    }
    else
    {
        $titulo=$block_tutorias->configdata->title;
    }

 //we get the event, and validate it 
    if(!$event = get_record('block_tutorias' ,'id',$id))
    {
        error(get_string('invalidevent','block_tutorias', $id));
    }

// print the header and associated data
    $site = get_site();
    $navlinks = array();
    $navlinks[] = array('name' => $titulo, 'link' => $CFG->wwwroot.'/blocks/tutorias/view.php?&instanceid='.$instanceid.'&courseid='.$courseid, 'type' => 'activityinstance');
    if($comemanage==1)
        { $navlinks[] = array('name' => get_string('manage', 'block_tutorias'), 'link' => $CFG->wwwroot.'/blocks/tutorias/manage.php?&instanceid='.$instanceid.'&courseid='.$courseid, 'type' => 'activityinstance');}
    $navlinks[] = array('name' => $event->tutorshiptitle, 'link' => $CFG->wwwroot.'/blocks/tutorias/view.php?eventid='.$id.'&instanceid='.$instanceid.'&courseid='.$courseid .'&manage='.$comemanage, 'type' => 'activityinstance');
    $navlinks[] = array('name' => get_string('suscribeto', 'block_tutorias', $event->tutorshiptitle), 'link' => null, 'type' => 'activityinstance');
    $navigation = build_navigation($navlinks);

    print_header("$course->shortname: $titulo", $course->fullname, $navigation, '', '', true, '', user_login_string($course, $USER));


$createform=new tutorias_suscribe_form();

if($createform->is_cancelled())
    {//Si se cancela el formulario se redirige a la página principal del curso
        redirect("$CFG->wwwroot/course/view.php?id=$course->id");
    }
    else if ($fromform = $createform->get_data())
    {
    if(tutorias_suscribed_tutorship($fromform->eventid,$fromform->userid))
    {error (get_string('errorsuscribetwice', 'block_tutorias'));}
    else
    {
        if(tutorias_position_free($fromform->eventid, $fromform->option) or ($event->type!=2))
        {
            if(isset($fromform->option2))
            {
                $option2=$fromform->option2;
            }
            else {$option2=0;}

            if(isset($fromform->multipletutorship))
            {
                $multipletutorship=$fromform->multipletutorship;
            }
            else {$multipletutorship=null;}

            $result=tutorias_suscribe_student($fromform->instanceid,$multipletutorship,$fromform->userid,$fromform->eventid,$fromform->option,$fromform->comments,$option2,($event->type==0));

            if($result==0)
            { 
                error(get_string('errortutorshipfull', 'block_tutorias'));
            }
            if($result==(-1))
            { 
                error(get_string('errorsuscribing2', 'block_tutorias'));
            }

            
            if($result)
            { 
                if($block_tutorias->configdata->alowsubscribesendmail==1){
                    //we send an e-mail to the teacher
                    $support_user=get_admin();
                    $subject=get_string('suscribesubject', 'block_tutorias',$event->tutorshiptitle);
                    $messagedata= new stdClass();
                    $messagedata->name = tutorias_username($fromform->userid);
                    $messagedata->tutorship=$event->tutorshiptitle;
                    $messagedata->body=$CFG->wwwroot.'/blocks/tutorias/view_students.php?eventid='.$event->id.'&courseid='.$course->id.'&instanceid='.$instanceid;                 
                    $message=get_string('suscribemessage', 'block_tutorias', $messagedata);                
                    tutorias_send_mail_student_suscribed($fromform->eventid,$support_user, $subject, $message);
                }

                //Usamos un hack para que la direccion la muestre bien. le decimos que somos el bloque upload
                add_to_log($courseid,'upload','block_tutorias student suscribed',$CFG->wwwroot.'/blocks/tutorias/view.php?eventid='.$fromform->eventid.'&courseid='.$course->id.'&instanceid='.$instanceid,'studentID='.$USER->id .'suscribed to eventID='.$fromform->eventid);
                echo "<br>";
                print_box(get_string('suscribedto', 'block_tutorias',$titulo));
                redirect("$CFG->wwwroot/course/view.php?id=$course->id");
            }
            else
            {
                error(get_string('errorsuscribing', 'block_tutorias'));
                $error= mysql_error(); 
                //Usamos un hack para que la direccion la muestre bien. le decimos que somos el bloque upload
                add_to_log($courseid,'upload','block_tutorias error suscrbing user','','error in tutorship. Error:'.$error);
            }
            redirect("$CFG->wwwroot/course/view.php?id=$courseid");
        }
        else
        {
            error (get_string('errorsuscribetwice2', 'block_tutorias'));
        }
    }


    }
    else{


     $toform['instanceid']=$instanceid;
         $toform['courseid']=$courseid;
         $toform['eventid']=$event->id;
     $toform['userid']=$USER->id;
     $toform['manage']=$comemanage;
         $createform->set_data($toform);            
         $createform->display();

    
    }

print_footer();
?>
