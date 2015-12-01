<?php
/**
 * @package    mod
 * @subpackage superchat
 * @copyright  2012 Eduardo Kraus  {@link http://eduardokraus.com}
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    //$settings->add(new admin_setting_configcheckbox('superchat/popup',
    //    get_string('popupname', 'superchat'),get_string('popupdescription', 'superchat'), 1));

    $settings->add(new admin_setting_configtext('superchat/server',
        'Servidor', 'Servidor que você instalou o Node JS', $_SERVER['HTTP_HOST'], PARAM_LOCALURL));

    $settings->add(new admin_setting_configtext('superchat/port',
        'Porta', 'Porta que você instalou o Node JS', 8080, PARAM_INT));

}
