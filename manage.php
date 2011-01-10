<?php

/**
 * Create/edit/delete tutorship or repetitions.
 *
 * @author dma
 * @package blocktutorias
 */

// declare any globals we need to use
    global $CFG, $USER;

// include moodle API and any supplementary files/API
    require_once('../../config.php');
    require_once( $CFG->dirroot.'/blocks/tutorias/lib.php');
    require_once( $CFG->libdir.'/blocklib.php' );

// check for all required variables
    $instanceid = required_param('instanceid', PARAM_INT);
    $courseid = required_param('courseid',PARAM_INT);
    //$id = optional_param('eventid',null, PARAM_INT );
    //$idrep = optional_param('repetitionid',null, PARAM_INT );
    //$idday = optional_param('dayid',null, PARAM_INT );

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

$navlinks = array();
$navlinks[] = array('name' => $titulo, 'link' => $CFG->wwwroot.'/blocks/tutorias/view.php?&instanceid='.$instanceid.'&courseid='.$courseid, 'type' => 'activityinstance');
$navlinks[] = array('name' => get_string('manage', 'block_tutorias'), 'link' => null, 'type' => 'activityinstance');
$navigation = build_navigation($navlinks);

print_header("$course->shortname: $titulo", $course->fullname, $navigation, '', '', true, '', user_login_string($course, $USER));




    //print the titles
        print_heading_with_help($titulo, 'help_manage', 'block_tutorias'); 
        //print_heading($titulo, 'center');
        
        echo '<center>';
        print_simple_box(get_string('managery', 'block_tutorias'), 'center', '', '#eee');
        echo '</center>';

        $link1= $CFG->wwwroot.'/blocks/tutorias/create.php';
        $options1=array();
        $options1["courseid"]=$courseid;
        $options1["instanceid"]=$instanceid;
        $options1["manage"]=1;
        $label1=get_string('createnewtutorship', 'block_tutorias');
        $botoncreate=print_single_button($link1, $options1, $label1,'get', '_self', true, '',  false, '');

        $link2= $CFG->wwwroot.'/blocks/tutorias/view.php';
        $options2=array();
        $options2["courseid"]=$courseid;
        $options2["instanceid"]=$instanceid;
        $options2["manage"]=1;
        $label2=get_string('viewalltutorship', 'block_tutorias');
        $botonViewAll=print_single_button($link2, $options2, $label2,'get', '_self', true, '',  false, '');

        $link3= $CFG->wwwroot.'/blocks/tutorias/view.php';
        $options3=array();
        $options3["courseid"]=$courseid;
        $options3["instanceid"]=$instanceid;
        $options3["viewusertutorship"]=1;
        $options3["manage"]=1;
        $label3=get_string('viewmytutorship', 'block_tutorias');
        $botonviewmy=print_single_button($link3, $options3, $label3,'get', '_self', true, '',  false, '');

        $link4= $CFG->wwwroot.'/blocks/tutorias/months.php';
        $thisdate = usergetdate(time()); // Date and time the user sees at his location  
        $options4=array();
        $options4["courseid"]=$courseid;
        $options4["instanceid"]=$instanceid;
        $options4["tut_y"]=$thisdate['year'];
        $options4["manage"]=1;
        $label4=get_string('viewcalendar', 'block_tutorias');
        $botonviewcalendar=print_single_button($link4, $options4, $label4,'get', '_self', true, '',  false, '');

     echo '<center>';
     echo $botoncreate;
     echo '<br>';
     echo $botonViewAll;
     echo '<br>';
     echo $botonviewcalendar;
     echo '<br>';
     echo $botonviewmy;
     echo '</center>';



/* codigo para imprimir pestañas en las paginas se intentara poner en un futuro


$row[] = new tabobject('tab1', "/blocks/tutorias/months.php",$label1);

$row[] = new tabobject('tab2', "/blocks/tutorias/months.php",$label2);

$row[] = new tabobject('tab3', "/blocks/tutorias/months.php",$label3);

$row[] = new tabobject('tab4', "/blocks/tutorias/months.php",$label4);
$tabs[]=$row;

print_tabs($tabs);
*/


// print the footer
    print_footer();
?>
