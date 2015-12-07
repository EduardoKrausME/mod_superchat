<?php
/***************************
 * User: kraus
 * Date: 06/12/2015
 * Time: 18:51
 ***************************/


require_once ( dirname ( dirname ( dirname ( __FILE__ ) ) ) . '/config.php' );
require_once ( dirname ( __FILE__ ) . '/lib.php' );

$id = required_param ( 'id', PARAM_INT );

$page = new moodle_page();
$page->set_url ( '/user/profile.php' );
$page->set_context ( context_system::instance () );
$renderer = $page->get_renderer ( 'core' );

$user = $DB->get_record ( 'user', array ( 'id' => $id ), '*', MUST_EXIST );

$up3 = new user_picture( $user );
$up3->size = 100;

ob_clean ();

$image = $up3->get_url ( $page, $renderer )->out ( false );

header ( 'Location: ' . $image );