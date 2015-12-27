<?php
/**
*
* @package adm
* @version $Id: h_search.php 1915 2012-08-29 14:10:21Z saanina $
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/


// not for directly open
if (!defined('IN_ADMIN'))
{
	exit();
}

//for style ..
$stylee = "admin_search";
//search files
$action = basename(ADMIN_PATH) . "?cp=h_search";

//wut the default user system
$default_user_system = (int) $config['user_system'] == 1 ? true : false;

$H_FORM_KEYS	= kleeja_add_form_key('adm_files_search');
$H_FORM_KEYS2	= kleeja_add_form_key('adm_users_search');

$current_smt	= isset($_GET['smt']) ? (preg_match('![a-z0-9_]!i', trim($_GET['smt'])) ? trim($_GET['smt']) : 'files') : 'files';

#filling the inputs automatically via GET
$filled_ip = $filled_username = '';
if(isset($_GET['s_input']))
{
	if((int) $_GET['s_input'] == 2)
	{
		$filled_username = htmlspecialchars($_GET['s_value']);
	}
	elseif((int) $_GET['s_input'] == 1)
	{
		$filled_ip = htmlspecialchars($_GET['s_value']);
	}
}


if (isset($_POST['search_file']))
{
	if(!kleeja_check_form_key('adm_files_search'))
	{
		kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, basename(ADMIN_PATH) . '?cp=h_search', 1);
	}
	
	#delete all searches greater than 10
	$s_del = array(
							'SELECT'	=> "filter_id",
							'FROM'		=> "{$dbprefix}filters",
							'WHERE'		=> "filter_type='file_search' AND filter_user=" . $userinfo['id'],
							'ORDER BY'	=> "filter_id DESC",
							'LIMIT'		=> '5, 18446744073709551615'
							);

	$result = $SQL->build($s_del);
	$ids = '';
	while($row=$SQL->fetch_array($result))
	{
		$ids .= ($ids != '' ? ', ' : '') . $row['filter_id'];
	}
	$SQL->free($result);

	if($ids != '')
	{
		$query_del	= array(
							'DELETE'	=> "{$dbprefix}filters",
							'WHERE'		=> "filter_id IN('" . implode("', '", $ids) . "')"
						);

		$SQL->build($query_del);
	}

	#add as a file_search filter
	$s = $_POST;

	#reduce number of array keys
	unset($s['search_file'], $s['k_form_key'], $s['k_form_time']);
	foreach ($s as $key => $v)
	{
		if ($s[$key] == '')
		{
			unset($s[$key]);
		}
	}

	$d = serialize($s);

	if(($search_id = insert_filter('file_search', $d)))
	{
		$filter = get_filter($search_id);
		redirect(basename(ADMIN_PATH) . "?cp=c_files&search_id=" . $filter['filter_uid'], false);
	}
	else
	{
		kleeja_admin_err($lang['ERROR_TRY_AGAIN'], true, $lang['ERROR'], true, basename(ADMIN_PATH) . '?cp=h_search', 1);
	}
}


if (isset($_POST['search_user']))
{
	if(!kleeja_check_form_key('adm_users_search'))
	{
		kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, basename(ADMIN_PATH) . '?cp=h_search&smt=users', 1);
	}

	#delete all searches greater than 10
	$s_del = array(
							'SELECT'	=> "filter_id",
							'FROM'		=> "{$dbprefix}filters",
							'WHERE'		=> "filter_type='user_search' AND filter_user=" . $userinfo['id'],
							'ORDER BY'	=> "filter_id DESC",
							'LIMIT'		=> '5, 18446744073709551615'
							);

	$result = $SQL->build($s_del);
	$ids = '';
	while($row=$SQL->fetch_array($result))
	{
		$ids .= ($ids != '' ? ', ' : '') . $row['filter_id'];
	}
	$SQL->free($result);

	if($ids != '')
	{
		$query_del	= array(
							'DELETE'	=> "{$dbprefix}filters",
							'WHERE'		=> "filter_id IN('" . implode("', '", $ids) . "')"
						);

		$SQL->build($query_del);
	}

	#add as a user_search filter
	$s = $_POST;
	unset($s['search_user'], $s['k_form_key'], $s['k_form_time']);
	$d = serialize($s);
	if(($search_id = insert_filter('user_search', $d)))
	{
		$filter = get_filter($search_id);
		redirect(basename(ADMIN_PATH) . "?cp=g_users&smt=show_su&search_id=" . $filter['filter_uid'], false);
	}
	else
	{
		kleeja_admin_err($lang['ERROR_TRY_AGAIN'], true, $lang['ERROR'], true, basename(ADMIN_PATH) . '?cp=h_search&smt=users', 1);
	}
}

//secondary menu
$go_menu = array(
				'files' => array('name'=>$lang['R_SEARCH'], 'link'=> basename(ADMIN_PATH) . '?cp=h_search&amp;smt=files', 'goto'=>'files', 'current'=> $current_smt == 'files'),
				#'sep1' => array('class'=>'separator'),
				'users' => array('name'=>$lang['SEARCH_USERS'], 'link'=> basename(ADMIN_PATH) . '?cp=h_search&amp;smt=users', 'goto'=>'users', 'current'=> $current_smt == 'users'),
				#'sep2' => array('class'=>'separator'),
	);
	
if(!$default_user_system)
{
	unset($go_menu['users']);
}
