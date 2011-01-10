<?php

 /**
 * Show a calendar with all tutorship.
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
    $cal_y = required_param('tut_y', PARAM_INT );
    $comemanage = optional_param('manage',0, PARAM_INT );

// ensure we have a valid courseid and can load the associated course object
    if (! $course = get_record('course', 'id', $courseid) ) {
        error(get_string('invalidcourse', 'block_tutorias', $courseid));
    }

// ensure the user has access to this course
    require_login($course);

// ensure the user has appropriate permissions to access this area
    require_capability('block/tutorias:viewtutory', get_context_instance(CONTEXT_COURSE, $courseid));

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

// print the header and associated data
    $site = get_site();
    $navlinks = array();
    $navlinks[] = array('name' => $titulo, 'link' => $CFG->wwwroot.'/blocks/tutorias/view.php?&instanceid='.$instanceid.'&courseid='.$courseid, 'type' => 'activityinstance');
    if($comemanage==1)
    { $navlinks[] = array('name' => get_string('manage', 'block_tutorias'), 'link' => $CFG->wwwroot.'/blocks/tutorias/manage.php?&instanceid='.$instanceid.'&courseid='.$courseid, 'type' => 'activityinstance');}
    $navlinks[] = array('name' =>get_string('calendar_year','block_tutorias',$cal_y), 'link' => null, 'type' => 'activityinstance');
    $navigation = build_navigation($navlinks);

     print_header(strip_tags($site->fullname), $site->fullname, $navigation, '', '<meta name="description" content="'. s(strip_tags($site->summary)) .'">', true, '', user_login_string($course, $USER));

//print the titles
    print_heading($titulo, 'center');
    $href_prev=$CFG->wwwroot.'/blocks/tutorias/months.php?tut_y='.($cal_y-1).'&instanceid='.$instanceid.'&courseid='.$courseid ;
    $href_next=$CFG->wwwroot.'/blocks/tutorias/months.php?tut_y='.($cal_y+1).'&instanceid='.$instanceid.'&courseid='.$courseid ;
    $left_arrow=link_arrow_left($cal_y-1, $href_prev, true, 'previous');
    $right_arrow=link_arrow_right(($cal_y+1), $href_next, true, 'previous');
    echo '<center>';
    print_simple_box($left_arrow.' '.get_string('calendar_year','block_tutorias',$cal_y).' '.$right_arrow, 'center', '', '#eee');
    echo '</center>';

//Adapt the object block_tutorias for the use with tutorias_calendar_get_mini
   $bloque->instance=$block_tutorias;
   $bloque->config=$block_tutorias->configdata;
//create and print the table with the months
    $content='<center><table class="table_year">';
    for($i=0;$i<3;$i++)
    {
    $content.='<tr>';
    for($j=1;$j<=4;$j++)
        {   
        $cal_m=(($i*4)+$j);
        $time = make_timestamp($cal_y,  $cal_m,1);
        $content.='<td class="td_month"><b>'.ucfirst(userdate($time, get_string('strftime_monthyear','block_tutorias'))).'</b>';
        $content.=tutorias_calendar_get_mini($bloque,$cal_m, $cal_y); 
        $content.='</td>';
    }
    $content.='</tr>';
    }
    $content.='</table></center>';
    echo $content;

// print the footer
    print_footer();
?>
