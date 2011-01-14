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
 * Loads the AU
 *
 * @package   mod-skillsoft
 * @author    Martin Holden
 * @copyright 2009 Martin Holden
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('locallib.php');

$a = required_param('a', PARAM_INT); //activity id

$target = $CFG->wwwroot.'/mod/skillsoft/sso.php?a='.$a;
$skillsoftpixdir = $CFG->modpixpath.'/skillsoft/pix';
?>
<html>
<head>
<title><?php echo get_string('skillsoft_ssotitle', 'skillsoft');?></title>
<script type="text/javascript">
	//<![CDATA[
        function doredirect() {
                document.body.innerHTML = "<p><?php echo get_string('skillsoft_ssoloading', 'skillsoft');?>&nbsp;<img src='<?php echo $skillsoftpixdir;?>/wait.gif'><p>";
                document.location = "<?php echo $target ?>";
        }
      //]]>
        </script>
</head>
<body onload="doredirect();">
<p><?php echo get_string('skillsoft_ssoloading', 'skillsoft');?></p>
</body>
</html>
