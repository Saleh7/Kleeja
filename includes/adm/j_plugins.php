<?php
/**
*
* @package adm
* @version $Id: j_plugins.php 1999 2012-09-17 20:11:21Z saanina $
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/

// not for directly open
if (!defined('IN_ADMIN'))
{
	exit();
}



//show icon of plugin
if(isset($_GET['iconp'])):

//is there any changes from recent installing plugins
$icon = false;
if (($plgicons = $cache->get('__plugins_icons__')))
{
	if(!empty($plgicons[$_GET['iconp']]))
	{
		$icon = base64_decode($plgicons[$_GET['iconp']]);
	}
}

if(!$icon)
{
	$icon = file_get_contents($STYLE_PATH_ADMIN . 'images/default_plguin_icon.png');
}

header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Accept-Ranges: bytes');
header('Content-Length: ' . strlen($icon));
header('Content-Type: image/png');
echo $icon;
$SQL->close();
exit;

endif;


#security vars
$H_FORM_KEYS	= kleeja_add_form_key('adm_plugins');
$GET_FORM_KEY	= kleeja_add_form_key_get('adm_plugins');


#initiate plugins class
include PATH . 'includes/plugins.php';
$plg = new kplugins();

//return values of ftp from config, if not get suggested one 
$ftp_info = array('host', 'user', 'pass', 'path', 'port');

if(!empty($config['ftp_info']))
{
	$ftp_info = @unserialize($config['ftp_info']);
}
else
{
	//todo : make sure to figure this from OS, and some other things
	$ftp_info['path'] = str_replace('/includes/adm', '', dirname(__file__)) . '/';
	#mose 
	if(strpos($ftp_info['path'], 'public_html') !== false)
	{
		$ftppath_parts = explode('public_html', $ftp_info['path']);
		$ftp_info['path'] = '/public_html'. $ftppath_parts[1];
	}
	else
	{
		$ftp_info['path'] = '/public_html/';
	}

	$ftp_info['port'] = 21;
	$ftp_info['host'] = str_replace(array('http://', 'https://'), array('', ''), $config['siteurl']);

	#ie. up.anmmy.com, www.anmmy.com
	if(sizeof(explode('.', $ftp_info['host'])) > 1 || (sizeof(explode('.', $ftp_info['host'])) == 2 && strpos($ftp_info['host'], 'www.') === false))
	{
		$siteurl_parts = explode('.', $ftp_info['host']);
		$ftp_info['host'] = 'ftp.' . $siteurl_parts[sizeof($siteurl_parts)-2] . '.' . $siteurl_parts[sizeof($siteurl_parts)-1];
	}

	$ftp_info['host'] = str_replace('www.', 'ftp.', $ftp_info['host']);

	if(strpos($ftp_info['host'], '/') !== false)
	{
		$siteurl_parts = explode('/', $ftp_info['host']);
		$ftp_info['host'] = $siteurl_parts[0];
	}
}

$is_ftp_supported = $plg->is_ftp_supported;


//clean changes files
if(isset($_GET['cc'])):

if ($dh = @opendir(PATH . 'cache'))
{
	while (($file = @readdir($dh)) !== false)
	{

		if(preg_match('!changes_of_[a-z0-9]+.zip!', $file))
		{
			kleeja_unlink(PATH . 'cache/' . $file);
		}
	}
	@closedir($dh);
}

$cache->clean('__changes_files__');

//redirect(basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php'));

//show first page of plugins
elseif (!isset($_GET['do_plg'])):

//for style ..
$stylee		= "admin_plugins";
$current_smt= isset($_GET['smt']) ? (preg_match('![a-z0-9_]!i', trim($_GET['smt'])) ? trim($_GET['smt']) : 'general') : 'general';
$action		= basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;smt=' . $current_smt;
$no_plugins	= false;

//kleeja depend on its users .. and kleeja love them .. so let's tell them about that ..
$klj_d_s = $lang['KLJ_MORE_PLUGINS'][rand(0, sizeof($lang['KLJ_MORE_PLUGINS'])-1)];


//
// Check form key
//
if (isset($_POST['submit_new_plg']))
{
	if(!kleeja_check_form_key('adm_plugins', 3600))
	{
		kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, $action, 1);
	}
}

//empty array of icons
$plugins_icons = array();


//get plugins
$query = array(
				'SELECT'	=> 'p.plg_id, p.plg_name, plg_icon, p.plg_disabled, p.plg_ver, p.plg_ver, p.plg_author, p.plg_dsc, p.plg_instructions',
				'FROM'		=> "{$dbprefix}plugins p"
			);

$result = $SQL->build($query);
		
if($SQL->num_rows($result)>0)
{
	$arr = array();
	
	$i = 1;
	while($row=$SQL->fetch_array($result))
	{
		$desc = unserialize(kleeja_base64_decode($row['plg_dsc']));

		$arr[]	= array(
						'i'					=> $i % 3 == 0,
						'plg_id'			=> $row['plg_id'],
						'plg_name'			=> str_replace('-', ' ', $row['plg_name']) . ($row['plg_disabled'] == 1 ? ' [ x ]': ''),
						'plg_disabled'		=> (int) $row['plg_disabled'] == 1 ? true : false,
						'plg_ver'			=> $row['plg_ver'],
						'plg_author'		=> $row['plg_author'],
						'plg_dsc'			=> isset($desc[$config['language']]) ? $desc[$config['language']] : $desc['en'],
						'plg_instructions'	=> trim($row['plg_instructions']) == '' ? false : true,
						'plg_icon_url'		=> basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;iconp=' . $row['plg_id']
				);		
				$i++;				

		if(!empty($row['plg_icon']))
		{
			$plugins_icons[$row['plg_id']] = $row['plg_icon'];
		}
	}
}
else
{
	$no_plugins	=	true;
}


$SQL->freeresult($result);

//save icons in cache ...
if (!$cache->exists('__plugins_icons__') || $cache->get('__plugins_icons__') === false)
{
	$cache->save('__plugins_icons__', $plugins_icons);
}


//cached templates
$there_is_cached = false;
$cached_file = PATH . 'cache/styles_cached.php';
if(file_exists($cached_file))
{
	$there_is_cached =  sprintf($lang['CACHED_STYLES_DISC'] , '<a onclick="javascript:get_kleeja_link(this.href); return false;" href="' . basename(ADMIN_PATH) . '?cp=m_styles&amp;sty_t=cached">' . $lang['CLICKHERE'] .'</a>');
}

//is there any changes from recent installing plugins
if (!($changes_files = $cache->get('__changes_files__')))
{
	if ($dh = @opendir(PATH . 'cache'))
	{
		while (($file = @readdir($dh)) !== false)
		{

			if(preg_match('!changes_of_[a-z0-9]+.zip!', $file))
			{
				$changes_files[] = array(	
								'file'	=> $file,
								'path'	=> basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;do_plg=1&amp;m=6&amp;fn=' . str_replace(array('changes_of_', '.zip'),'', $file) . '&amp;' . $GET_FORM_KEY,
							);
			}
		}
		@closedir($dh);
	}
	$cache->save('__changes_files__', $changes_files);
}

$is_there_changes_files = empty($changes_files) ? false : true;


//after submit 
else:

	$plg_id = intval($_GET['do_plg']);

	//check _GET Csrf token
	//remember to add token at every m=? request !
	if((int) $_GET['m'] != 6 && (int) $_GET['m'] != 4)
	{
		if(!kleeja_check_form_key_get('adm_plugins'))
		{
			kleeja_admin_err($lang['INVALID_GET_KEY'], true, $lang['ERROR'], true, basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php'), 2);
		}
	}

	//handle all m=?
	switch($_GET['m'])
	{
		case '1': // disable the plguin		
		case '2': //enable it

			$action	= (int) $_GET['m'] == 1 ? 1 : 0;
			
			//check if there is style require this plugin
			if($action == 1)
			{
				if(($style_info = kleeja_style_info($config['style'])) != false)
				{
					$plugins_required = array_map('trim', explode(',', $style_info['plugins_required']));
					if(in_array($_GET['pn'], $plugins_required))
					{
						kleeja_admin_err($lang['PLUGIN_REQ_BY_STYLE_ERR']);
					}
				}
			}
			
			//update
			$update_query = array(
									'UPDATE'	=> "{$dbprefix}plugins",
									'SET'		=> "plg_disabled = $action",
									'WHERE'		=> "plg_id=" . $plg_id
							);

			$SQL->build($update_query);
			if($SQL->affected())
			{
				delete_cache(array('data_plugins', 'data_config'));
			}		

			//show msg
			kleeja_admin_info($lang['PLGUIN_DISABLED_ENABLED'], false, '', true, basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;smt=' . $current_smt);

		break;
		
		//Delete plguin
		case '3': 

			//check if there is style require this plugin
			if(($style_info = kleeja_style_info($config['style'])) != false)
			{
				$plugins_required = array_map('trim', explode(',', $style_info['plugins_required']));
				if(in_array($_GET['pn'], $plugins_required))
				{
					kleeja_admin_err($lang['PLUGIN_REQ_BY_STYLE_ERR']);
				}
			}

			$stylee		= "admin_plugin_mfile";
			$action		= basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;m=3&amp;un=1&amp;pn=' . htmlspecialchars($_GET['pn']) . '&amp;do_plg=' . $plg_id . '&amp;' . $GET_FORM_KEY;
			$for_unistalling = true;

			//after submit
			if(isset($_GET['un']))
			{
				if(isset($_POST['_fmethod']) && $_POST['_fmethod'] == 'kftp')
				{
					if(empty($_POST['ftp_host']) || empty($_POST['ftp_port']) || empty($_POST['ftp_user']) ||empty($_POST['ftp_pass']))
					{
						kleeja_admin_err($lang['EMPTY_FIELDS'], true,'', true, str_replace('un=1', '', $action));
					}
					else
					{
						
						$plg->info = $ftpinfo = array('host'=>$_POST['ftp_host'], 'port'=>$_POST['ftp_port'], 'user'=>$_POST['ftp_user'], 'pass'=>$_POST['ftp_pass'], 'path'=>$_POST['ftp_path']);

						$ftpinfo['pass'] = '';
						update_config('ftp_info', serialize($ftpinfo), false);
						
						if(!$plg->check_connect())
						{
							kleeja_admin_err($lang['LOGIN_ERROR'], true,'', true, str_replace('un=1', '', $action));
						}
					}
				}
				else if(isset($_POST['_fmethod']) && $_POST['_fmethod'] == 'zfile')
				{
					$plg->f_method = 'zfile';
					$plg->check_connect();
				}

				//before delete we have look for unistalling 
				$query	= array(
								'SELECT'	=> 'plg_uninstall, plg_files',
								'FROM'		=> "{$dbprefix}plugins",
								'WHERE'		=> "plg_id=" . $plg_id
							);

				$result = $SQL->fetch_array($SQL->build($query));
	
				//do uninstalling codes
				if(trim($result['plg_uninstall']) != '')
				{
					eval($result['plg_uninstall']);
				}

				//delete files of plugin
				if(trim($result['plg_files']) != '')
				{
					$plg->delete_files(@unserialize(kleeja_base64_decode($result['plg_files'])));
				}

				//delete some data in Kleeja tables
				$delete_from_tables = array('plugins', 'hooks', 'lang', 'config');
				foreach($delete_from_tables as $table)
				{
					$query_del	= array(
										'DELETE'	=> "{$dbprefix}{$table}",
										'WHERE'		=> "plg_id=" . $plg_id 
									);

					$SQL->build($query_del);
				}

				//delete caches ..
				$cache->clean(array('__changes_files__', '__plugins_icons__'));
				delete_cache(array('data_plugins', 'data_config'));

				$plg->atend();

				if(empty($plg->zipped_files))
				{
					kleeja_admin_info($lang['PLUGIN_DELETED'], false, '', true, basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;' . $current_smt);
				}

				//if there is a zip?
				$text = sprintf($lang['PLUGIN_DELETED_ZIPPED'], '<a target="_blank"  href="' . basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;do_plg=' . $plg->plg_id . '&amp;m=6&amp;fn=' . $plg->zipped_files . '&amp;smt=' . $current_smt . '">', '</a>');
				$text .= '<br /><br /><a  onclick="javascript:get_kleeja_link(this.href); return false;"  href="' . basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;smt=' . $current_smt . '">' . $lang['GO_BACK_BROWSER'] . '</a>';
				kleeja_admin_info($text, false, '', true, false);
			}

		break;
		case '4': //plugin instructions
			$query	= array(
							'SELECT'	=> 'p.plg_name, p.plg_ver, p.plg_instructions',
							'FROM'		=> "{$dbprefix}plugins p",
							'WHERE'		=> "p.plg_id=" . $plg_id
						);

			$result = $SQL->fetch_array($SQL->build($query));


			if(empty($result)) //no instructions
			{
				redirect(basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php'));
			}
	
			$info = unserialize(kleeja_base64_decode($result['plg_instructions']));
			$info = isset($info[$config['language']]) ? $info[$config['language']] : $info['en'];
			kleeja_admin_info(
							'<h3>' . $result['plg_name'] . ' &nbsp;' . $result['plg_ver']  . ' : </h3>' . 
							$info . '<br /><a   onclick="javascript:get_kleeja_link(this.href); return false;" href="' . basename(ADMIN_PATH) . '?cp=' .
							basename(__file__, '.php') . '&amp;smt=' . $current_smt . '">' . $lang['GO_BACK_BROWSER'] . '</a>'
							);

		break;
		
		//downaloding zipped changes ..
		case 6:

			if(!isset($_GET['fn']))
			{
				kleeja_admin_err($lang['ERROR']);
			}

			$_f		= preg_replace('![^a-z0-9]!', '', $_GET['fn']);
			$name	= 'changes_of_' . $_f . '.zip';

			if(!file_exists(PATH . 'cache/' . $name))
			{
				kleeja_admin_err($lang['ERROR']);
			}

			if (is_browser('mozilla'))
			{
				$h_name = "filename*=UTF-8''" . rawurlencode(htmlspecialchars_decode($name));
			}
			else if (is_browser('opera, safari, konqueror'))
			{
				$h_name = 'filename="' . str_replace('"', '', htmlspecialchars_decode($name)) . '"';
			}
			else
			{
				$h_name = 'filename="' . rawurlencode(htmlspecialchars_decode($name)) . '"';
			}

			if (@ob_get_length())
			{
				@ob_end_clean();
			}

			// required for IE, otherwise Content-Disposition may be ignored
			if(@ini_get('zlib.output_compression'))
			{
				@ini_set('zlib.output_compression', 'Off');
			}

			header('Pragma: public');
			header('Content-Type: application/zip');
			header('X-Download-Options: noopen');
			header('Content-Disposition: attachment; '  . $h_name);
			
			echo file_get_contents(PATH . 'cache/' . $name);
			$SQL->close();
			exit;

		break;
	}

endif;//else submit


//new plugin from xml
if(isset($_POST['submit_new_plg']))
{
	$text = '';
	// oh , some errors
	if($_FILES['imp_file']['error'])
	{
		$text = $lang['ERR_IN_UPLOAD_XML_FILE'];
	}
	else if(!is_uploaded_file($_FILES['imp_file']['tmp_name']))
	{
		$text = $lang['ERR_UPLOAD_XML_FILE_NO_TMP'];
	}

	//get content
	$contents = @file_get_contents($_FILES['imp_file']['tmp_name']);
	// Delete the temporary file if possible
	kleeja_unlink($_FILES['imp_file']['tmp_name']);

	// Are there contents?
	if(!trim($contents))
	{
		kleeja_admin_err($lang['ERR_UPLOAD_XML_NO_CONTENT'],true,'',true, basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php'));
	}

	if(empty($text))
	{
		if(isset($_POST['_fmethod']) && $_POST['_fmethod'] == 'kftp')
		{
			$plg->f_method = 'kftp';
			if(empty($_POST['ftp_host']) || empty($_POST['ftp_port']) || empty($_POST['ftp_user']) ||empty($_POST['ftp_pass']))
			{
				kleeja_admin_err($lang['EMPTY_FIELDS'], true,'', true, basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php'));
			}
			else
			{
				$plg->info = $ftpinfo = array('host'=>$_POST['ftp_host'], 'port'=>$_POST['ftp_port'], 'user'=>$_POST['ftp_user'], 'pass'=>$_POST['ftp_pass'], 'path'=>$_POST['ftp_path']);

				$ftpinfo['pass'] = '';
				update_config('ftp_info', serialize($ftpinfo), false);
				
				if(!$plg->check_connect())
				{
					kleeja_admin_err($lang['LOGIN_ERROR'], true,'', true, basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '?#!cp=' . basename(__file__, '.php'));
				}
			}
		}
		else if(isset($_POST['_fmethod']) && $_POST['_fmethod'] == 'zfile')
		{
			$plg->f_method = 'zfile';
			$plg->check_connect();
		}

		$return = $plg->add_plugin($contents);

		$plg->atend();
		
		switch($return)
		{
			//plugin added
			case 'done':
				$text = $lang['NEW_PLUGIN_ADDED'];
				$text .= '<script type="text/javascript"> setTimeout("get_kleeja_link(\'' . basename(ADMIN_PATH) . '?cp=' .  basename(__file__, '.php') .  '\');", 2000);</script>' . "\n";
			break;
			case 'xyz': //exists before
				kleeja_admin_err($lang['PLUGIN_EXISTS_BEFORE'],true,'',true, basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php'));			
			break;
			case 'upd': // updated success
				$text = $lang['PLUGIN_UPDATED_SUCCESS'];
			break;

			#--->weiredooo stuff
			case 'inst':
				$text = $lang['NEW_PLUGIN_ADDED'];
				$text .= '<script type="text/javascript"> setTimeout("get_kleeja_link(\'' . basename(ADMIN_PATH) . '?cp=' .  basename(__file__, '.php') . '&do_plg=' . $plg->plg_id . '&m=4&' . $GET_FORM_KEY . '\');", 2000);</script>' . "\n";
			break;
			case 'zipped':
				$text = sprintf($lang['PLUGIN_ADDED_ZIPPED'], '<a target="_blank" href="' . basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;do_plg=' . $plg->plg_id . '&amp;m=6&amp;fn=' . $plg->zipped_files . '&amp;' . $GET_FORM_KEY . '">', '</a>');
				$text .= '<br /><br /><a onclick="javascript:get_kleeja_link(this.href); return false;" href="' . basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '">' . $lang['GO_BACK_BROWSER'] . '</a>';
			break;
			case 'zipped/inst':
				$text = sprintf($lang['PLUGIN_ADDED_ZIPPED_INST'], 
								'<a target="_blank" href="' . basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;do_plg=' . $plg->plg_id . '&amp;m=6&amp;fn=' . $plg->zipped_files . '&amp;' . $GET_FORM_KEY . '">',
								'</a>',
								'<a onclick="javascript:get_kleeja_link(this.href); return false;" href="' . basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;do_plg=' . $plg->plg_id . '&amp;m=4&amp;' . $GET_FORM_KEY . '">',
								'</a>'
								);
				$text .= '<br /><br /><a onclick="javascript:get_kleeja_link(this.href); return false;" href="' . basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '">' . $lang['GO_BACK_BROWSER'] . '</a>';
			break;
			default:
				kleeja_admin_err($lang['ERR_IN_UPLOAD_XML_FILE'],true,'',true, basename(ADMIN_PATH) . '?#!cp=' . basename(__file__, '.php'));	
		}
	}
	
	$cache->clean(array('__changes_files__', '__plugins_icons__'));
	delete_cache(array('data_plugins', 'data_config'));

	$stylee	= "admin_info";
}


//secondary menu
//$go_menu = array(
//				'general' => array('name'=>$lang['R_PLUGINS'], 'link'=> basename(ADMIN_PATH) . '?cp=j_plugins&amp;smt=general', 'goto'=>'general', 'current'=> $current_smt == 'general'),
//				'newplg' => array('name'=>$lang['ADD_NEW_PLUGIN'], 'link'=> basename(ADMIN_PATH) . '?cp=j_plugins&amp;smt=newplg', 'goto'=>'newplg', 'current'=> $current_smt == 'newplg'),
//	);
