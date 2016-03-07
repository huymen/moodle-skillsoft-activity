
# Introduction #
This Module is only supported on Moodle 1.9.x series, for a version for Moodle 2.x visit:
http://code.google.com/p/moodle2-skillsoft-activity

The installation process follows the standard Moodle module install process.

It is a **mandatory** requirement that PHP SOAP is enabled see http://www.php.net/manual/en/book.soap.php

Please be sure to read the [README.txt](http://code.google.com/p/moodle-skillsoft-activity/source/browse/trunk/skillsoft/README.txt) contained in the download

Once installed and configured a simple diagnostics page introduced in v1.5.2 allows you to test OLSA connectivity see [olsadiag](olsadiag.md)

**IMPORTANT: Only version 2011073100 or later supports the situation where the Moodle Server can only access the internet via a Proxy Server.**

# Using the ZIP file #
  1. Download the ZIP file
  1. Extract all the files, ensuring folders are created, to the MOODLEHOME/mod/. You should end up with a skillsoft folder.
  1. Login to Moodle as administrator
  1. Click "Notifications" in the left hand menu, thsi will trigger the database table creation. Once this is completed you will be taken to the [configuration](configuration.md) screen


