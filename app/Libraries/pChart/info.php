<?php

/**
 * pChart
 *
 * @author Jean-Damien Pogolotti
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @link http://www.pchart.net/
 * @link http://phpmanufaktur.de
 * @copyright 2011 - 2012
 * @license http://www.pchart.net/license
 */

// include class.secure.php to protect this file and the whole CMS!
if (defined('WB_PATH')) {
  if (defined('LEPTON_VERSION'))
    include(WB_PATH.'/framework/class.secure.php');
}
else {
  $oneback = "../";
  $root = $oneback;
  $level = 1;
  while (($level < 10) && (!file_exists($root.'/framework/class.secure.php'))) {
    $root .= $oneback;
    $level += 1;
  }
  if (file_exists($root.'/framework/class.secure.php')) {
    include($root.'/framework/class.secure.php');
  }
  else {
    trigger_error(sprintf("[ <b>%s</b> ] Can't include class.secure.php!", $_SERVER['SCRIPT_NAME']), E_USER_ERROR);
  }
}
// end include class.secure.php

$module_directory     = 'pchart';
$module_name          = 'pChart';
$module_function      = (defined('LEPTON_VERSION')) ? 'library' : 'snippet';
$module_version       = '2.1.3';
$module_status        = 'stable';
$module_platform      = '2.8';
$module_author        = 'Jean-Damien Pogolotti, Ralf Hertsch (WebsiteBaker Integration)';
$module_license       = 'GNU General Public License';
$module_description   = 'pChart - a PHP Charting library';
$module_home          = 'http://www.pchart.net/';
$module_guid          = 'D34EAD8A-BDF5-4370-80C9-E7D0C17B4EB0';
