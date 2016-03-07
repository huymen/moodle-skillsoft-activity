
# Introduction #

The configuration options for the module allow you to enter the details of OLSA Server your organisation is using, choose how often old sessionids are purged from the database and configure the tracking mode for the courses.

![http://sites.google.com/site/moodleskillsoftactivity/images/Moodle19ConfigSettings.png](http://sites.google.com/site/moodleskillsoftactivity/images/Moodle19ConfigSettings.png)

# Settings #
## SkillSoft OLSA EndPoint ##
This is URL to the Web Services and is unique for each customer. This value is supplied by SkillSoft when you have licensed OLSA.

## SkillSoft OLSA Customer ID ##
This is OLSA Customer ID and is unique for each customer. This value is supplied by SkillSoft when you have licensed OLSA.

## SkillSoft OLSA Shared Secret ##
This is OLSA Shared Secret and is unique for each customer. This value is supplied by SkillSoft when you have licensed OLSA.

## Number of hours to keep sessionid ##
Every time a user launches an asset a new unique sessionid is created for session tracking purposes, this value determines how often the completed sessions are purged from the database. The purpose is to keep the database table size as small as possible.

## SkillSoft Tracking Mode ##
This determines where asset usage data is returned. With "Track to LMS" the aicc usage data for an asset, such as score, time spent is immediately sent to Moodle.

With "Track to OLSA" mode the data is initially stored in SkillSofts servers, making advanced functionality such as download support available, and then the data is automatically synchronised back to Moodle.

There are two options, using the SkillSofts OnDemandCommunications mode or using a Custom Report.

## Moodle/SkillSoft User Identifier ##
This option determines which value from Moodle is used for the OLSA Username, this is critical when "Track to OLSA" is used to allow synchronisation of data.

You can choose between using the Moodle generated unique user id `user->id` or the Moodle Username `user->username`.

There are benefits to using the unique Moodle generated user id, especially when using "Track To OLSA" mode. This is because this value remians the same even if the users username in Moodle is changed, and thus ensures only one account exists in the OLSA server. If we use the users username, and it is changed a new account would be created in OLSA for this new username resulting in two accounts on the OLSA server.

The benefit of using the username, is that it makes it easier to use "Track to OLSA" mode when potentially user could be accessing the OLSA server via multiple points of entry for example SkillSofts SkillPort LMS, via additional portal integrations etc. So long as the "username" in all these systems is the same there will be a single record for the user in OLSA and a thus one consistent usage record. This means that if a user accesses an Asset in Moodle and say completes 50%, if they then access from SkillPort using same username the asset will show that 50% completion and if they then complete it in SkillPort that completion will be synched to Moodle.

Read [UserIdenticationDetails](UserIdenticationDetails.md) to understand how the id is retrieved from Moodle

**IMPORTANT: If the Moodle username is used, the username must also be  valid as an AICC CMIIdentifier value as defined by the AICC guidlines. Which means the only valid characters outside of A-Z (case insensitive) and 0-9, are a dash `-` (or hyphen) and the underscore `_` character. This means an email address is invalid as it contains the @ symbol and periods (.)**

**This restriction can be disabled using the [configuration#Enforce\_strict\_AICC\_student\_id\_format](configuration#Enforce_strict_AICC_student_id_format.md) setting**

## SkillSoft Default Group List ##
This option configures the default groupcodes used when utilising the new seamless login to SkillPort option.

When using Track to OLSA there is a new special assetid 'SSO' this assetid when used will create a new activity that allows the user to be seamlessly logged into the SkillPort platform.

The seamless login will create a user in SkillPort if they do not exist and set the SkillPort username based on the Moodle/SkillSoft User Identifier setting above.

The SkillPort user account for new user and existing SkillPort users will be updated to with the Moodle users first name, last name and email.

For new users the SkillPort group membership is controlled by this setting in Moodle. Any users that needs to be created in SkillPort will automatically be members of the groups defined here.

Existing users group membership will be unchanged.

## Account Prefix ##
The value entered here is append to the front of the Moodle/SkillSoft User Identifier, both for any launch type.

So for example if this is set to `CUSTOMERX_` and the Moodle Username is the identifier, for user martin the SkillPort username would be `CUSTOMERX_martin`.

## Use OLSA SSO ##
This setting determines whether content is launched using AICC mode or whether all launches use the Single SignOn URL defined below. In most instances it is recommended to use the AICC launch method but in some instances SSO may be preferable, this is because with SSO Moodle could be configured to point at perhaps an existing SSO process to SkillPort in place in the business.

## Single SignOn Url ##
This is the Url that the user is directed to when Use OLSA SSO mode is used. The page the Url directs to is responsible for determining username to send to SkillSoft.

This feature is especially useful if the customer has already implemented SSO to SkillPort in their environment, as they can reuse this existing functionality.

## Select the OLSA Action Type ##
When using SSO mode the administrator can choose to "launch" the asset without displaying the SkillPort UI or log the user into SkillPort UI on the "Summary Page" for the asset.

The end result for each action type is dependant on certain settings in SkillPort, see [actiontyperesults](actiontyperesults.md)

## Custom Report Start Date ##
When using the "Track to OLSA - Custom Report" mode this value is used to set the start date for the report range. The end date is always the previous day unless Custom Report Include Todays Data is selected.

If left blank ALL usage data from SkillSoft is retrieved, this is particularly useful for importing historical data into Moodle for usage already generated in SkillPort.

## Custom Report Include Todays Data ##
When not selected the custom report will retrieve data upto and including the previous day. This option makes the report retrieve data including todays data.

With this set the custom report method can be called multiple times during the same day and new usage is continually retrieved.

## Clear the Cached WSDL Files ##
By default the WSDL files for the OLSA Web Services will be cached in a temporary folder on the Moodle server. This allows the administrator to remove these temporary files and force a refresh.

## Disable the usage data task ##
This allows the the Custom Report or OnDemand Communications feature to be disable in the CRON task.

## Reset the custom report cycle ##
This allows the administrator to reset the Custom Report CRON job incase of "lock up"

## Enforce strict AICC student\_id format ##
By default the module uses AICC HACP 2.2 for communications between the courses and Moodle.

The AICC 2.2 standard defines that the student\_id can only contain characters in A-Za-z0-9\-_:._

This means that if the Moodle/SkillSoft User Identifier is set to Username and the Username is an email for example, the AICC code will throw a fatal Exception as an email is not a valid AICC 2.2 student\_id.

By disabling this feature you can remove the check on teh valid characters.