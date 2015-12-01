<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Prints a particular instance of superchat
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_superchat
 * @copyright  2015 Eduardo Kraus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Replace superchat with the name of your module and remove this line.

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // ... superchat instance ID - it should be named as the first character of the module.

if ($id) {
    $cm         = get_coursemodule_from_id('superchat', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $superchat  = $DB->get_record('superchat', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $superchat  = $DB->get_record('superchat', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $superchat->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('superchat', $superchat->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$event = \mod_superchat\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $superchat);
$event->trigger();

// Print the page header.

$PAGE->set_url('/mod/superchat/view.php', array('id' => $cm->id));
//$PAGE->set_title(format_string($superchat->name));
//$PAGE->set_heading(format_string($course->fullname));

?><!DOCTYPE html>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="www.eduardokraus.com">

    <title><?php echo $superchat->name ?></title>

    <link href="http://fonts.googleapis.com/css?family=Open+Sans Condensed:300italic,300,700" rel="stylesheet" type="text/css">
    <link href="<?php echo $CFG->wwwroot ?>/mod/superchat/css/stylesheet-v3.css" rel="stylesheet" type="text/css">

</head>
<body>

<section>
    <div id="user-section">
        <div class="user-screen">
            <div class="students no-text">
                Alunos deste Chat
            </div>
            <ul id="users">
                <?php
                $context = context_course::instance($course->id );

                $query = "SELECT u.id as id, firstname, lastname, picture, imagealt, email, roleid
                                 FROM {role_assignments} as a
                                 JOIN {user} as u ON a.userid=u.id
                               WHERE contextid=" . $context->id . "
                               ORDER BY roleid, firstname, lastname";

                $students = $DB->get_recordset_sql( $query );
                foreach( $students as $student ) {?>
                    <li id="student_<?php echo $student->id ?>">
                        <?php
                        $imagem = $OUTPUT->user_picture($student, array('size'=>50));
                        $array = array();
                        preg_match( '/src="([^"]*)"/i', $imagem, $array ) ;
                        $image = $array[1];
                        ?>
                        <img src="<?php echo $image ?>" alt="<?php echo $student->firstname ?>">
                        <span class="user-name no-text"><?php echo $student->firstname . ' ' . $student->lastname ?></span>
                        <span class="user-status no-text"><?php
                            echo ( $student->roleid == 5 ) ? 'Aluno' : 'Professor' ;
                            ?></span>
                    </li>
                <?php
                }
                ?>
            </ul>
        </div>
    </div>

    <div id="chat-section">
        <div class="chat-screen">
            <ul id="chats">
            </ul>
        </div>
        <div id="chatfooter">
            <?php
            $config = get_config('superchat');
            ?>
            <input type="hidden" name="server_room" id="server_room" value="<?php echo $cm->id ?>">
            <input type="hidden" name="server_host" id="server_host" value="<?php echo $config->server ?>">
            <input type="hidden" name="server_port" id="server_port" value="<?php echo $config->port ?>">

            <input type="hidden" name="userid"   id="userid"   value="<?php echo $USER->id ?>">
            <input type="hidden" name="fullname" id="fullname" value="<?php echo $USER->firstname . ' ' . $USER->lastname ?>">
            <input type="hidden" name="photo"    id="photo"    value="<?php
                    $imagem = $OUTPUT->user_picture($USER, array('size'=>100));
                    $array = array();
                    preg_match( '/src="([^"]*)"/i', $imagem, $array ) ;
                    echo  $array[1];
                ?>">
            <div class="message-area">
                <div id="message-background"></div>
                <div id="message-placeholder">Digite uma mensagem</div>
                <div id="message" placeholder="Digite uma mensagem" dir="auto" contenteditable="true" class="input"></div>
            </div>

            <input type="submit" id="submit" value="SEND" disabled class="disabled"/>
        </div>
    </div>
</section>

<script src="<?php echo $CFG->wwwroot ?>/mod/superchat/js/socket.io.js"></script>
<script src="<?php echo $CFG->wwwroot ?>/mod/superchat/js/jquery.min.js"></script>
<script src="<?php echo $CFG->wwwroot ?>/mod/superchat/js/chat-v3.js"></script>

</body>
</html>

