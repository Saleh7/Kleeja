<?php
/**
*
* @package Kleeja_up_helpers
* @version $Id: KljUploader.php 2002 2012-09-18 04:47:35Z saanina $
* @copyright (c) 2007-2012 Kleeja.com
* @license ./docs/license.txt
*
*/

//no for directly open
if (!defined('IN_COMMON'))
{
	exit();
}

#
# Other helpers that will be used at uploading in Kleeja
# 
#



/**
 * checking the safety and validty of sub-extension of given file 
 * 
 */
function ext_check_safe($filename)
{
	#bad files extensions
	$not_allowed =	array('php', 'php3' ,'php5', 'php4', 'asp' ,'shtml' , 'html' ,'htm' ,'xhtml' ,'phtml', 'pl', 'cgi', 'htaccess', 'ini');
	
	#let split the file name, suppose it filename.gif.php
	$tmp	= explode(".", $filename);

	#if it's less than 3, that its means normal
	if(sizeof($tmp) < 3)
	{
		return true;
	}

	$before_last_ext = $tmp[sizeof($tmp)-2];

	#in the bad extenion, return false to tell him
	if (in_array(strtolower($before_last_ext), $not_allowed)) 
	{
		return false;
	}
	else
	{
		return true;
	}
}


/**
 * create htaccess files for uploading folder
 */
function generate_safety_htaccess($folder)
{
	#data for the htaccess
	$htaccess_data = "<Files ~ \"^.*\.(php|php*|cgi|pl|phtml|shtml|sql|asp|aspx)\">\nOrder allow,deny\nDeny from all\n</Files>\n<IfModule mod_php4.c>\nphp_flag engine off\n</IfModule>\n<IfModule mod_php5.c>\nphp_flag engine off\n</IfModule>\nRemoveType .php .php* .phtml .pl .cgi .asp .aspx .sql";
	
	#generate the htaccess
	$fi		= @fopen($folder . "/.htaccess", "w");
	$fi2	= @fopen($folder . "/thumbs/.htaccess","w");
	$fy		= @fwrite($fi, $htaccess_data);
	$fy2	= @fwrite($fi2, $htaccess_data);
}

/**
 * create an uploading folder
 */
function make_folder($folder)
{
	#try to make a new upload folder 
	$f = @mkdir($folder);
	$t = @mkdir($folder . '/thumbs');

	if($f && $t)
	{
		#then try to chmod it to 777
		$chmod	= @chmod($folder, 0777);
		$chmod2	= @chmod($folder . '/thumbs/', 0777);	

		#make it safe
		generate_safety_htaccess($folder);

		#create empty index so nobody can see the contents
		$fo		= @fopen($folder . "/index.html","w");
		$fo2	= @fopen($folder . "/thumbs/index.html","w");
		$fw		= @fwrite($fo,'<a href="http://kleeja.com"><p>KLEEJA ..</p></a>');
		$fw2	= @fwrite($fo2,'<a href="http://kleeja.com"><p>KLEEJA ..</p></a>');
	}

	return $f && $t ? true : false;	
}

/**
 * Change the file name depend on given decoding type
 */
function change_filename_decoding($filename, $i_loop, $ext, $decoding_type)
{
	$return = '';

	#change it, time..
	if($decoding_type == "time")
	{
		list($usec, $sec) = explode(" ", microtime());
		$extra = str_replace('.', '', (float)$usec + (float)$sec);
		$return = $extra . $i_loop . '.' . $ext;
	}
	# md5
	elseif($decoding_type == "md5")
	{
		list($usec, $sec) = explode(" ", microtime());
		$extra	= md5(((float)$usec + (float)$sec) . $filename);
		$extra	= substr($extra, 0, 12);
		$return	= $extra . $i_loop . "." . $ext;
	}
	# exists before, change it a little
	elseif($decoding_type == 'exists')
	{
		$return = substr($filename, 0, -(strlen($ext)+1)) . '_' . substr(md5($rand . time() . $i_loop), rand(0, 20), 5) . '.' . $ext;
	}
	#nothing
	else
	{
		$filename = substr($filename, 0, -(strlen($ext)+1));
		$return = preg_replace('/[,.?\/*&^\\\$%#@()_!|"\~\'><=+}{; ]/', '-', $filename) . '.' . $ext;
		$return = preg_replace('/-+/', '-', $return);
	}

	($hook = kleeja_run_hook('change_filename_decoding_func')) ? eval($hook) : null; //run hook

	return $return;
}

/**
 * Change the file name depend on used templates {rand:..} {date:..}
 */
function change_filename_templates($filename)
{
	#random number...
	if (preg_match("/{rand:([0-9]+)}/i", $filename, $m))
	{
		$filename = preg_replace("/{rand:([0-9]+)}/i", substr(md5(time()), 0, $m[1]), $filename);
	}
	
	#current date
	if (preg_match("/{date:([a-zA-Z-_]+)}/i", $filename, $m))
	{
		$filename = preg_replace("/{date:([a-zA-Z-_]+)}/i", date($m[1]), $filename);
	}
	
	($hook = kleeja_run_hook('change_filename_templates_func')) ? eval($hook) : null; //run hook

	return $filename;
}


function check_mime_type($mime, $this_is_image,$file_path)
{
	//This code for images only
	//it's must be improved for all files in future !
	if($this_is_image == false)
	{
		return true;
	}

	$return = false;
	$s_items = @explode(':', 'image:png:jpg:gif:bmp:jpeg');
	foreach($s_items as $r)
	{
		if(strpos($mime, $r) !== false)
		{
			$return = true;
			break;
		}
	}

	//onther check
	//$w = @getimagesize($file_path);
	//$return =  ($w && (strpos($w['mime'], 'image') !== false)) ? true : false;

	//another check
	if($return == true)
	{
		if(@kleeja_filesize($file_path) > 4*(1000*1024))
		{
			return true;
		}
		
		//check for bad things inside files ...
		//<.? i cant add it here cuz alot of files contain it 
		$maybe_bad_codes_are = array('<script', 'zend', 'base64_decode');
		
		if(!($data = @file_get_contents($file_path)))
		{
			return true;
		}
		
		foreach($maybe_bad_codes_are as $i)
		{
			if(strpos(strtolower($data), $i) !== false)
			{
				$return = false;
				break;
			}
		}
	}

	($hook = kleeja_run_hook('kleeja_check_mime_func')) ? eval($hook) : null; //run hook
	
	return $return;
}


/**
 * to prevent flooding at uploading  
 */
function user_is_flooding($user_id = '-1')
{
	global $SQL, $dbprefix, $config;

	$return = 'empty';

	($hook = kleeja_run_hook('user_is_flooding_func')) ? eval($hook) : null; //run 

	if($return != 'empty')
	{
		return $return;
	}

	//if the value is zero (means that the function is disabled) then return false immediately
	if(($user_id == '-1' && $config['guestsectoupload'] == 0) || $user_id != '-1' && $config['usersectoupload'] == 0)
	{
		return false;
	}

	//In my point of view I see 30 seconds is not bad rate to stop flooding .. 
	//even though this minimum rate sometime isn't enough to protect Kleeja from flooding attacks 
	$time = time() - ($user_id == '-1' ? $config['guestsectoupload'] : $config['usersectoupload']); 

	$query = array(
					'SELECT'	=> 'f.time',
					'FROM'		=> "{$dbprefix}files f",
					'WHERE'     => 'f.time >= ' . $time . ' AND f.user_ip = \'' .  $SQL->escape(get_ip()) . '\'',
				);

	if ($SQL->num_rows($SQL->build($query)))
	{
		return true;
	}

	return false;
}
