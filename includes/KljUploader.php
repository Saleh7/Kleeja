<?php
/**
*
* @package Kleeja
* @version $Id: KljUploader.php 2125 2013-02-04 01:55:42Z saanina $
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/


//no for directly open
if (!defined('IN_COMMON'))
{
	exit();
}

#includes imortant functions
include dirname(__file__) . '/up_helpers/others.php';
include dirname(__file__) . '/up_helpers/thumbs.php';
include dirname(__file__) . '/up_helpers/watermark.php';
include dirname(__file__) . '/up_helpers/remote_uploading.php';


/*
 * uploading class, the most important class in Kleeja
 * Where files uploaded by this class, depend on Kleeja settings
 */
class KljUploader
{
	# folder name
	var $folder;

	# number of fields
	var $filesnum;

	# allowed file types
	var $types;

	# current file name
	var $filename;

	# current file name after we change it
	var $filename2;

	# total uploaded files
	var $total = 0;

	# current file extension, aka type
	var $typet;

	# current file size
	var $sizet;

	# MySQL insert-id for current file
	var $id_for_url;

	# orginal file name, this is exactly what in upload folder
	var $name_for_url;

	# decoding type: md5 or time or no
	var $decode = 0;

	# current user id, -1 = guest
	var $id_user;

	# errors or info messages, shown after uploading as a loop
	var $messages = array();

	# is captcha is enabled?
	var $safe_code;

	# check if user is administrator, true = yes
	var $user_is_adm = false;

	#prefix of filename
	var $prefix = '';

	/**
	 * Processing current upload, aka 'after user click upload button to upload his files'
	 */
	function process()
	{
		global $SQL, $dbprefix, $config, $lang;

		($hook = kleeja_run_hook('kljuploader_process_func')) ? eval($hook) : null; //run hook

		# check folder our real folder
		if(!file_exists($this->folder))
		{
			if(!make_folder($this->folder))
			{
				$this->messages[] = array($lang['CANT_DIR_CRT'], 'index_err');
			}
		}

		# check the live-exts-folder, live exts plugin codes
		if(!empty($config['imagefolderexts']) && !file_exists($config['imagefolder']))
		{
			if(!make_folder($config['imagefolder']))
			{
				$this->messages[] = array($lang['CANT_DIR_CRT'], 'index_err');
			}
		}

		# when uploading_type = 1, then we upload from _file input
		# if uploading_type = 2, then we uploading from url which is disabled by default and is buggy
		$uploading_type = isset($_POST['submitr']) ? 1 : (isset($_POST['submittxt']) ? 2 : false);


		# add your uploading_type through the hook
		($hook = kleeja_run_hook('kljuploader_process_func_uploading_type')) ? eval($hook) : null; //run hook

		#no uploading yet, or just go to index.php, so we have make a new session
		if(!$uploading_type)
		{
			unset($_SESSION['FIILES_NOT_DUPLI'], $_SESSION['FIILES_NOT_DUPLI_LINKS']);
		}

		# is captcha on, and there is uploading going on
		if($this->safe_code && $uploading_type)
		{
			#captcha is wrong
			if(!kleeja_check_captcha())
			{
				return $this->messages[] = array($lang['WRONG_VERTY_CODE'], 'index_err');
			}
		}

		# to prevent flooding, user must wait, waiting-time is grapped from Kleeja settings, admin is exceptional
		if(!$this->user_is_adm && user_is_flooding($this->id_user))
		{
			return $this->messages[] = array(sprintf($lang['YOU_HAVE_TO_WAIT'], ($this->id_user == '-1') ? $config['guestsectoupload'] : $config['usersectoupload']), 'index_err');
		}

		# flooading ..
		if ($uploading_type == 1 && isset($_SESSION['FIILES_NOT_DUPLI']))
		{
			for($i=0; $i<=$this->filesnum; $i++)
			{
				if((!empty($_SESSION['FIILES_NOT_DUPLI']['file_' . $i . '_']['name']) && !empty($_FILES['file_' . $i . '_']['name'])) && ($_SESSION['FIILES_NOT_DUPLI']['file_' . $i . '_']['name'] == $_FILES['file_' . $i . '_']['name']))
				{
					redirect('./');
				}
			}
		}

		if ($uploading_type == 2 && isset($_SESSION['FIILES_NOT_DUPLI_LINKS']))
		{
			for($i=0; $i<=$this->filesnum; $i++)
			{
				if((!empty($_SESSION['FIILES_NOT_DUPLI_LINKS']['file_' . $i . '_']) && !empty($_POST['file_' . $i . '_']) && trim($_POST['file_' . $i . '_']) != $lang['PAST_URL_HERE'] && trim($_SESSION['FIILES_NOT_DUPLI_LINKS']['file_' . $i . '_']) != $lang['PAST_URL_HERE']) && ($_SESSION['FIILES_NOT_DUPLI_LINKS']['file_' . $i . '_']) == ($_POST['file_' . $i . '_']))
				{
					redirect('./');
				}
			}
		}

		# flooding code, making sure every ok session is cleared
		if (isset($_POST['submitr']))
		{
			if(isset($_SESSION['FIILES_NOT_DUPLI']))
			{
				unset($_SESSION['FIILES_NOT_DUPLI']);
			}

			$_SESSION['FIILES_NOT_DUPLI'] = $_FILES;
		}
		elseif(isset($_POST['submittxt']))
		{
			if(isset($_SESSION['FIILES_NOT_DUPLI_LINKS']))
			{
				unset($_SESSION['FIILES_NOT_DUPLI_LINKS']);
			}

			$_SESSION['FIILES_NOT_DUPLI_LINKS'] = $_POST;
		}

		#now close session to let user open any other page in Kleeja
		@session_write_close();

		# uploading process, empty check-list for now
		$check = false;

		# add your uploading_type through the hook
		($hook = kleeja_run_hook('kljuploader_process_func_uploading_type_later')) ? eval($hook) : null; //run hook

		# do upload
		switch($uploading_type)
		{
			#uploading from a _files input
			case 1:

			($hook = kleeja_run_hook('kljuploader_process_func_uploading_type_1')) ? eval($hook) : null; //run hook

			# loop the uploaded files
			for($i=0; $i<=$this->filesnum; $i++)
			{
				//no file!
				if(empty($_FILES['file_' . $i . '_']['tmp_name']))
				{
					continue;
				}

				# file name
				$this->filename = isset($_FILES['file_' . $i . '_']['name']) ? htmlspecialchars(str_replace(array(';',','), '', $_FILES['file_' . $i . '_']['name'])) : '';

				# add the file to the check-list
				$check .= isset($_FILES['file_' . $i . '_']['name']) ? $_FILES['file_' . $i . '_']['name'] : '';
				# get the extension of file
				$this->typet = strtolower(array_pop(explode('.', $this->filename)));

				# them the size
				$this->sizet = !empty($_FILES['file_' . $i . '_']['size']) ?  intval($_FILES['file_' . $i . '_']['size']) : null;
				# get the other filename, changed depend on kleeja settings
				$this->filename2 = change_filename_decoding($this->filename, $i, $this->typet, $this->decode);
				# filename templates {rand:..}, {date:..}
				$this->filename2 = change_filename_templates(trim($this->prefix) . $this->filename2);

				($hook = kleeja_run_hook('kljuploader_process_func_uploading_type_1_loop')) ? eval($hook) : null; //run hook

				# file exists before? change it a little
				if(file_exists($this->folder . '/' . $this->filename2))
				{
					$this->filename2 = change_filename_decoding($this->filename2, $i, $this->typet, 'exists');
				}

				# now, let process it
				if(!in_array(strtolower($this->typet), array_keys($this->types)))
				{
					# guest
					if($this->id_user == '-1')
					{
						$this->messages[] = array(sprintf($lang['FORBID_EXT'], $this->typet) . '<br /> <a href="' .  ($config['mod_writer'] ? "register.html" : "ucp.php?go=register") . '" title="' . htmlspecialchars($lang['REGISTER']) . '">' . $lang['REGISTER'] . '</a>', 'index_err');
					}
					# not guest, user I meant €
					else
					{
						$this->messages[] = array(sprintf($lang['FORBID_EXT'], $this->typet), 'index_err');
					}
				}
				# bad chars in the filename
				elseif(preg_match ("#[\\\/\:\*\?\<\>\|\"]#", $this->filename2))
				{
					$this->messages[] = array(sprintf($lang['WRONG_F_NAME'], htmlspecialchars($_FILES['file_' . $i . '_']['name'])), 'index_err');
				}
				# check file extension for bad stuff
				elseif(ext_check_safe($_FILES['file_' . $i . '_']['name']) == false)
				{
					$this->messages[] = array(sprintf($lang['WRONG_F_NAME'], htmlspecialchars($_FILES['file_' . $i . '_']['name'])), 'index_err');
				}
				# check the mime-type for the file
				elseif(check_mime_type($_FILES['file_' . $i . '_']['type'], in_array(strtolower($this->typet), array('gif', 'png', 'jpg', 'jpeg', 'bmp')), $_FILES['file_' . $i . '_']['tmp_name']) == false)
				{
					$this->messages[] = array(sprintf($lang['NOT_SAFE_FILE'], htmlspecialchars($_FILES['file_' . $i . '_']['name'])), 'index_err');
				}
				# check file size
				elseif($this->types[strtolower($this->typet)] > 0 && $this->sizet >= $this->types[strtolower($this->typet)])
				{
					$this->messages[] = array(sprintf($lang['SIZE_F_BIG'], htmlspecialchars($_FILES['file_' . $i . '_']['name']), Customfile_size($this->types[strtolower($this->typet)])), 'index_err');
				}
				# no errors, so upload it
				else
				{
					($hook = kleeja_run_hook('kljuploader_process_func_uploading_type_1_loop_upload')) ? eval($hook) : null; //run hook

					#if this is listed as live-ext from Kleeja settings
					$live_exts	= array_map('trim', explode(',', $config['imagefolderexts']));
					$folder_to_upload = $this->folder;
					if(in_array(strtolower($this->typet), $live_exts))
					{
						# live-exts folder, if empty use default folder
						$folder_to_upload = trim($config['imagefolder']) == '' ? trim($config['foldername']) : trim($config['imagefolder']);
						# change to time decoding for filename
						if((int) $config['imagefoldere'])
						{
							//$this->filename2 = change_filename_decoding($this->filename2, $i, $this->typet, 'time');
						}
					}

					# now, upload the file
					$file = move_uploaded_file($_FILES['file_' . $i . '_']['tmp_name'], $folder_to_upload . "/" . $this->filename2);

					if ($file)
					{
						$this->saveit($this->filename2, $folder_to_upload, $this->sizet, $this->typet, $this->filename);
					}
					else
					{
						$this->messages[] = array(sprintf($lang['CANT_UPLAOD'], $this->filename2), 'index_err');
					}
				}
			}#loop

			# well, there is no file uploaded
			if(!isset($check) || empty($check))
			{
				$this->messages[] = array($lang['CHOSE_F'], 'index_err');
			}

			break;


			#uploading from a url text-input
			case 2:

			#if not enabled, quit it
			if((int) $config['www_url'] != '1')
			{
				break;
			}

			($hook = kleeja_run_hook('kljuploader_process_func_uploading_type_2')) ? eval($hook) : null; //run hook

			#loop text inputs
			for($i=0; $i<=$this->filesnum; $i++)
			{
				# get file name
				$this->filename = (isset($_POST['file_' . $i . '_'])) ? basename(htmlspecialchars($_POST['file_' . $i . '_'])) : '';
				//print $this->filename;
				# add it to the check-list
				$check .= (isset($_POST['file_' . $i . '_']) && trim($_POST['file_' . $i . '_']) != $lang['PAST_URL_HERE']) ? $_POST['file_' . $i . '_'] : '';
				# file extension, type
				$this->typet = explode(".", $this->filename);
				if(in_array($this->typet[count($this->typet)-1], array('html', 'php', 'html')))
				{
					$this->typet = strtolower($this->typet[count($this->typet)-2]);
				}
				else
				{
					$this->typet = strtolower($this->typet[count($this->typet)-1]);
				}

				# change to another filename depend on kleeja settings
				$this->filename2 = change_filename_decoding($this->filename, $i, $this->typet, $this->decode);
				$this->filename2 = change_filename_templates(trim($this->prefix) . $this->filename2);

				($hook = kleeja_run_hook('kljuploader_process_func_uploading_type_2_loop')) ? eval($hook) : null; //run hook

				# process is begun
				if(empty($_POST['file_' . $i . '_']) || trim($_POST['file_' . $i . '_']) == $lang['PAST_URL_HERE'])
				{
					#if empty is not big deal, it's a multi-text-input, remember?
				}
				#forbbiden type ? quit it
				elseif(!in_array(strtolower($this->typet),array_keys($this->types)))
				{
					$this->messages[] = array(sprintf($lang['FORBID_EXT'], htmlspecialchars($_POST['file_' . $i . '_']), $this->typet), 'index_err');
				}
				# file exists before ? quit it
				elseif(file_exists($this->folder . '/' . $this->filename2))
				{
					$this->messages[] = array(sprintf($lang['SAME_FILE_EXIST'], htmlspecialchars($this->filename2)), 'index_err');
				}
				# no errors, ok, lets upload now
				else
				{
					($hook = kleeja_run_hook('kljuploader_process_func_uploading_type_2_loop_upload')) ? eval($hook) : null; //run hook

                    #if this is listed as live-ext from Kleeja settings
					$live_exts	= explode(',', $config['imagefolderexts']);
					$folder_to_upload = $this->folder;
					if(in_array(strtolower($this->typet), $live_exts))
					{
						# live-exts folder, if empty use default folder
						$folder_to_upload = trim($config['imagefolder']) == '' ? trim($config['foldername']) : trim($config['imagefolder']);
						# change to time decoding for filename
						if((int) $config['imagefoldere'])
						{
							//$this->filename2 = change_filename_decoding($this->filename2, $i, $this->typet, 'time');
						}
					}

					#no prefix ? http or even ftp, then add one
					if(!in_array(substr($_POST['file_' . $i . '_'], 0, 4), array('http', 'ftp:')))
					{
						$_POST['file_' . $i . '_'] = 'http://' . $_POST['file_' . $i . '_'];
					}

					#get size, if big quit it
					$this->sizet = get_remote_file_size($_POST['file_' . $i . '_']);

					if($this->types[strtolower($this->typet)] > 0 && $this->sizet >= $this->types[strtolower($this->typet)])
					{
						$this->messages[] = array(sprintf($lang['SIZE_F_BIG'], htmlspecialchars($_POST['file_' . $i . '_']), Customfile_size($this->types[strtolower($this->typet)])), 'index_err');
					}
					else
					{
						#get remote data, if no data quit it
						$data = fetch_remote_file($_POST['file_' . $i . '_'], $folder_to_upload . "/" . $this->filename2, 6, false, 2, true);
						if($data === false)
						{
							$this->messages[] = array($lang['URL_CANT_GET'], 'index_err');
						}
						else
						{
							$this->saveit($this->filename2, $folder_to_upload, $this->sizet, $this->typet);
						}
					}
				}#else

			}#end loop

			# if not file uploaded as the check-list said, then show error
			if(!isset($check) || empty($check))
			{
				$this->messages[] = array($lang['CHOSE_F'], 'index_err');
			}

			break;

			default:
				($hook = kleeja_run_hook('kljuploader_process_switch_default_func')) ? eval($hook) : null; //run hook
		}#end switch
	}


	/**
	 * Insert the file data to database, also make other things like,
	 * thumb, watermark and etc..
	 */
	function saveit ($filname, $folderee, $sizeee, $typeee, $real_filename = '')
	{
		global $SQL, $dbprefix, $config, $lang;

		#sometime cant see file after uploading.. but ..
		@chmod($folderee . '/' . $filname , 0644);

		#file data, filter them
		$name 	= (string)	$SQL->escape($filname);
		$size	= (int) 	$sizeee;
		$type 	= (string)	strtolower($SQL->escape($typeee));
		$folder	= (string)	$SQL->escape($folderee);
		$timeww	= (int)		time();
		$user	= (int)		$this->id_user;
		$code_del=(string)	md5($name . uniqid());
		$ip		= (string)	$SQL->escape(get_ip());
		$realf	= (string)	$SQL->escape($real_filename);
		$id_form= (string)	$SQL->escape($config['id_form']);
		$is_img = in_array($type, array('png','gif','jpg','jpeg', 'bmp')) ? true : false;

		# insertion query
		$insert_query = array(
								'INSERT'	=> 'name ,size ,time ,folder ,type,user,code_del,user_ip, real_filename, id_form',
								'INTO'		=> "{$dbprefix}files",
								'VALUES'	=> "'$name', '$size', '$timeww', '$folder','$type', '$user', '$code_del', '$ip', '$realf', '$id_form'"
								);

		($hook = kleeja_run_hook('qr_insert_new_file_kljuploader')) ? eval($hook) : null; //run hook

		# do the query
		$SQL->build($insert_query);

		# orginal name of file to use it in the file url
		$this->name_for_url  = $name;
		# inset id so it can be used in url like in do.php?id={id_for_url}
		$this->id_for_url  = $SQL->insert_id();

		# update Kleeja stats
		$update_query = array(
								'UPDATE'	=> "{$dbprefix}stats",
								'SET'		=> ($is_img ? "imgs=imgs+1" : "files=files+1") . ",sizes=sizes+" . $size . ""
							);

		($hook = kleeja_run_hook('qr_update_no_files_kljuploader')) ? eval($hook) : null; //run hook

		$SQL->build($update_query);


		# inforamation of file, used for generating a url boxes
		$file_info = array('::ID::'=>$this->id_for_url, '::NAME::'=>$this->name_for_url, '::DIR::'=> $folderee, '::FNAME::'=>$realf);

		# show del code link box
		$extra_del = '';
		if ($config['del_url_file'])
		{
			$extra_del	= get_up_tpl_box('del_file_code', array('b_title'=> $lang['URL_F_DEL'], 'b_code_link'=> kleeja_get_link('del', array('::CODE::'=>$code_del))));
		}

		//show imgs
		if($is_img)
		{
			$img_html_result = '';

			# get default thumb dimensions
			$thmb_dim_w = $thmb_dim_h = 150;
			if(strpos($config['thmb_dims'], '*') !== false)
			{
				list($thmb_dim_w, $thmb_dim_h) = array_map('trim', explode('*', $config['thmb_dims']));
			}

			# generate thumb now
			helper_thumb($folderee . '/' . $filname, strtolower($this->typet), $folderee . '/thumbs/' . $filname, $thmb_dim_w, $thmb_dim_h);

			if(($config['thumbs_imgs'] != 0) && in_array(strtolower($this->typet), array('png','jpg','jpeg','gif', 'bmp')))
			{
				$img_html_result .= get_up_tpl_box('image_thumb', array(
																			'b_title'	=> $lang['URL_F_THMB'],
																			'b_url_link'=> kleeja_get_link('image', $file_info),
																			'b_img_link'=> kleeja_get_link('thumb', $file_info)
																			));
			}

			# watermark on image
			if(($config['write_imgs'] != 0) && in_array(strtolower($this->typet), array('gif', 'png', 'jpg', 'jpeg', 'bmp')))
			{
				helper_watermark($folderee . "/" . $filname, strtolower($this->typet));
			}

			#then show, image box
			$img_html_result .= get_up_tpl_box('image', array(
																'b_title'	=> $lang['URL_F_IMG'],
																'b_bbc_title'=> $lang['URL_F_BBC'],
																'b_url_link'=> kleeja_get_link('image', $file_info),
															));

			#add del link box to the result if there is any
			$img_html_result .= $extra_del;

			($hook = kleeja_run_hook('saveit_func_img_res_kljuploader')) ? eval($hook) : null; //run hook
			$this->total++;

			#show success message
			$this->messages[] = array($lang['IMG_DOWNLAODED'] . '<br />' . $img_html_result, 'index_info');
		}
		else
		{
			#then show other files
			$else_html_result = get_up_tpl_box('file', array(
																'b_title'	=> $lang['URL_F_FILE'],
																'b_bbc_title'=> $lang['URL_F_BBC'],
																'b_url_link'=> kleeja_get_link('file', $file_info),
															));
			#add del link box to the result if there is any
			$else_html_result .= $extra_del;

			($hook = kleeja_run_hook('saveit_func_else_res_kljuploader')) ? eval($hook) : null; //run hook
			$this->total++;

			#show success message
			$this->messages[] = array($lang['FILE_DOWNLAODED'] . '<br />' . $else_html_result, 'index_info');
		}

		($hook = kleeja_run_hook('saveit_func_kljuploader')) ? eval($hook) : null; //run hook

		# clear some variables from memory
		unset($filename, $folderee, $sizeee, $typeee);
	}

}#end class
