<?php
/***************************
 * User: kraus
 * Date: 06/12/2015
 * Time: 04:35
 ***************************/

require_once ( dirname ( dirname ( dirname ( __FILE__ ) ) ) . '/config.php' );
require_once ( dirname ( __FILE__ ) . '/lib.php' );

$currentgroupid = 0;
echo $_REQUEST[ 'room' ];
$room = substr ( $_REQUEST[ 'room' ], 33 );
if ( strpos ( $room, '_' ) )
{
    $rooms = explode ( '_', $room );
    $room = $rooms[ 0 ];
    $currentgroupid = $rooms[ 1 ];
}

$json = json_decode( $_REQUEST['history'] );

foreach ( $json as $history )
{
    $superchat = new stdClass();
    $superchat->superchatid = $room;
    $superchat->userid      = $history->userid;
    $superchat->groupid     = $currentgroupid;
    $superchat->message     = $history->message;
    $superchat->timestamp   = $history->timestamp;

    $DB->insert_record('superchat_messages', $superchat);
}
