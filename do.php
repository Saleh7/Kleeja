<?php
/**
*
* @package Kleeja
* @version $Id: do.php 1260 2009-11-23 22:57:20Z saanina $
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/


define ('IN_INDEX' , true);
define ('IN_DOWNLOAD', true);

include ('includes/common.php');

($hook = kleeja_run_hook('begin_download_page')) ? eval($hook) : null; //run hook

//
//page of wait downloading files
//
if (isset($_GET['id']) || isset($_GET['filename']))
{
	($hook = kleeja_run_hook('begin_download_id_filename')) ? eval($hook) : null; //run hook

	$query = array(
					'SELECT'	=> 'f.id, f.real_filename, f.name, f.folder, f.size, f.time, f.uploads, f.type',
					'FROM'		=> "{$dbprefix}files f",
				);

	#if user system is default, we use users table
	if((int) $config['user_system'] == 1)
	{
		$query['SELECT'] .= ', u.name AS fusername, u.id AS fuserid';
		$query['JOINS']	=	array(
									array(
										'LEFT JOIN'	=> "{$dbprefix}users u",
										'ON'		=> 'u.id=f.user'
									)
								);
	}

	if (isset($_GET['filename']))
	{
		$filename_l  = (string) $SQL->escape($_GET['filename']);
		if(isset($_GET['x']))
		{ 
			$query['WHERE']	= "f.name='" . $filename_l . '.' . $SQL->escape($_GET['x']) . "'";
		}
		else 
		{
			$query['WHERE']	= "f.name='" . $filename_l . "'";
		}
	}
	else
	{
		$id_l = intval($_GET['id']);
		$query['WHERE']	= "f.id=" . $id_l;
	}

	($hook = kleeja_run_hook('qr_download_id_filename')) ? eval($hook) : null; //run hook
	$result	= $SQL->build($query);

	if ($SQL->num_rows($result) != 0)
	{
		while($row=$SQL->fetch_array($result))
		{
			@extract ($row);
		}

		$SQL->freeresult($result);

		// some vars
		$fname	 	= $name;
		$fname2 	= str_replace('.', '-', htmlspecialchars($name));
		$name 		= $real_filename != '' ? str_replace('.' . $type, '', htmlspecialchars($real_filename)) : $name;
		$name		= (strlen($name) > 70) ? substr($name, 0, 70) . '...' : $name;
		$fusername	= $config['user_system'] == 1 && $fuserid > -1 ? $fusername : false;
		$userfolder	= $config['siteurl'] . ($config['mod_writer'] ? 'fileuser-' . $fuserid . '.html' : 'ucp.php?go=fileuser&amp;id=' .  $fuserid);

		if (isset($_GET['filename']))
		{
			$url_file	= ($config['mod_writer']) ? $config['siteurl'] . "downf-" . $fname2 . ".html" : $config['siteurl'] . "do.php?downf=" . $fname;
		}
		else
		{
			$url_file	= ($config['mod_writer']) ? $config['siteurl'] . "down-" . $id . ".html" : $config['siteurl'] . "do.php?down=" . $id;
		}

		if(!empty($config['livexts']))
		{
			$livexts = explode(',',$config['livexts']);
			if(in_array($type,$livexts))
			{
				if (isset($_GET['filename']))
				{
					$url_filex	= $config['mod_writer'] ? $config['siteurl'] . "downexf-" . $fname2 . ".html" : $config['siteurl'] . "do.php?downexf=" . $fname;
				}
				else
				{
					$url_filex	= $config['mod_writer'] ? $config['siteurl'] . "downex-" . $id . ".html" : $config['siteurl'] . "do.php?downex=" . $id;
				}
						
				redirect($url_filex, false);
			}
		}

		$REPORT		= ($config['mod_writer']) ?  $config['siteurl'] . "report-" . $id . ".html" :  $config['siteurl'] . "go.php?go=report&amp;id=" . $id;
		$seconds_w	= $config['sec_down'];
		$time		= kleeja_date($time);
		$size		= Customfile_size($size);

		$file_ext_icon = file_exists('images/filetypes/' . $type . '.png') ? 'images/filetypes/' . $type . '.png' : 'images/filetypes/file.png';
		$sty		= 'download';
		$title 		=  $name . ' ' . $lang['DOWNLAOD'];
	}
	else
	{
		//file not exists
		($hook = kleeja_run_hook('not_exists_qr_downlaod_file')) ? eval($hook) : null; //run hook
		kleeja_err($lang['FILE_NO_FOUNDED']);
	}

	($hook = kleeja_run_hook('b4_showsty_downlaod_id_filename')) ? eval($hook) : null; //run hook

	//add http reffer to session to prevent errors with some browsers ! 
	if (isset($_GET['filename']))
	{
		$_SESSION['HTTP_REFERER'] = $config['siteurl'] . ($config['mod_writer'] ? "downloadf" . $fname . ".html" : "do.php?filename=" . $fname);
	}
	else
	{
		$_SESSION['HTTP_REFERER'] = $config['siteurl'] . ($config['mod_writer'] ? "download" . $id . ".html" : "do.php?id=" . $id);
	}

	// show style ...
	Saaheader($title);
	echo $tpl->display($sty);
	Saafooter();
}

//
//download file 
//
# guidline for _get variable names
# down: [0-9], default, came from do.php?id=[0-9]
# downf: [a-z0-9].[ext], came from do.php?filename=[a-z0-9].[ext]
#
# img: [0-9], default, direct from do.php?img=[0-9]
# imgf: [a-z0-9].[ext], direct from do.php?imgf=[a-z0-9].[ext]
#
# thmb: [0-9], default, direct from do.php?thmb=[0-9]
# thmbf: [a-z0-9].[ext], direct from do.php?thmbf=[a-z0-9].[ext]
#
# live extensions feature uses downex, downexf as in down & downf  
#
# x : used only for html links, where x = extension, downf is filename without extension

else if (isset($_GET['down']) || isset($_GET['downf']) ||
		isset($_GET['img']) || isset($_GET['imgf']) ||  
		isset($_GET['thmb']) || isset($_GET['thmbf']) || 
		isset($_GET['downex']) || isset($_GET['downexf']))
{
	($hook = kleeja_run_hook('begin_down_go_page')) ? eval($hook) : null; //run hook	


	kleeja_log('downloading file start -  (' . implode(', ', $_GET) . ') -> ' . $_SERVER['HTTP_REFERER']);

	//must know from where he came ! and stop him if not image
	//todo: if it's download manger, let's pass this
	if(isset($_GET['down']) || isset($_GET['downf']))
	{
		if(!isset($_SERVER['HTTP_REFERER']) || empty($_SERVER['HTTP_REFERER']))
		{
			if(function_exists('getenv'))
			{
				$_SERVER['HTTP_REFERER'] = getenv('HTTP_REFERER') ? getenv('HTTP_REFERER') : null;
			}

			if(!isset($_SERVER['HTTP_REFERER'])|| empty($_SERVER['HTTP_REFERER']))
			{
				if(isset($_SESSION['HTTP_REFERER']))
				{
					$_SERVER['HTTP_REFERER'] = $_SESSION['HTTP_REFERER'];
					unset($_SESSION['HTTP_REFERER']);
				}
			}
		}

		//if not from our site and the waiting page
		$isset_down_h = isset($_GET['downf']) && isset($_GET['x']) ? 'downloadf-' . htmlspecialchars($_GET['downf']) . '-' . htmlspecialchars($_GET['x']) . '.html' : (isset($_GET['down']) ? 'download' . htmlspecialchars($_GET['down']) . '.html' : '');
		$not_reffer = true;
		if(strpos($_SERVER['HTTP_REFERER'], $isset_down_h) !== false)
		{
			$not_reffer = false;
		}

		$isset_down = isset($_GET['downf']) ? 'do.php?filename=' . htmlspecialchars($_GET['downf']) : (isset($_GET['down']) ? 'do.php?id=' .htmlspecialchars($_GET['down']) : '');
		if(strpos($_SERVER['HTTP_REFERER'], $isset_down) !== false)
		{
			$not_reffer = false;
		}

		if(empty($_SERVER['HTTP_REFERER']) || strpos($config['siteurl'], str_replace(array('http://', 'www.'), '', htmlspecialchars($_SERVER['HTTP_REFERER']))))
		{
			$not_reffer = false;
		}

		if($not_reffer)
		{
			if(isset($_GET['downf']))
			{
				$go_to = $config['siteurl'] . ($config['mod_writer'] && isset($_GET['x']) ? "downloadf-" . htmlspecialchars($_GET['downf']) . '-' . htmlspecialchars($_GET['x']) . ".html" : "do.php?filename=" . htmlspecialchars($_GET['downf']));
			}
			else
			{
				$go_to = $config['siteurl'] . ($config['mod_writer'] ? 'download' . htmlspecialchars($_GET['down']) . '.html' : 'do.php?id=' . htmlspecialchars($_GET['down']));
			}

			redirect($go_to);
			$SQL->close();
			exit;
		}
	}

	//download by id or filename
	//is the comming variable is filename(filename123.gif) or id (123) ?
	$is_id_filename = (isset($_GET['downf']) || isset($_GET['imgf']) || isset($_GET['thmbf']) || isset($_GET['downexf'])) ? true : false;

	if($is_id_filename)
	{
		$var = isset($_GET['downf']) ? 'downf' : (isset($_GET['imgf']) ? 'imgf' : (isset($_GET['thmbf']) ? 'thmbf' : (isset($_GET['downexf']) ? 'downexf' : false)));

		#x, represent the extension, came from html links
		if(isset($_GET['x']) && $var)
		{
			$filename = $SQL->escape($_GET[$var]) . '.' . $SQL->escape($_GET['x']);
		}
		else
		{
			$filename = $SQL->escape($_GET[$var]);
		}
	}
	else
	{
		$id = isset($_GET['down']) ? intval($_GET['down']) : (isset($_GET['img']) ? intval($_GET['img']) : (isset($_GET['thmb']) ? intval($_GET['thmb']) : (isset($_GET['downex']) ? intval($_GET['downex']) : null)));
	}

	//is internet explore 8 ?
	$is_ie8 = is_browser('ie8');
	//is internet explore 6 ?
	$is_ie6 = is_browser('ie6');

	$livexts = explode(",", $config['livexts']);

	//get info file
	$query = array('SELECT'	=> 'f.id, f.name, f.real_filename, f.folder, f.type, f.size, f.time',
					'FROM'		=> "{$dbprefix}files f",
					'WHERE'		=> $is_id_filename ? "f.name='" . $filename . "'" .  (isset($_GET['downexf']) ? " AND f.type IN ('" . implode("', '", $livexts) . "')" : '') :
													'f.id=' . $id  . (isset($_GET['downex']) ? " AND f.type IN ('" . implode("', '", $livexts) . "')" : ''),
					);

	($hook = kleeja_run_hook('qr_down_go_page_filename')) ? eval($hook) : null; //run hook
	$result	= $SQL->build($query);

	$is_live = false;
	$pre_ext = array_pop(@explode('.', $filename));
	$is_image = in_array(strtolower(trim($pre_ext)), array('gif', 'jpg', 'jpeg', 'bmp', 'png')) ? true : false; 

	if ($SQL->num_rows($result) != 0)
	{
		while($row=$SQL->fetch_array($result))
		{
			$ii	= $row['id'];
			$n	= $row['name'];
			$rn	= $row['real_filename'];
			$t	= strtolower(trim($row['type']));
			$f	= $row['folder'];
			$ftime	= $row['time'];
			$d_size	= $row['size'];

			//img or not
			$is_image = in_array($t, array('gif', 'jpg', 'jpeg', 'bmp', 'png')) ? true : false; 
			//live url
			$is_live = in_array($t, $livexts) ? true : false; 
		}

		$SQL->freeresult($result);

		//check if the vistor is new in this page before updating kleeja counter
		if(!preg_match('/,' . $ii . ',/i', $usrcp->kleeja_get_cookie('oldvistor')))
		{
			//updates number of uploads ..
			$update_query = array(
									'UPDATE'=> "{$dbprefix}files",
									'SET'	=> 'uploads=uploads+1, last_down=' . time(),
									'WHERE'	=> $is_id_filename ? "name='" . $filename . "'" : 'id=' . $id,
								);

			($hook = kleeja_run_hook('qr_update_no_uploads_down')) ? eval($hook) : null; //run hook
			$SQL->build($update_query);

			//
			//Define as old vistor
			//if this vistor has other views then add this view too
			//old vistor just for 1 day
			//
			if($usrcp->kleeja_get_cookie('oldvistor'))
			{
				$usrcp->kleeja_set_cookie('oldvistor', $usrcp->kleeja_get_cookie('oldvistor') . $ii . ',', time()+86400);
			}
			else
			{
				//first time 
				$usrcp->kleeja_set_cookie('oldvistor', ',' . $ii . ',', time()+86400);
			}
		}
	}
	else
	{
		//not exists img or thumb
		if(isset($_GET['img']) || isset($_GET['thmb']) || isset($_GET['thmbf']) || isset($_GET['imgf']))
		{
			($hook = kleeja_run_hook('not_exists_qr_down_img')) ? eval($hook) : null; //run hook

			$f = 'images';
			$n = 'not_exists.jpg';

			//set image conditon on
			$is_image = true;

			//unset some var
			if(isset($_GET['thmb']) || isset($_GET['thmbf']))
			{
				unset($_GET['thmb'], $_GET['thmbf']);
			}
		}
		else
		{
			
			//not exists file
			($hook = kleeja_run_hook('not_exists_qr_down_file')) ? eval($hook) : null; //run hook
			kleeja_err($lang['FILE_NO_FOUNDED']);
		}
	}

	//downalod porcess
	$path_file = (isset($_GET['thmb']) || isset($_GET['thmbf']))  ? "./{$f}/thumbs/{$n}" : "./{$f}/{$n}";
	$chunksize = 1024*120; //1 kelobyte * 120 = 120kb that will send to user every loop
	$resuming_on = true;

	($hook = kleeja_run_hook('down_go_page')) ? eval($hook) : null; //run hook	

	# this is a solution to ignore downloading through the file, redirecct to the actual file
	# where you can add 'define("MAKE_DOPHP_301_HEADER", true);' in config.php to stop the load
	# if there is any.
	if(defined('MAKE_DOPHP_301_HEADER'))
	{
		header('HTTP/1.1 301 Moved Permanently');
		header('Location: ' . $path_file);
		exit;
	}

	//start download ,,
	if(!is_readable($path_file))
	{
		($hook = kleeja_run_hook('down_file_not_exists')) ? eval($hook) : null; //run hook
		if($is_image)
		{
			$path_file = 'images/not_exists.jpg';
		}
		else
		{
			big_error($lang['FILE_NO_FOUNDED'],  $lang['NOT_FOUND']);
		}
	}

	if(!($size = @kleeja_filesize($path_file)))
	{
		$size = $d_size;
	}

	$name = empty($rn) ? $n : $rn;

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

	//Figure out the MIME type (if not specified) 
	$ext		= array_pop(explode('.', $path_file));
	$mime_type	= get_mime_for_header($ext);

	if (@ob_get_length())
	{
		@ob_end_clean();
	}

	// required for IE, otherwise Content-Disposition may be ignored
	if(@ini_get('zlib.output_compression'))
	{
		@ini_set('zlib.output_compression', 'Off');
	}

	header('Date: ' . gmdate('D, d M Y H:i:s', $ftime) . ' GMT');
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $ftime) . ' GMT');
	header('Content-Encoding: none');
	header('Content-Disposition: ' . (($is_image || $is_live) ? 'inline' : 'attachment') . '; '  . $h_name);
	#if($is_image)
	#{
	#	header('Content-Transfer-Encoding: binary');
	#}

	if(!$is_image && !$is_live && $is_ie8)
	{
		header('X-Download-Options: noopen');
	}

	#header(($is_ie6 ? 'Expires: -1' : 'Expires: Mon, 26 Jul 1997 05:00:00 GMT'));	
	#(($is_ie8) ? '; authoritative=true; X-Content-Type-Options: nosniff;' : '')

	if (($pfile = @fopen($path_file, 'rb')) === false)
	{
		#so ... it's failed to open !
		header("HTTP/1.0 404 Not Found");
		@fclose($pfile);
		big_error($lang['FILE_NO_FOUNDED'],  $lang['NOT_FOUND']);
	}

	#sending some headers
	header('Accept-Ranges: bytes');

	#prevent some limits
	@set_time_limit(0);

	// multipart-download and download resuming support
	$range_enable = false;
	if(isset($_SERVER['HTTP_RANGE']) && strpos($_SERVER['HTTP_RANGE'],'bytes=') !== false && !$is_image && !$is_live && $resuming_on)
	{
		header('HTTP/1.1 206 Partial Content');

		$ranges		= explode(',', substr(trim($_SERVER['HTTP_RANGE']), 6));
		$boundary	= substr(md5($name . microtime()), 24);

		# many ranges requested 
		if(sizeof($ranges) > 1)
		{
			$content_length = 0;
			foreach ($ranges as $range)
			{
				list($first, $last) = kleeja_set_range($range, $size);
				$content_length += strlen("\r\n--$boundary\r\n");
				$content_length += strlen("Content-Type: $mime_type\r\n");
				$content_length += strlen("Content-range: bytes $first-$last/$size\r\n\r\n");
				$content_length += $last-$first+1;
			}
			$content_length += strlen("\r\n--$boundary--\r\n");

			header("Content-Length: $content_length");
			header("Content-Type: multipart/x-byteranges; boundary=$boundary");

			foreach ($ranges as $range)
			{
				list($first, $last) = kleeja_set_range($range, $size);
				echo "\r\n--$boundary\r\n";
				echo "Content-Type: $mime_type\r\n";
				echo "Content-range: bytes $first-$last/$size\r\n\r\n";
				fseek($pfile, $first);
				kleeja_buffere_range($pfile, $last-$first+1, $chunksize);
			}
			echo "\r\n--$boundary--\r\n";
		}
		else
		{
			#single range is request.
			list($first, $last) = kleeja_set_range($ranges[0], $size); 
			header("Content-Length: " . ($last-$first+1));
			header("Content-Range: bytes $first-$last/$size");
			header("Content-Type: $mime_type");  
			fseek($pfile, $first);
			kleeja_buffere_range($pfile, $last-$first+1, $chunksize);
		}
	}
	else
	{

		header("Content-Length: " . $size);
		header("Content-Type: $mime_type");

		if(!$size)
		{
			while (!feof($pfile))
			{
				echo fread($pfile, $chunksize);
				@ob_flush();
			}
		}
		else
		{
			kleeja_buffere_range($pfile, $size, $chunksize);
		}
	}

	flush();
	fclose($pfile);
	$SQL->close();
	exit; // done
}

//
//no one of above are there, you can use this hook to get more actions here
//
else
{
	($hook = kleeja_run_hook('err_navig_download_page')) ? eval($hook) : null; //run hook
	kleeja_err($lang['ERROR_NAVIGATATION']);
}

($hook = kleeja_run_hook('end_download_page')) ? eval($hook) : null; //run hook


#<-- EOF
