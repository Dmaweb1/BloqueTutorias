<?php

/**
 * Edit the events of a repetition from the event given.
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
    $id = required_param('eventid', PARAM_INT );
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
    $navlinks[] = array('name' => get_string('editevent', 'block_tutorias'), 'link' => null, 'type' => 'activityinstance');
    $navigation = build_navigation($navlinks);

    print_header("$course->shortname: $titulo", $course->fullname, $navigation, '', '', true, '', user_login_string($course, $USER));

//if are students suscribed to the event we can't edit the event except: name, descriptionn, place and visible    
    if( tutorias_students_suscribed_repetition_fromnow($id))
    {$editable_level=1;}
    else
    {$editable_level=0;}
    
    $custom_data=Array("editable_level" => $editable_level);

//create and print the form
    $createform=new tutorias_create_form(null,$custom_data);


    if($createform->is_cancelled())
    {//Si se cancela el formulario se redirige a la página principal del curso
        redirect("$CFG->wwwroot/course/view.php?id=$course->id");
    }
    else if ($fromform = $createform->get_data())
    {
     //Añadimos el código para guardar los datos en la base de datos y después redireccionamos
        $tabla='block_tutorias';
        $exito=true;


    $tutorship= new stdClass();
    //If editable_level==1 we only can update name, descriptionn, place and visible        
    if($editable_level==1)
    {
        $tutorship->id=$id;
        $tutorship->tutorshiptitle = $fromform->tutorshiptitle;
        $tutorship->intro = $fromform->intro;
        $tutorship->place = $fromform->place;
        $tutorship->visible = isset($fromform->visible);
        $tutorship->timemodified= time();

        $tutorship->id=$event->idrepetition;
        $result = update_record($tabla, $tutorship);
        $result += tutorias_update_recordsV2($tabla, $tutorship, 'idrepetition', 'starttime >= '.$event->starttime);
        if($result)
        { 
            //Usamos un hack para que la direccion la muestre bien. le decimos que somos el bloque upload
            add_to_log($courseid,'upload','block_tutorias edited tutorship repetition',$CFG->wwwroot.'/blocks/tutorias/view.php?eventid='.$tutorship->id.'&courseid='.$course->id.'&instanceid='.$instanceid,'tutorship repetition edited');
            echo "<br>";
                print_box(get_string('editedevents', 'block_tutorias'));

            if($block_tutorias->configdata->alowmulsendmail==1)
            {
                $support_user=get_admin();
                $subject=get_string('editsubject', 'block_tutorias',$tutorship->tutorshiptitle);
                $messagedata= new stdClass();
                $messagedata->name=$tutorship->tutorshiptitle;
                $messagedata->body=$CFG->wwwroot.'/blocks/tutorias/view.php?eventid='.$tutorship->id.'&courseid='.$course->id.'&instanceid='.$instanceid;                 
                $message=get_string('editmessage', 'block_tutorias', $messagedata);
                tutorias_send_mail_students_tutorship($tutorship->id,$support_user, $subject, $message);

            }
            redirect("$CFG->wwwroot/course/view.php?id=$course->id");
        }
        else
        {
            error(get_string('erroreditingevents', 'block_tutorias'));
            $error= mysql_error(); 
            //Usamos un hack para que la direccion la muestre bien. le decimos que somos el bloque upload
            add_to_log($courseid,'upload','block_tutorias error editing tutorship repetition','','error in tutorship. Error:'.$error);
        }

    }
    else
    {
        if(isset($fromform->enablerepeat))
        {
           //We delete the events and then we create again.
            $res=tutorias_delete_repetition_from_now($event->idrepetition, $id);
            if (!$res) 
            {
                error(get_string('deleterror','block_tutoriasl',$id));
            }
            else
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
                    add_to_log($courseid,'upload','block_tutorias edited tutorship',$CFG->wwwroot.'/blocks/tutorias/view.php?eventid='.$idrep.'&courseid='.$course->id.'&instanceid='.$instanceid,'tutorship repetition created');
                    echo "<br>";
                        print_box(get_string('editedevents', 'block_tutorias'));
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
        }
        else
        {
            //We delete the event and then we create again.
            $res=tutorias_delete_repetition_from_now($id,$instanceid);
            if (!$res) 
            {
                error(get_string('deleterror','block_tutoriasl',$id));
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
    }
    }
    else
    {
    
    if($editable_level==1)
    {
        print_box(get_string('editable_level1', 'block_tutorias'),"box noticebox noticeboxcontent boxaligncenter block_tutorias_warning_box");
    }

    print_box(get_string('editrepetitionfromnow', 'block_tutorias'),"box noticebox noticeboxcontent boxaligncenter block_tutorias_warning_box");

    $first_event_rep=tutorias_is_first_event_rep($instanceid,$event->id);
    if($event->idrepetition==0)
    {
        $idrep=$event->id;
        $rep_time=false;
    }
    else
    {
        $idrep=$event->idrepetition;
        $rep_time=true;
    }
    if($first_event_rep or $rep_time)
    {
        $rep_time=tutorias_get_time_betwn_rep($instanceid,$idrep);
    }
    if($rep_time!=false)
    {
        if($rep_time["day"]==14)
        {
            $toform['enablerepeat']=true;
            $toform['repeateach']='+1 fortnight';
            $toform['repetitionstart']=$event->starttime;
            $toform['repetitionend']=tutorias_get_date_end_repetition($instanceid,$idrep);
        }
        if($rep_time["day"]==5)
        {
            $toform['enablerepeat']=true;
            $toform['repeateach']='+1 week';
            $toform['repetitionstart']=$event->starttime;
            $toform['repetitionend']=tutorias_get_date_end_repetition($instanceid,$idrep);
        }
        if($rep_time["day"]==1)
        {
            $toform['enablerepeat']=true;
            $toform['repeateach']='+1 day';
            $toform['repetitionstart']=$event->starttime;
            $toform['repetitionend']=tutorias_get_date_end_repetition($instanceid,$idrep);
        }
        elseif($rep_time["month"]==1)
        {
            $toform['enablerepeat']=true;
            $toform['repeateach']='+1 month';
            $toform['repetitionstart']=$event->starttime;
            $toform['repetitionend']=tutorias_get_date_end_repetition($instanceid,$idrep);
        }
    }
    else
    {
        $toform['enablerepeat']=false;
    }


    $toform['teacherid']=$event->teacherid;
    $toform['tutorshiptitle']=$event->tutorshiptitle;
    $toform['intro']=$event->intro;
    $toform['starttime']=$event->starttime;
    $toform['place']=$event->place;
    $toform['visible']=$event->visible;
    $toform['type']=$event->type;
    $toform['idrepetition']=$event->id;
    $toform['notaviablebeforeactive']=($event->notaviablebefore!=0);
//We calculate how much time before is not abiable the torship
        $notaviablebefore_time=date_diff($event->starttime, $event->starttime+$event->notaviablebefore);
        if($notaviablebefore_time["hour"]>0)
        {
            $toform['notaviablebeforetype']='+1 hour';
            $toform['notaviablebeforetime']=$notaviablebefore_time["hour"];
        }
        if($notaviablebefore_time["day"]>0)
        {
            $toform['notaviablebeforetype']='+1 day';
            $toform['notaviablebeforetime']=$notaviablebefore_time["day"];
        }
        if($notaviablebefore_time["month"]>0)
        {
            $toform['notaviablebeforetype']='+1 month';
            $toform['notaviablebeforetime']=$notaviablebefore_time["month"];
        }
    
    $toform['durationstudent']=$event->durationstudent;
    $toform['duration']=$event->duration;
    $toform['freepositions']=$event->freepositions;
    
     $toform['eventid']=$id;
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
