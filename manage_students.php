<?php // $Id: members.php,v 1.3.2.8 2008/07/24 11:39:05 skodak Exp $
/**
 * Add/remove members from tutorship.
 *
 * @author mjga&dma
 * @package blocktutorias
 */

// declare any globals we need to use
    global $CFG, $USER;

// include moodle API and any supplementary files/API
    require_once("../../config.php");
    require_once("lib.php");
    require_once( $CFG->libdir.'/blocklib.php' );

// check for all required variables
    $instanceid = required_param('instanceid', PARAM_INT);
    $courseid = required_param('courseid',PARAM_INT);
    $id = required_param('eventid', PARAM_INT );
    $comemanage = optional_param('manage',0, PARAM_INT );

//Get the contex of the course
    $context = get_context_instance(CONTEXT_COURSE, $courseid);

// ensure we have a valid courseid and can load the associated course object
    if (! $course = get_record('course', 'id', $courseid) ) {
        error(get_string('invalidcourse', 'block_tutorias', $courseid));
    }
//we get the event, and validate it 
    if(!$event = get_record('block_tutorias' ,'id',$id))
    {
        error(get_string('invalidevent','block_tutorias', $id));
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

$strsearch = get_string('search');
$strshowall = get_string('showall');

$returnurl = $CFG->wwwroot.'/blocks/tutorias/view_students.php?eventid='.$id.'&instanceid='.$instanceid.'&courseid='.$courseid;


if ($frm = data_submitted()) {

    if (isset($frm->cancel)) {
        redirect($returnurl);

    } else if (isset($frm->add) and !empty($frm->addselect)) {

        foreach ($frm->addselect as $userid) {
            if (! $userid = clean_param($userid, PARAM_INT)) {
                continue;
            }
            if (tutorias_suscribe_student($instanceid,false,$userid,$id,$frm->opcion,get_string('addbyteacher', 'block_tutorias'),-1))
        {
        //Usamos un hack para que la direccion la muestre bien. le decimos que somos el bloque upload
        add_to_log($courseid,'upload','block_tutorias student suscribed',$CFG->wwwroot.'/blocks/tutorias/view.php?eventid='.$id.'&courseid='.$course->id.'&instanceid='.$instanceid,'studentID='.$userid .'suscribed to eventID='.$id);
        }
        else
        {
                $error= mysql_error();
        //Usamos un hack para que la direccion la muestre bien. le decimos que somos el bloque upload
        add_to_log($courseid,'upload','block_tutorias error suscrbing user','','error in tutorship. Error:'.$error);
        print_error('errorsuscribing', 'block_tutoriasl', $returnurl);
        
            }
        }

    } else if (isset($frm->remove) and !empty($frm->removeselect)) {

        foreach ($frm->removeselect as $userid) {
            if (! $userid = clean_param($userid, PARAM_INT)) {
                continue;
            }
            if (tutorias_unsuscribe_student($instanceid,$userid,$id,true))
        {
        //Usamos un hack para que la direccion la muestre bien. le decimos que somos el bloque upload
        add_to_log($courseid, 'upload', 'block_tutorias unsuscribe user from tutorship',$CFG->wwwroot.'/blocks/tutorias/view.php?eventid='.$id.'&courseid='.$course->id.'&instanceid='.$instanceid,'studentID= '.$userid .'unsuscribed to eventID= '.$id);
            } 
          else
        {
                $error= mysql_error();
        //Usamos un hack para que la direccion la muestre bien. le decimos que somos el bloque upload
        add_to_log($courseid,'upload','block_tutorias error unsuscrbing user','','error in tutorship. Error:'.$error);
        print_error('unsuscribeerror', 'block_tutoriasl', $returnurl);
            }
        }
    }
}


// Get members, and display
//obtenemos los students que están matriculados en este group, que son los 
//que el teacher podrá borrar
//$prefix=$CFG->prefix;
//$sql="select * from ".$prefix."user where id in (select studentid from ".$prefix."block_tutorias_students where eventid = $id)";
$students=tutorias_get_students_tutorship($id);
$groupmemberscount=0;    

if ($students) {
$groupmembersoptions="";
        foreach($students as $member) {
            $groupmembersoptions .= '<option value="'.$member->id.'">'.fullname($member, true).'</option>';
            $groupmemberscount++;
        }
} else {
    $groupmembersoptions ="<option>&nbsp;</option>";
}

// Get potential members, and display

$potential_members=tutorias_get_potential_members($courseid);
$contextlists = get_related_contexts_string($context);

if (count($potential_members)!=0) {
$potentialmemberscount=0;
$potentialmembersoptions="";
        foreach($potential_members as $i => $member) {
        if(!has_capability('block/tutorias:managetutory', $context, $member->id) and (!is_array($students) or !array_key_exists($i,$students)))
        {
        $user=$member;
        $potentialmembersoptions .= '<option value="'.$user->id.'">'.fullname($user, true).'</option>';
                $potentialmemberscount++;
        }
        }
} else {
    $potentialmembersoptions .="<option>&nbsp;</option>";
}


$freepos=tutorias_free_positions($id);
$horas=array();
    
    foreach($freepos as $i => $posicion)
    {
        $horas[$i]=strftime(get_string('strftime_time', 'block_tutorias'),($event->starttime+ ($event->durationstudent*$i)))."-".strftime(get_string('strftime_time', 'block_tutorias'),($event->starttime + ($event->durationstudent*($i+1)) ));
    }
$horas[-1]=get_string('withouthour', 'block_tutorias');

$choose_menu=choose_from_menu($horas,"opcion",'','choose','',-1,true);

// Print the page and form
$strparticipants = get_string('participants');
$stradduserstogroup = get_string('addstudentgroup', 'block_tutorias');


$navlinks = array();
$navlinks[] = array('name' => $titulo, 'link' => $CFG->wwwroot.'/blocks/tutorias/view.php?&instanceid='.$instanceid.'&courseid='.$courseid, 'type' => 'activityinstance');
if($comemanage==1)
        { $navlinks[] = array('name' => get_string('manage', 'block_tutorias'), 'link' => $CFG->wwwroot.'/blocks/tutorias/manage.php?&instanceid='.$instanceid.'&courseid='.$courseid, 'type' => 'activityinstance');}
$navlinks[] = array('name' => $event->tutorshiptitle, 'link' => $CFG->wwwroot.'/blocks/tutorias/view.php?eventid='.$id.'&instanceid='.$instanceid.'&courseid='.$courseid , 'type' => 'activityinstance');
$navlinks[] = array('name' => get_string('participants', 'block_tutorias'), 'link' => $CFG->wwwroot.'/blocks/tutorias/view_students.php?eventid='.$id.'&instanceid='.$instanceid.'&courseid='.$courseid, 'type' => 'activityinstance');
$navlinks[] = array('name' => get_string('addremovestudents', 'block_tutorias'), 'link' => null, 'type' => 'activityinstance');
$navigation = build_navigation($navlinks);

print_header("$course->shortname: $titulo", $course->fullname, $navigation, '', '', true, '', user_login_string($course, $USER));

// Print Javascript for showing the selected users group membership
?>
<script type="text/javascript">
//<![CDATA[
var userSummaries = Array(
<?php
$membercnt = count($potentialmembers);
$i=1;
foreach ($potentialmembers as $userid => $potentalmember) {

    if (isset($usergroups[$userid])) {
        $usergrouplist = '<ul>';

        foreach ($usergroups[$userid] as $groupitem) {
            $usergrouplist .= '<li>'.addslashes_js(format_string($groupitem->name)).'</li>';
        }
        $usergrouplist .= '</ul>';
    }
    else {
        $usergrouplist = '';
    }
    echo "'$usergrouplist'";
    if ($i < $membercnt) {
        echo ', ';
    }
    $i++;
}
?>
);

function updateUserSummary() {

    var selectEl = document.getElementById('addselect');
    var summaryDiv = document.getElementById('group-usersummary');
    var length = selectEl.length;
    var selectCnt = 0;
    var selectIdx = -1;

    for(i=0;i<length;i++) {
        if (selectEl.options[i].selected) {
            selectCnt++;
            selectIdx = i;
        }
    }

    if (selectCnt == 1 && userSummaries[selectIdx]) {
        summaryDiv.innerHTML = userSummaries[selectIdx];
    } else {
        summaryDiv.innerHTML = '';
    }

    return(true);
}
//]]>
</script>

<div id="addmembersform">
    <h3 class="main"><?php print_string('addremove', 'block_tutorias'); echo ": $event->tutorshiptitle"; ?></h3>
    <form id="assignform" method="post" action="manage_students.php">
    <div>
    <input type="hidden" name="courseid" value="<?php echo $courseid; ?>" />
    <input type="hidden" name="eventid" value="<?php echo $id; ?>" />
    <input type="hidden" name="instanceid" value="<?php echo $instanceid; ?>" />

    <table cellpadding="6" class="generaltable generalbox groupmanagementtable boxaligncenter" summary="">
    <tr>
      <td valign="top">
          <p>
            <label for="removeselect"><?php print_string('existingmembers', 'block_tutorias', $groupmemberscount); //count($contextusers) ?></label>
          </p>
          <select name="removeselect[]" size="20" id="removeselect" multiple="multiple"
                  onfocus="document.getElementById('assignform').add.disabled=true;
                           document.getElementById('assignform').remove.disabled=false;
                           document.getElementById('assignform').addselect.selectedIndex=-1;"
                  onclick="this.focus();">
          <?php echo $groupmembersoptions ?>
          </select></td>
      <td valign="top">
<?php // Hidden assignment? ?>

        <?php check_theme_arrows(); ?>
        <p class="arrow_button">
            <input name="add" id="add" type="submit" value="<?php echo $THEME->larrow.'&nbsp;'.get_string('add'); ?>" title="<?php print_string('add'); ?>" /><br />
            <input name="remove" id="remove" type="submit" value="<?php echo get_string('remove').'&nbsp;'.$THEME->rarrow; ?>" title="<?php print_string('remove'); ?>" /><br>
<?php echo $choose_menu; ?>
        </p>
      </td>
      <td valign="top">
          <p>
            <label for="addselect"><?php print_string('potentialmembers', 'block_tutorias', $potentialmemberscount); //$usercount ?></label>
          </p>
          <select name="addselect[]" size="20" id="addselect" multiple="multiple"
                  onfocus="document.getElementById('assignform').add.disabled=false;
                           document.getElementById('assignform').remove.disabled=true;
                           document.getElementById('assignform').removeselect.selectedIndex=-1;"
                  onclick="this.focus();">
          <?php
          
                echo $potentialmembersoptions;
              ?>
         </select>
         <br />
         
       </td>
      
    </tr>
    <tr><td>
        <input type="submit" name="cancel" value="<?php print_string('back'); ?>" />
    </td></tr>
    </table>
    </div>
    </form>
</div>

<?php
    print_footer($course);
?>
