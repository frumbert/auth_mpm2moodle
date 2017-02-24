# auth / mpm2moodle

This is an auth plugin for Moodle. It was designed with Moodle 3.1 in mind, but should work as far back as 2.7 without too much work (before that, you have to rip out the Events code - then it works back to 2.2-ish).

Data is encrypted at the external portal end and handed over a standard http GET request. Only the minimum required information is sent in order to create a Moodle user record. The user is automatically created if not present at the Moodle end, and then authenticated, and then added to a Cohort whose name and idnumber matches the name you pass in, then sent on their merry way.

## Derivative work

It's based on my https://github.com/frumbert/wp2moodle-moodle and pretty much works the same. Go read about that.

## Integrations

This was designed for use with https://github.com/frumbert/block_course_status_tracker, but you could pretty easily use it as your own generic SSO solution for Moodle.

## How to install this plugin

Note, this plugin must exist in a folder named "mpm2moodle" - rename the zip file or folder before you upload it (preferably use something like `cd moodle/auth/ && git pull https://github.com/frumbert/auth_mpm2moodle mpm2moodle` if you have git tools on your server).

1. Upload/extract this to your moodle/auth folder (should be called "~/auth/mpm2moodle/", where ~ is your Moodle root)
2. Activate the plugin in the administration / authentication section
3. Click settings and enter the same shared secret that you enter for the mpm2moodle settings in external portal and whatever else you like to fiddle with.
4. The logoff url will perform a Moodle logout, then redirect to this url. You can get it to log off in external portal as well by hitting the external portal-end logout page too.
5. The link timeout is the number of minutes before the incoming link is thought to be invalid (to allow for variances in server times). This means links that were generated in the past can't be re-used, copied, bookmarked, etc.
5. Disable any other authentication methods as required. You can still use as many as you like. Manual enrolments must be enabled on courses that use group enrolment.

## Usage

You can not use this plugin directly; it is launched by an external portal.

## Settings

These all live in the standard Moodle place - e.g. /admin/auth_config.php?auth=mpm2moodle

Encryption key: the shared secret that's used to encrypt (using mcrypt) the data for the querystring
Link timeout: How long the url is considered valid (in minutes)
Logoff url: Where you want to go after logout
Entry url: Where you want to go after login
Update profile fields: If you want the data that is sent in to be able to update Moodle user profile fields (like their name or email address). Yes, probably.
Default first name: guess
Default last name: ditto
Manager Role Name: Users whose "Department" field matches this value are considered to be managers (for their Institute)
Role names: The list of valid Departments that a user can have
Enrol managers as teacher: do I have to explain this?

## Licence

GPL2, as per Moodle. Or is it GPL3 these days? Whatever Moodle uses.