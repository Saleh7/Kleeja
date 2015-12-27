<?php
/**
*
* @package adm
* @version $Id: n_extra.php 1768 2011-04-28 13:11:09Z saanina $
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
$stylee		= "admin_extra";
$current_smt	= isset($_GET['smt']) ? (preg_match('![a-z0-9_]!i', trim($_GET['smt'])) ? trim($_GET['smt']) : 'he') : 'he';
$action		= basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;smt=' . $current_smt;
$H_FORM_KEYS	= kleeja_add_form_key('adm_extra');

//
// Check form key
//
if (isset($_POST['submit']))
{
	if(!kleeja_check_form_key('adm_extra'))
	{
		kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, $action, 1);
	}
}

$query	= array(
				'SELECT'	=> 'ex_header,ex_footer',
				'FROM'		=> "{$dbprefix}stats"
			);

$result = $SQL->build($query);
		
//is there any change !
$affected = false;

while($row=$SQL->fetch_array($result))
{
	$ex_header = isset($_POST['ex_header']) ? $_POST['ex_header'] : $row['ex_header'];
	$ex_footer = isset($_POST['ex_footer']) ? $_POST['ex_footer'] : $row['ex_footer'];


	//when submit !!
	if (isset($_POST['submit']))
	{
		$ex_header = htmlspecialchars_decode($ex_header);
		$ex_footer = htmlspecialchars_decode($ex_footer);

		//update
		$update_query	= array(
								'UPDATE'	=> "{$dbprefix}stats",
								'SET'		=> "ex_header = '" . $SQL->real_escape($ex_header) . "', ex_footer = '" . $SQL->real_escape($ex_footer) . "'"
							);

		$SQL->build($update_query);

		if($SQL->affected())
		{
			$affected = true;
			//delete cache ..
			delete_cache('data_extra');
		}
	}
	else
	{
		$ex_header = htmlspecialchars($ex_header);
		$ex_footer = htmlspecialchars($ex_footer);
	}
}

$SQL->freeresult($result);


//after submit 
if (isset($_POST['submit']))
{
	kleeja_admin_info(($affected ? $lang['EXTRA_UPDATED'] : $lang['NO_UP_CHANGE_S']), true, '', true,  $action);
}


//secondary menu
$go_menu = array(
				'he' => array('name'=>$lang['ADD_HEADER_EXTRA'], 'link'=> basename(ADMIN_PATH) . '?cp=n_extra&amp;smt=he', 'goto'=>'he', 'current'=> $current_smt == 'he'),
				'fe' => array('name'=>$lang['ADD_FOOTER_EXTRA'], 'link'=> basename(ADMIN_PATH) . '?cp=n_extra&amp;smt=fe', 'goto'=>'fe', 'current'=> $current_smt == 'fe'),
	);
