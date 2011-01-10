<?php
$string['blockname'] = 'Bloque Tutorías';
$string['addpage'] = 'Add a Page';

// strings for config_instance.html
$string['configtitle'] = 'Title';
$string['configweek1day'] = 'Start day of the week';
$string['conf_dom'] = 'Sunday';
$string['conf_lun'] = 'Monday';
$string['conf_mar'] = 'Tuesday';
$string['conf_mier'] = 'Wednesday';
$string['conf_jue'] = 'Thursday';
$string['conf_vier'] = 'Friday';
$string['conf_sab'] = 'Saturday';
$string['configalowmultipletutorship'] = 'Allow students to select multiple slots in a tutorship';
$string['conf_yes'] = 'Yes';
$string['conf_no'] = 'No';
$string['allowsendmail'] = 'Send e-mail when tutorship is edited';
$string['alowsubscribesendmail'] = 'Send e-mail when student is subscribed to tutorship';
$string['alowunsubscribesendmail'] = 'Send e-mail when student is unsubscribed to tutorship';
$string['alowuremembersendmail'] = 'Send an e-mail to the teacher one day before the tutorship to inform about the students subscribed';
$string['dont_allow_teacher_edit'] = 'The administrator don\'t allow edit the block configuration.';

// strings for config_global.html
$string['allow_teacher_edit'] = 'Allow teachers to edit the options of the block.';
$string['help_config'] = 'config Bloque Tutorias';

// strings for permissions and capabilities
$string['tutorias:viewtutory'] = 'See Tutorships';
$string['tutorias:suscribetutory'] = 'Susbcribe Tutorships';
$string['tutorias:managetutory'] = 'Manage Tutorships';

//strings for block
$string['managetutory'] = 'Manage Tutorships';
$string['forbidenseetutory'] = 'You can\'t see the tutorships';
$string['hideindividual'] = 'Individual tutorships are shown (click to hide)';
$string['showindividual'] = 'Individual tutorships are hidden (click to show)';
$string['hidegroup'] = 'Group tutorships are shown (click to hide)';
$string['showgroup'] = 'Group tutorships are hidden (click to show)';
$string['hideevent'] = 'Events tutorships are shown (click to hide)';
$string['showevent'] = 'Events are hidden (click to show)';
$string['hidereview'] = 'Reviews tutorships are shown (click to hide)';
$string['showreview'] = 'Reviews are hidden (click to show)';
$string['eventsfull'] = 'Full';
$string['eventsfull_tooltip'] = 'Tutorship or event without free slots.';
$string['strftime_monthyear'] = '%%B %%Y';
$string['calendar_year'] = '$a Calendar';
$string['username'] = '$a->firstname $a->lastname';

// strings for create_form
$string['particularsettings'] = 'Specific settings of the tutorship';
$string['tutorshiptitle'] = 'Tutorship name';
$string['intro'] = 'Summary';
$string['durationstudent'] = 'Minutes per student';
$string['starttime'] = 'Start date';
$string['duration'] = 'Total time (minutes)';
$string['place'] = 'Place';
$string['repetitionstart'] = 'Repeat from';
$string['repetitionend'] = 'Repeat until';
$string['repeateach'] = 'Repeat each';
$string['individual'] = 'Individual';
$string['review'] = 'Review';
$string['group'] = 'Group';
$string['event'] = 'Event';
$string['typetutorship'] = 'Type';
$string['notaviablebefore'] = 'Not enable before';
$string['notaviablebeforetype'] = 'type';
$string['notaviablebeforetime'] = 'time';
$string['enablerepeat'] = 'Activate repetitions';
$string['visible'] = 'Visible';

$string['day'] = 'Day';
$string['month'] = 'Month';
$string['week'] = 'Week';
$string['fortnight'] = 'Fortnight';
$string['days'] = 'Days';
$string['hours'] = 'Hours';
$string['months'] = 'Months';
$string['weeks'] = 'Weeks';

$string['errornumber'] = 'Error, You must enter a number here.';

$string['help_create'] = 'Create Tutorship';
$string['help_tutorshiptitle'] = 'Tutorship name';
$string['help_typetutorship'] = 'Type';
$string['help_starttime'] = 'Start date';
$string['help_enablerepeat'] = 'Activate repetitions';
$string['help_repeateach'] = 'Repeat each';
$string['help_repetitionstart'] = 'Repeat from';
$string['help_repetitionend'] = 'Repeat until';
$string['help_notaviablebefore'] = 'Not enable before';
$string['help_durationstudent'] = 'Minutes per student';
$string['help_duration'] = 'Total time (minutes)';
$string['help_place'] = 'Place';
$string['help_visible'] = 'Visible';

//Strings via view.php
$string['invalidevent'] = 'The id $a is not a valid event id';
$string['teacher'] = 'Teacher';
$string['strftime_fulldate'] = '%%A, %%d of %%B de %%Y';
$string['strftime_fulltime'] = '%%r';
$string['hour'] = 'Start Time';
$string['missingparameters'] = 'Error, Missing parameters.';
$string['noevents'] = 'There are no events to show.';
$string['invalidrepetitionid'] = 'The id $a is not a valid repetition id';
$string['visible1'] = 'Yes';
$string['visible0'] = 'No';
$string['repetitionsof'] = 'Repetitions of';
$string['eventsof'] = 'Events of';
$string['yourevents'] = 'Your Events';
$string['eventsofday'] = 'Events of day';
$string['repeateachday'] = 'The event is repeated every $a day/s';
$string['repeateachmonth'] = 'The event is repeated every $a months/es';
$string['repeateachyear'] = 'The event is repeated every $a year/s';
$string['seerep'] = 'View all events of repetition';
$string['editrep'] = 'Edit all events of repetition';
$string['edifromnow'] = 'Edit events of repetition from this';
$string['firstrep'] = 'This is the first event of repetition';
$string['delrep'] = 'Delete all events of repetition';
$string['delfromnow'] = 'Delete events of repetition from this';
$string['editevent'] = 'Edit event';
$string['viewstudents'] = 'View students';
$string['suscribestudent'] = 'Susbcribe';
$string['unsuscribestudent'] = 'Unsubscribe';
$string['deleteevent'] = 'Delete event';
$string['repetition'] = 'Repetitions';
$string['posfree'] = 'Number of free slots';
$string['unlimited'] = 'Unlimited';
$string['susbcribed_at'] = 'Subscribed at:';
//$string['strftime_time'] = '%%R';

// strings via create.php
$string['invalidcourse'] = 'The id $a is not a valid course id';
$string['nopage'] = 'Block for the id: $a does not exist';
$string['createdevents'] = 'List of events created';
$string['editedevents'] = 'List of events edited';
$string['createdevent'] = 'Event created';
$string['editedevent'] = 'Event edited';
$string['errorcreatingevent'] = 'Error creating the event';
$string['erroreditingevent'] = 'Error editing the event';
$string['erroreditingevents'] = 'Error edinting the list of events';

// strings via delete.php
$string['confirmdelete'] = 'Confirm';
$string['deletevent'] = 'Do you really want to delete \'$a\'?';
$string['deleterep'] = 'Do you really want to delete the repetition \'$a\'?, all events of repetition will be removed and students subscribe to these events will be unsuscribed.';
$string['deleterror'] = 'Error deleting the event \'$a\'';
$string['sessionerror'] = 'Session error';
$string['deleteok'] = 'The event was successfully removed \'$a\'';
$string['delerepteok'] = 'The event repetition was successfully removed \'$a\'';
$string['deleteventwarning'] = 'Warning: This tutorship has not finished yet and there are students subscribed.';
$string['deleterepwarning'] = 'Warning: This repetition has not finished yet and there are students subscribed.';
$string['deleterepnowwarning'] = 'Warning: This the events to delete has not finished yet and there are students subscribed.';
$string['delereptenowok'] = 'The selected events were successfully removed';
$string['deleterepnow'] = 'Do you really want to delete the repetition \'$a\' from selected?, the actual event and all the next events of repetition will be removed and students subscribe to these events will be unsuscribed.';

// strings via suscribe.php
$string['strftime_fulldatetime'] = '%%c';
$string['strftime_time'] = '%%R';
$string['strftime_minutes'] = '%%M';
$string['selecthour'] = 'Select time';
$string['suscribeto'] = 'Susbcribe to: $a';
$string['selectother'] = 'do you want to select another slot?';
$string['durationtutorship'] = 'The duration of each tutorship is $a minutes,';
$string['allowmultipletutorship'] = 'but the teacher has allowed up to 2 slots per student.';
$string['twhoequalhour'] = 'The same time can not be chosen twice';
$string['comments'] = 'Comments:';
$string['errorsuscribetwice'] = 'Error: you can not subscribe twice to the same tutorship.';
$string['suscribedto'] = 'You have been subscribed to: $a';
$string['errorsuscribing'] = 'Error: you have not been subscribed to the tutorship.';
$string['errorsuscribetwice2'] = 'Error: the selected slot is occupied.';
$string['errortutorshipfull'] = 'Error: the tutorship is complete';
$string['errorsuscribing2'] = 'Error: One of the choosen slots has just been occupied. Check your selection.';
$string['suscribemessage'] = 'The student $a->name has been subscribed to tutorship $a->tutorship, <a href=\"$a->body\">Ver</a>.';
$string['username'] = '$a->lastname, $a->firstname';
$string['suscribesubject'] = 'New student subscribed to \'$a\'.';

$string['help_option'] = 'Select time';
$string['help_multipletutorship'] = 'do you want to select another slot?';
$string['help_option2'] = 'Select time';
$string['help_suscribe'] = 'Suscribe to';

// strings via view_students.php
$string['lastname'] = 'Lastname';
$string['studentname'] = 'Firstname';
$string['hour'] = 'Hour';
$string['suscribedsto'] = 'Subcribed to: $a';
$string['select'] = 'Selects';
$string['sendmailselected'] = 'Send an email to selected users ';
$string['participants'] = 'Participants';

// strings via unsuscribe.php
$string['confirunsuscribe'] = 'Confirm';
$string['invalidsucription'] = 'The id $a is not a valid subscription id.';
//TODO
$string['unsuscribe'] = 'Do you really want to unsubscribe from \'$a\'?';
$string['unsuscribeerror'] = 'Error unsuscribing from: \'$a\'';
$string['unsuscribeok'] = 'You have been correctly unsubscribed from \'$a\'.';
$string['unsuscribeto'] = 'Unsubscribe from: $a';
$string['unsuscribemessage'] = 'The student $a->name hasbeen unsubscribe from $a->tutorship, <a href=\"$a->body\">Ver</a>.';
$string['unsuscribesubject'] = 'A student has unsubscribe of \'$a\'.';

// strings via manage_students.php
$string['addremovestudents'] = 'Add/Remove users';
$string['withouthour'] = 'No Time';
$string['addbyteacher'] = 'Student added by teacher';
$string['addremove']= 'Add/Remove students';
$string['existingmembers']= 'Current Members: $a';
$string['potentialmembers']= 'Potential members: $a';
$string['addstudentgroup']= 'Add';

//strigs via export
$string['filename'] = 'tutorship';
$string['export'] = 'Export to Excel';
$string['numstudents'] = 'Number of students';
$string['name'] = 'Name';
$string['mail'] = 'E-Mail';
$string['comments2'] = 'Comments';
$string['students'] = 'Students';

//strings via manage
$string['manage'] = 'Manage';
$string['managery'] = 'Managery';
$string['createnewtutorship'] = 'Create new tutorship';
$string['viewalltutorship'] = 'View all the tutorships';
$string['viewmytutorship'] = 'View my tutorship';
$string['viewcalendar'] = 'View yearly calendar';

//strings via edit
$string['editable_level1'] = 'This event has students subscribed, you can only edit: Name, Description, Location and Visibility.';
$string['editrepetition'] = 'You are editing a repeat, all the events of the repetition will be edited.';
$string['editsubject'] = 'Event \'$a\' updated.';
$string['editmessage'] = 'The event $a->name has been updated <a href=\"$a->body\">view.</a>.';
$string['editrepetitionfromnow'] = 'You are editing a repeat, all the events of the repetition from now will be edited.<br> New repetition will be created.';

//strings via cron
$string['remembersubject'] = 'Tutorship of day: \'$a\'.';
$string['remembermessage'] = '<h1> $a->name </h1><br> <b>Tutorship: </b> $a->tutorship <br> <b>Students: </b> $a->table <br>';
?>
