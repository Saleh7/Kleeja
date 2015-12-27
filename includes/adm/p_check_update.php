<?php
/**
*
* @package adm
* @version $Id: p_check_update.php 1770 2011-04-30 21:29:42Z saanina $
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/

// not for directly open
if (!defined('IN_ADMIN'))
{
	exit();
}

$stylee	= "admin_check_update";
$current_smt	= isset($_GET['smt']) ? (preg_match('![a-z0-9_]!i', trim($_GET['smt'])) ? trim($_GET['smt']) : 'general') : 'general';
$error = false;
$update_link = $config['siteurl'] . 'install/update.php?lang=' . $config['language'];

#to prevent getting the url data for all cats
if($current_smt == 'general'):

//get data from kleeja database
$b_url	= empty($_SERVER['SERVER_NAME']) ? $config['siteurl'] : $_SERVER['SERVER_NAME'];
$b_data = fetch_remote_file('http://www.kleeja.com/check_vers/?i=' . urlencode($b_url) . '&v=' . KLEEJA_VERSION, false, 6);

if ($b_data === false && !isset($_GET['show_msg']))
{
	$text	= $lang['ERROR_CHECK_VER'];
	$error	= true;
}
else
{
	//
	// there is a file that we brought it !
	//
	$b_data = @explode('|', $b_data);

	$version_data = trim(htmlspecialchars($b_data[0]));

	if (version_compare(strtolower(KLEEJA_VERSION), strtolower($version_data), '<'))
	{
		$text	= sprintf($lang['UPDATE_NOW_S'] , KLEEJA_VERSION, strtolower($version_data)) . '<br /><br />' . $lang['UPDATE_KLJ_NOW'];
		$error	= true;
	}
	else if (version_compare(strtolower(KLEEJA_VERSION), strtolower($version_data), '='))
	{
		$text	= $lang['U_LAST_VER_KLJ'];
	}
	else if (version_compare(strtolower(KLEEJA_VERSION), strtolower($version_data), '>'))
	{
		$text	= $lang['U_USE_PRE_RE'];
	}

	//lets recore it
	$v = @unserialize($config['new_version']);

	//To prevent expected error [ infinit loop ]
	if(isset($_GET['show_msg']))
	{
		$query_get	= array(
							'SELECT'	=> '*',
							'FROM'		=> "{$dbprefix}config",
							'WHERE'		=> "name = 'new_version'"
						);

		$result_get =  $SQL->build($query_get);

		if(!$SQL->num_rows($result_get))
		{
			//add new config value
			add_config('new_version', '');
		}
	}

	$data	= array(
					'version_number'	=> $version_data,
					'last_check'		=> time(),
					'msg_appeared'		=> isset($_GET['show_msg']) ? true : false,
					'copyrights'		=> !empty($b_data[1]) && strpos($b_data[1], 'yes') !== false ? true : false,
				);

	$data = serialize($data);

	update_config('new_version', $SQL->real_escape($data), false);
	delete_cache('data_config');
}

//then go back  to start
if(isset($_GET['show_msg']))
{
	redirect(basename(ADMIN_PATH) . '?update_done=1');
	$SQL->close();
	exit;
}

#end current_smt == general
endif;

//secondary menu
$go_menu = array(
				'general' => array('name'=>$lang['R_CHECK_UPDATE'], 'link'=> basename(ADMIN_PATH) . '?cp=p_check_update&amp;smt=general', 'goto'=>'general', 'current'=> $current_smt == 'general'),
				'howto' => array('name'=>$lang['HOW_UPDATE_KLEEJA'], 'link'=> basename(ADMIN_PATH) . '?cp=p_check_update&amp;smt=howto', 'goto'=>'howto', 'current'=> $current_smt == 'howto'),
				'site' => array('name'=>'Kleeja.com', 'link'=> basename(ADMIN_PATH) . '?cp=p_check_update&amp;smt=site', 'goto'=>'site', 'current'=> $current_smt == 'site'),
	);
