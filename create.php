<?php
/**
 * Edit the tutorship.
 *
 * @author dma
 * @package blocktutorias
 */
// declare any globals we need to use
    global $CFG, $USER;

// include moodle API and any supplementary files/API
    require_once( '../../config.php');
    require_once( $CFG->dirroot.'/blocks/tutorias/lib.php');
    require_once( $CFG->libdir.'/blocklib.php' );
    require_once( $CFG->dirroot.'/blocks/tutorias/tutorias_create_form.php');

// check for all required variables
    $instanceid = required_param('instanceid', PARAM_INT);
    $courseid = required_param('courseid',PARAM_INT);
    $comemanage = optional_param('manage',0, PARAM_INT );

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

// print the header and associated data
    $site = get_site();
    $navlinks = array();
    $navlinks[] = array('name' => $titulo, 'link' => $CFG->wwwroot.'/blocks/tutorias/view.php?&instanceid='.$instanceid.'&courseid='.$courseid, 'type' => 'activityinstance');
    if($comemanage==1)
        { $navlinks[] = array('name' => get_string('manage', 'block_tutorias'), 'link' => $CFG->wwwroot.'/blocks/tutorias/manage.php?&instanceid='.$instanceid.'&courseid='.$courseid, 'type' => 'activityinstance');}
    $navlinks[] = array('name' => get_string('createnewtutorship', 'block_tutorias'), 'link' => null, 'type' => 'activityinstance');
    $navigation = build_navigation($navlinks);

    print_header("$course->shortname: $titulo", $course->fullname, $navigation, '', '', true, '', user_login_string($course, $USER));

//create and print the form
    $createform=new tutorias_create_form();

    if($createform->is_cancelled())
    {//Si se cancela el formulario se redirige a la página principal del curso
        redirect("$CFG->wwwroot/course/view.php?id=$course->id");
    }
    else if ($fromform = $createform->get_data())
    {
        
    //var_dump($fromform);

    //Añadimos el código para guardar los datos en la base de datos y después redireccionamos
        $tabla='block_tutorias';
        $exito=true;

    $tutorship= new stdClass();
    if(isset($fromform->enablerepeat))
    {
        $offset=strtotime($fromform->repeateach,0);
        $start=(int)$fromform->repetitionstart;
        //we add 24h to include the last day in the repetition
        $end=(int)$fromform->repetitionend+86390;
        $idrep=0;
        $result=0;
        for($i=$start;$i<=$end;$i+=$offset)
        {
            $tutorship->blockid = $fromform->blockid;
            $tutorship->courseid = $fromform->courseid;
            $tutorship->instanceid = $fromform->instanceid;
            $tutorship->teacherid = $fromform->teacherid;
            $tutorship->tutorshiptitle = $fromform->tutorshiptitle;
            $tutorship->intro = $fromform->intro;
            $tutorship->starttime = $i;
            $tutorship->place = $fromform->place;
            $tutorship->visible = isset($fromform->visible);
            $tutorship->type = $fromform->type;
            $tutorship->idrepetition = $idrep;
            $tutorship->complete=0;

            if(isset($fromform->notaviablebeforeactive))
            {
                $tutorship->notaviablebefore=(strtotime($fromform->notaviablebeforetype,0) *$fromform->notaviablebeforetime);
            }


            switch($tutorship->type)
            {
            case 0:
                $tutorship->durationstudent = $fromform->durationstudent;
                $tutorship->duration = $fromform->duration;
                $tutorship->freepositions=floor($fromform->duration/$fromform->durationstudent);
            break;
                case 1:
                $tutorship->durationstudent = 0;
                $tutorship->duration = $fromform->duration;
                $tutorship->freepositions=-1;
            break;
            case 2:
                $tutorship->durationstudent = 0;
                $tutorship->duration = 0;
                $tutorship->freepositions=-1;
            break;
            case 3:
                $tutorship->durationstudent = 0;
                $tutorship->duration = $fromform->duration;
                $tutorship->freepositions=-1;
            break;    
            }
            $tutorship->timemodified = $tutorship->timecreated = time();

            $result += insert_record($tabla, $tutorship);
            if($idrep==0)
            {
                $idrep=$result;
            }
        }
        if($result)
        { 
            //Usamos un hack para que la direccion la muestre bien. le decimos que somos el bloque upload
            add_to_log($courseid,'upload','block_tutorias add tutorship',$CFG->wwwroot.'/blocks/tutorias/view.php?eventid='.$idrep.'&courseid='.$course->id.'&instanceid='.$instanceid,'tutorship repetition created');
            echo "<br>";
                print_box(get_string('createdevents', 'block_tutorias'));
            redirect("$CFG->wwwroot/course/view.php?id=$course->id");
        }
        else
        {
            error(get_string('errorcreatingevents', 'block_tutorias'));
            $error= mysql_error(); 
            //Usamos un hack para que la direccion la muestre bien. le decimos que somos el bloque upload
            add_to_log($courseid,'upload','block_tutorias error adding tutorship repetition','','error in tutorship. Error:'.$error);
        }

    }
    else
    {
             $tutorship->blockid = $fromform->blockid;
        $tutorship->courseid = $fromform->courseid;
        $tutorship->instanceid = $fromform->instanceid;
        $tutorship->teacherid = $fromform->teacherid;
        $tutorship->tutorshiptitle = $fromform->tutorshiptitle;
        $tutorship->intro = $fromform->intro;
        $tutorship->starttime = $fromform->starttime;
        $tutorship->place = $fromform->place;
        $tutorship->visible = isset($fromform->visible);
        $tutorship->type = $fromform->type;
        $tutorship->idrepetition =0;
        $tutorship->complete=0;

        if(isset($fromform->notaviablebeforeactive))
        {
            $tutorship->notaviablebefore=(strtotime($fromform->notaviablebeforetype,0) *$fromform->notaviablebeforetime);
        }

        switch($tutorship->type)
            {
            case 0:
                $tutorship->durationstudent = $fromform->durationstudent;
                $tutorship->duration = $fromform->duration;
                $tutorship->freepositions=floor($fromform->duration/$fromform->durationstudent);
            break;
                case 1:
                $tutorship->durationstudent = 0;
                $tutorship->duration = $fromform->duration;
                $tutorship->freepositions=-1;
            break;
            case 2:
                $tutorship->durationstudent = 0;
                $tutorship->duration = 0;
                $tutorship->freepositions=-1;
            break;
            case 3:
                $tutorship->durationstudent = 0;
                $tutorship->duration = $fromform->duration;
                $tutorship->freepositions=-1;
            break;
            }

        $tutorship->timemodified = $tutorship->timecreated = time();

        $result = insert_record($tabla, $tutorship);

        if($result)
        { 
            //Usamos un hack para que la direccion la muestre bien. le decimos que somos el bloque upload
            add_to_log($courseid,'upload','block_tutorias add tutorship',$CFG->wwwroot.'/blocks/tutorias/view.php?eventid='.$result.'&courseid='.$course->id.'&instanceid='.$instanceid,'tutorship created');
            echo "<br>";
            print_box(get_string('createdevent', 'block_tutorias'));
            redirect("$CFG->wwwroot/course/view.php?id=$course->id");        
        }
        else
        {
            error(get_string('errorcreatingevent', 'block_tutorias'));
            $error= mysql_error(); 
            //Usamos un hack para que la direccion la muestre bien. le decimos que somos el bloque upload
            add_to_log($courseid,'upload','block_tutorias error adding tutorship','','error in tutorship. Error:'.$error);
        }
    }
    }
    else
    {
         $toform['instanceid']=$instanceid;
         $toform['courseid']=$courseid;
         $toform['blockid']=$block_tutorias->blockid;
     $toform['teacherid']=$USER->id;
         $createform->set_data($toform);            
         $createform->display();
    }


// print the footer
    print_footer();
?>
