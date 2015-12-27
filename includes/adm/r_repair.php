<?php
/**
*
* @package adm
* @version $Id: r_repair.php 2103 2012-11-16 14:52:48Z saanina $
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/


// not for directly open
if (!defined('IN_ADMIN'))
{
	exit();
}


#turn time-limit off
@set_time_limit(0);

#get current case
$case = false;
if(isset($_GET['case']))
{
	$case = htmlspecialchars($_GET['case']);
}


#set form ket
$GET_FORM_KEY = kleeja_add_form_key_get('REPAIR_FORM_KEY');


//check _GET Csrf token
if($case && in_array($case, array('clearc', 'sync_files', 'sync_images', 'sync_users', 'tables', 'sync_sizes', 'status_file')))
{
	if(!kleeja_check_form_key_get('REPAIR_FORM_KEY'))
	{
		kleeja_admin_err($lang['INVALID_GET_KEY'], true, $lang['ERROR'], true, basename(ADMIN_PATH), 2);
	}
}

switch($case):

default:

# Get real number from database right now
$all_files = get_actual_stats('files');
$all_images = get_actual_stats('imgs');
$all_users = get_actual_stats('users');
$all_sizes = Customfile_size(get_actual_stats('sizes'));


#links
$del_cache_link		= basename(ADMIN_PATH) . '?cp=r_repair&amp;case=clearc&amp;' . $GET_FORM_KEY;
$resync_files_link	= $config['siteurl'] . 'go.php?go=resync&amp;case=sync_files';
$resync_images_link	= $config['siteurl'] . 'go.php?go=resync&amp;case=sync_images';
$resync_users_link	= basename(ADMIN_PATH) . '?cp=r_repair&amp;case=sync_users&amp;' . $GET_FORM_KEY;
$resync_sizes_link	= basename(ADMIN_PATH) . '?cp=r_repair&amp;case=sync_sizes&amp;' . $GET_FORM_KEY;
$repair_tables_link	= basename(ADMIN_PATH) . '?cp=r_repair&amp;case=tables&amp;' . $GET_FORM_KEY;
$status_file_link	= basename(ADMIN_PATH) . '?cp=r_repair&amp;case=status_file&amp;' . $GET_FORM_KEY;



$stylee = "admin_repair";

break;


// We, I mean developrts and support team anywhere, need sometime
// some inforamtion about the status of Kleeja .. this will give 
// a zip file contain those data ..
case 'status_file':

if(isset($_GET['_ajax_']))
{
	exit('Ajax is forbidden here !');
}

include PATH . 'includes/plugins.php';
$zip = new zipfile();
	
#kleeja version
$zip->create_file(KLEEJA_VERSION, 'kleeja_version.txt');

#grab configs
$d_config = $config;
unset($d_config['h_key'], $d_config['ftp_info']);
$zip->create_file(var_export($d_config, true), 'config_vars.txt');
unset($d_config);

#php info
ob_start();
@phpinfo();
$phpinfo = ob_get_contents();
ob_end_clean();
$zip->create_file($phpinfo, 'phpinfo.html');
unset($phpinfo);

#config file data
$config_file_data = file_get_contents(PATH . 'config.php');
$cvars = array('dbuser', 'dbpass', 'dbname', 'script_user', 'script_pass', 'script_db');
$config_file_data = preg_replace('!\$(' . implode('|', $cvars). ')(\s*)=(\s*)["|\']([^\'"]+)["|\']!', '$\\1\\2=\\3"******"', $config_file_data);
$zip->create_file($config_file_data, 'config.php');
unset($config_file_data);

#kleeja log
if(file_exists(PATH . 'cache/kleeja_log.log') && defined('DEV_STAGE'))
{
	$zip->create_file(file_get_contents(PATH . 'cache/kleeja_log.log'), 'kleeja_log.log');
}
	
#Groups info
$zip->create_file(var_export($d_groups, true), 'groups.txt');

#eval test, Im not so sure about this test, must be
#tried at real situation.
$t = 'OFF';
@eval('$t = "ON";');
$zip->create_file($t, 'evalTest.txt');

#plugins info
$zip->create_file(var_export($all_plg_hooks, true), 'hooks_info.txt');
$zip->create_file(var_export($all_plg_plugins, true), 'plugins_info.txt');

#ban info
$zip->create_file(var_export($banss, true), 'ban_info.txt');

#stats
$stat_vars = array('stat_files', 'stat_imgs', 'stat_sizes', 'stat_users', 'stat_last_file', 'stat_last_f_del',
				'stat_last_google', 'stat_last_bing', 'stat_google_num', 'stat_bing_num', 'stat_last_user');
$zip->create_file(var_export(compact($stat_vars), true), 'stats.txt');
unset($stat_vars);

#push it
header('Content-Type: application/zip');
header('X-Download-Options: noopen');
header('Content-Disposition: attachment; filename="KleejaDataForSupport' .  date('dmY'). '.zip"');
echo $zip->zipped_file();
$SQL->close();
exit;

break;


//
//fix tables ..
//
case 'tables':

$query	= "SHOW TABLE STATUS";
$result	= $SQL->query($query);
$text = '';
	
while($row=$SQL->fetch_array($result))
{
	$queryf	=	"REPAIR TABLE `" . $row['Name'] . "`";
	$resultf = $SQL->query($queryf);
	if ($resultf)
	{
		$text .= '<li>' . $lang['REPAIRE_TABLE'] . $row['Name'] . '</li>';
	}
}
	
$SQL->freeresult($result);

$text .= '<script type="text/javascript"> setTimeout("get_kleeja_link(\'' . basename(ADMIN_PATH) . '?cp=r_repair' .  '\');", 2000);</script>' . "\n";
$stylee = 'admin_info';


break;

//
//re-sync sizes ..
//
case 'sync_sizes':


$query_s	= array(
					'SELECT'	=> 'size',
					'FROM'		=> "{$dbprefix}files"
				);

$result_s = $SQL->build($query_s);

$files_number = $files_sizes = 0;

while($row=$SQL->fetch_array($result_s))
{
	$files_number++;
	$files_sizes = $files_sizes+$row['size'];
}

$SQL->freeresult($result_s);

$update_query	= array(
						'UPDATE'	=> "{$dbprefix}stats",
						'SET'		=> "files=" . $files_number . ", sizes=" . $files_sizes
					);

if ($SQL->build($update_query))
{
	$text .= '<li>' . $lang['REPAIRE_F_STAT'] . '</li>';
}

delete_cache('data_stats');

$stylee = 'admin_info';

break;


//
//re-sync total users number ..
//
case 'sync_users':

$query_w	= array(
					'SELECT'	=> 'name',
					'FROM'		=> "{$dbprefix}users"
				);

$result_w = $SQL->build($query_w);
		
$user_number = 0;
while($row=$SQL->fetch_array($result_w))
{
	$user_number++;
}
	
$SQL->freeresult($result_w);

$update_query	= array(
						'UPDATE'	=> "{$dbprefix}stats",
						'SET'		=> "users=" . $user_number
					);

$result = $SQL->build($update_query);

delete_cache('data_stats');
$text = sprintf($lang['SYNCING'], $lang['USERS_ST']);
$text .= '<script type="text/javascript"> setTimeout("get_kleeja_link(\'' . basename(ADMIN_PATH) . '?cp=r_repair' .  '\');", 2000);</script>' . "\n";

$stylee = 'admin_info';


break;


//
//clear all cache ..
//
case 'clearc':

#clear cache
delete_cache('', true);

#show done, msg
$text .= '<li>' . $lang['REPAIRE_CACHE'] . '</li>';
$text .= '<script type="text/javascript"> setTimeout("get_kleeja_link(\'' . basename(ADMIN_PATH) . '?cp=r_repair' .  '\');", 2000);</script>' . "\n";

$stylee = 'admin_info';

break;

endswitch;




