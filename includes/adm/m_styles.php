<?php
/**
*
* @package adm
* @version $Id: m_styles.php 2062 2012-10-17 05:18:36Z saanina $
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/

// not for directly open
if (!defined('IN_ADMIN'))
{
	exit();
}

//prevent notice
if(!isset($_GET['sty_t']))
{
	$_GET['sty_t'] = null;
}

#current secondary menu action
$current_smt = isset($_GET['smt']) ? (preg_match('![a-z0-9_]!i', trim($_GET['smt'])) ? trim($_GET['smt']) : 'general') : 'general';

switch ($_GET['sty_t']) 
{
	default:
	case 'st' :

		//for style ..
		$stylee 	= "admin_styles";
		$action 	= basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') .'&amp;sty_t=st' . '&amp;smt=' . $current_smt;
		$edit_tpl_action		= basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') .'&amp;sty_t=style_orders&amp;style_id=' . $config['style'] .  '&amp;smt=' . $current_smt . '&amp;method=1&amp;tpl_choose=';
		$show_all_tpls_action	= basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') .'&amp;style_choose=' . $config['style'] . '&amp;method=1' . '&amp;smt=' . $current_smt;
		$GET_FORM_KEY			= kleeja_add_form_key_get('adm_style_del_edit');
		$H_FORM_KEYS2			= kleeja_add_form_key('adm_style_order_add');
		$H_FORM_KEYS3			= kleeja_add_form_key('adm_style_order_bkup');

		//kleeja depend on its users .. and kleeja love them .. so let's tell them about that ..
		$klj_d_s = $lang['KLJ_MORE_STYLES'][rand(0, sizeof($lang['KLJ_MORE_STYLES'])-1)];

		//get styles
		$arr = array();
		if ($dh = @opendir(PATH . 'styles'))
		{
			while (($file = @readdir($dh)) !== false)
			{
				if(strpos($file, '.') === false && $file != '..' && $file != '.')
				{
					$arr[] = array(	
									'style_name'	=> $file,
									'is_default'	=> $config['style'] == $file ? true : false,
									'link_show_tpls'	=> basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') .'&amp;sty_t=st&amp;style_choose=' . $file . '&amp;method=1&amp;smt=curstyle',
									'link_mk_default'	=> basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') .'&amp;sty_t=st&amp;style_choose=' . $file . '&amp;method=2&amp;smt=curstyle',
							);
				}
			}
			@closedir($dh);
		}


		//after submit
		if(isset($_REQUEST['style_choose']))
		{
			$style_id = str_replace('..', '', $_REQUEST['style_choose']);

			//if empty, let's ignore it
			if(empty($style_id))
			{
				redirect(basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php'));
			}

			//is there any possiblity to write on files
			$not_style_writeable = true;
			$d_style_path = PATH . 'styles/' . $style_id; 
			if (!is_writable($d_style_path))
			{
				@chmod($d_style_path, 0777);
				if (is_writable($d_style_path))
				{
					$not_style_writeable = false;
				}
			}
			else
			{
				//there is no possiblity to write on files
				$not_style_writeable = false;
			}

			$lang['STYLE_DIR_NOT_WR'] = sprintf($lang['STYLE_DIR_NOT_WR'], $d_style_path);

			//handling styles, show or make default ...
			switch($_REQUEST['method'])
			{
				default:
				case '1': //show templates

					//for style ..
					$stylee = 'admin_show_tpls';
					$action = basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') .'&amp;sty_t=style_orders';
					$action2 = basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') .'&amp;sty_t=style_orders';

					//get backup templates
					$show_bk_templates = false;
					include_once  PATH . 'includes/bk_templates.php';
					if (version_compare(strtolower(KLEEJA_VERSION), strtolower($bk_version), '='))
					{
						$show_bk_templates = true;
						$bkup_templates = array_keys($bkup_templates);
					}

					//get style detalis
					$style_details1 = array('name'=>'[!]', 'desc'=> '[!]', 'version'=> '[!]', 'copyright'=>'[!]', 'kleeja_version'=>'[!]', 'depend_on' => $lang['NONE'], 'plugins_required'=>'[!]');
					$style_details = kleeja_style_info($style_id);

					//fix if not array
					if(!is_array($style_details))
					{
						$style_details = array();
					}
					else
					{
						//sepcify language of description of style
						if(!empty($style_details['desc']))
						{
							if(!empty($style_details['desc'][$config['language']]))
							{
								$style_details['desc'] = $style_details['desc'][$config['language']];
							}
							else
							{
								$style_details['desc'] = $style_details['desc']['en'];
							}
						}
					}

					$style_details += array_diff_assoc($style_details1, $style_details);

					//get_tpls
					$tpls_basic = $tpls_msg = $tpls_user = $tpls_other = $tpls_all = array();
					if ($dh = @opendir($d_style_path))
					{
						while (($file = readdir($dh)) !== false)
						{
							if(array_pop(explode('.', $file)) == 'html' && !is_dir($d_style_path . '/' . $file) && $file != 'index.html')
							{
								if(in_array($file, array('header.html', 'footer.html', 'index_body.html')))
								{
									$tpls_basic[] = array('template_name' => $file );
								}
								else if(in_array($file, array('info.html', 'err.html')))
								{
									$tpls_msg[]	= array('template_name' => $file );
								}
								else if(in_array($file, array('login.html', 'register.html', 'profile.html', 'get_pass.html', 'fileuser.html', 'filecp.html')))
								{
									$tpls_user[] = array('template_name'=> $file);
								}
								else
								{
									$tpls_other[] = array('template_name' => $file);
								}
								
								$tpls_all[$file] = true;
							}
						}
						closedir($dh);
					}

					//show only template required in this style
					$bkup_templates = array_intersect(array_keys($tpls_all), $bkup_templates);

				break;

				case '2': // make as default

					//check _GET Csrf token
					if(isset($_REQUEST['home']) && !kleeja_check_form_key_get('adm_start_actions'))
					{
						kleeja_admin_err($lang['INVALID_GET_KEY'], true, $lang['ERROR'], true, basename(ADMIN_PATH) . '?cp=start', 2);
					}
	
					//
					//check if this style depend on other style and 
					//check kleeja version that required by this style
					//
					if(($style_info = kleeja_style_info($style_id)) != false)
					{
						if(isset($style_info['depend_on']) && !file_exists(PATH . 'styles/' . $style_info['depend_on']))
						{
							kleeja_admin_err(sprintf($lang['DEPEND_ON_NO_STYLE_ERR'], $style_info['depend_on']));
						}

						if(isset($style_info['kleeja_version']) && version_compare(strtolower($style_info['kleeja_version']), strtolower(KLEEJA_VERSION), '>'))
						{
							kleeja_admin_err(sprintf($lang['KLJ_VER_NO_STYLE_ERR'], $style_info['kleeja_version']));
						}
						
						//is this style require some plugins to be installed
						if(isset($style_info['plugins_required']))
						{
							$plugins_required = array_map('trim', explode(',', $style_info['plugins_required']));
	
							$query = array(
											'SELECT'	=> 'plg_name',
											'FROM'		=> "{$dbprefix}plugins",
										);

							$result = $SQL->build($query);

							if($SQL->num_rows($result) != 0)
							{
								$plugins_required = array_flip($plugins_required);
								while($row=$SQL->fetch_array($result))
								{
									if(in_array($row['plg_name'], $plugins_required))
									{
										unset($plugins_required[$row['plg_name']]);
									}
								}
							}

							$SQL->freeresult($result);
							
							$plugins_required = array_flip($plugins_required);
							if(sizeof($plugins_required))
							{
								kleeja_admin_err(sprintf($lang['PLUGINS_REQ_NO_STYLE_ERR'], implode(', ', $plugins_required)));
							}
						}
					}

					//make it as default
					update_config('style', $style_id);
					update_config('style_depend_on', isset($style_info['depend_on']) ? $style_info['depend_on'] : '');

					//delete all cache to get new style
					delete_cache('', true);

					//show msg
					kleeja_admin_info(sprintf($lang['STYLE_NOW_IS_DEFAULT'], htmlspecialchars($style_id)), true, '', true, basename(ADMIN_PATH) . '?cp=' . (isset($_REQUEST['home']) ? 'start' : basename(__file__, '.php')));
				break;				
			}
		}

	break;

	case 'style_orders' :

		//style id ..
		$style_id = str_replace('..', '', htmlspecialchars($_GET['style_id']));
		$redirect_to = basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&style_choose=' . $style_id . '&method=1';

		if(empty($_GET['tpl_choose']))
		{
			#redirect($redirect_to);
		}

		//edit or del tpl 
		if(isset($_GET['tpl_choose']) && !empty($_GET['tpl_choose']) && isset($_GET['style_id']) && isset($_GET['method']))
		{
			//check _GET Csrf token
			if(!kleeja_check_form_key_get('adm_style_del_edit'))
			{
				kleeja_admin_err($lang['INVALID_GET_KEY'], true, $lang['ERROR'], true, $redirect_to, 2);
			}

			//tpl name 
			$tpl_name =	str_replace('..', '', htmlspecialchars($_GET['tpl_choose']));
			$tpl_path = PATH . 'styles/' . $style_id . '/' . $tpl_name;
			$d_style_path = PATH . 'styles/' . $style_id; 

			if(!file_exists($tpl_path))
			{
				$text = sprintf($lang['TPL_PATH_NOT_FOUND'], $tpl_path);
				$_GET['method'] = 0;
			}
			else if (!is_writable($d_style_path))
			{
				$text = sprintf($lang['STYLE_DIR_NOT_WR'], $d_style_path);
				$_GET['method'] = 0;
			}
	
			if(!isset($_GET['method']))
			{
				$_GET['method'] = 0;
			}

			switch((int) $_GET['method'])
			{
				case 0:
					$stylee = "admin_info";
				break; 
				//edit tpl
				case 1:

					//for style ..
					$stylee = "admin_edit_tpl";
					$action = basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;sty_t=style_orders';
					$action_return	= basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;style_choose=' . $style_id . '&amp;method=1';
	
					//is there any possiablity to write on files
					$not_style_writeable = true;
					$d_style_path = PATH . 'styles/' . $style_id; 
					$lang['STYLE_DIR_NOT_WR'] = sprintf($lang['STYLE_DIR_NOT_WR'], $d_style_path);
					if (!is_writable($d_style_path))
					{
						@chmod($d_style_path, 0777);
						if (is_writable($d_style_path))
						{
							$not_style_writeable = false;
						}
					}
					else
					{
						$not_style_writeable = false;
					}

					$template_content	= file_get_contents($tpl_path);
					$template_content	= htmlspecialchars(stripslashes($template_content));

				break;
				
				 //delete tpl
				case 2:

					kleeja_unlink($tpl_path);

					//show msg
					$link	= basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;style_choose=' . $style_id . '&amp;method=1';
					$text	= $lang['TPL_DELETED']  . '<br /> <a href="' . $link . '">' . $lang['GO_BACK_BROWSER'] . '</a><meta HTTP-EQUIV="REFRESH" content="1; url=' . $link . '">' ."\n";
					$stylee	= "admin_info";	

				break;
			}
		}

		// submit edit of tpl
		if(isset($_POST['template_content']))
		{
			$style_id = str_replace('..', '', $SQL->escape($_POST['style_id']));
			//tpl name 
			$tpl_name =	htmlspecialchars_decode($_POST['tpl_choose']);
			$tpl_path = PATH . 'styles/' . $style_id . '/' . $tpl_name;
			$tpl_content = stripslashes($_POST['template_content']);
			$link	= basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;sty_t=st&amp;style_choose=' . $style_id . '&amp;method=1';
			
			//try to make template writable
			if (!is_writable($tpl_path)) 
			{
				@chmod($tpl_path, 0777);
			}

			//edit template
			if (is_writable($tpl_path)) 
			{
				$filename = @fopen($tpl_path, 'w');
				fwrite($filename, $tpl_content);
				fclose($filename);
				//delete cache ..
				delete_cache('tpl_' . str_replace('html', 'php', $tpl_name));
				//show msg
				$text = $lang['TPL_UPDATED'];
				$text	.= '<script type="text/javascript"> setTimeout("get_kleeja_link(\'' . $link .  '\');", 2000);</script>' . "\n";
				$stylee = 'admin_info';
			}
			else
			{
				$text = sprintf($lang['T_ISNT_WRITEABLE'], $tpl_name);
				$text	.= '<script type="text/javascript"> setTimeout("get_kleeja_link(\'' . $link .  '\');", 2000);</script>' . "\n";
				$stylee = 'admin_err';
				//kleeja_admin_err(, true,'', true, $link, 5);
			}
			//kleeja_admin_info(, true,'', true, $link, 5);
		}

		//new template file
		if(isset($_POST['submit_new_tpl']))
		{
			//
			// Check form key
			//
			if(!kleeja_check_form_key('adm_style_order_add'))
			{
				kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, $redirect_to, 1);
			}

			//style id 
			$style_id = str_replace('..', '', $SQL->escape($_POST['style_id']));
			//tpl name 
			$tpl_name =	str_replace(array('..', '.html', '.php'), '', $_POST['new_tpl']);
			$tpl_path = PATH . 'styles/' . $style_id . '/' . $tpl_name . '.html';
	
			//same name, exists before, let's edit it
			if(file_exists($tpl_path))
			{
				$tpl_path = PATH . 'styles/' . $style_id . '/' . str_replace('.html', substr(uniqid('_'), 0, 5) . '.html', $tpl_name);
			}
				
			$tpl_content = '';
			if($filename = @fopen($tpl_path, 'w'))
			{
				@fwrite($filename, $tpl_content);
				@fclose($filename);
			}

			$link	= basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;style_choose=' . $style_id . '&amp;method=1';
			$text	= $lang['TPL_CREATED']  . '<br /> <a href="' . $link . '">' . $lang['GO_BACK_BROWSER'] . '</a><meta HTTP-EQUIV="REFRESH" content="1; url=' . $link . '">' ."\n";
			$stylee	= "admin_info";
		}

		//return bakup template
		if(isset($_POST['submit_bk_tpl']))
		{
			//
			// Check form key
			//
			if(!kleeja_check_form_key('adm_style_order_bkup'))
			{
				kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, $redirect_to, 1);
			}
			
			//style id 
			$style_id = str_replace('..', '', $SQL->escape($_POST['style_id']));
			$tpl_name = str_replace('..', '', $SQL->escape($_POST['tpl_choose']));
			include_once  PATH . 'includes/bk_templates.php';

			if(!isset($bkup_templates[$tpl_name]))
			{
				redirect(basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&style_choose=' . $style_id . '&method=1');
				exit;
			}

			$tpl_path = PATH . 'styles/' . $style_id . '/' . $tpl_name;

			if(is_writable($tpl_path))
			{
				if($filename = @fopen($tpl_path, 'w'))
				{
					@fwrite($filename, kleeja_base64_decode($bkup_templates[$tpl_name]));
					@fclose($filename);
				}
			}
			else
			{
				$cached[$tpl_name] = array(
										'action'		=> 'replace_with', 
										'find'			=> '',
										'action_text'	=> kleeja_base64_decode($bkup_templates[$tpl_name]),
									);

				if(file_exists(PATH . 'cache/styles_cached.php'))
				{
					$cached_content = file_get_contents(PATH . 'cache/styles_cached.php');
					$cached_content = kleeja_base64_decode($cached_content);
					$cached_content = unserialize($cached_content);
					$cached += $cached_content;
				}

				$filename = @fopen(PATH . 'cache/styles_cached.php' , 'w');
				@fwrite($filename, kleeja_base64_encode(serialize($cached)));
				@fclose($filename);
			}

			$link	= basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;style_choose=' . $style_id . '&amp;method=1';
			$text	= sprintf($lang['TPL_BK_RETURNED'], $tpl_name)  . '<br /> <a href="' . $link . '">' . $lang['GO_BACK_BROWSER'] . '</a><meta HTTP-EQUIV="REFRESH" content="1; url=' . $link . '">' ."\n";
			$stylee	= "admin_info";
		}

	break;

	//show cached temaplte process ...
	//that came from plugins or return backup or even if style folder
	//is not writable
	case 'cached':

		$cached_file = PATH . 'cache/styles_cached.php';

		//delete cached styles
		if(isset($_GET['del']))
		{
			delete_cache('styles_cached');
			$text	= $lang['CACHED_STYLES_DELETED'];
			$stylee	= 'admin_info';
		}
		elseif(!file_exists($cached_file))
		{
			$text = $lang['NO_CACHED_STYLES'];
			$stylee = 'admin_info';
		}
		else
		{
			$content = file_get_contents($cached_file);
			$content = kleeja_base64_decode($content);
			$content = unserialize($content);

			ob_start();
			foreach($content as $template_name=>$do)
			{
				echo '<strong>' . $lang['OPEN'] . '</strong> : <br /> ' . (substr($template_name, 0, 6) == 'admin_' ? $STYLE_PATH_ADMIN : $STYLE_PATH) . $template_name . '<br />';
				switch(trim($do['action'])):
					case 'replace_with':

						echo '<strong> ' . $lang['SEARCH_FOR'] . '<strong> : <br />';

						//if it's to code
						if(strpos($do['find'], '(.*?)') !== false)
						{
							$do['find'] = explode('(.*?)', $do['find']);
							echo '<textarea style="direction:ltr;width:90%">' . trim(htmlspecialchars($do['find'][0])) . '</textarea> <br />';
							echo '<strong> ' . $lang['REPLACE_TO_REACH'] . '<strong> : <br />';
							echo '<textarea style="direction:ltr;width:90%">' . trim(htmlspecialchars($do['find'][1])) . '</textarea> <br />';
						}
						else if(trim($do['find']) == '')
						{
							echo '<strong>' . $lang['REPLACE_WHOLW_TPL'] . '</strong><br />';
						}
						else
						{
							echo '<textarea style="direction:ltr;width:90%;height:50px">' . trim(htmlspecialchars($do['find'])) . '</textarea> <br />';
						}
	
						echo '<strong> ' . $lang['REPLACE_WITH'] . '<strong> : <br />';
						echo '<textarea style="direction:ltr;width:90%;height:100px">' . trim(htmlspecialchars($do['action_text'])) . '</textarea> <br />'; 

					break;
					case 'add_after':

						echo '<strong> ' . $lang['SEARCH_FOR'] . '<strong> : <br />';
						echo '<textarea style="direction:ltr;width:90%;height:50px">' . trim(htmlspecialchars($do['find'])) . '</textarea> <br />';
						echo '<strong> ' . $lang['ADD_AFTER'] . '<strong> : <br />';
						echo '<textarea style="direction:ltr;width:90%;height:100px">' . trim(htmlspecialchars($do['action_text'])) . '</textarea> <br />'; 

					break;	
					case 'add_after_same_line':

						echo '<strong> ' . $lang['SEARCH_FOR'] . '<strong> : <br />';
						echo '<textarea style="direction:ltr;width:90%;height:50px">' . trim(htmlspecialchars($do['find'])) . '</textarea> <br />';
						echo '<strong> ' . $lang['ADD_AFTER_SAME_LINE'] . '<strong> : <br />';
						echo '<textarea style="direction:ltr;width:90%;height:100px">' .trim(htmlspecialchars($do['action_text'])) . '</textarea> <br />'; 

					break;
					case 'add_before':

						echo '<strong> ' . $lang['SEARCH_FOR'] . '<strong> : <br />';
						echo '<textarea style="direction:ltr;width:90%;height:50px">' . trim(htmlspecialchars($do['find'])) . '</textarea> <br />';
						echo '<strong> ' . $lang['ADD_BEFORE'] . '<strong> : <br />';
						echo '<textarea style="direction:ltr;width:90%;height:100px">' . trim(htmlspecialchars($do['action_text'])) . '</textarea> <br />'; 

					break;	
					case 'add_before_same_line':

						echo '<strong> ' . $lang['SEARCH_FOR'] . '<strong> : <br />';
						echo '<textarea style="direction:ltr;width:90%;height:50px">' . trim(htmlspecialchars($do['find'])) . '</textarea> <br />';
						echo '<strong> ' . $lang['ADD_BEFORE_SAME_LINE'] . '<strong> : <br />';
						echo '<textarea style="direction:ltr;width:90%;height:100px">' . trim(htmlspecialchars($do['action_text'])) . '</textarea> <br />'; 

					break;
					case 'new':

						echo '<strong> ' . $lang['ADD_IN'] . '<strong> : <br />';
						echo '<textarea style="direction:ltr;width:90%;height:150px">' . trim(htmlspecialchars($do['action_text'])) . '</textarea> <br />'; 

					break;
				endswitch;	

				echo '<br /><hr /><br />';
			}
		
			$text = ob_get_contents();
			ob_end_clean();

			$text .= '<br /><br /><a href="' . basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;sty_t=cached&amp;del=1">' . $lang['DELETE_CACHED_STYLES'] . '</a>';  
			$stylee = 'admin_info';
		}
	break;
		
	//
	// Create backup version of default style, this work for developers only
	//
	case 'bk':

		//must be writable
		if(!is_writable(PATH . 'includes/bk_templates.php') || !defined('DEV_STAGE'))
		{
			redirect(basename(ADMIN_PATH));
		}

		//default must be here
		$style_folder = PATH . 'styles/default/';
		if (!$dh = @opendir($style_folder))
		{
			redirect(basename(ADMIN_PATH));
		}

		//open bk_template.php to write contents of templates to it.
		$bkf = @fopen(PATH . 'includes/bk_templates.php', 'wb');

		$bkf_contents = "<" . "?php\n//\n//bakup of Kleeja templates\n//Automatically generated from DEV version cp=" . basename(__file__, '.php') . "&sty_t=bk\n//\n\n//no for directly open\nif (!defined('IN_COMMON'))\n{";
		$bkf_contents .= "\n\texit();\n}\n\n//for version\n\$bk_version = '" . KLEEJA_VERSION . "';";
		$bkf_contents .= "\n\n//Done in : " . date('d-m-Y H:i a') . "\n\n\$bkup_templates = array(\n";

		$f = 0;
		while (($file = @readdir($dh)) !== false)
		{
			//exceptions
			if(!in_array(strtolower($file), array('.', '..', 'index.html', 'javascript.js', 'css', '.svn', 'images', '.htaccess', 'ie', 'info.txt'))) 
			{
				$f++;
				$bkf_contents .= "\t'" . $file . "' => '" . kleeja_base64_encode(file_get_contents($style_folder . $file)) . "',\n";
			}
		}
		
		$bkf_contents .= "\n);";

		//write to bk_template.php
		@ftruncate($bkf, 0);
		@fwrite($bkf, $bkf_contents);

		//...
		@fclose($bkf); 
		@closedir($bkf);

		$text ='Done, ' . $f . ' files !';
		$stylee = 'admin_info';

	break;
}

if(!isset($stylee))
{
	$text	= '--------';
	$stylee  = 'admin_info';
}

$arrow_html = $lang['DIR'] == 'rtl' ? ' &rarr; ' : ' &larr; ';

//secondary menu
$go_menu = array();
$go_menu['general'] = array('name'=>$lang['R_STYLES'], 'link'=> basename(ADMIN_PATH) . '?cp=m_styles&amp;smt=general', 'goto'=>'general', 'current'=> $current_smt == 'general');
	
if(isset($_GET['style_choose']))
{
	$go_menu['curstyle'] =  array('name'=>$_GET['style_choose'] . $arrow_html . $lang['STYLE'], 'link'=> basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') .'&amp;sty_t=st&amp;style_choose=' . $style_id . '&amp;method=1&amp;smt=curstyle', 'goto'=>'curstyle', 'current'=> $current_smt == 'curstyle');
}

$go_menu['basictpls'] = array('name'=>$lang['STYLE_IS_DEFAULT'] . $arrow_html . $lang['TPLS_RE_BASIC'], 'link'=> basename(ADMIN_PATH) . '?cp=m_styles&amp;smt=basictpls', 'goto'=>'basictpls', 'current'=> $current_smt == 'basictpls');

