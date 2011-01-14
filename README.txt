********* DEVELOPMENT BRANCH ***********
14-JAN-2011
This code enables an administrator to choose to integrate Moodle
with SkillPort using OLSA Web Services Functions to perform the
"launch" of the content.

This has the added advantage that in the file sso.php the developer
can extend the code performing the SSO command to include additional
profile data about a user not available when using the AICC method.

See http://code.google.com/p/moodle-skillsoft-activity/wiki/UserIdenticationDetails

The above link explains the limitation of the AICC method.

In addition the code has been extended to allow a "prefix" to be
appended to the user identifier sent by Moodle to SkillPort (AICC
and Web Service methods). This is of great benefit when Moodle
may be only one of many systems inetgrated with the SkillPort site.

The code for extracting the usage data has been extended to support
the prefix and a new function in locallib.php - skilsoft_getusername_from_loginname
performs the task of converting the username returned by SkillPort
into the Moodle USERID.

The code also now supports the use of the OLSA Custom Report method
for retrieving usage data. This method unlike ODC is generally used to
get "bulk" report data in CSV format.

KNOWN ISSUES:
The custom report process works in the following way when triggered by the
CRON job:

1. Submit a report request to SkillPort using StartDate ($cfg skillsoft_reportstartdate)
 or 1-JAN-2000 if this was blank and EndDate of today -1 day (basically all data from
 startdate up to and including yesterdays data).

2. Report request returns a "handle" we store

3. Go in to a loop, where we "poll" server for the handle. If the report is not yet
ready we sleep for 60 seconds, then poll again.

4. If successful we retrieve a URL to the report on the Skillport server which
we then download and save locally in moodledata\temp\reports

5. Load the CSV and import into database, and on success delete downloaded copy.

6. Uppdate the $CFG value of skillsoft_reportstartdate with the "end date" from
this report.

7. Process the imported data to add users progress to gradebook etc

The issue is that the report, for larger user populations, can take 1hr+ to
generate, and thus PHP may time out whilst running cron.php.

A better approach would be to utilise the data stored in the table
skillsoft_report_track whihc is updated during each step above and when teh cron
job runs in Moodle take next appropriate step rather than wait in our code for report
to be ready.

So the process woudl be something like:

Is there a report already submitted (skillsoft_report_track) - look for a record
where URL = "" (so we haven't got it from SkillPort during a poll).
If no report waiting submit report request, End Custom Report CRON job.


If yes, poll for the report using the handle in the table.
If "report not ready", end Custom Report CRON job.
If any other error reported by OLSA, delete this "handle" in skillsoft_report_track and
resubmit report. End custom report CRON job.
If URL returned, download and process report and update the skillsoft_reportstartdate.


This way the Custom Report CRON job would not hold up the CRON.PHP page for a long period
whilst it polls.

********* DEVELOPMENT BRANCH ***********

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

Seamless Login to SkillPort - NOV2010
-------------------------------------
When using Track to OLSA there is a new special assetid 'SSO'
this assetid when used will create a new activity that allows
the user to be seamlessly logged into the SkillPort platform.

The seamless login will create a user in SkillPort if they
do not exist and set the SkillPort username based on setting
above to be either internal Moodle unique id or the Moodle 
username.

The SkillPort user account for, new user and existing SkillPort
users will be updated to with the Moodle users first name, last
name and email.

For new users the SkillPort group membership is controlled by
the skillsoft_defaultssogroup setting in Moodle. Any users that
need to be created in SkillPort will automatically be members of
the groups defined here.

Existing users group membership will be unchanged.


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
Updated November 2010 (Module Version: 2010112400)
