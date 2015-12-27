<?php
/**
*
* @package Kleeja
* @version $Id: ucp.php 2128 2013-02-14 12:01:02Z saanina $
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/


define ( 'IN_INDEX' , true);

if(isset($_GET['go']))
{
	switch($_GET['go'])
	{
		case 'login':
		define ('IN_LOGINPAGE' , true);
		case 'logout': case 'get_pass':
		define ('IN_LOGIN' , true);
		break;
		case 'register':
		define ('IN_REGISTER' , true);
		break;
	}
}

if(isset($_GET['go']) && $_GET['go'] == 'login' && isset($_POST['submit']))
{
	define('IN_LOGIN_POST', true);
}

//include imprtant file ..
include ('includes/common.php');

($hook = kleeja_run_hook('begin_usrcp_page')) ? eval($hook) : null; //run hook

//difne empty var
$extra = '';

//now we will navigate ;)
if(!isset($_GET['go']))
{
	$_GET['go'] = null;
}

switch ($_GET['go'])
{
	//
	//login page
	//
	case 'login' :

		//page info
		$stylee				= 'login';
		$titlee				= $lang['LOGIN'];
		$action				= 'ucp.php?go=login' . (isset($_GET['return']) ? '&amp;return=' . htmlspecialchars($_GET['return']) : '');
		$forget_pass_link	= !empty($forgetpass_script_path) && (int) $config['user_system'] != 1 ? $forgetpass_script_path : 'ucp.php?go=get_pass';
		$H_FORM_KEYS		= kleeja_add_form_key('login');
		//no error yet
		$ERRORS = false;

		//_post
		$t_lname = isset($_POST['lname']) ? htmlspecialchars($_POST['lname']) : '';
		$t_lpass = isset($_POST['lpass']) ? htmlspecialchars($_POST['lpass']) : '';

		($hook = kleeja_run_hook('login_before_submit')) ? eval($hook) : null; //run hook

		//logon before !
		if ($usrcp->name())
		{
			($hook = kleeja_run_hook('login_logon_before')) ? eval($hook) : null; //run hook

			$errorpage = true;
			$text	= $lang['LOGINED_BEFORE'] . ' ..<br /> <a href="' . $config['siteurl']  . ($config['mod_writer'] ?  'logout.html' : 'ucp.php?go=logout') . '">' . $lang['LOGOUT'] . '</a>';
			kleeja_info($text);
		}
		elseif (isset($_POST['submit']))
		{
			$ERRORS	= array();

			($hook = kleeja_run_hook('login_after_submit')) ? eval($hook) : null; //run hook

			//check for form key
			if(!kleeja_check_form_key('login'))
			{
				$ERRORS['form_key'] = $lang['INVALID_FORM_KEY'];
			}
			elseif (empty($_POST['lname']) || empty($_POST['lpass']))
			{
				$ERRORS['empty_fields'] = $lang['EMPTY_FIELDS'];
			}
			elseif(!$usrcp->data($_POST['lname'], $_POST['lpass'], false, (!isset($_POST['remme']) ? false : $_POST['remme'])))
			{
				$ERRORS['login_check'] = $lang['LOGIN_ERROR'];
			}

			($hook = kleeja_run_hook('login_after_submit2')) ? eval($hook) : null; //run hook

			if(empty($ERRORS))
			{
				if(isset($_GET['return']))
				{
					redirect(urldecode($_GET['return']));
					$SQL->close();
					exit;
				}

				$errorpage = true;
				($hook = kleeja_run_hook('login_data_no_error')) ? eval($hook) : null; //run hook

				$text	= $lang['LOGIN_SUCCESFUL'] . ' <br /> <a href="' . $config['siteurl'] . '">' . $lang['HOME'] . '</a>';
				kleeja_info($text, '', true, $config['siteurl'], 1);
			}
		}


		break;

		//
		//register page
		//
		case 'register' :

		//page info
		$stylee	= 'register';
		$titlee	= $lang['REGISTER'];
		$action	= 'ucp.php?go=register';
		$H_FORM_KEYS = kleeja_add_form_key('register');
		//no error yet
		$ERRORS = false;

		//config register
		if ((int) $config['register'] != 1 && (int) $config['user_system'] == 1)
		{
			kleeja_info($lang['REGISTER_CLOSED'], $lang['PLACE_NO_YOU']);
		}
		else if ($config['user_system'] != '1')
		{
			($hook = kleeja_run_hook('register_not_default_sys')) ? eval($hook) : null; //run hook

			if(!empty($register_script_path))
			{
				$goto_forum_link = $register_script_path;
			}
			else
			{
				if(isset($script_path))
				{
					$goto_forum_link = ($config['user_system'] == 'api') ? dirname($script_path) : $script_path;
					if($config['user_system'] == 'phpbb' || ($config['user_system'] == 'api' && strpos($script_path, 'phpbb') !== false))
					{
						$goto_forum_link .= '/ucp.php?mode=register';
					}
					else if($config['user_system'] == 'vb' || ($config['user_system'] == 'api' && strpos($script_path, 'vb') !== false))
					{
						$goto_forum_link .= '/register.php';
					}
				}
				else
				{
					$goto_forum_link = '...';
				}
			}

			kleeja_info('<a href="' . $goto_forum_link . '" title="' . $lang['REGISTER'] . '" target="_blank">' . $lang['REGISTER']. '</a>', $lang['REGISTER']);
		}

		//logon before !
		if ($usrcp->name())
		{
			($hook = kleeja_run_hook('register_logon_before')) ? eval($hook) : null; //run hook
			kleeja_info($lang['REGISTERED_BEFORE']);
		}


		//_post
		$t_lname = isset($_POST['lname']) ? htmlspecialchars($_POST['lname']) : '';
		$t_lpass = isset($_POST['lpass']) ? htmlspecialchars($_POST['lpass']) : '';
		$t_lpass2 = isset($_POST['lpass2']) ? htmlspecialchars($_POST['lpass2']) : '';
		$t_lmail = isset($_POST['lmail']) ? htmlspecialchars($_POST['lmail']) : '';

		//no submit
		if (!isset($_POST['submit']))
		{
			($hook = kleeja_run_hook('register_no_submit')) ? eval($hook) : null; //run hook
		}
		else // submit
		{
			$ERRORS = array();

			($hook = kleeja_run_hook('register_submit')) ? eval($hook) : null; //run hook

			//check for form key
			if(!kleeja_check_form_key('register'))
			{
				$ERRORS['form_key'] = $lang['INVALID_FORM_KEY'];
			}
			if(!kleeja_check_captcha())
			{
				$ERRORS['captcha'] = $lang['WRONG_VERTY_CODE'];
			}
			if (trim($_POST['lname']) == '' || trim($_POST['lpass']) == '' || trim($_POST['lmail']) == '')
			{
				$ERRORS['empty_fields'] = $lang['EMPTY_FIELDS'];
			}
			if ($t_lpass != $t_lpass2)
			{
				$ERRORS['pass_neq_pass2'] = $lang['PASS_NEQ_PASS2'];
 			}
			if (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i", trim($_POST['lmail'])))
			{
				$ERRORS['lmail'] = $lang['WRONG_EMAIL'];
			}
			if (strlen(trim($_POST['lname'])) < 3 || strlen(trim($_POST['lname'])) > 50)
			{
				$ERRORS['lname'] = $lang['WRONG_NAME'];
			}
			else if ($SQL->num_rows($SQL->query("SELECT * FROM {$dbprefix}users WHERE clean_name='" . trim($SQL->escape($usrcp->cleanusername($_POST["lname"]))) . "'")) != 0)
			{
				$ERRORS['name_exists_before'] = $lang['EXIST_NAME'];
			}
			else if ($SQL->num_rows($SQL->query("SELECT * FROM {$dbprefix}users WHERE mail='" . strtolower(trim($SQL->escape($_POST["lmail"]))) . "'")) != 0)
			{
				$ERRORS['mail_exists_before'] = $lang['EXIST_EMAIL'];
			}

			($hook = kleeja_run_hook('register_submit2')) ? eval($hook) : null; //run hook

			//no errors, lets do process
			if(empty($ERRORS))
			{
				$name		= (string) $SQL->escape(trim($_POST['lname']));
				$user_salt		= (string) substr(kleeja_base64_encode(pack("H*", sha1(mt_rand()))), 0, 7);
				$pass		= (string) $usrcp->kleeja_hash_password($SQL->escape(trim($_POST['lpass'])) . $user_salt);
				$mail		= (string) strtolower(trim($SQL->escape($_POST['lmail'])));
				$session_id		= (string) session_id();
				$clean_name		= (string) $usrcp->cleanusername($name);

				$insert_query	= array(
								'INSERT'	=> 'name ,password, password_salt ,mail, register_time, session_id, clean_name, group_id',
								'INTO'		=> "{$dbprefix}users",
								'VALUES'	=> "'$name', '$pass', '$user_salt', '$mail', " . time() . ", '$session_id','$clean_name', " . $config['default_group']
							);

				($hook = kleeja_run_hook('qr_insert_new_user_register')) ? eval($hook) : null; //run hook

				if ($SQL->build($insert_query))
				{
					$last_user_id = $SQL->insert_id();
					$text = $lang['REGISTER_SUCCESFUL'] . ' <br /> <a href="' . $config['siteurl'] . '">' . $lang['HOME'] . '</a>';

					//update number of stats
					$update_query	= array(
								'UPDATE'	=> "{$dbprefix}stats",
								'SET'		=> "users=users+1, lastuser='$name'",
								);

					($hook = kleeja_run_hook('ok_added_users_register')) ? eval($hook) : null; //run hook

					if($SQL->build($update_query))
					{
						//delete cache ..
						delete_cache('data_stats');
					}

					//auto login
					$usrcp->data($t_lname, $t_lpass, false, false);
					kleeja_info($text, '', true, $config['siteurl'], 3);

				}
			}
		}

		break;

		//
		//logout action
		//
		case 'logout' :

		($hook = kleeja_run_hook('begin_logout')) ? eval($hook) : null; //run hook

		if ($usrcp->logout())
		{
			$text = $lang['LOGOUT_SUCCESFUL'] . '<br /> <a href="' .  $config['siteurl']  . '">' . $lang['HOME'] . '</a>';
			kleeja_info($text, $lang['LOGOUT'], true, $config['siteurl'], 1);
		}
		else
		{
			kleeja_err($lang['LOGOUT_ERROR']);
		}

		($hook = kleeja_run_hook('end_logout')) ? eval($hook) : null; //run hook

		break;

		//
		//files user page
		//
		case 'fileuser' :

		($hook = kleeja_run_hook('begin_fileuser')) ? eval($hook) : null; //run hook

		$stylee	= 'fileuser';
		$H_FORM_KEYS = kleeja_add_form_key('fileuser');

		$user_id_get	= isset($_GET['id']) ? intval($_GET['id']) : false;
		$user_id		= (!$user_id_get && $usrcp->id()) ? $usrcp->id() : $user_id_get;
		$user_himself	= $usrcp->id() == $user_id;
		$action			= $config['siteurl'] . 'ucp.php?go=fileuser';

		//no logon before
		if (!$usrcp->name() && !isset($_GET['id']))
		{
			kleeja_err($lang['USER_PLACE'], $lang['PLACE_NO_YOU'], true, 'index.php');
		}

		//Not allowed to browse files's folders
		if (!user_can('access_fileusers') && !$user_himself)
		{
			($hook = kleeja_run_hook('user_cannot_access_fileusers')) ? eval($hook) : null; //run hook
			kleeja_info($lang['HV_NOT_PRVLG_ACCESS'], $lang['HV_NOT_PRVLG_ACCESS']);
		}

		//fileuser is closed ?
		if ((int) $config['enable_userfile'] != 1 && !user_can('enter_acp'))
		{
			kleeja_info($lang['USERFILE_CLOSED'], $lang['CLOSED_FEATURE']);
		}

		//to get userdata!!
		$data_user = ((int) $config['user_system'] == 1) ? $usrcp->get_data('name, show_my_filecp', $user_id) : array('name' => $usrcp->usernamebyid($user_id), 'show_my_filecp' => '1');

		if(!$data_user['name'])
		{
			kleeja_err($lang['NOT_EXSIT_USER'], $lang['PLACE_NO_YOU']);
		}

		if(!$data_user['show_my_filecp'] && ($usrcp->id() != $user_id) && !user_can('enter_acp'))
		{
			kleeja_info($lang['USERFILE_CLOSED'], $lang['CLOSED_FEATURE']);
		}

		$query	= array(
					'SELECT'	=> 'f.id, f.name, f.real_filename, f.folder, f.type, f.uploads, f.time, f.size',
					'FROM'		=> "{$dbprefix}files f",
					'WHERE'		=> 'f.user=' . $user_id,
					'ORDER BY'	=> 'f.id DESC'
				);

		//pager
		$perpage		= 16;
		$result_p		= $SQL->build($query);
		$nums_rows		= $SQL->num_rows($result_p);
		$currentPage	= (isset($_GET['page'])) ? intval($_GET['page']) : 1;
		$Pager			= new SimplePager($perpage,$nums_rows,$currentPage);
		$start			= $Pager->getStartRow();

		$your_fileuser	= $config['siteurl'] . ($config['mod_writer'] ? 'fileuser-' . $usrcp->id() . '.html' : 'ucp.php?go=fileuser&amp;id=' .  $usrcp->id());
		$total_pages	= $Pager->getTotalPages();
		$linkgoto		= $config['siteurl'] . ($config['mod_writer'] ?  'fileuser-' . $user_id  . '.html' : 'ucp.php?go=fileuser&amp;id=' . $user_id);
		$page_nums		= $Pager->print_nums(str_replace('.html', '', $linkgoto));

		$no_results = true;

		if((int) $config['user_system'] != 1 && ($usrcp->id() != $user_id))
		{
			$data_user['name'] = $usrcp->usernamebyid($user_id);
		}
		$user_name = !$data_user['name'] ? false : $data_user['name'];

		#set page title
		$titlee	= $lang['FILEUSER'] . ': ' . $user_name;
		#there is result ? show them
		if($nums_rows != 0)
		{
			$no_results = false;

			$query['LIMIT'] = "$start, $perpage";
			($hook = kleeja_run_hook('qr_select_files_in_fileuser')) ? eval($hook) : null; //run hook

			$result	= $SQL->build($query);

			$i = ($currentPage * $perpage) - $perpage;
			$tdnumi = $num = $files_num = $imgs_num = 0;
			while($row=$SQL->fetch_array($result))
			{
				++$i;
				$file_info = array('::ID::' => $row['id'], '::NAME::' => $row['name'], '::DIR::' => $row['folder'], '::FNAME::' => $row['real_filename']);

				$is_image = in_array(strtolower(trim($row['type'])), array('gif', 'jpg', 'jpeg', 'bmp', 'png')) ? true : false;
				$url = $is_image ? kleeja_get_link('image', $file_info) : kleeja_get_link('file', $file_info);
				$url_thumb = $is_image ? kleeja_get_link('thumb', $file_info) : kleeja_get_link('thumb', $file_info);
				$url_fileuser = $is_image ? $url : (file_exists("images/filetypes/".  $row['type'] . ".png")? "images/filetypes/" . $row['type'] . ".png" : 'images/filetypes/file.png');

				//make new lovely arrays !!
				$arr[] 	= array(
						'id'		=> $row['id'],
						'name_img'	=> ($row['real_filename'] == '' ? ((strlen($row['name']) > 40) ? substr($row['name'], 0, 40) . '...' : $row['name']) : ((strlen($row['real_filename']) > 40) ? substr($row['real_filename'], 0, 40) . '...' : $row['real_filename'])),
						'url_thumb_img'	=> '<a title="' . ($row['real_filename'] == '' ? $row['name'] : $row['real_filename']) . '"  href="' . $url . '" onclick="window.open(this.href,\'_blank\');return false;"><img src="' . $url_fileuser . '" alt="' . $row['type'] . '" /></a>',
						'name_file'		=> '<a title="' . ($row['real_filename'] == '' ? $row['name'] : $row['real_filename']) . '"  href="' . $url . '" onclick="window.open(this.href,\'_blank\');return false;">' . ($row['real_filename'] == '' ? ((strlen($row['name']) > 40) ? substr($row['name'], 0, 40) . '...' : $row['name']) : ((strlen($row['real_filename']) > 40) ? substr($row['real_filename'], 0, 40) . '...' : $row['real_filename'])) . '</a>',
						'url_thumb_file'=> '<a title="' . ($row['real_filename'] == '' ? $row['name'] : $row['real_filename']) . '"  href="' . $url . '" onclick="window.open(this.href,\'_blank\');return false;"><img src="' . $url_fileuser . '" alt="' . $row['type'] . '" /></a>',
						'file_type'	=> $row['type'],
						'uploads'	=> $row['uploads'],
						'tdnum'		=> $tdnumi == 0 ? '<ul>': '',
						'tdnum2'	=> $tdnumi == 4 ? '</ul>' : '',
						'href'		=> $url,
						'size'		=> Customfile_size($row['size']),
						'time'		=> !empty($row['time']) ? kleeja_date($row['time']) : '...',
						'thumb_link'=> $is_image ? $url_thumb : $url_fileuser,
						'is_image'	=> $is_image,
					);

				$tdnumi = $tdnumi == 2 ? 0 : $tdnumi+1;

				if (isset($_POST['submit_files']) && $user_himself)
				{
					($hook = kleeja_run_hook('submit_in_fileuser')) ? eval($hook) : null; //run hook

					//check for form key
					if(!kleeja_check_form_key('fileuser', 1800 /* half hour */))
					{
						kleeja_info($lang['INVALID_FORM_KEY']);
					}

					if ($_POST['del_' . $row['id']])
					{
						//delete from folder ..
						@kleeja_unlink ($row['folder'] . '/' . $row['name'] );

						//delete thumb
						if (file_exists($row['folder'] . '/thumbs/' . $row['name'] ))
						{
							@kleeja_unlink ($row['folder'] . '/thumbs/' . $row['name'] );
						}

						$ids[] = $row['id'];
						if($is_image)
						{
							$imgs_num++;
						}
						else
						{
							$files_num++;
						}

						$sizes += $row['size'];
					}
				}
			}

			$SQL->freeresult($result_p);
			$SQL->freeresult($result);

			//
			//after submit
			//
			if (isset($_POST['submit_files']) && $user_himself)
			{
				//no files to delete
				if(isset($ids) && !empty($ids))
				{
					$query_del = array(
								'DELETE'	=> "{$dbprefix}files",
								'WHERE'		=> "id IN (" . implode(',', $ids) . ")"
							);

					($hook = kleeja_run_hook('qr_del_files_in_filecp')) ? eval($hook) : null; //run hook
					$SQL->build($query_del);

					if(($files_num <= $stat_files) && ($imgs_num <= $stat_imgs))
					{
						//update number of stats
						$update_query	= array(
									'UPDATE'	=> "{$dbprefix}stats",
									'SET'		=> "sizes=sizes-$sizes,files=files-$files_num, imgs=imgs-$imgs_num",
									);

						$SQL->build($update_query);
					}

					//delte is ok, show msg
					kleeja_info($lang['FILES_DELETED'], '', true, $linkgoto, 2);
				}
				else
				{
					//no file selected, show msg
					kleeja_info($lang['NO_FILE_SELECTED'], '', true, $linkgoto, 2);
				}
			}
		}#num result

		($hook = kleeja_run_hook('end_fileuser')) ? eval($hook) : null; //run hook

		break;

		case 'profile' :

		//no logon before
		if (!$usrcp->name())
		{
			kleeja_info($lang['USER_PLACE'], $lang['PLACE_NO_YOU']);
		}

		$stylee		= 'profile';
		$titlee		= $lang['PROFILE'];
		$action		= 'ucp.php?go=profile';
		$name		= $usrcp->name();
		$mail		= $usrcp->mail();
		extract($usrcp->get_data('show_my_filecp, password_salt'));
		$data_forum		= (int) $config['user_system'] == 1 ? true : false ;
		$link_avater	= sprintf($lang['EDIT_U_AVATER_LINK'], '<a target="_blank" href="http://www.gravatar.com/">' , '</a>');
		$H_FORM_KEYS = kleeja_add_form_key('profile');
		//no error yet
		$ERRORS = false;

		if(!empty($profile_script_path))
		{
			$goto_forum_link = $profile_script_path;
		}
		else
		{
			if(isset($script_path))
			{
				$goto_forum_link = ($config['user_system'] == 'api') ? dirname($script_path) : $script_path;
				if($config['user_system'] == 'phpbb' || ($config['user_system'] == 'api' && strpos(strtolower($script_path), 'phpbb') !== false))
				{
					$goto_forum_link .= '/ucp.php?i=164';
				}
				else if($config['user_system'] == 'vb' || ($config['user_system'] == 'api' && strpos(strtolower($script_path), 'vb') !== false))
				{
					$goto_forum_link .= '/profile.php?do=editprofile';
				}
			}
			else
			{
				$goto_forum_link = '...';
			}
		}

		//_post
		$t_pppass_old	= isset($_POST['pppass_old']) ? htmlspecialchars($_POST['pppass_old']) : '';
		$t_ppass_old	= isset($_POST['ppass_old']) ? htmlspecialchars($_POST['ppass_old']) : '';
		$t_ppass_new	= isset($_POST['ppass_new']) ? htmlspecialchars($_POST['ppass_new']) : '';
		$t_ppass_new2	= isset($_POST['ppass_new2']) ? htmlspecialchars($_POST['ppass_new2']) : '';

		($hook = kleeja_run_hook('no_submit_profile')) ? eval($hook) : null; //run hook

		//
		// after submit
		//
		if (isset($_POST['submit_data']))
		{
			$ERRORS	= array();

			($hook = kleeja_run_hook('submit_profile')) ? eval($hook) : null; //run hook

			//check for form key
			if(!kleeja_check_form_key('profile'))
			{
				$ERRORS['form_key'] = $lang['INVALID_FORM_KEY'];
			}

			#if there is new pass AND new pass1 = new pass2 AND old pass is exists & true
			if(!empty($_POST['ppass_new']))
			{
				if($_POST['ppass_new'] != $_POST['ppass_new2'])
				{
					$ERRORS['pass1_neq_pass2'] = $lang['PASS_O_PASS2'];
				}
				#if current pass is not correct
				elseif(empty($_POST['ppass_old']) || !$usrcp->kleeja_hash_password($_POST['ppass_old'] . $password_salt, $userinfo['password']))
				{
					$ERRORS['curnt_old_pass'] = $lang['CURRENT_PASS_WRONG'];
				}
			}

			#if email is not equal to current email AND email not exists before
			$new_mail = false;
			if($usrcp->mail() != trim(strtolower($_POST['pmail'])))
			{
				#if current pass is not correct
				if(empty($_POST['pppass_old']) || !$usrcp->kleeja_hash_password($_POST['pppass_old'] . $password_salt, $userinfo['password']))
				{
					$ERRORS['curnt_old_pass'] = $lang['CURRENT_PASS_WRONG'];
				}
				#If email is not valid
				elseif(!preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i', trim($_POST['pmail'])) || trim($_POST['pmail']) == '')
				{
					$ERRORS['wrong_email'] = $lang['WRONG_EMAIL'];
				}
				#if email already exists
				elseif ($SQL->num_rows($SQL->query("SELECT * FROM {$dbprefix}users WHERE mail='" . strtolower(trim($SQL->escape($_POST['pmail']))) . "'")) != 0)
				{
					$ERRORS['mail_exists_before'] = $lang['EXIST_EMAIL'];
				}

				$new_mail = true;
			}

			($hook = kleeja_run_hook('submit_profile2')) ? eval($hook) : null; //run hook

			//no errors , do it
			if(empty($ERRORS))
			{
				$user_salt 	= substr(kleeja_base64_encode(pack("H*", sha1(mt_rand()))), 0, 7);
				$mail		= $new_mail ? "mail='" . $SQL->escape(strtolower(trim($_POST['pmail']))) . "'" : '';
				$showmyfile	= intval($_POST['show_my_filecp']) != $show_my_filecp ?  ($mail == '' ? '': ',') . "show_my_filecp='" . intval($_POST['show_my_filecp']) . "'" : '';
				$pass		= !empty($_POST['ppass_new']) ? ($showmyfile != ''  || $mail != '' ? ',' : '') . "password='" . $usrcp->kleeja_hash_password($SQL->escape($_POST['ppass_new']) . $user_salt) .
								"', password_salt='" . $user_salt . "'" : "";
				$id			= (int) $usrcp->id();

				$update_query	= array(
								'UPDATE'	=> "{$dbprefix}users",
								'SET'		=> $mail . $showmyfile . $pass,
								'WHERE'		=> 'id=' . $id,
								);

				($hook = kleeja_run_hook('qr_update_data_in_profile')) ? eval($hook) : null; //run hook

				if(trim($update_query['SET']) == '')
				{
					$text = $lang['DATA_CHANGED_NO'];
				}
				else
				{
					$text = $lang['DATA_CHANGED_O_LO'];
					$SQL->build($update_query);
				}

				kleeja_info($text, '', true, $action);
			}

		}#else submit

		($hook = kleeja_run_hook('end_profile')) ? eval($hook) : null; //run hook

		break;

		//
		//reset password page
		//
		case 'get_pass' :

		//if not default system, let's give him a link for integrated script
		if ((int) $config['user_system'] != 1)
		{
			$text = '<a href="' . (!empty($forgetpass_script_path) ? $forgetpass_script_path : $script_path) . '">' . $lang['LOST_PASS_FORUM'] . '</a>';
			kleeja_info($text, $lang['PLACE_NO_YOU']);
		}

		//page info
		$stylee		= 'get_pass';
		$titlee		= $lang['GET_LOSTPASS'];
		$action		= 'ucp.php?go=get_pass';
		$H_FORM_KEYS = kleeja_add_form_key('get_pass');
		//no error yet
		$ERRORS = false;

		//after sent mail .. come here
		//example: http://www.moyad.com/up/ucp.php?go=get_pass&activation_key=1af3405662ec373d672d003cf27cf998&uid=1
		#
		if(isset($_GET['activation_key']) && isset($_GET['uid']))
		{
			($hook = kleeja_run_hook('get_pass_activation_key')) ? eval($hook) : null; //run hook

			$h_key = preg_replace('![^a-z0-9]!', '', $_GET['activation_key']);
			$u_id = intval($_GET['uid']);

			#if it's empty ?
			if(trim($h_key) == '')
			{
				big_error('No hash key', 'This is not a good link ... try again!');
			}

			$result = $SQL->query("SELECT new_password FROM {$dbprefix}users WHERE hash_key='" . $SQL->escape($h_key) . "' AND id=" . $u_id . "");
			if($SQL->num_rows($result))
			{
				$npass = $SQL->fetch_array($result);
				$npass = $npass['new_password'];
				//password now will be same as new password
				$update_query = array(
								'UPDATE'=> "{$dbprefix}users",
								'SET'	=> "password = '" . $npass . "', new_password = '', hash_key = ''",
								'WHERE'	=> 'id=' . $u_id,
							);

				($hook = kleeja_run_hook('qr_update_newpass_activation')) ? eval($hook) : null; //run hook

				$SQL->build($update_query);

				$text = $lang['OK_APPLY_NEWPASS'] . '<br /><a href="' . $config['siteurl']  . ($config['mod_writer'] ?  'login.html' : 'ucp.php?go=login') . '">' . $lang['LOGIN'] . '</a>';
				kleeja_info($text);
				exit;
			}

			//no else .. just do nothing cuz it's wrong and wrong mean spams !
			redirect($config['siteurl'], true, true);
			exit;//i dont trust functions :)
		}

		//logon before ?
		if ($usrcp->name())
		{
			($hook = kleeja_run_hook('get_pass_logon_before')) ? eval($hook) : null; //run hook
			kleeja_info($lang['LOGINED_BEFORE']);
		}

		//_post
		$t_rmail = isset($_POST['rmail']) ? htmlspecialchars($_POST['rmail']) : '';

		//no submit
		if (!isset($_POST['submit']))
		{
			($hook = kleeja_run_hook('no_submit_get_pass')) ? eval($hook) : null; //run hook
		}
		else // submit
		{

			$ERRORS	= array();

			($hook = kleeja_run_hook('submit_get_pass')) ? eval($hook) : null; //run hook
			//check for form key
			if(!kleeja_check_form_key('get_pass'))
			{
				$ERRORS['form_key'] = $lang['INVALID_FORM_KEY'];
			}
			if(!kleeja_check_captcha())
			{
				$ERRORS['captcha'] = $lang['WRONG_VERTY_CODE'];
			}
			if (empty($_POST['rmail']))
			{
				$ERRORS['empty_fields'] = $lang['EMPTY_FIELDS'];
			}
			if (!preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i', trim(strtolower($_POST['rmail']))))
			{
				$ERRORS['rmail'] = $lang['WRONG_EMAIL'];
			}
			else if ($SQL->num_rows($SQL->query("SELECT name FROM {$dbprefix}users WHERE mail='" . $SQL->escape(strtolower($_POST['rmail'])) . "'")) == 0)
			{
				$ERRORS['no_rmail'] = $lang['WRONG_DB_EMAIL'];
			}

			($hook = kleeja_run_hook('submit_get_pass2')) ? eval($hook) : null; //run hook

			//no errors, lets do it
			if(empty($ERRORS))
			{
				$query	= array(
						'SELECT'=> 'u.*',
						'FROM'	=> "{$dbprefix}users u",
						'WHERE'	=> "u.mail='" .  $SQL->escape(strtolower(trim($_POST['rmail']))) . "'"
						);

				($hook = kleeja_run_hook('qr_select_mail_get_pass')) ? eval($hook) : null; //run hook
				$result	=	$SQL->build($query);

				$row = $SQL->fetch_array($result);

				//generate password
				$chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
				$newpass = '';
				for ($i = 0; $i < 7; ++$i)
				{
					$newpass .= substr($chars, (mt_rand() % strlen($chars)), 1);
				}

				$hash_key = md5($newpass . time());
				$pass		= (string) $usrcp->kleeja_hash_password($SQL->escape($newpass) . $row['password_salt']);
				$to		= $row['mail'];
				$subject	= $lang['GET_LOSTPASS'] . ':' . $config['sitename'];
				$activation_link = $config['siteurl'] . 'ucp.php?go=get_pass&activation_key=' . urlencode($hash_key) . '&uid=' . $row['id'];
				$message	= "\n " . $lang['WELCOME'] . " " . $row['name'] . "\r\n " . sprintf($lang['GET_LOSTPASS_MSG'], $activation_link, $newpass)  . "\r\n\r\n kleeja.com";

				$update_query	= array(
								'UPDATE'=> "{$dbprefix}users",
								'SET'	=> "new_password = '" . $SQL->escape($pass) . "', hash_key = '" . $hash_key . "'",
								'WHERE'	=> 'id=' . $row['id'],
							);

				($hook = kleeja_run_hook('qr_update_newpass_get_pass')) ? eval($hook) : null; //run hook
				$SQL->build($update_query);

				$SQL->freeresult($result);

				//send it
				$send =  send_mail($to, $message, $subject, $config['sitemail'], $config['sitename']);

				if (!$send)
				{
					kleeja_err($lang['CANT_SEND_NEWPASS']);
				}
				else
				{
					$text	= $lang['OK_SEND_NEWPASS'] . '<br /><a href="' . $config['siteurl']  . ($config['mod_writer'] ?  'login.html' : 'ucp.php?go=login') . '">' . $lang['LOGIN'] . '</a>';
					kleeja_info($text);
				}

				//no need of this var
				unset($newpass);
			}
		}

		($hook = kleeja_run_hook('end_get_pass')) ? eval($hook) : null; //run hook

		break;

		//
		// Wrapper for captcha file
		//
		case 'captcha':
			include PATH . 'includes/captcha.php';
		exit;

		break;;

		//
		//add your own code here
		//
		default:

		($hook = kleeja_run_hook('default_usrcp_page')) ? eval($hook) : null; //run hook

		kleeja_err($lang['ERROR_NAVIGATATION']);

		break;
}#end switch

($hook = kleeja_run_hook('end_usrcp_page')) ? eval($hook) : null; //run hook

//
//show style ...
//
$titlee = empty($titlee) ? $lang['USERS_SYSTEM'] : $titlee;
$stylee = empty($stylee) ? 'info' : $stylee;

//header
Saaheader($titlee, false, $extra);

echo $tpl->display($stylee);
//footer
Saafooter();
