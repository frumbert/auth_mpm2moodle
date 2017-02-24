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
 * Strings for component 'auth_none', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   auth_mpm2moodle
 * @copyright 2012 onwards Tim St.Clair  {@link http://timstclair.me}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['auth_mpm2moodle_secretkey'] = 'Encryption key';
$string['auth_mpm2moodle_secretkey_desc'] = 'Must match remote plugin setting';

$string['auth_mpm2moodledescription'] = 'Uses remtote portal user details to create user & log onto Moodle';
$string['pluginname'] = 'MPM 2 Moodle (SSO)';

$string['auth_mpm2moodle_timeout'] = 'Link timeout';
$string['auth_mpm2moodle_timeout_desc'] = 'Minutes before incoming link is considered invalid (allow for reading time on remote page)';

$string['auth_mpm2moodle_logoffurl'] = 'Logoff Url';
$string['auth_mpm2moodle_logoffurl_desc'] = 'Url to redirect to if the user presses Logoff';

$string['auth_mpm2moodle_entryurl'] = 'Entry Url';
$string['auth_mpm2moodle_entryurl_desc'] = 'Url to redirect to after a successful logon (e.g. dashboard)';

$string['auth_mpm2moodle_autoopen_desc'] = 'Automatically open the course after successful auth (uses first match in cohort or group)';
$string['auth_mpm2moodle_autoopen'] = 'Auto open course?';

$string['auth_mpm2moodle_updateuser'] = 'Update user profile fields using remote values?';
$string['auth_mpm2moodle_updateuser_desc'] = 'If set, user profile fields such as first and last name will be overwritten each time the SSO occurs.';

$string['auth_mpm2moodle_firstname'] = 'Default first name';
$string['auth_mpm2moodle_firstname_desc'] = 'If no first name is specified by remote, use this value';

$string['auth_mpm2moodle_lastname'] = 'Default last name';
$string['auth_mpm2moodle_lastname_desc'] = 'If no last name is specified by remote, use this value';

$string['auth_mpm2moodle_managerRoleName'] = 'Manager Role Name';
$string['auth_mpm2moodle_managerRoleName_desc'] = 'Name of Role that manages users in their practice';

$string['auth_mpm2moodle_roleNames'] = 'Role Names';
$string['auth_mpm2moodle_roleNames_desc'] = 'Comma-seperated list of the names of the roles';

$string['auth_mpm2moodle_enrolAsTeacher'] = 'Enrol managers are teacher?';
$string['auth_mpm2moodle_enrolAsTeacher_desc'] = 'If set, managers are enrolled as Teachers instead of Students';