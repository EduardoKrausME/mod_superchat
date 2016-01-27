<?php
/***************************
 * User: kraus
 * Date: 05/12/2015
 * Time: 14:33
 ***************************/

ob_start ();

if ( !isset( $_GET[ 'session_tmp' ] ) )
    die( 'session_tmp not found' );

$session_tmp = $_GET[ 'session_tmp' ];

require_once ( dirname ( dirname ( dirname ( __FILE__ ) ) ) . '/config.php' );
require_once ( dirname ( __FILE__ ) . '/lib.php' );


$sql = 'SELECT DISTINCT u.*
          FROM {superchat_node_auth} sna
          JOIN {user} u ON u.id = sna.userid
         WHERE sna.session = ?';
$user = $DB->get_record_sql ( $sql, array ( $session_tmp ) );

if ( $user == null )
    die( 'user not found' );

$DB->delete_records ( 'superchat_node_auth', array ( 'session' => $session_tmp ) );

$page = new moodle_page();
$page->set_url ( '/user/profile.php' );
$page->set_context ( context_system::instance () );
$renderer = $page->get_renderer ( 'core' );

$up3 = new user_picture( $user );
$up3->size = 100;

ob_clean ();

header ( 'Content-Type: application/json' );
echo json_encode ( array (
        'userid'    => $user->id,
        'email'     => $user->email,
        'fullname'  => fullname ( $user ),
        'city'      => $user->city,
        'photo'     => $up3->get_url ( $page, $renderer )->out ( false )
    ) );