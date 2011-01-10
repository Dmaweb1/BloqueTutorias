<?php

 /**
 * Form used to create or edit tutorships.
 *
 * @author dma
 * @package blocktutorias
 */

require_once("$CFG->libdir/formslib.php");


class tutorias_create_form extends moodleform {
    

    function definition() {
        global $CFG, $COURSE;
         
        $mform =& $this->_form;
    //get howmany tings can be edited.
    //0= all;
    //1= only name, descriptionn, place and visible    
    $editable_level=$this->_customdata["editable_level"];
    if($editable_level==null)
    {$mform->addElement('hidden','editable_level',0);}
    else    
    {$mform->addElement('hidden','editable_level',$editable_level);}
        
    // add group for text areas
        $mform->addElement('header', 'displayinfo', get_string('general', 'form'));
             $mform->setHelpButton('displayinfo', array('help_create', get_string('help_create', 'block_tutorias'), 'block_tutorias'));

        // add page title element
        $mform->addElement('text','tutorshiptitle',get_string('tutorshiptitle','block_tutorias'));
            $mform->setType('tutorshiptitle', PARAM_TEXT);            
            $mform->addRule('tutorshiptitle', null, 'required', null, 'client');
            $mform->setHelpButton('tutorshiptitle', array('help_tutorshiptitle', get_string('help_tutorshiptitle', 'block_tutorias'), 'block_tutorias'));
 
        // add description element
        $mform->addElement('htmleditor', 'intro', get_string('intro', 'block_tutorias'));
            $mform->setType('intro', PARAM_RAW);
            $mform->addRule('intro', get_string('required'), 'required', null, 'client');
            $mform->setHelpButton('intro', array('writing', 'richtext'), false, 'editorhelpbutton');


        // add group for text areas
        $mform->addElement('header', 'tutoriasfieldset', get_string('particularsettings', 'block_tutorias'));

        //add type of the tutorship
        $arr_type=Array( '0' =>  get_string('individual', 'block_tutorias'),'1' =>  get_string('group', 'block_tutorias'),'2' =>  get_string('event', 'block_tutorias'),'3' =>  get_string('review', 'block_tutorias'));
        $mform->addElement('select', 'type', get_string('typetutorship', 'block_tutorias'), $arr_type);
            $mform->setType('type', PARAM_INT);
            $mform->disabledIf('type', 'editable_level','eq',1);
            $mform->setHelpButton('type', array('help_type', get_string('help_typetutorship', 'block_tutorias'), 'block_tutorias'));

        //add start time of the tutorship
        $options = array('startyear' => date('Y')-5,'optional'=>false);
        $mform->addElement('date_time_selector', 'starttime', get_string('starttime', 'block_tutorias'),$options);
                $mform->setDefault('starttime', strtotime('+1 day'));
                    //$mform->setHelpButton('fecha_inicio', array('fecha_inicio', get_string('fechainicio', 'gruposlab'), 'gruposlab'));
            $mform->disabledIf('starttime', 'enablerepeat', 'checked');
            $mform->setType('starttime', PARAM_INT);
            $mform->disabledIf('starttime', 'editable_level','eq',1);
            $mform->setHelpButton('starttime', array('help_starttime', get_string('help_starttime', 'block_tutorias'), 'block_tutorias'));

        // add checkbox selector for enable repetitions in optional area
        $mform->addElement('checkbox', 'enablerepeat', get_string('enablerepeat', 'block_tutorias'));
            $mform->setAdvanced('enablerepeat');
            //$mform->setType('starttime', PARAM_INT);
            $mform->disabledIf('enablerepeat', 'editable_level','eq',1);
            $mform->setHelpButton('enablerepeat', array('help_enablerepeat', get_string('help_enablerepeat', 'block_tutorias'), 'block_tutorias'));

            // add date_time selector in optional area
        $arr_repetitions=Array( '+1 day' => get_string('day', 'block_tutorias'),
                    '+1 week' => get_string('week', 'block_tutorias'), 
                    '+1 fortnight'=> get_string('fortnight', 'block_tutorias'), 
                    '+1 month'=> get_string('month', 'block_tutorias'));
        $mform->addElement('select', 'repeateach', get_string('repeateach', 'block_tutorias'),$arr_repetitions);
            $mform->setAdvanced('repeateach');
            $mform->disabledIf('repeateach', 'enablerepeat');
            $mform->disabledIf('repeateach', 'editable_level','eq',1);
            $mform->setHelpButton('repeateach', array('help_repeateach', get_string('help_repeateach', 'block_tutorias'), 'block_tutorias'));

        // add date_time selector for start repetition in optional area
        $options = array('startyear' => date('Y')-5,'optional'=>false);
        $mform->addElement('date_time_selector', 'repetitionstart', get_string('repetitionstart', 'block_tutorias'),$options);
            $mform->setDefault('repetitionstart', strtotime('+1 day'));
            $mform->setAdvanced('repetitionstart');
            $mform->disabledIf('repetitionstart', 'enablerepeat');
            $mform->disabledIf('repetitionstart', 'editable_level','eq',1);
            $mform->setHelpButton('repetitionstart', array('help_repetitionstart', get_string('help_repetitionstart', 'block_tutorias'), 'block_tutorias'));

        // add date_time selector for end repetition in optional area
        $options = array('startyear' => date('Y')-5,'optional'=>false);
        $mform->addElement('date_selector', 'repetitionend', get_string('repetitionend', 'block_tutorias'),$options);
            $mform->setDefault('repetitionend', strtotime('+1 month'));
            $mform->setAdvanced('repetitionend');
            $mform->disabledIf('repetitionend', 'enablerepeat');
            $mform->disabledIf('repetitionend', 'editable_level','eq',1);
            $mform->setHelpButton('repetitionend', array('help_repetitionend', get_string('help_repetitionend', 'block_tutorias'), 'block_tutorias'));
//echo '1 sem'.strtotime('+1 fortnight',0);
//echo 'ahora'.time();

        // add date_time selector for start repetition in optional area
         $notaviablebeforefromgroup=array();
        $arr_notaviablebefore=Array( '+1 hour' => get_string('hours', 'block_tutorias'),
                        '+1 day' => get_string('days', 'block_tutorias'),
                        '+1 week' => get_string('weeks', 'block_tutorias'),
                        '+1 month'=> get_string('months', 'block_tutorias'));
        $notaviablebeforefromgroup[]=& $mform->createElement('text', 'notaviablebeforetime', get_string('notaviablebeforetime', 'block_tutorias'), array('size'=>'3'));    
        $notaviablebeforefromgroup[]=& $mform->createElement('select', 'notaviablebeforetype', get_string('notaviablebeforetype', 'block_tutorias'),$arr_notaviablebefore);
        $notaviablebeforefromgroup[]=& $mform->createElement('checkbox', 'notaviablebeforeactive', null);        
        $mform->addGroup($notaviablebeforefromgroup, 'notaviablebefore', get_string('notaviablebefore', 'block_tutorias'), ' ', false);
            $mform->setAdvanced('notaviablebefore');
            $arr_group_rules= array();
            $arr_group_rules['notaviablebeforetime'][]=array(get_string('errornumber', 'block_tutorias'), 'numeric', null, 'client');
            $mform->addGroupRule('notaviablebefore', $arr_group_rules);
            $mform->disabledIf('notaviablebefore', 'notaviablebeforeactive');
            $mform->disabledIf('notaviablebefore', 'editable_level','eq',1);
            $mform->setHelpButton('notaviablebefore', array('help_notaviablebefore', get_string('help_notaviablebefore', 'block_tutorias'), 'block_tutorias'));

    //add the duration of the tutorship for each student
    $arr_durations_student=Array();
    for($i=5;$i<=60;$i+=5)
    {
        $arr_durations_student[$i*60]=$i;
    }
    $mform->addElement('select', 'durationstudent', get_string('durationstudent', 'block_tutorias'), $arr_durations_student);
        $mform->disabledIf('durationstudent', 'type','>',0);
        $mform->disabledIf('durationstudent', 'editable_level','eq',1);
        $mform->setHelpButton('durationstudent', array('help_durationstudent', get_string('help_durationstudent', 'block_tutorias'), 'block_tutorias'));

    //add the global duration of the tutorship
    $arr_durations=Array();
    for($i=30;$i<=240;$i+=30)
    {
        $arr_durations[$i*60]=$i;
    }
    $mform->addElement('select', 'duration', get_string('duration', 'block_tutorias'), $arr_durations);
         $mform->disabledIf('duration', 'type','eq',2);
        $mform->disabledIf('duration', 'editable_level','eq',1);
        $mform->setHelpButton('duration', array('help_duration', get_string('help_duration', 'block_tutorias'), 'block_tutorias'));

    //add place of the tutorship  
    $mform->addElement('text', 'place', get_string('place', 'block_tutorias'), array('size'=>'30'));
          $mform->setType('place', PARAM_TEXT);
        $mform->setHelpButton('place', array('help_place', get_string('help_place', 'block_tutorias'), 'block_tutorias'));

    // add checkbox selector for visible
    $mform->addElement('checkbox', 'visible', get_string('visible', 'block_tutorias'));
        $mform->setDefault('visible', true);
        $mform->setHelpButton('visible', array('help_visible', get_string('help_visible', 'block_tutorias'), 'block_tutorias'));

    //add hiden elements
    $mform->addElement('hidden','instanceid');
    $mform->addElement('hidden','blockid');
    $mform->addElement('hidden','teacherid');
    $mform->addElement('hidden','courseid');
    $mform->addElement('hidden','id','0');
      $mform->addElement('hidden','eventid','0');
    $mform->addElement('hidden','editrepetition');

    if($editable_level==1)
    {
    }

        $this->add_action_buttons();
    }
}

function set_help($par1, $par2) {
    return "Prueba";
}
?>

