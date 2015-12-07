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
 * @package    mod_superchat
 * @copyright  2015 Eduardo Kraus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = required_param('id', PARAM_INT); // Course_module ID, or
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

require_login ( $course, true, $cm );

$event = \mod_superchat\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $superchat);
$event->trigger();

// Print the page header.

$PAGE->set_url ( '/mod/superchat/view.php', array ( 'id' => $cm->id ) );

if ( isguestuser () )
{
    $PAGE->set_title ( $superchat->name );
    echo $OUTPUT->header ();
    echo $OUTPUT->confirm (
                        '<p>' . get_string ( 'errornoguests', 'superchat' ) . '</p>' .
                        '<p>' . get_string ( 'liketologin' ) . '</p>',
                    get_login_url(),
                    $CFG->wwwroot.'/course/view.php?id='.$course->id);
    echo $OUTPUT->footer ();
    exit;
}

/**
 * $groupmode == 0
 *    Nenhum Grupo
 * $groupmode == 1
 *    Grupos separados
 * $groupmode == 2
 *    Grupos visíveis
 */
$groupmode = groups_get_activity_groupmode($cm);
$usergroup = groups_get_user_groups ( $course->id, $USER->id );

$currentgroupid = 0;
if( $groupmode == 2 )
{
    if ( count ( $usergroup[ 0 ] ) == 0 )
        print_error('errornochat', 'superchat');
    elseif ( count ( $usergroup[ 0 ] ) == 1 )
        $currentgroupid = $usergroup[ 0 ][ 0 ];
    else
        $currentgroupid  = optional_param('group', $usergroup[ 0 ][ 0 ], PARAM_INT);

    if ( !groups_is_member ( $currentgroupid, $USER->id ) ) {
        print_error('errornogroup', 'superchat');


    }
}




// Page Chat
?><!DOCTYPE html>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="www.eduardokraus.com">

    <title><?php echo $superchat->name ?></title>

    <link href="http://fonts.googleapis.com/css?family=Open+Sans Condensed:300italic,300,700" rel="stylesheet" type="text/css">
    <link href="<?php echo $CFG->wwwroot ?>/mod/superchat/css/stylesheet-v4.css"              rel="stylesheet" type="text/css">

</head>
<body>

<section>
    <div id="user-section">
        <div class="user-screen">
            <div class="students no-text">
                <?php
                if( $groupmode == 2 )
                {
                    if ( count ( $usergroup[ 0 ] ) == 1 )
                    {
                        $currentgroupname = groups_get_group_name($currentgroupid);
                        echo $currentgroupname;
                    }
                    else
                    {?>
                        <form method="get" action="<?php echo $CFG->wwwroot ?>/mod/superchat/view.php" id="form_select_group">
                            <div>
                                <input type="hidden" name="id" value="<?php echo $id ?>">
                                <select id="select_group" class="select autosubmit singleselect" name="group">
                                    <?php
                                    $listGroup = array ();
                                    foreach ( $usergroup[ 0 ] as $group )
                                        $listGroup[] = $group;
                                    echo $listGroupIn = implode ( ',', $listGroup );

                                    $sql = "SELECT *
                                            FROM {groups} g
                                            WHERE g.id IN(".$listGroupIn.")";
                                    $groups = $DB->get_records_sql ( $sql );

                                    foreach( $groups as $group )
                                    {
                                        echo '<option value="'.$group->id.'"';
                                        if( $group->id == $currentgroupid )
                                            echo ' selected';
                                        echo '>'.$group->name.'</option>';
                                    }
                                    ?>
                                </select>
                                <noscript class="inline"><input type="submit" value="Vai" /></noscript>
                            </div>
                        </form><?php
                    }
                }
                else
                    echo get_string('studentsinthischat_title', 'superchat')
                ?>
            </div>
            <ul id="users">
                <?php
                // get a context by course
                $context = context_course::instance($course->id );

                // list all users
                if( $groupmode == 2 )
                {
                    $query = "SELECT roleid, u.*
                                FROM {role_assignments} as a
                                JOIN {user} as u ON a.userid=u.id
                                JOIN {groups_members} gm ON u.id = gm.userid
                              WHERE contextid = ?
                              AND gm.groupid = ?
                              ORDER BY roleid, firstname, lastname";
                    $students = $DB->get_recordset_sql ( $query, array ( $context->id, $currentgroupid ) );
                }
                else
                {
                    $query = "SELECT roleid, u.*
                                FROM {role_assignments} as a
                                JOIN {user} as u ON a.userid=u.id
                              WHERE contextid = ?
                              ORDER BY roleid, firstname, lastname";
                    $students = $DB->get_recordset_sql( $query, array($context->id) );
                }

                $studentNum = 0;
                // print all users
                foreach( $students as $student ) {?>
                    <li id="student_<?php echo $student->id ?>" data-prof="<?php echo $student->roleid == 5 ? 'b' : 'a'; ?>" data-order="<?php echo $studentNum++ ?>">
                        <?php
                        $page = new moodle_page();
                        $page->set_url ( '/user/profile.php' );
                        $page->set_context ( context_system::instance () );
                        $renderer = $page->get_renderer ( 'core' );
                        $up3 = new user_picture( $student );
                        $up3->size = 100;
                        $image = $up3->get_url ( $page, $renderer )->out ( false );
                        ?>
                        <img src="<?php echo $image ?>" alt="<?php echo $student->firstname ?>">
                        <span class="user-name no-text"><?php echo fullname($student) ?></span>
                        <span class="user-permission no-text"><?php
                            echo ( $student->roleid == 5 ) ? get_string('student', 'superchat') : get_string('teacher', 'superchat') ;
                            ?></span>
                        <span class="user-status"></span>
                    </li>
                <?php
                }
                ?>
            </ul>
        </div>
    </div>

    <div id="chat-section">
        <div class="chat-screen">
            <ul id="chats"></ul>
        </div>
        <div id="chatfooter">
            <?php
            $config = get_config('superchat');

            $server_room = md5 ( $CFG->wwwroot ) . '_' . $cm->id . '_' . $currentgroupid;

            $session_tmp_id = md5 ( session_id () );

            $superchat_node_auth = new stdClass();
            $superchat_node_auth->userid  = $USER->id;
            $superchat_node_auth->session = $session_tmp_id;
            $DB->insert_record ( 'superchat_node_auth', $superchat_node_auth );

            ?>
            <input type="hidden" name="server_room"  id="server_room"  value="<?php echo $server_room ?>">
            <input type="hidden" name="server_host"  id="server_host"  value="<?php echo $config->server ?>">
            <input type="hidden" name="server_port"  id="server_port"  value="<?php echo $config->port ?>">

            <input type="hidden" name="session_tmp"  id="session_tmp"  value="<?php echo $session_tmp_id ?>">

            <div class="new-message">
                <span class="background">
                    <?php
                    echo get_string('newmessages', 'superchat',
                        '<span class="rotate">☞</span>
                         <span class="rotate">☞</span>
                         <span class="rotate">☞</span>'
                    );
                    ?>

                </span>
            </div>

            <div id="message-area" style="display: none">
                <div class="relative">
                    <div id="message-placeholder"><?php echo get_string('typeamessage', 'superchat') ?></div>
                    <div id="message" dir="auto" contenteditable="true" class="input"></div>
                </div>
                <input type="submit" id="submit" value="<?php echo get_string('send', 'superchat') ?>" disabled class="disabled"/>
                <div style="clear: both;"></div>
            </div>

            <div class="status-area status-area-wait" id="wait-area">
                <div class="wait-area">
                    <?php echo get_string('waitconnection', 'superchat'); ?>
                </div>
            </div>
            <div class="status-area status-area-error" id="error-area1" style="display: none">
                <div class="error-area">
                    <?php echo get_string('error1connection', 'superchat'); ?> <span></span>
                </div>
            </div>
            <div class="status-area status-area-error" id="error-area2" style="display: none">
                <div class="error-area" >
                    <?php echo get_string('error2connection', 'superchat'); ?> <span></span>
                </div>
            </div>

        </div>
    </div>
</section>

<script src="http://<?php echo $config->server ?>:<?php echo $config->port ?>/socket.io/socket.io.js"></script>
<script src="<?php echo $CFG->wwwroot ?>/mod/superchat/js/jquery.min.js"></script>
<script src="<?php echo $CFG->wwwroot ?>/mod/superchat/js/chat-v4.js"></script>

</body>
</html>

