<?php

class auth_plugin_mpm2moodle_lib {

	// decrypt a string based on a key
	public static function decrypt_string($base64, $key) {
		if (!$base64) { return ""; }
		$data = str_replace(array('-','_'),array('+','/'),$base64); // manual de-hack url formatting
		$mod4 = strlen($data) % 4; // base64 length must be evenly divisible by 4
		if ($mod4) {
			$data .= substr('====', $mod4);
		}
		$crypttext = base64_decode($data);
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$decrypttext = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key.$key), $crypttext, MCRYPT_MODE_ECB, $iv);
		return trim($decrypttext);
	}

	// get a value from a saved querystring for the given key
	public static function get_key_value($string, $key) {
		$list = explode( '&', str_replace( '&amp;', '&', $string));
		foreach ($list as $pair) {
			$item = explode( '=', $pair);
			if (strtolower($key) == strtolower($item[0])) {
				return urldecode($item[1]); // not for use in $_GET etc, which is already decoded, however our encoder uses http_build_query() before encrypting
			}
		}
		return "";
	}

	// encrypted a string base on a key
	private static function encrypt_string($value, $key) {
	    if (!$value) {return "";}
	    $text = $value;
	    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
	    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	    $crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key . $key), $text, MCRYPT_MODE_ECB, $iv);
	    $data = base64_encode($crypttext);
	    $data = str_replace(array('+', '/', '='), array('-', '_', ''), $data);
	    return trim($data);
	}

	// build an encrypted logon string
	public static function build_url($userdata, $enrolas) {
	        date_default_timezone_set('UTC');
	        $enc = array(
	            "stamp" => time(),
	            "username" => $userdata["username"],
	            "firstname" => $userdata["firstname"],
	            "lastname" => $userdata["lastname"],
	            "email" => $userdata["email"],
	            "idnumber" => $userdata["idnumber"],
	            "role" => $userdata["role"],
	            "practice_name" => $userdata["practice_name"],
	            "practice_id" => $userdata["practice_id"],
	            "practice_logo" => $userdata["practice_logo"],
	            "updatable" => $userdata["updatable"],
	            "enrolas" => $enrolas
	        );
	        $details = http_build_query($enc);
	        return rtrim("/auth/mpm2moodle/login.php?data=" . self::encrypt_string($details, get_config('auth/mpm2moodle', 'sharedsecret')));
	}

	// truncate_userinfo requires and returns an array
	// but we want to send in and return a user object
	public static function truncate_user($userobj) {
		$user_array = truncate_userinfo((array) $userobj);
		$obj = new stdClass();
		foreach($user_array as $key=>$value) {
			$obj->{$key} = $value;
		}
		return $obj;
	}


	/*
	Issue: https://github.com/frumbert/mpm2moodle--wordpress-/issues/10
	Author: catasoft
	Purpose, enrols everyone as student using the manual enrolment plugin
	Todo:  do we trigger \core\event\user_enrolment_created::create() ??
	*/
	public static function enrol_into_course($courseid, $userid, $roleid = 5) {
		global $DB, $SESSION;
		$manualenrol = enrol_get_plugin('manual'); // get the enrolment plugin
		$enrolinstance = $DB->get_record('enrol',
			array('courseid'=>$courseid,
				'status'=>ENROL_INSTANCE_ENABLED,
				'enrol'=>'manual'
			),
			'*',
			MUST_EXIST
		);
		// retrieve enrolment instance associated with your course
		return $manualenrol->enrol_user($enrolinstance, $userid, $roleid); // enrol the user
	}

}