<?php
/**
*
* @package adm
* @version $Id: a_configs.php 2106 2012-11-18 12:49:57Z saanina $
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
$stylee 		= "admin_configs";
$current_smt	= isset($_GET['smt']) ? (preg_match('![a-z0-9_]!i', trim($_GET['smt'])) ? trim($_GET['smt']) : 'general') : 'general';
//words
$action 		= basename(ADMIN_PATH) . '?cp=options&amp;smt=' . $current_smt;
$n_submit 		= $lang['UPDATE_CONFIG'];
$options		= '';
#$current_type	= isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'general';
$CONFIGEXTEND	= false;
$H_FORM_KEYS	= kleeja_add_form_key('adm_configs');

//secondary menu
$query	= array(
				'SELECT'	=> 'DISTINCT(type)',
				'FROM'		=> "{$dbprefix}config c",
				'WHERE'		=> "c.option <> '' AND c.type <> 'groups'",
				'ORDER BY'	=> 'display_order'
			);

$result = $SQL->build($query);

while($row = $SQL->fetch_array($result))
{
	$name = !empty($lang['CONFIG_KLJ_MENUS_' . strtoupper($row['type'])]) ? $lang['CONFIG_KLJ_MENUS_' . strtoupper($row['type'])] : (!empty($olang['CONFIG_KLJ_MENUS_' . strtoupper($row['type'])]) ? $olang['CONFIG_KLJ_MENUS_' . strtoupper($row['type'])] : $lang['CONFIG_KLJ_MENUS_OTHER']);
	$go_menu[$row['type']] = array('name'=>$name, 'link'=>$action . '&amp;smt=' . $row['type'], 'goto'=>$row['type'], 'current'=> $current_smt == $row['type']);
}

$go_menu['all'] = array('name'=>$lang['CONFIG_KLJ_MENUS_ALL'], 'link'=>$action . '&amp;smt=all', 'goto'=>'all', 'current'=> $current_smt == 'all');

//
// Check form key
//
if (isset($_POST['submit']))
{
	if(!kleeja_check_form_key('adm_configs'))
	{
		kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, $action, 1);
	}
}



//general varaibles
#$action		= basename(ADMIN_PATH) . '?cp=options&amp;type=' .$current_type;
$STAMP_IMG_URL = file_exists(PATH . 'images/watermark.gif') ? PATH . 'images/watermark.gif' : PATH . 'images/watermark.png';
$stylfiles	= $lngfiles	= $authtypes =  $time_zones = '';
$optionss	= array();
$n_googleanalytics = '<a href="http://www.google.com/analytics">Google Analytics</a>';

$query	= array(
					'SELECT'	=> '*',
					'FROM'		=> "{$dbprefix}config",
					'ORDER BY'	=> 'display_order, type ASC'
			);

$CONFIGEXTEND	  = $SQL->escape($current_smt);
$CONFIGEXTENDLANG = $go_menu[$current_smt]['name'];
		
if($current_smt != 'all')
{
	$query['WHERE'] = "type = '" . $SQL->escape($current_smt) . "' OR type = ''";
}
else if($current_smt == 'all')
{
	$query['WHERE'] = "type <> 'groups' OR type = ''";
}

$result = $SQL->build($query);

$thumbs_are = get_config('thmb_dims');

while($row=$SQL->fetch_array($result))
{
	#make new lovely array !!
	$con[$row['name']] = $row['value'];

	if($row['name'] == 'thumbs_imgs') 
	{
		list($thmb_dim_w, $thmb_dim_h) = array_map('trim', @explode('*', $thumbs_are));
	}
	else if($row['name'] == 'time_zone') 
	{
		$zones = time_zones();
		foreach($zones as $z=>$t)
		{
			$time_zones .= '<option ' . ($con['time_zone'] == $t ? 'selected="selected"' : '') . ' value="' . $t . '">' . $z . '</option>' . "\n";
		}
	}
	else if($row['name'] == 'language') 
	{
		//get languages
		if ($dh = @opendir(PATH . 'lang'))
		{
			while (($file = readdir($dh)) !== false)
			{
				if(strpos($file, '.') === false && $file != '..' && $file != '.')
				{
					$lngfiles .= '<option ' . ($con['language'] == $file ? 'selected="selected"' : '') . ' value="' . $file . '">' . $file . '</option>' . "\n";
				}
			}
			@closedir($dh);
		}
	}
	else if($row['name'] == 'user_system') 
	{
		//get auth types
		//fix previus choice in old kleeja
		if(in_array($con['user_system'], array('2', '3', '4')))
		{
			$con['user_system'] = str_replace(array('2', '3', '4'), array('phpbb', 'vb', 'mysmartbb'), $con['user_system']);
		}

		$authtypes .= '<option value="1"' . ($con['user_system']=='1' ? ' selected="selected"' : '') . '>' . $lang['NORMAL'] . '</option>' . "\n";
		if ($dh = @opendir(PATH . 'includes/auth_integration'))
		{
			while (($file = readdir($dh)) !== false)
			{
				if(strpos($file, '.php') !== false)
				{
					$file = trim(str_replace('.php', '', $file));
					$authtypes .= '<option value="' . $file . '"' . ($con['user_system'] == $file ? ' selected="selected"' : '') . '>' . $file . '</option>' . "\n";
				}
			}
			@closedir($dh);
		}
	}

	($hook = kleeja_run_hook('while_fetch_adm_config')) ? eval($hook) : null; //run hook
				
	//options from database [UNDER TEST]
	if(!empty($row['option'])) 
	{
		$optionss[$row['name']] = array(
				'option'		 => '<div class="section">' . "\n" .  
									'<h3><label for="' . $row['name'] . '">' . (!empty($lang[strtoupper($row['name'])]) ? $lang[strtoupper($row['name'])] : $olang[strtoupper($row['name'])]) . '</label></h3>' . "\n" .
									'<div class="box">' . (empty($row['option']) ? '' : $tpl->admindisplayoption($row['option'])) . '</div>' . "\n" .
									'</div>' . "\n" . '<div class="clear"></div>',
				'type'			=> $row['type'],
				'display_order' => $row['display_order'],
			);
	}
			
	//when submit
	if (isset($_POST['submit']))
	{
		//-->
		$new[$row['name']] = (isset($_POST[$row['name']])) ? $_POST[$row['name']] : $con[$row['name']];

		//save them as you want ..
		if($row['name'] == 'thumbs_imgs')
		{
			if(intval($_POST['thmb_dim_w']) < 10)
			{
				$_POST['thmb_dim_w'] = 10;
			}

			if(intval($_POST['thmb_dim_h']) < 10)
			{
				$_POST['thmb_dim_h'] = 10;
			}

			$thumbs_were = trim($_POST['thmb_dim_w']) . '*' . trim($_POST['thmb_dim_h']);
			update_config('thmb_dims', $thumbs_were);
		}
		else if($row['name'] == 'livexts')
		{
			$new['livexts'] = implode(',', array_map('trim', explode(',', $_POST['livexts'])));
		}
		else if($row['name'] == 'prefixname')
		{
			$new['prefixname'] = preg_replace('/[^a-z0-9_\-\}\{\:\.]/', '', strtolower($_POST['prefixname']));
		}
		else if($row['name'] == 'siteurl')
		{
			if($_POST['siteurl'][strlen($_POST['siteurl'])-1] != '/')
			{
				$new['siteurl'] .= '/';
			}
			
			if($config['siteurl'] != $new['siteurl'])
			{
				#when site url changed, cookies will be currptued !
				//update_config('cookie_path', '');
				unset($_GET['_ajax_']);
			}
		}

		($hook = kleeja_run_hook('after_submit_adm_config')) ? eval($hook) : null; //run hook

		$update_query = array(
								'UPDATE'	=> "{$dbprefix}config",
								'SET'		=> "value='" . $SQL->escape($new[$row['name']]) . "'",
								'WHERE'		=> "name='" . $row['name'] . "'"
							);

		if($current_smt != 'all')
		{
			$query['WHERE'] .= " AND type = '" . $SQL->escape($current_smt) . "'";
		}

		$SQL->build($update_query);
	}
}

$SQL->freeresult($result);
$types = array();

foreach($optionss as $key => $option)
{
	if(empty($types[$option['type']]))
	{ 
		$types[$option['type']] = '<div class="tit_con"><h1>' . $go_menu[$option['type']]['name'] . '</h1></div>';
	}
}

foreach($types as $typekey => $type)
{
	$options .= $type;
	foreach($optionss as $key => $option)
	{
		if($option['type'] == $typekey)
		{
			$options .= $option['option'];
		}
	}
	$options .= '<div class="br"></div>';
}

//after submit
if (isset($_POST['submit']))
{
	($hook = kleeja_run_hook('after_submit_adm_config')) ? eval($hook) : null; //run hook

	//empty ..
	/*
	if (empty($_POST['sitename']) || empty($_POST['siteurl']) || empty($_POST['foldername']) || empty($_POST['filesnum']))
	{
		$text	= $lang['EMPTY_FIELDS'];
		$stylee	= "admin_err";
	}
	elseif (!is_numeric($_POST['filesnum']) || !is_numeric($_POST['sec_down']))
	{
		$text	= $lang['NUMFIELD_S'];
		$stylee	= "admin_err";
	}
	else
	{
	*/

	#delete cache ..
	delete_cache('data_config');

	#some configs need refresh page ..
	$need_refresh_configs = array('language');
	foreach($need_refresh_configs as $l)
	{
		if(isset($_POST[$l]) and $_POST[$l] != $config[$l])
		{
			header('Location: ' . basename(ADMIN_PATH));
			exit();
		}
	}

	kleeja_admin_info($lang['CONFIGS_UPDATED'], true, '', true,  basename(ADMIN_PATH) . '?cp=options', 3);
	//}
}#submit
