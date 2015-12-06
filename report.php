<?php
/***************************
 * User: kraus
 * Date: 06/12/2015
 * Time: 06:13
 ***************************/


/// This page prints reports and info about Super Chats

require_once('../../config.php');
require_once('lib.php');

$id     = required_param ( 'id', PARAM_INT );
$group  = optional_param ( 'group', 0, PARAM_INT );

$url = new moodle_url('/mod/superchat/report.php', array('id'=>$id));
$PAGE->set_url($url);

if (! $cm = get_coursemodule_from_id('superchat', $id)) {
    print_error('invalidcoursemodule');
}
if (! $superchat = $DB->get_record('superchat', array('id'=>$cm->instance))) {
    print_error('invalidcoursemodule');
}
if (! $course = $DB->get_record('course', array('id'=>$superchat->course))) {
    print_error('coursemisconf');
}

$context = context_module::instance($cm->id);
$PAGE->set_context($context);
$PAGE->set_heading($course->fullname);

$PAGE->requires->css('/mod/superchat/css/chat-section-v4.css');

require_login($course, false, $cm);


$reporttitle = get_string('reporttitle', 'superchat');
$PAGE->set_title ( format_string ( $superchat->name ) . ": " . $reporttitle );
echo $OUTPUT->header();
echo $OUTPUT->heading($reporttitle, 2);

groups_print_activity_menu ( $cm, $CFG->wwwroot . "/mod/superchat/report.php?id=$cm->id", false, false );


$sql = 'SELECT *
        FROM {superchat_messages}
        WHERE superchatid = ?
          AND groupid = ?
        ORDER BY id ASC';
$superchat_messages = $DB->get_records_sql ( $sql, array( $id, $group ) );


if ( count ( $superchat_messages ) )
{
    echo '
    <div id="chat-section" class="report">
        <ul id="chats" class="report">';

    $ultimaMensagemDe = 0;
    $ultimaDataEm = '';

    foreach ( $superchat_messages as $message )
    {
        $dataMensagem = userdate ( $message->timestamp, '%A, %d de %B de %Y' );
        if ( $ultimaDataEm != $dataMensagem )
            echo '<li class="message-status no-text"><span>' . $dataMensagem . '</span></li>';

        $ultimaDataEm = $dataMensagem;

        $who = 'you';
        if ( $message->userid == $USER->id )
            $who = 'me';

        if( $ultimaMensagemDe == $message->userid )
        {
            // If the last message sent chatting is not that person,
            // places the image
            echo '<li class="' . $who . ' no-name">
                  <div class="image off"></div>
                      <div class="message">
                          <div>' . $message->message . '</div>
                          <time>'.userdate($message->timestamp, '%H:%M').'</time>
                      </div>
                  <div class="clear"></div>
              </li>';
        }
        else{
            // Otherwise poses no image

            $user = $DB->get_record ( 'user', array ( 'id' => $message->userid ) );

            $page = new moodle_page();
            $page->set_url ( '/user/profile.php' );
            $page->set_context ( context_system::instance () );
            $renderer = $page->get_renderer ( 'core' );
            $up3 = new user_picture( $user );
            $up3->size = 100;
            $image = $up3->get_url ( $page, $renderer )->out ( false );

            echo '<li class=' . $who . '>
                  <div class="image">
                      <img src=' . $image . ' />
                  </div>
                  <div class="message">
                      <b class="no-text">' . fullname ( $user ) . '</b>
                      <div>' . $message->message . '</div>
                      <time class="no-text">'.userdate($message->timestamp, '%H:%M').'</time>
                  </div>
                  <div class="clear"></div>
              </li>';
        }
        $ultimaMensagemDe = $message->userid;
    }


    echo '</ul></div>';
}
else{
    echo $OUTPUT->heading(get_string('reportnohistory', 'superchat'), 4);
}

echo $OUTPUT->footer();
exit;

