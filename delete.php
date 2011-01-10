 <?php

/**
 * Delete the tutorship.
 *
 * @author dma
 * @package blocktutorias
 */

require_once( '../../config.php');
require_once( $CFG->dirroot.'/blocks/tutorias/lib.php');
require_once( $CFG->libdir.'/blocklib.php' );

global $CFG, $USER, $SITE;

$courseid = required_param('courseid',PARAM_INT);
$id = required_param('eventid', PARAM_INT );
$instanceid = required_param('instanceid', PARAM_INT);
$confirm = optional_param('confirm',0,PARAM_INT);
$deleterepetition = optional_param('deleterepetition',0,PARAM_INT);
$comemanage = optional_param('manage',0, PARAM_INT );

if (! $course = get_record('course', 'id', $courseid) ) {
error(get_string('invalidcourse', 'block_simplehtml'). $courseid);
}

require_login($course);
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
    $navlinks[] = array('name' => get_string('deleteevent', 'block_tutorias').": ".$event->tutorshiptitle, 'link' => null, 'type' => 'activityinstance');
    $navigation = build_navigation($navlinks);

    print_header("$course->shortname: $titulo", $course->fullname, $navigation, '', '', true, '', user_login_string($course, $USER));



if(!$confirm){
    $optionsno = array('id'=>$courseid);
    $optionsyes = array ('deleterepetition'=>$deleterepetition,'instanceid'=>$instanceid, 'courseid'=>$courseid,'eventid'=>$id,'confirm'=>1, 'sesskey'=>sesskey());
    print_heading(get_string('confirmdelete', 'block_tutorias'));
    if($deleterepetition==1)
    {
        if(tutorias_students_suscribed_repetition_tutorship($event->id) and !tutorias_get_repetition_finished($instanceid,$event->idrepetition))
        {
            print_box(get_string('deleterepwarning', 'block_tutorias'),"box noticebox noticeboxcontent boxaligncenter block_tutorias_warning_box");
        }
        notice_yesno(get_string('deleterep', 'block_tutorias', $event->tutorshiptitle), 'delete.php',
                   $CFG->wwwroot.'/course/view.php', $optionsyes, $optionsno, 'get', 'get');
    }
    else
    {
        if(tutorias_students_suscribed_tutorship($event->id) and !tutorias_get_tutorship_finished($instanceid,$event->id))
        {
            print_box(get_string('deleteventwarning', 'block_tutorias'),"box noticebox noticeboxcontent boxaligncenter block_tutorias_warning_box");
        }
        notice_yesno(get_string('deletevent', 'block_tutorias', $event->tutorshiptitle), 'delete.php',
                   $CFG->wwwroot.'/course/view.php', $optionsyes, $optionsno, 'get', 'get');
    }
} 
else {
    if (confirm_sesskey()) {

    if($deleterepetition==0)
    {   
        $tutorship_is_first_even= tutorias_is_first_event_rep($instanceid,$id);
        if($tutorship_is_first_even)
        {
            $events_rep=tutorias_get_events_repetitions($instanceid,$id);
            array_shift($events_rep);
            $event1=array_shift($events_rep);
            $new_id_rep=$event1->id;
            $event1->idrepetition=0;
            $result = update_record('block_tutorias', $event1); 
            foreach($events_rep as $event)
            {
               $event->idrepetition=$new_id_rep;
               $result = update_record('block_tutorias', $event); 
            }

        }
     
        if (! delete_records('block_tutorias','id',$id)) 
        {
            error(get_string('deleterror','block_tutoriasl',$id));
        }else
        {
            print_box(get_string('deleteok', 'block_tutorias', $id));
            add_to_log($id, 'block_tutorias', 'delete tutorship', $CFG->wwwroot.'course/view.php?&id='.
            $courseid, '', $id, $USER->id);
        }
    }elseif($deleterepetition==1)
    {
        if($event->idrepetition!=0)
        {$idrep=$event->idrepetition;}
        else
        {$idrep=$event->id;}
        //first we delete the students
        $res = tutorias_unsuscribe_students_repetition($idrep);
        //second we delete all the events of the repetition
        if($res){ $res = $res and (bool) delete_records('block_tutorias','idrepetition',$idrep);}
        //last we delete the first event of the repetition
        if($res){$res = $res and (bool)delete_records('block_tutorias','id',$idrep);}
        if (!$res) 
        {
            error(get_string('deleterror','block_tutoriasl',$id));
        }else
        {
            print_box(get_string('delerepteok', 'block_tutorias', $id));
                add_to_log($id, 'block_tutorias', 'delete repetition tutorship', $CFG->wwwroot.'course/view.php?&id='.
            $courseid, '', $id, $USER->id);
        }
    }
    }
    else {
        error(get_string('sessionerror','block_tutorias'));
    }
    echo "<br>";
    
    redirect("$CFG->wwwroot/course/view.php?id=$courseid");
}

print_footer();
?>
