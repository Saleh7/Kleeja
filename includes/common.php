<?php
/**
*
* @package Kleeja
* @version $Id: common.php 2071 2012-10-21 06:40:50Z saanina $
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/

// not for directly open
if (!defined('IN_INDEX'))
{
	exit();
}

//we are in the common file
define ('IN_COMMON', true);

//
//Development stage;
//if this enabled, KLeeja will treats you as a developer, things
//disabled for users, will be enabled for you
//
//define('DEV_STAGE', true);

// Report all errors, except notices
defined('DEV_STAGE') ? @error_reporting( E_ALL ) : @error_reporting(E_ALL ^ E_NOTICE);

//filename of config.php
define('KLEEJA_CONFIG_FILE', 'config.php');


if(@extension_loaded('apc'))
{
	define('APC_CACHE', true);
}

//if sessions is started before, let's destroy it!
if(isset($_SESSION))
{
	@session_unset(); // fix bug with php4
	@session_destroy();
}

// start session
$s_time = 86400 * 2; // 2 : two days
if(defined('IN_ADMIN'))
{
	//session_set_cookie_params($admintime);
	if (function_exists('session_set_cookie_params'))
	{
    	session_set_cookie_params($adm_time, $adm_path);
  	}
	elseif (function_exists('ini_set'))
	{
    	ini_set('session.cookie_lifetime', $adm_time);
    	ini_set('session.cookie_path', $adm_path);
  	}
}

if(function_exists('ini_set'))
{
	if (version_compare(PHP_VERSION, '5.0.0', 'ge') && substr(PHP_OS, 0 ,3) != 'WIN')
	{
		ini_set('session.hash_function', 1);
		ini_set('session.hash_bits_per_character', 6);
	}
	ini_set('session.use_only_cookies', false);
	ini_set('session.auto_start', false);
	ini_set('session.use_trans_sid', true);
	ini_set('session.cookie_lifetime', $s_time);
	ini_set('session.gc_maxlifetime', $s_time);
	//& is not valid xhtml, so we replaced with &amp;
	ini_set('arg_separator.output', '&amp;');
	//
	//this will help people with some problem with their sessions path
	//
	//session_save_path('./cache/');
}

/**
* functions for start
*/
function kleeja_show_error($errno, $errstr = '', $errfile = '', $errline = '')
{
	switch ($errno)
	{
		case E_NOTICE: case E_WARNING: case E_USER_WARNING: case E_USER_NOTICE: case E_STRICT: break;
		default:
			header('HTTP/1.1 503 Service Temporarily Unavailable');
			echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">' . "\n<head>\n";
			echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />' . "\n";
			echo '<title>Kleeja Error</title>' . "\n" . '<style type="text/css">' . "\n\t";
			echo '.error {color: #333;background:#ffebe8;float:left;width:73%;text-align:left;margin-top:10px;border: 1px solid #dd3c10; padding: 10px;font-family:tahoma,arial;font-size: 12px;}' . "\n";
			echo "</style>\n</head>\n<body>\n\t" . '<div class="error">' . "\n\n\t\t<h2>Kleeja error  : </h2><br />" . "\n";
			echo "\n\t\t<strong> [ " . $errno . ':' . basename($errfile) . ':' . $errline . ' ] </strong><br /><br />' . "\n\t\t" . $errstr . "\n\t";
			echo "\n\t\t" . '<br /><br /><small>Visit <a href="http://www.kleeja.com/" title="kleeja">Kleeja</a> Website for more details.</small>' . "\n\t";
			echo "</div>\n</body>\n</html>";
			global $SQL;
			if(isset($SQL))
			{
				@$SQL->close();
			}
			exit;
		break;
    }
}
set_error_handler('kleeja_show_error');

function stripslashes_our($value)
{
	return is_array($value) ? array_map('stripslashes_our', $value) : stripslashes($value);
}
function kleeja_clean_string($value)
{
	if(is_array($value))
	{
		return array_map('kleeja_clean_string', $value);
	}
	$value = str_replace(array("\r\n", "\r", "\0"), array("\n", "\n", ''), $value);
	return $value;
}
//unsets all global variables set from a superglobal array
function unregister_globals()
{
	$register_globals = @ini_get('register_globals');
	if ($register_globals === "" || $register_globals === "0" || strtolower($register_globals) === "off")
	{
		return;
	}

	if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS']))
	{
		exit('Kleeja is queen of candies ...');
	}

	$input = array_merge($_GET, $_POST, $_COOKIE, $_SERVER, $_ENV, $_FILES, isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array());
	$no_unset = array('GLOBALS', '_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');

	foreach ($input as $k => $v)
	{
		if (!in_array($k, $no_unset) && isset($GLOBALS[$k]))
		{
			unset($GLOBALS[$k]);
			unset($GLOBALS[$k]);//make sure
		}
	}

	unset($input);
}
//time of start and end and wutever
function get_microtime()
{
	list($usec, $sec) = explode(' ', microtime());	return ((float)$usec + (float)$sec);
}
//is bot ?
function is_bot($bots = array('googlebot', 'bing' ,'msnbot'))
{
	if(isset($_SERVER['HTTP_USER_AGENT']))
	{
		return preg_match('/(' . implode('|', $bots) . ')/i', ($_SERVER['HTTP_USER_AGENT'] ? $_SERVER['HTTP_USER_AGENT'] : @getenv('HTTP_USER_AGENT'))) ? true : false;
	}
	return false;
}

$IS_BOT = is_bot();
$starttm = get_microtime();

//Kill globals varibles
unregister_globals();

if(!is_bot())
{
	@session_name('sid');
	@session_start();
}

//try close it
if (@get_magic_quotes_runtime())
{
	@set_magic_quotes_runtime(0);
}

if(@get_magic_quotes_gpc())
{
	$_GET	= stripslashes_our($_GET);
	$_POST	= stripslashes_our($_POST);
	$_COOKIE	= stripslashes_our($_COOKIE);
	$_REQUEST	= stripslashes_our($_REQUEST);//we use this sometime
}

//clean string and remove bad chars
$_GET		= kleeja_clean_string($_GET);
$_POST		= kleeja_clean_string($_POST);
$_REQUEST	= kleeja_clean_string($_REQUEST);
$_COOKIE		= kleeja_clean_string($_COOKIE);


//path
if(!defined('PATH'))
{
	if(!defined('__DIR__'))
	{
		define('__DIR__', dirname(__FILE__));
	}
	define('PATH', str_replace(DIRECTORY_SEPARATOR . 'includes', '', __DIR__) . DIRECTORY_SEPARATOR);
}

// no config
if (!file_exists(PATH . KLEEJA_CONFIG_FILE))
{
	header('Location: ./install/index.php');
	exit;
}

// there is a config
include (PATH . KLEEJA_CONFIG_FILE);

//no enough data
if (!$dbname || !$dbuser)
{
	header('Location: ./install/index.php');
	exit;
}

//include files .. & classes ..
$root_path = PATH;
$db_type = isset($db_type) ? $db_type : 'mysql';

include (PATH . 'includes/functions_alternative.php');
include (PATH . 'includes/version.php');

switch ($db_type)
{
	case 'mysqli':
		include (PATH . 'includes/mysqli.php');
	break;
	default:
		include (PATH . 'includes/mysqli.php');
}
include (PATH . 'includes/style.php');
include (PATH . 'includes/usr.php');
include (PATH . 'includes/pager.php');
include (PATH . 'includes/functions.php');
include (PATH . 'includes/functions_display.php');
if(defined('IN_ADMIN'))
{
	include (PATH . 'includes/functions_adm.php');
}
else
{
	include (PATH . 'includes/KljUploader.php');
	$kljup	= new KljUploader;
}

//fix intregation problems
if(empty($script_encoding))
{
	$script_encoding = 'windows-1256';
}

// start classes ..
$SQL	= new SSQL($dbserver, $dbuser, $dbpass, $dbname);
//no need after now
unset($dbpass);
$tpl	= new kleeja_style;
$usrcp	= new usrcp;

//then get caches
include (PATH . 'includes/cache.php');

//getting dynamic configs
$query = array(
				'SELECT'	=> 'c.name, c.value',
				'FROM'		=> "{$dbprefix}config c",
				'WHERE'		=> 'c.dynamic = 1',
			);

$result = $SQL->build($query);

while($row=$SQL->fetch_array($result))
{
	$config[$row['name']] = $row['value'];
}

$SQL->freeresult($result);

//check user or guest
$usrcp->kleeja_check_user();

//+ configs of the current group
$config = array_merge($config, (array) $d_groups[$usrcp->group_id()]['configs']);



//no tpl caching in dev stage
if(defined('DEV_STAGE'))
{
	$tpl->caching = false;
}

//kleeja session id
$klj_session = $SQL->escape(session_id());


//admin path
$adminpath = 'admin/index.php';
!defined('ADMIN_PATH') ? define('ADMIN_PATH', $config['siteurl'] . $adminpath) : null;

//site url must end with /
if($config['siteurl'])
{
	$config['siteurl'] = ($config['siteurl'][strlen($config['siteurl'])-1] != '/') ? $config['siteurl'] . '/' : $config['siteurl'];
}

// for gzip : php.net
//fix bug # 181
//we stopped this in development stage cuz it's will hide notices

//header
header('Content-type: text/html; charset=UTF-8');
header('Cache-Control: private, no-cache="set-cookie"');
header('Expires: 0');
header('Pragma: no-cache');
header('x-frame-options: SAMEORIGIN');
header('x-xss-protection: 1; mode=block');

//check lang
if(!$config['language'] || empty($config['language']))
{
	if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && strlen($_SERVER['HTTP_ACCEPT_LANGUAGE']) > 2)
	{
		$config['language'] = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
		if(!file_exists(PATH . 'lang/' . $config['language'] . '/common.php'))
		{
			$config['language'] = 'en';
		}
	}
}

//check style
if(!$config['style'] || empty($config['style']))
{
	$config['style'] = 'default';
}

//check h_kay, important for kleeja
if(empty($config['h_key']))
{
	$h_k = sha1(microtime() . rand(0, 100));
	if(!update_config('h_key', $h_k))
	{
		add_config('h_key', $h_k);
	}
}

//Global vars for Kleeja
$STYLE_PATH				= $config['siteurl'] . 'styles/' . (trim($config['style_depend_on']) == '' ? $config['style'] : $config['style_depend_on']) . '/';
$THIS_STYLE_PATH		= $config['siteurl'] . 'styles/' . $config['style'] . '/';
$THIS_STYLE_PATH_ABS	= PATH . 'styles/' . $config['style'] . '/';
$STYLE_PATH_ADMIN 		= $config['siteurl'] . 'admin/'. (is_browser('mobile') || defined('IN_MOBILE') ? 'toffee/' : 'toffee/');
$STYLE_PATH_ADMIN_ABS	= PATH . 'admin/'. (is_browser('mobile') || defined('IN_MOBILE') ? 'toffee/' : 'toffee/');
$DEFAULT_PATH_ADMIN_ABS = PATH . 'admin/toffee/';
$DEFAULT_PATH_ADMIN		= $config['siteurl'] . 'admin/toffee/';


//get languge of common
get_lang('common');
//ban system
get_ban();

/** Enable this if you want it !
$load = 0;
if (stristr(PHP_OS, 'win'))
{
	ob_start();
	passthru('typeperf -sc 1 "\processor(_total)\% processor time"',$status);
	$content = ob_get_contents();
	ob_end_clean();
	if ($status === 0)
	{
		if (preg_match("/\,\"([0-9]+\.[0-9]+)\"/",$content,$load))
		{
			$load = $load[1];
		}
	}
	echo $load;
}
else
{
	if((function_exists('sys_getloadavg') && $load = sys_getloadavg()) || ($load = explode(' ', @file_get_contents('/proc/loadavg'))))
	{
		$load = $load[0];
	}
}

if ($load > 80  && !defined('IN_ADMIN') && !defined('IN_LOGIN'))
{
	#tell the BOTs to not cache this page in thier search engines
	if(is_bot())
	{
		header('HTTP/1.1 503 Too busy, try again later');
	}
	kleeja_info($lang['LOAD_IS_HIGH_NOW'], $lang['SITE_CLOSED']);
}
*/


//install.php exists
if (file_exists(PATH . 'install') && !defined('IN_ADMIN') && !defined('IN_LOGIN') && !defined('DEV_STAGE'))
{
	#Different message for admins! delete install folder
	kleeja_info((user_can('enter_acp') ? $lang['DELETE_INSTALL_FOLDER'] : $lang['WE_UPDATING_KLEEJA_NOW']), $lang['SITE_CLOSED']);
}


//site close ..
$login_page = '';
if ($config['siteclose'] == '1' && !user_can('enter_acp') && !defined('IN_LOGIN') && !defined('IN_ADMIN'))
{
	//if download, images ?
	if(defined('IN_DOWNLOAD') && (isset($_GET['img']) || isset($_GET['thmb']) || isset($_GET['thmbf']) || isset($_GET['imgf'])))
	{
		@$SQL->close();
		$fullname = "images/site_closed.jpg";
		$filesize = filesize($fullname);
		header("Content-length: $filesize");
		header("Content-type: image/jpg");
		readfile($fullname);
		exit;
	}

	// Send a 503 HTTP response code to prevent search bots from indexing the maintenace message
	header('HTTP/1.1 503 Service Temporarily Unavailable');
	kleeja_info($config['closemsg'], $lang['SITE_CLOSED']);
}

//exceed total size
if (($stat_sizes >= ($config['total_size'] *(1048576))) && !defined('IN_LOGIN') && !defined('IN_ADMIN'))// convert megabytes to bytes
{
	// Send a 503 HTTP response code to prevent search bots from indexing the maintenace message
	header('HTTP/1.1 503 Service Temporarily Unavailable');
	kleeja_info($lang['SIZES_EXCCEDED'], $lang['STOP_FOR_SIZE']);
}


kleeja_detecting_bots();

//check for page numbr
if(empty($perpage) || intval($perpage) == 0)
{
	$perpage = 14;
}

//captch file
$captcha_file_path = $config['siteurl'] . 'ucp.php?go=captcha';

($hook = kleeja_run_hook('end_common')) ? eval($hook) : null; //run hook


#<-- EOF
