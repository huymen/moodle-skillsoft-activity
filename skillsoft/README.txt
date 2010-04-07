SkillSoft Asset Module
Author: Martin Holden, SkillSoft http://www.skillsoft.com
================================================================

Moodle Compatibility
--------------------
This plugin will work with Moodle 1.9.5+. It is developed as a Moodle plugin/block.


PHP Requirement
---------------
PHP 5.2.x SOAP Client enabled in PHP.INI

Download Binary
---------------
The ZIP file containing the activity is located at::

Source (SVN)
------------
The source code for this plugin is located at::
http://code.google.com/p/moodle-skillsoft-activity/downloads/list

Install
-------
To install this plugin just extract the contents into MOODLE_HOME/mod/.
See the Moodle docs for help installing plugins/blocks::
http://docs.moodle.org/en/Installing_contributed_modules_or_plugins

The plugin also requires that the Moodle CRON Job is configured, and
scheduled to run every 5 minutes.
See the Moodle docs for help configuring CRON::
http://docs.moodle.org/en/Cron


Configuration
-------------
The configuration of the block is handled in the typical Moodle way.
You must login as an administrator and then go to::
Site Administration > Modules > Blocks > Manage blocks > SkillSoft Asset > Settings


TRACK TO OLSA MODE
------------------
The SkillSoft OLSA Site you will be using will need no special
configuration changes, from standard setup

You can choose between using the Moodle internal unique
student id or the Username, as the value that is used
as the username in OLSA.

As it is possible to change the Username, where as the internal
student id is controlled by Moodle an remains the same for the account
we recommend using the internal student id.

You may consider using the Username if you are integrating other
systems with the same OLSA server, this way so long as the Username in
OLSA is consistent with the Moodle Username only a single user record
will exist in OLSA.

Download Support
----------------
If you choose to us the internal unique student id from Moodle,
which the users will not know it is important to ensure that
the SCM Full SSO configuration is used.

* Note regarding usage data synchronisation *
When using Track to OLSA there is no distiction between asset
launches from different Moodle Courses. This means that if two
Moodle courses have the same SkillSoft Asset then access from
either course will result in update of the usage data in both.


TRACK TO LMS MODE
-----------------
The SkillSoft OLSA site you will be using will need to have the
following OLSA Player Configurations set:

Player RO Configuration
-----------------------
Standard AICC Configuration plus ensure OBJECTIVES data not used:
	AICC_CORE_LESSON_FOR_RESULTS=true
	AICC_CORE_VENDOR_FOR_DATE=true
	E3_AICC_OBJECTIVES_STATUS_FOR_RESULTS=false

SkillSim RO Configuration
-------------------------
Standard AICC Configuration plus ensure OBJECTIVES data not used:
	AICC_CORE_LESSON_FOR_RESULTS=true 

* Note regarding usage data *
When using Track to LMS mode usage data is returned immediately
to Moodle and Moodle stores this data. In this mode there is
a distiction between asset launches from different Moodle Courses.
This means that if two Moodle courses have the same SkillSoft
Asset then access from each course is tracked seperately.

================================================================
Updated April 2010
