<?php
/**
 * Export students to excel.
 *
 * @author mjga&dma
 * @package blocktutorias
 */

// declare any globals we need to use
    global $CFG, $USER;

// include moodle API and any supplementary files/API
    require_once( '../../config.php');
    require_once( $CFG->dirroot.'/blocks/tutorias/lib.php');
    require_once( $CFG->libdir.'/filelib.php');
    require_once( $CFG->libdir.'/blocklib.php' );

// check for all required variables
    $instanceid = required_param('instanceid', PARAM_INT);
    $courseid = required_param('courseid',PARAM_INT);
    $id = required_param('eventid', PARAM_INT );
 
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



$students=tutorias_get_students_tutorship($id,'position');

$dirpath=$CFG->dataroot.'/temp/excel/';

$filepath=tutorias_file_export($students, $event,$titulo, $dirpath);
add_to_log($courseid,'upload','block_tutorias export tutorship',$CFG->wwwroot.'/blocks/tutorias/view.php?eventid='.$id.'&courseid='.$courseid.'&instanceid='.$instanceid,'tutorship exported');

send_temp_file($dirpath.$filepath,$filepath);
//close_window();
?>
