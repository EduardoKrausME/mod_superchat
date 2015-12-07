<?php
/***************************
 * User: kraus
 * Date: 06/12/2015
 * Time: 19:15
 ***************************/


require_once ( dirname ( dirname ( dirname ( __FILE__ ) ) ) . '/config.php' );
require_once ( dirname ( __FILE__ ) . '/lib.php' );

$id = required_param ( 'id', PARAM_TEXT ); // Course_module ID, or


$id = substr ( $id, 33 );
$currentgroupid = 0;
if ( strpos ( $id, '_' ) )
{
    $rooms = explode ( '_', $id );
    $id = $rooms[ 0 ];
    $currentgroupid = $rooms[ 1 ];
}


if( $id )
{
    $cm = get_coursemodule_from_id ( 'superchat', $id, 0, false, MUST_EXIST );
    $course = $DB->get_record ( 'course', array ( 'id' => $cm->course ), '*', MUST_EXIST );
}

require_login ( $course, true, $cm );

$sql = 'SELECT COUNT(*) AS num
        FROM {superchat_messages} sm
        WHERE sm.groupid = ?
          AND sm.superchatid = ?';
$count = $DB->get_record_sql ( $sql, array ( $currentgroupid, $id ) );


$minrows = $count->num - 40;
if ( $minrows < 0 )
    $minrows = 0;

$sql = 'SELECT sm.id, sm.userid, sm.message, sm.timestamp, u.firstname, u.lastname
        FROM {superchat_messages} sm
        JOIN  {user} u on u.id = sm.userid
        WHERE sm.groupid = ?
          AND sm.superchatid = ?
        ORDER BY sm.timestamp ASC
        LIMIT ' . $minrows . ', ' . $count->num;
$historys = $DB->get_records_sql ( $sql, array ( $currentgroupid, $id ) );

$returnHistory = array(
    'numtotal' => $count->num,
    'messages' => array()
);
foreach($historys as $history)
{
    $returnHistory['messages'][] = array(
        'userid'    => $history->userid,
        'message'   => $history->message,
        'timestamp' => $history->timestamp,
        'fullname'  => $history->firstname . ' ' . $history->lastname
    );
}

header ( 'Content-Type: application/json' );
echo json_encode ( $returnHistory );