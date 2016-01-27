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
 * Library of interface functions and constants for module superchat
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 *
 * All the superchat specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_superchat
 * @copyright  2015 Eduardo Kraus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Example constant, you probably want to remove this :-)
 */
define('SUPERCHAT_ULTIMATE_ANSWER', 42);

/* Moodle core API */

/**
 * Returns the information on whether the module supports a feature
 *
 * See {@link plugin_supports()} for more info.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function superchat_supports($feature) {

    switch($feature) {
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        default:                              return null;
    }
}

/**
 * Saves a new instance of the superchat into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $superchat Submitted data from the form in mod_form.php
 * @param mod_superchat_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted superchat record
 */
function superchat_add_instance(stdClass $superchat, mod_superchat_mod_form $mform = null) {
    global $DB;

    $superchat->timecreated = time();

    $superchat->id = $DB->insert_record('superchat', $superchat);

    return $superchat->id;
}

/**
 * Updates an instance of the superchat in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $superchat An object from the form in mod_form.php
 * @param mod_superchat_mod_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 */
function superchat_update_instance(stdClass $superchat, mod_superchat_mod_form $mform = null) {
    global $DB;

    $superchat->timemodified = time();
    $superchat->id = $superchat->instance;

    $result = $DB->update_record('superchat', $superchat);

    return $result;
}

/**
 * Removes an instance of the superchat from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function superchat_delete_instance($id) {
    global $DB;

    if (! $superchat = $DB->get_record('superchat', array('id' => $id))) {
        return false;
    }

    $DB->delete_records('superchat', array('id' => $superchat->id));

    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass $course The course record
 * @param stdClass $user The user record
 * @param cm_info|stdClass $mod The course module info object or record
 * @param stdClass $superchat The superchat instance record
 * @return stdClass|null
 */
function superchat_user_outline($course, $user, $mod, $superchat) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/* Gradebook API */

/**
 * Is a given scale used by the instance of superchat?
 *
 * This function returns if a scale is being used by one superchat
 * if it has support for grading and scales.
 *
 * @param int $superchatid ID of an instance of this module
 * @param int $scaleid ID of the scale
 * @return bool true if the scale is used by the given superchat instance
 */
function superchat_scale_used($superchatid, $scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('superchat', array('id' => $superchatid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Serves the files from the superchat file areas
 *
 * @package mod_superchat
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the superchat's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function superchat_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    send_file_not_found();
}


/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {@link get_array_of_activities()} in course/lib.php
 *
 * @param object $coursemodule
 * @return object info
 */
function superchat_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;

    if (!$superchat = $DB->get_record('superchat', array('id'=>$coursemodule->instance),
        'id, name, intro, introformat')) {
        return NULL;
    }

    $info = new cached_cm_info();
    $info->name = $superchat->name;

    $config = get_config('superchat');
    $config->popup = true;
    if( $config->popup )
    {
        $fullurl = "$CFG->wwwroot/mod/superchat/view.php?id=$coursemodule->id";
        $width  = 620;
        $height = 480;
        $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
        $info->onclick = "window.open('$fullurl', '', '$wh'); return false;";
    }
    return $info;
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $chatnode The node to add module settings to
 */
function superchat_extend_settings_navigation(settings_navigation $settings, navigation_node $superchatnode)
{
    global $PAGE;
    $superchatnode->add(get_string('viewreport', 'superchat'), new moodle_url('/mod/superchat/report.php', array('id'=>$PAGE->cm->id)));
}