<?php

 /**
 * Form used to suscribe students to a tutorship.
 *
 * @author dma
 * @package blocktutorias
 */

require_once("$CFG->libdir/formslib.php");
require_once($CFG->dirroot.'/blocks/tutorias/lib.php');

class tutorias_suscribe_form extends moodleform {

    function definition() {
        global $CFG, $COURSE, $event, $block_tutorias;
         
        $mform =& $this->_form;
        $freepos=tutorias_free_positions($event->id);

    if($block_tutorias->configdata->alowmultipletutorship=="")
        {
        $alowmultipletutorship=false;
    }
    else
    {
        $alowmultipletutorship=$block_tutorias->configdata->alowmultipletutorship;
    }

    $horas=array();
    foreach($freepos as $i => $posicion)
    {
        $horas[$i]=strftime(get_string('strftime_time', 'block_tutorias'),($event->starttime+ ($event->durationstudent*$i)))."-".strftime(get_string('strftime_time', 'block_tutorias'),($event->starttime + ($event->durationstudent*($i+1)) ));
    }

    $durationstudent=strftime("%M",$event->durationstudent);

    // add group for text areas
    $mform->addElement('header', 'tutoriasfieldset', get_string('suscribeto', 'block_tutorias',$event->tutorshiptitle));
        $mform->setHelpButton('tutoriasfieldset', array('help_suscribe', get_string('help_suscribe', 'block_tutorias'), 'block_tutorias'));
    //add type of the tutorship

    $mform->addElement('select', 'option', get_string('selecthour', 'block_tutorias'), $horas);
        $mform->setType('option', PARAM_INT);
        $mform->addRule('option', null, 'required', null, 'client');
        $mform->setHelpButton('option', array('help_option', get_string('help_option', 'block_tutorias'),'block_tutorias'));

    if(($alowmultipletutorship)and ($durationstudent!=0))
    {
        $mform->addElement('static', 'description', get_string('durationtutorship', 'block_tutorias',$durationstudent)
            .get_string('allowmultipletutorship', 'block_tutorias'),'');         

        $mform->addElement('selectyesno', 'multipletutorship',  get_string('selectother', 'block_tutorias'));
            $mform->setDefault('multipletutorship', 0);
            $mform->setHelpButton('multipletutorship', array('help_multipletutorship', get_string('help_multipletutorship', 'block_tutorias'),'block_tutorias'));

        $mform->addElement('select', 'option2', get_string('selecthour', 'block_tutorias'), $horas);
            $mform->setType('option2', PARAM_INT);
            $mform->disabledIf('option2', 'multipletutorship', 0);
            $mform->addRule(array('option2','option'), get_string('twhoequalhour', 'block_tutorias'), 'compare','!=','server',true);
            $mform->setHelpButton('option2', array('help_option2', get_string('help_option2', 'block_tutorias'),'block_tutorias'));
    }
    else
    {
        if($durationstudent!=0){
        $mform->addElement('static', 'description', get_string('durationtutorship', 'block_tutorias',$durationstudent)
            ,'');}
    }

    $mform->addElement('htmleditor', 'comments', get_string('comments', 'block_tutorias'));
        $mform->setType('comments', PARAM_RAW);
        $mform->setHelpButton('comments', array('writing', 'richtext'), false, 'editorhelpbutton');

    //add hiden elements
    $mform->addElement('hidden','instanceid');
    $mform->addElement('hidden','eventid');
    $mform->addElement('hidden','userid');
    $mform->addElement('hidden','courseid');
    $mform->addElement('hidden','manage');
        
        $this->add_action_buttons(true,get_string('suscribestudent', 'block_tutorias'),false);
    }
}
?>

