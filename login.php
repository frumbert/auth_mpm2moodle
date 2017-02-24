<?php
/**
 * @author Tim St.Clair - timst.clair@gmail.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package local/mpm2moodle
 * @version 1.0
 *
 * Moodle-end component of the wpMoodle Wordpress plugin.
 * Accepts user details passed across from Wordpress, creates a user in Moodle, authenticates them, and enrols them in the specified Cohort(s) or Group(s)
 *
 * 2012-05  Created
 * 2014-04  Added option to bypass updating user record for existing users
 *          Added option to enrol user into multiple cohorts or groups by specifying comma-separated list of identifiers
**/


global $CFG, $USER, $SESSION, $DB;

require('../../config.php');
require_once($CFG->libdir.'/moodlelib.php');
require_once($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->dirroot.'/group/lib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot."/lib/enrollib.php");

require_once($CFG->dirroot."/auth/mpm2moodle/lib.php");

$context = context_system::instance();
$PAGE->set_url("$CFG->httpswwwroot/auth/mpm2moodle/login.php");
$PAGE->set_context($context);

// logon may somehow modify this
$SESSION->wantsurl = $CFG->wwwroot.'/';

// $PASSTHROUGH_KEY = "the quick brown fox humps the lazy dog"; // must match mpm2moodle wordpress plugin setting
$PASSTHROUGH_KEY = get_config('auth/mpm2moodle', 'sharedsecret');
if (!isset($PASSTHROUGH_KEY)) {
	echo "Sorry, this plugin has not yet been configured. Please contact the Moodle administrator for details.";
}

$rawdata = $_GET['data'];
if (!empty($rawdata)) {


	// get the data that was passed in
	$userdata = auth_plugin_mpm2moodle_lib::decrypt_string($rawdata, $PASSTHROUGH_KEY);
	$userobj = array();
	parse_str(str_replace( '&amp;', '&', $userdata), $userobj);
	$SESSION->mpm2moodle = $userobj;

	// time (in minutes) before incoming link is considered invalid
	$timeout = (integer) get_config('auth/mpm2moodle', 'timeout');
	if ($timeout == 0) { $timeout = 5; }

	$default_firstname = get_config('auth/mpm2moodle', 'firstname') ?: "no-firstname"; // php 5.3 ternary
	$default_lastname = get_config('auth/mpm2moodle', 'lastname') ?: "no-lastname";
	$updatable = (get_config('auth/mpm2moodle', 'updateuser') !== "no");

 	$timestamp = intval(auth_plugin_mpm2moodle_lib::get_key_value($userdata, "stamp"));
	$UTC = new DateTimeZone("UTC");

	$date1 = new DateTime();
	$date1->setTimestamp($timestamp);
	$date1->setTimezone( $UTC );

	$date2 = new DateTime("now");
	$date2->setTimezone( $UTC );

	$mins = round(abs($date2->getTimestamp() - $date1->getTimestamp()) / 60,0);

	// check the timestamp to make sure that the request is still within a few minutes of this servers time
	if ($mins <= $timeout) { // less than N minutes passed since this link was created, so it's still ok

		$username = trim(strtolower(auth_plugin_mpm2moodle_lib::get_key_value($userdata, "username")));
		$firstname = auth_plugin_mpm2moodle_lib::get_key_value($userdata, "firstname") ?: $default_firstname;
		$lastname = auth_plugin_mpm2moodle_lib::get_key_value($userdata, "lastname") ?: $default_lastname;
		$email = auth_plugin_mpm2moodle_lib::get_key_value($userdata, "email");
		$idnumber = auth_plugin_mpm2moodle_lib::get_key_value($userdata, "idnumber"); // id from external system

		$role = auth_plugin_mpm2moodle_lib::get_key_value($userdata, "role"); // Reception/Admin, Nurse, Practice Manager, Allied Health, Registrar, Junior Doctor
		$practice_name = auth_plugin_mpm2moodle_lib::get_key_value($userdata, "practice_name");
		$practice_id = auth_plugin_mpm2moodle_lib::get_key_value($userdata, "practice_id");
		$practice_logo = auth_plugin_mpm2moodle_lib::get_key_value($userdata, "practice_logo");

		if (isset($practice_logo) && !empty($practice_logo)) {
			$SESSION->practice_logo = $practice_logo;
		}

		if (auth_plugin_mpm2moodle_lib::get_key_value($userdata, "updatable") == "no") {
			$updatefields = false;
		} else {
			$updatefields = $updatable;
		}

		if ($idnumber > "") {
			$enrolas = auth_plugin_mpm2moodle_lib::get_key_value($userdata, "enrolas") ?: "student";
			$teachers = explode(",", get_config("auth/mpm2moodle","teachers") ?: "");
			if (!in_array($idnumber, $teachers) && $enrolas == "teacher") { // add
				$teachers[] = $idnumber;
			} else if (in_array($idnumber, $teachers) && $enrolas != "teacher") { // remove
				$index = array_search($idnumber, $teachers);
				unset($teachers[$index]);
				$teachers = array_values($teachers);
			}
			set_config("teachers", implode(",", $teachers), "auth/mpm2moodle");
		}


		if ($DB->record_exists('user', array('username'=>$username, 'idnumber'=>'', 'auth'=>'manual'))) { // does a user with this username but no idnumber already exist?
			$updateuser = get_complete_user_data('username', $username);
			$updateuser->idnumber = $idnumber;
			$updateuser->department = $role;
			$updateuser->institution = $practice_name;
			if ($updatefields) {
				$updateuser->email = $email;
				$updateuser->firstname = $firstname;
				$updateuser->lastname = $lastname;
			}

			// make sure we haven't exceeded any field limits
			$updateuser = auth_plugin_mpm2moodle_lib::truncate_user($updateuser); // typecast obj to array, works just as well

			$updateuser->timemodified = time(); // record that we changed the record
			$DB->update_record('user', $updateuser);

			// trigger correct update event
			\core\event\user_updated::create_from_userid($updateuser->id)->trigger();

			// ensure we have the latest data
			$user = get_complete_user_data('idnumber', $idnumber);

		} else if ($DB->record_exists('user', array('idnumber'=>$idnumber))) { // match user on idnumber
			if ($updatefields) {
				$updateuser = get_complete_user_data('idnumber', $idnumber);
				// $updateuser->idnumber = $idnumber;
				$updateuser->email = $email;
				$updateuser->firstname = $firstname;
				$updateuser->lastname = $lastname;

				$updateuser->department = $role;
				$updateuser->institution = $practice_name;
				// $updateuser->username = $username;

				$updateuser = auth_plugin_mpm2moodle_lib::truncate_user($updateuser); // make sure we haven't exceeded any field limits
				$updateuser->timemodified = time(); // when we last changed the data in the record

				$DB->update_record('user', $updateuser);

				// trigger correct update event
				\core\event\user_updated::create_from_userid($updateuser->id)->trigger();
			}
			// ensure we have the latest data
			$user = get_complete_user_data('idnumber', $idnumber);

		} else { // create new user

			$auth = 'mpm2moodle'; // so they log in - and out - with this plugin
			$authplugin = get_auth_plugin($auth);
			$newuser = new stdClass();
			if ($newinfo = $authplugin->get_userinfo($username)) {
				$newinfo = auth_plugin_mpm2moodle_lib::truncate_user($newinfo);
				foreach ($newinfo as $key => $value){
					$newuser->$key = $value;
				}
			}

			if (!empty($newuser->email)) {
				if (email_is_not_allowed($newuser->email)) {
					unset($newuser->email);
				}
			}
			if (!isset($newuser->city)) {
				$newuser->city = '';
			}
			$newuser->auth = $auth;
			$newuser->policyagreed = 1;
			$newuser->idnumber = $idnumber;
			$newuser->username = $username;
			$newuser->password = md5($idnumber . $PASSTHROUGH_KEY . time()); // does't matter

			// $DB->set_field('user', 'password',  $hashedpassword, array('id'=>$user->id));
			$newuser->firstname = $firstname;
			$newuser->lastname = $lastname;
			$newuser->email = $email;
			if (empty($newuser->lang) || !get_string_manager()->translation_exists($newuser->lang)) {
				$newuser->lang = $CFG->lang;
			}
			$newuser->confirmed = 1; // don't want an email going out about this user
			$newuser->lastip = getremoteaddr();
			$newuser->timecreated = time();
			$newuser->timemodified = $newuser->timecreated;
			$newuser->mnethostid = $CFG->mnet_localhost_id;

			$newuser->institution = $practice_name;
			$newuser->department = $role;

			// make sure we haven't exceeded any field limits
			$newuser = auth_plugin_mpm2moodle_lib::truncate_user($newuser);

			$newuser->id = $DB->insert_record('user', $newuser);

			$user = get_complete_user_data('id', $newuser->id);
			\core\event\user_created::create_from_userid($user->id)->trigger();

		}

		$authplugin = get_auth_plugin('mpm2moodle');

		if ($DB->record_exists("user", array("id" => $user->id, "suspended" => 1))) {
			// account exsits but has been suspended - can't log in - silently log off and redirect
			$authplugin->logoutpage_hook();
		}

		// practice_id groups people from the same practice together (mainly for reporting purposes)
		// first, lets make a cohort for this practice
		if (!$DB->record_exists('cohort', array('idnumber'=>$practice_id))) {
			$row = new stdClass();
			$row->contextid = context_system::instance()->id;
			$row->name = $practice_name;
			$row->idnumber = $practice_id;
			$row->description = '';
			$row->descriptionformat = 1;
			$row->visible = 1;
			$row->component = '';
			$row->timecreated = time();
			$row->timemodified = time();
			$row->id = $DB->insert_record('cohort', $row);

			$event = \core\event\cohort_created::create(array(
			    'context' => context::instance_by_id($row->contextid),
			    'objectid' => $row->id,
			));
			$event->add_record_snapshot('cohort', $row);
			$event->trigger();

		}
		// and add this member to the cohort (which triggers core\event\cohort_member_added)
		$cohortrow = $DB->get_record('cohort', array('idnumber'=>$practice_id));
		if (!$DB->record_exists('cohort_members', array('cohortid'=>$cohortrow->id, 'userid'=>$user->id))) {
			cohort_add_member($cohortrow->id, $user->id);
		}

		// all that's left to do is to authenticate this user and set up their active session
		if ($authplugin->user_login($user->username, $user->password)) {
			$user->loggedin = true;
			$user->site     = $CFG->wwwroot;
			complete_user_login($user); // now performs \core\event\user_loggedin event

			// set page after login
			$entryUrl = get_config('auth/mpm2moodle', 'entryurl');
			if (isset($entryUrl) && !empty($entryUrl)) {
				$SESSION->wantsurl =  $entryUrl;
			}
		}
	} else {
		// didn't validate, head back to where the logoff url is configured
		$url = get_config('auth/mpm2moodle', 'logoffurl');
		if (isset($url) && !empty($url)) {
			header("Location: " . $url, true, 301);
		}
	}
}

// redirect to the homepage
redirect($SESSION->wantsurl);
?>

