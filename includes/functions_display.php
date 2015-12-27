<?php
/**
*
* @package Kleeja
* @version $Id: functions_display.php 2128 2013-02-14 12:01:02Z saanina $
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/


//no for directly open
if (!defined('IN_COMMON'))
{
	exit();
}


/**
* Header
* 
* To show header in any page you want .. 
* parameter : title : title of page as in <title></title>
*/	
function Saaheader($title, $outscript = false, $extra = '')
{
	global $tpl, $usrcp, $lang, $olang, $user_is, $username, $config;
	global $extras, $script_encoding, $errorpage, $userinfo, $charset;
	global $STYLE_PATH;

	//is user ? and username
	$user_is = ($usrcp->name()) ? true: false;
	$username = ($usrcp->name()) ? $usrcp->name() : $lang['GUST'];

	//our default charset
	$charset = 'utf-8';

	$side_menu = array(
		1 => array('name'=>'profile', 'title'=>$lang['PROFILE'], 'url'=>$config['mod_writer'] ? 'profile.html' : 'ucp.php?go=profile', 'show'=>$user_is),
		2 => array('name'=>'fileuser', 'title'=>$lang['YOUR_FILEUSER'], 'url'=>$config['mod_writer'] ? 'fileuser.html' : 'ucp.php?go=fileuser', 'show'=>$config['enable_userfile'] && user_can('access_fileuser')),
		3 => $user_is ?
			 array('name'=>'logout', 'title'=>$lang['LOGOUT'], 'url'=>$config['mod_writer'] ? 'logout.html' : 'ucp.php?go=logout', 'show'=>true) : 
			 array('name'=>'login', 'title'=>$lang['LOGIN'], 'url'=>$config['mod_writer'] ? 'login.html' : 'ucp.php?go=login', 'show'=>true),
		4 => array('name'=>'register', 'title'=>$lang['REGISTER'], 'url'=>$config['mod_writer'] ? 'register.html' : 'ucp.php?go=register', 'show'=>!$user_is && $config['register']),
	);

	$top_menu = array(
		1 => array('name'=>'index', 'title'=>$lang['INDEX'], 'url'=>$config['siteurl'], 'show'=>true),
		2 => array('name'=>'rules', 'title'=>$lang['RULES'], 'url'=>$config['mod_writer'] ? 'rules.html' : 'go.php?go=rules', 'show'=>true),
		3 => array('name'=>'guide', 'title'=>$lang['GUIDE'], 'url'=>$config['mod_writer'] ? 'guide.html' : 'go.php?go=guide', 'show'=>true),
		4 => array('name'=>'stats', 'title'=>$lang['STATS'], 'url'=>$config['mod_writer'] ? 'stats.html' : 'go.php?go=stats', 'show'=>$config['allow_stat_pg'] && user_can('access_stats')),
		5 => array('name'=>'report', 'title'=>$lang['REPORT'], 'url'=>$config['mod_writer'] ? 'report.html' : 'go.php?go=report', 'show'=>user_can('access_report')),
		6 => array('name'=>'call', 'title'=>$lang['CALL'], 'url'=>$config['mod_writer'] ? 'call.html' : 'go.php?go=call', 'show'=>user_can('access_call')),
	);

	//check for extra header 
	$extras['header'] = empty($extras['header']) ? false : $extras['header'];

	($hook = kleeja_run_hook('Saaheader_links_func')) ? eval($hook) : null; //run hook

	//assign some variables
	$tpl->assign("dir", $lang['DIR']);
	$tpl->assign("title", $title);
	$tpl->assign("side_menu", $side_menu);
	$tpl->assign("top_menu", $top_menu);
	$tpl->assign("go_current", (isset($_GET['go']) ? htmlentities($_GET['go']) : (empty($_GET) ? 'index': '')));
	$tpl->assign("go_back_browser", $lang['GO_BACK_BROWSER']);
	$tpl->assign("H_FORM_KEYS_LOGIN", kleeja_add_form_key('login'));
	$tpl->assign("action_login", 'ucp.php?go=login' . (isset($_GET['return']) ? '&amp;return=' . htmlspecialchars($_GET['return']) : ''));
	$tpl->assign("EXTRA_CODE_META", $extra);
	$default_avatar = $STYLE_PATH . 'images/user_avater.png';
	$tpl->assign("user_avatar", 'http://www.gravatar.com/avatar/' . md5(strtolower(trim($userinfo['mail']))) . '?s=100&amp;d=' . urlencode($default_avatar));
	$header = $tpl->display("header");

	if($config['siteclose'] == '1' && user_can('enter_acp') && !defined('IN_ADMIN'))
	{
		//add notification bar 
		$header = preg_replace('/<body([^\>]*)>/i', "<body\\1>\n<!-- site is closed -->\n<p style=\"width: 100%; text-align:center; background:#FFFFA6; color:black; border:thin;top:0;left:0; position:absolute; width:100%;clear:both;\">" . $lang['NOTICECLOSED'] . "</p>\n<!-- #site is closed -->", $header);
	}
	
	($hook = kleeja_run_hook('Saaheader_func')) ? eval($hook) : null; //run hook

	echo $header;
	flush();
}


/**
* Footer
*
* To show footer of any page you want 
* paramenters : none
*/
function Saafooter($outscript = false)
{
	global $tpl, $SQL, $starttm, $config, $usrcp, $lang, $olang;
	global $do_gzip_compress, $script_encoding, $errorpage, $extras, $userinfo;

	//show stats ..
	$page_stats = '';
	if ($config['statfooter'] != 0) 
	{
		$gzip			= $config['gzip'] == '1' ?  "Enabled" : "Disabled";
		$hksys			= !defined('STOP_HOOKS') ? "Enabled" : "Disabled";
		$endtime		= get_microtime();
		$loadtime		= number_format($endtime - $starttm , 4);
		$queries_num	= $SQL->query_num;
		$time_sql		= round($SQL->query_num / $loadtime) ;
		$page_url		= preg_replace(array('/([\&\?]+)debug/i', '/&amp;/i'), array('', '&'), kleeja_get_page());
		$link_dbg		= user_can('enter_acp') &&  $config['mod_writer'] != '1' ? '[ <a href="' .  str_replace('&', '&amp;', $page_url) . (strpos($page_url, '?') === false ? '?' : '&amp;') . 'debug">More Details ... </a> ]' : null;
		$page_stats		= "<strong>[</strong> GZIP : $gzip - Generation Time: $loadtime Sec  - Queries: $queries_num - Hook System:  $hksys <strong>]</strong>  " . $link_dbg ;
	}
		
	$tpl->assign("page_stats", $page_stats);
		
	//if admin, show admin in the bottom of all page
	$tpl->assign("admin_page", (user_can('enter_acp') ? '<a href="' . ADMIN_PATH . '" class="admin_cp_link"><span>' . $lang['ADMINCP'] .  '</span></a>' : ''));
	
	//assign cron
	$tpl->assign("run_queue", '<img src="' . $config['siteurl'] . 'go.php?go=queue" width="1" height="1" alt="queue" />');


	// if google analytics, new version 
	//http://www.google.com/support/googleanalytics/bin/answer.py?answer=55488&topic=11126
	$googleanalytics = '';
	if (strlen($config['googleanalytics']) > 4)
	{
		$googleanalytics .= '<script type="text/javascript">' . "\n";
		$googleanalytics .= '<!--' . "\n";
		$googleanalytics .= 'var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");' . "\n";
		$googleanalytics .= 'document.write("\<script src=\'" + gaJsHost + "google-analytics.com/ga.js\' type=\'text/javascript\'>\<\/script>" );' . "\n";
		$googleanalytics .= '-->' . "\n";
		$googleanalytics .= '</script>' . "\n";
		$googleanalytics .= '<script type="text/javascript">' . "\n";
		$googleanalytics .= '<!--' . "\n";
		$googleanalytics .= 'var pageTracker = _gat._getTracker("' . $config['googleanalytics'] . '");' . "\n";
		$googleanalytics .= 'pageTracker._initData();' . "\n";
		$googleanalytics .= 'pageTracker._trackPageview();' . "\n";
		$googleanalytics .= '-->' . "\n";
		$googleanalytics .= '</script>' . "\n";
	}

	$tpl->assign("googleanalytics", $googleanalytics);	

	//check for extra header 
	if(empty($extras['footer']))
	{
		$extras['footer'] = false;
	}

	($hook = kleeja_run_hook('func_Saafooter')) ? eval($hook) : null; //run hook

	$footer = $tpl->display("footer");
	
	($hook = kleeja_run_hook('Saafooter_func')) ? eval($hook) : null; //run hook

	echo $footer;

	//page analysis 
	if (isset($_GET['debug']) && user_can('enter_acp'))
	{
		kleeja_debug();
	}

	//at end, close sql connections
	$SQL->close();
}

/**
* To return file size in propriate format
*/
function Customfile_size($size)
{
	$sizes = array(' B', ' KB', ' MB', ' GB', ' TB', 'PB', ' EB');
	$ext = $sizes[0];
	for ($i=1; (($i < count($sizes)) && ($size >= 1024)); $i++)
	{
		$size = $size / 1024;
		$ext  = $sizes[$i];
	}
	$result	=	 round($size, 2).$ext;
	($hook = kleeja_run_hook('func_Customfile_size')) ? eval($hook) : null; //run hook
	return  $result;
}

/**
* print error message 
* parameters : msg : text that will show as error mressage
					title : <title>title of page</title>
					exit : stop script after showing msg 
*/
function kleeja_err($msg, $title = '', $exit = true, $redirect = false, $rs = 2, $extra_code_header, $style = 'err')
{
	global $text, $tpl, $SQL;

	($hook = kleeja_run_hook('kleeja_err_func')) ? eval($hook) : null; //run hook

	// assign {text} in err template
	$text	= $msg . ($redirect ? redirect($redirect, false, $exit, $rs, true) : '');
	//header
	Saaheader($title, false, $extra_code_header);
	//show tpl
	echo $tpl->display($style);
	//footer
	Saafooter();

	if($exit)
	{
		$SQL->close();
		exit();
	}
}


/**
* print inforamtion message 
* parameters : msg : text that will show as inforamtion
					title : <title>title of page</title>
					exit : stop script after showing msg 
*/
function kleeja_info($msg, $title='', $exit = true, $redirect = false, $rs = 5, $extra_code_header = '')
{
	global $text, $tpl, $SQL;

	($hook = kleeja_run_hook('kleeja_info_func')) ? eval($hook) : null; //run hook

	return kleeja_err($msg, $title, $exit, $redirect, $rs, $extra_code_header, 'info');
}


/**
* Show debug information 
* 
* parameters: none
*/
function kleeja_debug()
{
	global $SQL,$do_gzip_compress, $all_plg_hooks;

	($hook = kleeja_run_hook('kleeja_debug_func')) ? eval($hook) : null; //run hook
	
		//get memory usage ; code of phpbb
		if (function_exists('memory_get_usage'))
		{
				if ($memory_usage = memory_get_usage())
				{
					$base_memory_usage	=	0;
					$memory_usage -= $base_memory_usage;
					$memory_usage = ($memory_usage >= 1048576) ? round((round($memory_usage / 1048576 * 100) / 100), 2) . ' MB' : (($memory_usage >= 1024) ? round((round($memory_usage / 1024 * 100) / 100), 2) . ' KB' : $memory_usage . ' BYTES');
					$debug_output = 'Memory Usage : <em>' . $memory_usage . '</em>';
				}
		}
		
		//thrn show it
		echo '<div class="debug_kleeja">';
		echo '<fieldset  dir="ltr"><legend><br /><br /><em style="font-family: Tahoma; color:red">[Page Analysis]</em></legend>';
		echo '<p>&nbsp;</p>';
		echo '<p><h2><strong>General Information :</strong></h2></p>';
		echo '<p>Gzip : <em>' . ($do_gzip_compress !=0 ?  "Enabled" : "Disabled") . '</em></p>';
		echo '<p>Queries Number :<em> ' .  $SQL->query_num . ' </i></p>';
		echo '<p>Hook System :<em> ' .  ((!defined('STOP_HOOKS'))?  "Enabled" : "Disabled"). ' </em></p>';
		echo '<p>Active Hooks :<em> ' .  sizeof($all_plg_hooks). ' </em></p>';
		echo '<p>' . $debug_output . '</p>';
		echo '<p>&nbsp;</p>';
		echo '<p><h2><strong><em>SQL</em> Information :</strong></h2></p> ';
		
		if(is_array($SQL->debugr))
		{ 
			foreach($SQL->debugr as $key=>$val)
			{
				echo '<fieldset name="sql"  dir="ltr" style="background:white"><legend><em>Query # [' . ($key+1) . '</em>]</legend> ';
				echo '<textarea style="font-family:Courier New,monospace;width:99%; background:#F4F4F4" rows="5" cols="10">' . $val[0] . '';
				echo '</textarea>	<br />';
				echo 'Duration :' . $val[1] . ''; 
				echo '</fieldset>';
				echo '<br /><br />';
			}
		}
		else
		{
			echo '<p><strong>NO SQLs</strong></p>';
		}
		
		echo '<p>&nbsp;</p><p><h2><strong><em>HOOKS</em> Information :</strong></h2></p> ';
		echo '<ul>';
		
		if(sizeof($all_plg_hooks) > 0)
		{ 
				foreach($all_plg_hooks as $k=>$v)
				{
					foreach($v as $p=>$c) $p=$p; $c=$c; // exactly 
					
					echo '<li><em>Plugin  # [' . $p . ']</em>';
					//echo '<textarea style="font-family:Courier New,monospace;width:99%; background:#F4F4F4" rows="5" cols="10">' . htmlspecialchars($c) . '</textarea><br />';
					echo ' : hook_name :' . $k . '</li>';
				}
		}
		else
		{
			echo '<p><strong>NO-HOOKS</strong></p>';
		}
		
		echo '</ul>';
		echo '</div>';
}

/**
* Show error of critical problem !
* 
* parameter: error_title : title of prblem
*			msg_text: message of problem
*/
function big_error ($error_title,  $msg_text, $error = true)
{
	global $SQL; 
	echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">' . "\n";
	echo '<head>' . "\n";
	echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />' . "\n";
	echo '<title>' . htmlspecialchars($error_title) . '</title>' . "\n";
	echo '<style type="text/css">' . "\n\t";
	echo '* { margin: 0; padding: 0; }' . "\n\t";
	echo '.error {color: #333;background:#ffebe8;float:left;width:73%;text-align:left;margin-top:10px;border: 1px solid #dd3c10;} .info {color: #333;background:#fff9d7;border: 1px solid #e2c822;}' . "\n\t";
	echo '.error,.info {padding: 10px;font-family:"lucida grande", tahoma, verdana, arial, sans-serif;font-size: 12px;}' . "\n";
	echo '</style>' . "\n";
	echo '</head>' . "\n";
	echo '<body>' . "\n\t";
	echo '<div class="' . ($error ? 'error' : 'info') . '">' . "\n";
	echo "\n\t\t<h2>Kleeja " . ($error ? 'error' : 'information message') . " : </h2><br />" . "\n";
	echo "\n\t\t<strong> [ " . $error_title . ' ] </strong><br /><br />' . "\n\t\t" . $msg_text . "\n\t";
	echo "\n\t\t" . '<br /><br /><small>Visit <a href="http://www.kleeja.com/" title="kleeja">Kleeja</a> Website for more details.</small>' . "\n\t";
	echo '</div>' . "\n";
	echo '</body>' . "\n";
	echo '</html>';
	@$SQL->close();
	exit();
}


/**
* Redirect
*
*/
function redirect($url, $header = true, $exit = true, $sec = 0, $return = false)
{
	global $SQL;

	($hook = kleeja_run_hook('redirect_func')) ? eval($hook) : null; //run hook

    if (!headers_sent() && $header && !$return)
	{
		header('Location: ' . str_replace(array('&amp;'), array('&'), $url)); 
    }
	else
	{
		#if ajax, mostly in acp
		if(isset($_GET['_ajax_']))
		{
			global $lang, $tpl, $text;
			$text = $lang['WAIT'] . '<script type="text/javascript"> setTimeout("get_kleeja_link(\'' . str_replace(array('&amp;'), array('&'), $url) . '\');", ' . $sec*1000 . ');</script>';
			if($exit)
			{
				echo_ajax(1, $tpl->display('admin_info'));
				$SQL->close();
				exit;
			}
		}
		else
		{
			$gre = '<script type="text/javascript"> setTimeout("window.location.href = \'' .  str_replace(array('&amp;'), array('&'), $url) . '\'", ' . $sec*1000 . '); </script>';
			$gre .= '<noscript><meta http-equiv="refresh" content="' . $sec . ';url=' . $url . '" /></noscript>';
		}

		if($return)
		{
			return $gre;
		}

		echo $gre;
	}

	if($exit)
	{
		$SQL->close();
		exit;
	}
}

/**
*
* Prevent CSRF, 
*
* This will generate security token for GET request
*/
function kleeja_add_form_key_get($request_id)
{
	global $config;
	
	$return = 'formkey=' . substr(sha1($config['h_key'] . date('H-d-m') . $request_id), 0, 20);
	
	($hook = kleeja_run_hook('kleeja_add_form_key_get_func')) ? eval($hook) : null; //run hook
	return $return;
}


function kleeja_check_form_key_get($request_id)
{
	global $config;

	$token = substr(sha1($config['h_key'] . date('H-d-m') . $request_id), 0, 20);

	$return = false;
	if($token == $_GET['formkey'])
	{
		$return = true; 
	}

	($hook = kleeja_run_hook('kleeja_check_form_key_get_func')) ? eval($hook) : null; //run hook
	return $return;
}

/**
* Prevent CSRF, 
*
* This will generate hidden fields for kleeja forms
*/
function kleeja_add_form_key($form_name)
{
	global $config;
	$now = time();
	$return = '<input type="hidden" name="k_form_key" value="' . sha1($config['h_key'] . $form_name . $now) . '" /><input type="hidden" name="k_form_time" value="' . $now . '" />' . "\n";
	
	($hook = kleeja_run_hook('kleeja_add_form_key_func')) ? eval($hook) : null; //run hook
	return $return;
}

/**
* Prevent CSRF, 
*
* This will check hidden fields that came from kleeja forms
*/
function kleeja_check_form_key($form_name, $require_time = 150 /*seconds*/ )
{
	global $config;

	if(defined('IN_ADMIN'))
	{
		//we increase it for admin to be a duble 
		$require_time *= 2;
	}

	$return = false;
	if (isset($_POST['k_form_key']) && isset($_POST['k_form_time']))
	{
		$key_was = trim($_POST['k_form_key']);
		$time_was = intval($_POST['k_form_time']);
		$different = time() - $time_was;

		//check time that user spent in the form 
		if($different && (!$require_time || $require_time >= $different))
		{
			if(sha1($config['h_key'] . $form_name . $time_was) === $key_was)
			{
				$return = true;
			}
		}
	}
	
	($hook = kleeja_run_hook('kleeja_check_form_key_func')) ? eval($hook) : null; //run hook
	return $return;
}

/**
* Link generator 
*
* Files can be many links styles, so this will generate the current style of link
*/

function kleeja_get_link ($pid, $extra = array())
{
	global $config;
	
	$links = array();
	
	//to avoid problems
	$config['id_form'] = empty($config['id_form']) ? 'id' : $config['id_form'];
	
	//for prevent bug with rewrite
	if($config['mod_writer'] && !empty($extra['::NAME::']) && $config['id_form'] != 'direct')
	{
		$extra['::NAME::'] = str_replace('.', '-', $extra['::NAME::']);
	}
	
	switch($config['id_form'])
	{
		case 'id':
			if($config['mod_writer'])
			{
				$links += array(
							'thumb' => 'thumb::ID::.html',
							'image' => 'image::ID::.html',
							'del'	=> 'del::CODE::.html',
							'file'	=> 'download::ID::.html',
						);
			}
			else
			{
				$links += array(
							'thumb' => 'do.php?thmb=::ID::',
							'image' => 'do.php?img=::ID::',
							'del'	=> 'go.php?go=del&amp;cd=::CODE::',
							'file'	=> 'do.php?id=::ID::',
						);
			}
		break;
		case 'filename':
			if($config['mod_writer'])
			{
				$links += array(
							'thumb' => 'thumbf-::NAME::.html',
							'image' => 'imagef-::NAME::.html',
							'del'	=> 'del::CODE::.html',
							'file'	=> 'downloadf-::NAME::.html',
						);
			}
			else
			{
				$links += array(
							'thumb' => 'do.php?thmbf=::NAME::',
							'image' => 'do.php?imgf=::NAME::',
							'del'	=> 'go.php?go=del&amp;cd=::CODE::',
							'file'	=> 'do.php?filename=::NAME::',
						);
			}
		break;
		case 'direct':
			if($config['mod_writer'])
			{
				$links += array(
							'del'	=> 'del::CODE::.html',
						);
			}
			else
			{
				$links += array(
							'del'	=> 'go.php?go=del&amp;cd=::CODE::',
						);
			}
			
			$links += array(
						'thumb' => '::DIR::/thumbs/::NAME::',
						'image' => '::DIR::/::NAME::',
						'file'	=> '::DIR::/::NAME::',
						);
		break;
		default:
			//add another type of links 
			//if $config['id_form']  == 'another things' : do another things .. 
			($hook = kleeja_run_hook('kleeja_get_link_d_func')) ? eval($hook) : null; //run hook
		break;
	}

	($hook = kleeja_run_hook('kleeja_get_link_func')) ? eval($hook) : null; //run hook
    
    //phpfalcon plugin
   	if(isset($extra['::NAME::']))
	{
		if($config['mod_writer'] && !empty($extra['::NAME::']) && $config['id_form'] != 'direct')
		{
			$ext = @explode("-", $extra['::NAME::']);
		}
		else
		{
			$ext = @explode(".", $extra['::NAME::']);
		}

		
		//$ext = $ext[sizeof($ext)-1];
		$ex	= explode(',',$config['imagefolderexts']);
		
		if(in_array(strtolower(array_pop($ext)), $ex))
		{
			
			if($config['mod_writer'] && !empty($extra['::NAME::']) && $config['id_form'] != 'direct')
			{
				$extra['::NAME::'] = $extra['::NAME::'] = preg_replace('!-([^-]+)$!', ".$1", $extra['::NAME::']);	
			}
			
			$links = array_merge($links, array(
						'thumb' => '::DIR::/thumbs/::NAME::',
						'image' => '::DIR::/::NAME::',
						'file'	=> '::DIR::/::NAME::',
						));
		}
	}
    
    
	$return = $config['siteurl'] . str_replace(array_keys($extra), array_values($extra), $links[$pid]);
	
	($hook = kleeja_run_hook('kleeja_get_link_func_rerun')) ? eval($hook) : null; //run hook
	
	return $return; 
}

/**
*  Uploading boxes 
*
* Parse template of boxes and print them
*/
function get_up_tpl_box($box_name, $extra = array())
{
	global $THIS_STYLE_PATH_ABS, $config;
	static $boxes = false;

	//prevent loads
	//also this must be cached in future
	if($boxes !== true)
	{
		$tpl_path = $THIS_STYLE_PATH_ABS . 'up_boxes.html';
		if(!file_exists($tpl_path))
		{
			$depend_on = false;
			if(trim($config['style_depend_on']) != '')
			{
				$depend_on = $config['style_depend_on'];
			}
			else
			{
				$depend_on = 'default';
			}
			
			$tpl_path = str_replace('/' . $config['style'] . '/', '/' . trim($depend_on) . '/', $tpl_path);
		}
	
		$tpl_code = file_get_contents($tpl_path);
		$tpl_code = preg_replace("/\n[\n\r\s\t]*/", '', $tpl_code);//remove extra spaces
		$matches = preg_match_all('#<!-- BEGIN (.*?) -->(.*?)<!-- END (?:.*?) -->#', $tpl_code, $match);
		
		$boxes = array();
		for ($i = 0; $i < $matches; $i++)
		{
			if (empty($match[1][$i]))
			{
				continue;//it's empty , let's leave it
			}

			$boxes[$match[1][$i]] = $match[2][$i];
		}
	}

	//extra value 
	$extra += array(
				'siteurl' => $config['siteurl'],
				'sitename' => $config['sitename'],
			);

	//return compiled value
	$return = $boxes[$box_name];
	foreach($extra as $var=>$val)
	{
		$return = preg_replace('/{' . $var . '}/', $val, $return);
	}

	/*
	 * We add this hook here so you can subtitue you own vars
	 * and even add your own boxes to this template.
	 */
	($hook = kleeja_run_hook('get_up_tpl_box_func')) ? eval($hook) : null; //run hook

	return $return;
}


/**
* Extract info of a style
*/
function kleeja_style_info($style_name)
{
	$inf_path = PATH . 'styles/' . $style_name . '/info.txt';

	//is info.txt exists or not
	if(!file_exists($inf_path))
	{
		return false;
	}

	$inf_c = file_get_contents($inf_path);
	//some ppl will edit this file with notepad or even with office word :)
	$inf_c = str_replace(array("\r\n", "\r"), array("\n", "\n"), $inf_c);

	//as lines
	$inf_l = @explode("\n", $inf_c);
	$inf_l = array_map('trim', $inf_l);

	$inf_r = array();
	foreach($inf_l as $m)
	{
		//comments
		if(isset($m[0]) && $m[0] == '#' || trim($m) == '')
		{
			continue;
		}

		$t = array_map('trim', @explode('=', $m, 2));
		# ':' mean ummm, mean something secondary as in sub-array
		if(strpos($t[0], ':') !== false)
		{
			$t_t0 = array_map('trim', @explode(':', $t[0]));
			$inf_r[$t_t0[0]][$t_t0[1]] = $t[1];
		}
		else
		{
			$inf_r[$t[0]] = $t[1];
		}
	}

	($hook = kleeja_run_hook('kleeja_style_info_func')) ? eval($hook) : null; //run hook

	return $inf_r;
}


/**
* Browser detection
* returns whether or not the visiting browser is the one specified [part of kleeja style system]
* i.e. is_browser('ie6') -> true or false
* i.e. is_browser('ie, opera') -> true or false
*/
function is_browser($b)
{
	//is there , which mean -OR-
	if(strpos($b, ',') !== false)
	{
		$e = explode(',', $b);
		foreach($e as $n)
		{
			if(is_browser(trim($n)))
			{
				return true;
			}
		}

		return false;
	}
	
	//if no agent, let's take the worst case
	$u_agent = (!empty($_SERVER['HTTP_USER_AGENT'])) ? htmlspecialchars((string) $_SERVER['HTTP_USER_AGENT']) : (function_exists('getenv') ? getenv('HTTP_USER_AGENT') : '');
	$t = trim(preg_replace('/[^a-z]/', '', $b));
	$r = trim(preg_replace('/[a-z]/', '', $b));

	$return = false;
	switch($t)
	{
		case 'ie':
			$return = strpos(strtolower($u_agent), trim('msie ' . $r)) !== false ? true : false;
		break;
		case 'firefox':
			$return = strpos(str_replace('/', ' ', strtolower($u_agent)), trim('firefox ' . $r)) !== false ? true : false;
		break;
		case 'safari':
			$return = strpos(strtolower($u_agent), trim('safari/' . $r)) !== false ? true : false;
		break;
		case 'chrome':
			$return = strpos(strtolower($u_agent), trim('chrome ' . $r)) !== false ? true : false;
		break;
		case 'flock':
			$return = strpos(strtolower($u_agent), trim('flock ' . $r)) !== false ? true : false;
		break;
		case 'opera':
			$return = strpos(strtolower($u_agent), trim('opera ' . $r)) !== false ? true : false;
		break;
		case 'konqueror':
			$return = strpos(strtolower($u_agent), trim('konqueror/' . $r)) !== false ? true : false;
		break;
		case 'mozilla':
			$return = strpos(strtolower($u_agent), trim('gecko/' . $r)) !== false ? true : false;
		break;
		case 'webkit':
			$return = strpos(strtolower($u_agent), trim('applewebkit/' . $r)) !== false ? true : false;
		break;
		/**
		 * Mobile Phones are so popular those days, so we have to support them ...
		 * This is still in our test lab.
		 * @see http://en.wikipedia.org/wiki/List_of_user_agents_for_mobile_phones
		 **/
		case 'mobile':
			$mobile_agents = array('iPhone;', 'iPod;', 'blackberry', 'Android', 'HTC' , 'IEMobile', 'LG/', 'LG-',
									'LGE-', 'MOT-', 'Nokia', 'SymbianOS', 'nokia_', 'PalmSource', 'webOS', 'SAMSUNG-', 
									'SEC-SGHU', 'SonyEricsson', 'BOLT/', 'Mobile Safari', 'Fennec/', 'Opera Mini');
			$return = false;
			foreach($mobile_agents as $agent)
			{
				if(strpos($u_agent, $agent) !== false)
				{
					$return = true;
					break;
				}
			}
		break;
	}
    
	($hook = kleeja_run_hook('is_browser_func')) ? eval($hook) : null; //run hook
    return $return;
}


/**
* Converting array to JSON format, nested arrays not supported
*/
function generate_json($array)
{
	$json = '';
	$json_escape = array(
		array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'),
		array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"')
	);

	foreach($array as $key=>$value)
	{
		$json .= ($json != '' ? ', ' : '') . '"' . $key . '":' . 
				(preg_match('^[0-9]+$', $value) ? $v : '"' . str_replace($json_escape[0], $json_escape[1], $value) . '"');  
	}

	return '{' . $json . '}';
}

/**
* Send an answer for ajax request
*/
function echo_ajax($code_number, $content, $menu = '')
{
	global $SQL;
	$SQL->close();

	exit(generate_json(array('code' => $code_number, 'content' => $content, 'menu' => $menu)));
}


/**
* Send an answer for ajax request [ARRAY]
*/
function echo_array_ajax($array)
{
	global $SQL;
	$SQL->close();
    
    //generate_json has some bugs so I will use json_encode insted :[
	exit(@json_encode($array));
}

/**
* show date in a human-readable-text
*/
define('TIME_FORMAT', 'd-m-Y h:i a'); # to be moved to configs later
function kleeja_date($time, $human_time = true, $format = false)
{
	global $lang, $config;

	if((time() - $time > (86400 * 9)) || $format || !$human_time)
	{
		$format = !$format ? TIME_FORMAT : $format;
		$time	= $time + ((int) $config['time_zone']*60*60);
		return str_replace(array('am', 'pm'), array($lang['TIME_AM'], $lang['TIME_PM']), gmdate($format, $time));
	}

	$lengths	= array("60","60","24","7","4.35","12","10");
	$now		= time();
	$difference	= ($now > $time) ? $now - $time :  $time - $now;
	$tense		= ($now > $time) ? $lang['W_AGO'] : $lang['W_FROM'];
	for($j = 0; $difference >= $lengths[$j] && $j < sizeof($lengths)-1; $j++)
	{
		$difference /= $lengths[$j];
	}
	$difference = round($difference);
	$return = $difference;	
	if($difference != 1)
	{
		if($difference == 2)
		{
			$return = $lang['W_PERIODS2'][$j];
		}
		else
		{		
			$return = $difference . ' ' . ($difference > 10 ? $lang['W_PERIODS'][$j] :  $lang['W_PERIODS_P'][$j]);
		}
	}
	else
	{
		$return = $lang['W_PERIODS'][$j];
	}

	$return = $lang['W_FROM'] .  ' ' . $return;

	return $return;
}


/*
 * World Time Zones
 */
function time_zones()
{
	return array(
		'Kwajalein' => -12.00,
		'Pacific/Midway' => -11.00,
		'Pacific/Honolulu' => -10.00,
		'America/Anchorage' => -9.00,
		'America/Los_Angeles' => -8.00,
		'America/Denver' => -7.00,
		'America/Tegucigalpa' => -6.00,
		'America/New_York' => -5.00,
		'America/Caracas' => -4.30,
		'America/Halifax' => -4.00,
		'America/St_Johns' => -3.30,
		'America/Argentina/Buenos_Aires' => -3.00,
		'America/Sao_Paulo' => -3.00,
		'Atlantic/South_Georgia' => -2.00,
		'Atlantic/Azores' => -1.00,
		'Europe/Dublin' => 0,
		'Europe/Belgrade' => 1.00,
		'Europe/Minsk' => 2.00,
		'Asia/Riyadh' => 3.00,
		'Asia/Tehran' => 3.30,
		'Asia/Muscat' => 4.00,
		'Asia/Yekaterinburg' => 5.00,
		'Asia/Kolkata' => 5.30,
		'Asia/Katmandu' => 5.45,
		'Asia/Dhaka' => 6.00,
		'Asia/Rangoon' => 6.30,
		'Asia/Krasnoyarsk' => 7.00,
		'Asia/Brunei' => 8.00,
		'Asia/Seoul' => 9.00,
		'Australia/Darwin' => 9.30,
		'Australia/Canberra' => 10.00,
		'Asia/Magadan' => 11.00,
		'Pacific/Fiji' => 12.00,
		'Pacific/Tongatapu' => 13.00
	);
}
