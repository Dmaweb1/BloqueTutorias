<?php

/**
 * Tutorship Block is a block that allow the studentes to suscribe to 
 * the tutorship offered by the teachers, the student could chose one hour
 * or slot to asist to the tutorship and also the student can write some 
 * comments or the topic of the tutorship to the teacher.
 *
 * @author dma
 * @package blocktutorias
 */

global $CFG;

class block_tutorias extends block_base {

    /**
     * Initialize the block 
     */
    function init() {
//Esta funcion se ejecuta cada vez que el bloque se carga(se pinta en portada) 

        $this->title = get_string('blockname', 'block_tutorias');
        $this->version = 2010121201;
 
        $this->cron = 3600;
    }
 
    /**
     * Perform operations of initialization after install the block.
     */
    function after_install() {
//Este metodo se ejecuta una vez por cada instalacion del bloque lo usamos para poner los valores por defecto
        global $CFG;

        if(!isset($CFG->block_tutorias_allow_teachers_config))
            {set_config('block_tutorias_allow_teachers_config',"1");}
        if(!isset($CFG->block_tutorias_default_alowmultipletutorship))
            {set_config('block_tutorias_default_alowmultipletutorship',"0");}
        if(!isset($CFG->block_tutorias_default_dia_comienzo))
            {set_config('block_tutorias_default_dia_comienzo',"1");}
        if(!isset($CFG->block_tutorias_default_alowmulsendmail))
            {set_config('block_tutorias_default_alowmulsendmail',"1");}
        if(!isset($CFG->block_tutorias_default_alowsubscribesendmail))
            {set_config('block_tutorias_default_alowsubscribesendmail',"1");}
        if(!isset($CFG->block_tutorias_default_alowunsubscribesendmail))
            {set_config('block_tutorias_default_alowunsubscribesendmail',"1");}
        if(!isset($CFG->block_tutorias_default_alowuremembersendmail))
            {set_config('block_tutorias_default_alowuremembersendmail',"1");}
    }

    /**
     * Generate the content of the block.
     * @return content
     */
    function get_content() {
        global $USER, $CFG, $SESSION, $COURSE;

        $cal_m = optional_param( 'tut_m', 0, PARAM_INT );
        $cal_y = optional_param( 'tut_y', 0, PARAM_INT );

        if ($this->content !== NULL) {
            return $this->content;
        }

        require_once($CFG->dirroot.'/blocks/tutorias/lib.php');

        $this->content = new stdClass;
        
        $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
        
        if (has_capability('block/tutorias:viewtutory', $context)) 
        {
            $this->content->text = $this->config->text;
            //$canmanage = has_capability('block/tutorias:managepages', $context) && isediting($this->instance->pageid);
        
        
        $this->content->text .= tutorias_calendar_top_controls(array('instanceid' => $this->instance->id,'id' => $COURSE->id, 'm' => $cal_m, 'y' =>  $cal_y));
            $this->content->text .= tutorias_calendar_get_mini($this,$cal_m, $cal_y); 
        $this->content->text .= tutorias_filter_controls();
        }
        else 
        {
            $this->content->text = get_string('forbidenseetutory', 'block_tutorias');
        }

        if (has_capability('block/tutorias:managetutory', $context)) {
                        $link= $CFG->wwwroot.'/blocks/tutorias/manage.php';
                        $options=array();
                        $options["courseid"]=$COURSE->id;
                        $options["instanceid"]=$this->instance->id;
                        $label=get_string('managetutory', 'block_tutorias');
                        $boton=print_single_button($link, $options, $label,'get', '_self', true, '',  false, '');
            $this->content->footer = "<CENTER>$boton</CENTER>";

         }
        else {
            if (has_capability('block/tutorias:suscribetutory', $context))
            {
                        $link= $CFG->wwwroot.'/blocks/tutorias/view.php';
                        $options=array();
                        $options["courseid"]=$COURSE->id;
                        $options["instanceid"]=$this->instance->id;
                        $options["viewusertutorship"]=1;
                        $label=get_string('viewmytutorship', 'block_tutorias');
                        $boton=print_single_button($link, $options, $label,'get', '_self', true, '',  false, '');
                   $this->content->footer = "<CENTER>$boton</CENTER>";
               }
            else 
        {
                $this->content->footer = '';
            }
        }

        return $this->content;
    }

    /**
    * Enable or disable the global configuration of the block. 
    * @return boolean
    */  
    function instance_allow_config() {
//El bloque tiene configuracion global
        return true;
    }
    
    /**
    * Delete the data of the block. 
    */  
    function instance_delete(){
//Eliminamos los registros de una instancia al borrarla
        delete_records('block_tutorias_students','instanceid', $this->instance->id);
        delete_records('block_tutorias','instanceid', $this->instance->id);
    }

    /**
    * Perform clean operations before delete the block,
    */ 
    function before_delete() 
    {
//Borramos las variables globales anestes de borrar el bloque
        global $CFG;
        unset_config(block_tutorias_allow_teachers_config);
        unset_config(block_tutorias_default_alowmultipletutorship);
        unset_config(block_tutorias_default_dia_comienzo);
        unset_config(block_tutorias_default_alowmulsendmail);
        unset_config(block_tutorias_default_alowunsubscribesendmail);
        unset_config(block_tutorias_default_alowsubscribesendmail);
        unset_config(block_tutorias_default_alowuremembersendmail);
    }    

    /**
     * Generate the content of the block.
     */
    function specialization() {
//se ejecuta despues de init con la variable config ya rellena cada vez que se carga el bloque (pinta en portada)
        global $CFG;

        if(!empty($this->config->title)){
            $this->title = $this->config->title;
        }
        else{
            $this->config->title = get_string('blockname', 'block_tutorias');
        }
        if(empty($this->config->text)){
            $this->config->text = '';
        } 
        if(!isset($this->config->alowmultipletutorship) )  {
            if(isset($CFG->block_tutorias_default_alowmultipletutorship))
                {$this->config->alowmultipletutorship=$CFG->block_tutorias_default_alowmultipletutorship;}
            else{$this->config->alowmultipletutorship=0;} }
        
        if(!isset($this->config->dia_comienzo)) {
                if(isset($CFG->block_tutorias_default_dia_comienzo))
                    {$this->config->dia_comienzo=$CFG->block_tutorias_default_dia_comienzo;}
                else{$this->config->dia_comienzo=1;}}
        if(!isset($this->config->alowmulsendmail) ){
                if(isset($CFG->block_tutorias_default_alowmulsendmail))
                    {$this->config->alowmulsendmail=$CFG->block_tutorias_default_alowmulsendmail;}
                else{$this->config->alowmulsendmail=0;}}
        if(!isset($this->config->alowunsubscribesendmail)){ 
                if(isset($CFG->block_tutorias_default_alowunsubscribesendmail))
                    {$this->config->alowunsubscribesendmail=$CFG->block_tutorias_default_alowunsubscribesendmail;}
                else{$this->config->alowunsubscribesendmail=0;}}
        if(!isset($this->config->alowsubscribesendmail)) {
                if(isset($CFG->block_tutorias_default_alowunsubscribesendmail))
                    {$this->config->alowsubscribesendmail=$CFG->block_tutorias_default_alowsubscribesendmail;}
                else{$this->config->alowsubscribesendmail=0;}}
        if(!isset($this->config->alowuremembersendmail)) {
                if(isset($CFG->block_tutorias_default_alowuremembersendmail))
                    {$this->config->alowuremembersendmail=$CFG->block_tutorias_default_alowuremembersendmail;}
                else{$this->config->alowuremembersendmail=0;}}
        $this->instance_config_commit();
    }

    /**
    * Enable or disable multiple instances of the block in the courses. 
    * @return boolean
    */    
    function instance_allow_multiple() {
//Permititimos varios bloques en el mismo curso.
        return true;
    }

     /**
     * Enable or disable configuration of each instance of the block. 
     * @return boolean
     */     
    function has_config() {
    //Permititimos la configuracion de cada bloque.
        return true;
    }

     /**
     * Save the global config of the block. 
     * @return config
     */    
    function config_save($data) {
       return parent::config_save($data);
    }

     /**
     * Save the instance config of the block. 
     * @return config
     */ 
    function instance_config_save($data) {
      return parent::instance_config_save($data);
    }
    
    /**
     * Function to be run periodically according to the moodle cron
     * This function searches for things that need to be done, such
     * as sending out mail, toggling flags etc ...
     *
     * @uses $CFG
     * @return boolean
     * @todo Finish documenting this function
     **/
    function cron() 
    {

        global $CFG;  

        require_once($CFG->dirroot.'/config.php');
        require_once($CFG->dirroot.'/blocks/tutorias/lib.php');
        require_once($CFG->libdir.'/blocklib.php' );

        $time = localtime(time(), true);

        if(!($time[tm_hour]>=23))
        {  return true; }


        $blocktype = get_record( 'block', 'name', 'tutorias' );
        $timeStart=mktime(0,0,0,date('m'),1+date('d'),date('Y'));
        $timeEnd=mktime(0,0,0,date('m'),2+date('d'),date('Y'));
        echo"\n\tProcessing events of day :".userdate($timeStart);

        $select= '(blockid ='.$blocktype->id.') AND (starttime >='.$timeStart.') AND (starttime <='.$timeEnd.')';
        $events = get_records_select('block_tutorias', $select);
        foreach ($events as $event)
        {
            $block_tutorias=tutorias_get_block($event->instanceid);
            if(tutorias_students_suscribed_tutorship($event->id) && ($block_tutorias->configdata->alowuremembersendmail==1))
            {
                echo"\n\t\tProcessing event $event->tutorshiptitle ...";
                //we send an e-mail to the teacher
                $support_user=get_admin();
                $subject=get_string('remembersubject', 'block_tutorias', userdate($timeStart));
                $messagedata= new stdClass();
                $messagedata->name = $block_tutorias->configdata->title;
                $messagedata->tutorship=$event->tutorshiptitle;
                $messagedata->table=tutorias_draw_students_table($event->id, $event->instanceid,0,true);
                $message=get_string('remembermessage', 'block_tutorias', $messagedata);                
                tutorias_send_mail_teacher($event->id,$support_user, $subject, $message);
            }else
            {
                echo"\n\t\tEvent $event->tutorshiptitle skipped.";
            }
           
        }
        return true;
    }

    /**
    * Enable or disable the header of the block. 
    * @return boolean
    */   
    function hide_header() {
        return false;
    }
    
    /**
     * Sets the width of the block. 
     * @return int
     */   
    function preferred_width() {
        // The preferred value is in pixels
        return 200;
    }

    /**
     * Sets some styles to the block. 
     * @return array
     */    
    function html_attributes() {
        return array(
            'class'       => 'sideblock block_'. $this->name(),
        );
    }

    /**
     * Sets the applicable formats. 
     * @return array
     */    
    function applicable_formats() {
//Establecemos que se puede mostrar en los cursos y en la portada.
        return array('site-index' => true,
                     'course-view' => true,
                     'mod' => true);
    }
}
