<?php
/***************************
 * User: kraus
 * Date: 05/12/2015
 * Time: 14:33
 ***************************/

if( !isset($_GET[ 'sessionid' ]) )
{
    header("HTTP/1.0 404 Not Found");
    die('sessionid not found');
}
if( !isset($_GET[ 'sessionname' ]) )
{
    header("HTTP/1.0 404 Not Found");
    die('sessionname not found');
}

session_id    ( $_GET[ 'sessionid' ] );
session_name  ( $_GET[ 'sessionname' ] );
session_start ();

require_once ( dirname ( dirname ( dirname ( __FILE__ ) ) ) . '/config.php' );
require_once ( dirname ( __FILE__ ) . '/lib.php' );

if( $USER->id == 0 )
{
    header("HTTP/1.0 404 Not Found");
    die('user not found');
}

error_reporting ( E_ALL );
ini_set ( 'display_errors', 'On' );

$page = new moodle_page();
$page->set_url ( '/user/profile.php' );
$page->set_context ( context_system::instance () );
$renderer = $page->get_renderer ( 'core' );

$up3 = new user_picture( $_SESSION[ 'USER' ] );
$up3->size = 100;

ob_clean();


header ( 'Content-Type: application/json' );
echo json_encode(
    array(
        'userid'   => $USER->id,
        'email'    => $USER->email,
        'fullname' => fullname ( $USER ),
        'city'     => $USER->city,
        'photo'    => $up3->get_url ( $page, $renderer )->out ( false )
    )
);