<?php
/**
*
* @package Kleeja
* @version $Id: go.php 2087 2012-11-04 09:06:59Z saanina $
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/

define ('IN_INDEX' , true);
define ('IN_GO' , true);

include ('includes/common.php');

($hook = kleeja_run_hook('begin_go_page')) ? eval($hook) : null; //run hook

if(!isset($_GET['go']))
{
	$_GET['go'] = null;
}

switch ($_GET['go'])
{
	//
	//Page of allowed extensions for all groups
	//
	case 'exts' :
	case 'guide' :

		$stylee	= 'guide';
		$titlee	= $lang['GUIDE'];

		$tgroups = $ttgroups = array();
		$tgroups = array_keys($d_groups);
		$same_group= $rando = 0;
		foreach($tgroups as $gid)
		{
			#if this is admin group, dont show it public
			if($gid == 1 && (int) $userinfo['group_id'] != 1)
			{
				continue;
			}

			#TODO: if no exts, show that
			foreach($d_groups[$gid]['exts'] as $ext=>$size)
			{
				$ttgroups[] = array(
									'ext'	=> $ext,
									'size'	=> Customfile_size($size),
									'group'	=> $gid,
									'group_name'=> str_replace(array('{lang.ADMINS}', '{lang.USERS}', '{lang.GUESTS}'), 
														array($lang['ADMINS'], $lang['USERS'], $lang['GUESTS']),
														$d_groups[$gid]['data']['group_name']),
									'most_firstrow'=> $same_group == 0 ? true : false,
									'firstrow'=> $same_group ==0 or $same_group != $gid ? true : false,
									'rando'	=> $rando,
				);
				$same_group = $gid;
			}
			$rando = $rando ? 0 : 1;
		}

		($hook = kleeja_run_hook('guide_go_page')) ? eval($hook) : null; //run hook

	break;

	//
	//Page of reporting
	//
	case 'report' :

		//page info
		$stylee	= 'report';
		$titlee	= $lang['REPORT'];
		$id_d	= isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['rid']) ? intval($_POST['rid']) : 0);
		$url_id	= (int) $config['mod_writer'] == 1 ? $config['siteurl'] . 'download' . $id_d . '.html' : $config['siteurl'] . 'do.php?id=' . $id_d;
		$action	= $config['siteurl'] . 'go.php?go=report';
		$H_FORM_KEYS	= kleeja_add_form_key('report');
		$NOT_USER		= !$usrcp->name() ? true : false; 
		$s_url			= isset($_POST['surl']) ? htmlspecialchars($_POST['surl']) : '';

		#Does this file exists ?
		if(isset($_GET['id']) || isset($_POST['rid']))
		{
			$query = array(
							'SELECT'	=> 'f.real_filename, f.name',
							'FROM'		=> "{$dbprefix}files f",
							'WHERE'		=> 'id=' . $id_d
						);

			($hook = kleeja_run_hook('qr_report_go_id')) ? eval($hook) : null; //run hook

			$result	= $SQL->build($query);

			if ($SQL->num_rows($result))
			{
				$row = $SQL->fetch_array($result);
				$filename_for_show	= $row['real_filename'] == '' ? $row['name'] : $row['real_filename'];
			}
			else
			{
				($hook = kleeja_run_hook('not_exists_qr_report_go_id')) ? eval($hook) : null; //run hook
				kleeja_err($lang['FILE_NO_FOUNDED']);
			}
			$SQL->freeresult($result);
		}

		//no error yet 
		$ERRORS = false;

		//_post
		$t_rname = isset($_POST['rname']) ? htmlspecialchars($_POST['rname']) : ''; 
		$t_rmail = isset($_POST['rmail']) ? htmlspecialchars($_POST['rmail']) : ''; 
		$t_rtext = isset($_POST['rtext']) ? htmlspecialchars($_POST['rtext']) : ''; 

		if (!isset($_POST['submit']))
		{
			// first
			($hook = kleeja_run_hook('no_submit_report_go_page')) ? eval($hook) : null; //run hook
		}
		else
		{
			$ERRORS	= array();

			($hook = kleeja_run_hook('submit_report_go_page')) ? eval($hook) : null; //run hook

			//check for form key
			if(!kleeja_check_form_key('report'))
			{
				$ERRORS['form_key'] = $lang['INVALID_FORM_KEY'];
			}
			if(!kleeja_check_captcha())
			{
				$ERRORS['captcha']	= $lang['WRONG_VERTY_CODE'];
			}
			if ((empty($_POST['rname']) && $NOT_USER))
			{
				$ERRORS['rname'] = $lang['EMPTY_FIELDS'] . ' : ' . (empty($_POST['rname']) && $NOT_USER ? ' [ ' . $lang['YOURNAME'] . ' ] ' : '')  
									. (empty($_POST['rurl']) ? '  [ ' . $lang['URL']  . ' ] ': '');
			}
			if(isset($_POST['surl']) && trim($_POST['surl']) == '')
			{
				$ERRORS['surl']	=  $lang['EMPTY_FIELDS'] . ' : [ ' . $lang['URL_F_FILE'] . ' ]'; 
			}
			if (isset($_POST['rmail']) &&  !preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i", trim(strtolower($_POST['rmail']))) && $NOT_USER)
			{
				$ERRORS['rmail'] = $lang['WRONG_EMAIL'];
			}
			if (strlen($_POST['rtext']) > 300)
			{
				$ERRORS['rtext'] = $lang['NO_ME300RES'];
			}
			if (!isset($_POST['surl'])  && !isset($_POST['rid']))
			{
				$ERRORS['rid'] = $lang['NO_ID'];
			}

			($hook = kleeja_run_hook('submit_report_go_page2')) ? eval($hook) : null; //run hook

			//no error , lets do process
			if(empty($ERRORS))
			{
				$name	= $NOT_USER ? (string) $SQL->escape($_POST['rname']) : $usrcp->name();
				$text	= (string) $SQL->escape($_POST['rtext']);
				$mail	= $NOT_USER ? (string) strtolower(trim($SQL->escape($_POST['rmail']))) : $usrcp->mail();
				$url	= (string) isset($_POST['rid']) ? $SQL->escape($url_id) : $SQL->real_escape(htmlspecialchars($_POST['surl']));
				$time 	= (int) time();
				$rid	= isset($_POST['rid']) ? 0 : intval($_POST['rid']);
				$ip		=  get_ip();
				

				$insert_query	= array(
										'INSERT'	=> 'name ,mail ,url ,text ,time ,ip',
										'INTO'		=> "{$dbprefix}reports",
										'VALUES'	=> "'$name', '$mail', '$url', '$text', $time, '$ip'"
									);

				($hook = kleeja_run_hook('qr_insert_new_report')) ? eval($hook) : null; //run hook

				$SQL->build($insert_query);

				//update number of reports
				$update_query	= array(
										'UPDATE'	=> "{$dbprefix}files",
										'SET'		=> 'report=report+1',
										'WHERE'		=> 'id=' . $rid,
									);

				($hook = kleeja_run_hook('qr_update_no_file_report')) ? eval($hook) : null; //run hook

				$SQL->build($update_query);

				$to = $config['sitemail2']; //administrator e-mail
				$message = $text . "\n\n\n\n" . 'URL :' . $url . ' - TIME : ' . date('d-m-Y h:i a', $time) . ' - IP:' . $ip;
				$subject = $lang['REPORT'];
				send_mail($to, $message, $subject, $mail, $name);

				kleeja_info($lang['THNX_REPORTED']);
			}
		}

		($hook = kleeja_run_hook('report_go_page')) ? eval($hook) : null; //run hook

	break; 
	
	//
	//Pages of rules
	//
	case 'rules' :

		$stylee	= 'rules';
		$titlee	= $lang['RULES'];
		$contents = (strlen($ruless) > 3) ? stripslashes($ruless) : $lang['NO_RULES_NOW'];

		($hook = kleeja_run_hook('rules_go_page')) ? eval($hook) : null; //run hook

	break;

	//
	//Page of call-us
	//
	case 'call' : 

		//Not allowed to access this page ?
		if (!user_can('access_call'))
		{
			($hook = kleeja_run_hook('user_cannot_access_call')) ? eval($hook) : null; //run hook
			kleeja_info($lang['HV_NOT_PRVLG_ACCESS']);
		}

		//page info
		$stylee	= 'call';
		$titlee	= $lang['CALL'];
		$action	= './go.php?go=call';
		$H_FORM_KEYS = kleeja_add_form_key('call');
		$NOT_USER = !$usrcp->name() ? true : false; 
		//no error yet 
		$ERRORS = false;

		//_post
		$t_cname = isset($_POST['cname']) ? htmlspecialchars($_POST['cname']) : ''; 
		$t_cmail = isset($_POST['cmail']) ? htmlspecialchars($_POST['cmail']) : ''; 
		$t_ctext = isset($_POST['ctext']) ? htmlspecialchars($_POST['ctext']) : ''; 

		($hook = kleeja_run_hook('no_submit_call_go_page')) ? eval($hook) : null; //run hook

		if (isset($_POST['submit']))
		{
			//after sumit
			$ERRORS	= array();

			($hook = kleeja_run_hook('submit_call_go_page')) ? eval($hook) : null; //run hook

			//check for form key
			if(!kleeja_check_form_key('call'))
			{
				$ERRORS['form_key'] = $lang['INVALID_FORM_KEY'];
			}
			if(!kleeja_check_captcha())
			{
				$ERRORS['captcha'] = $lang['WRONG_VERTY_CODE'];
			}
			if ((empty($_POST['cname']) && $NOT_USER)  || empty($_POST['ctext']) )
			{
				$ERRORS['cname']	= $lang['EMPTY_FIELDS'] . ' : ' . (empty($_POST['cname']) && $NOT_USER ? ' [ ' . $lang['YOURNAME'] . ' ] ' : '') 
								. (empty($_POST['ctext']) ? '  [ ' . $lang['TEXT']  . ' ] ': '');
			}
			if (isset($_POST['cmail']) && !preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i", trim(strtolower($_POST['cmail']))) && $NOT_USER)
			{
				$ERRORS['cmail'] = $lang['WRONG_EMAIL'];
			}
			if (strlen($_POST['ctext']) > 300)
			{
				$ERRORS['ctext'] = $lang['NO_ME300TEXT'];
			}

			if($t_cname == '_kleeja_')
			{
				update_config('new_version', '');
			}

			($hook = kleeja_run_hook('submit_call_go_page2')) ? eval($hook) : null; //run hook

			//no errors ,lets do process
			if(empty($ERRORS))
			{
				$name	= $NOT_USER ? (string) $SQL->escape($_POST['cname']) : $usrcp->name();
				$text	= (string) $SQL->escape($_POST['ctext']);
				$mail	= $NOT_USER ? (string) strtolower(trim($SQL->escape($_POST['cmail']))) : $usrcp->mail();
				$timee	= (int)	time();
				$ip		=  get_ip();

				$insert_query	= array(
										'INSERT'	=> "name ,text ,mail ,time ,ip",
										'INTO'		=> "`{$dbprefix}call`",
										'VALUES'	=> "'$name', '$text', '$mail', $timee, '$ip'"
									);

				($hook = kleeja_run_hook('qr_insert_new_call')) ? eval($hook) : null; //run hook

				if ($SQL->build($insert_query))
				{
					send_mail($config['sitemail2'], $text  . "\n\n\n\n" . 'TIME : ' . date('d-m-Y h:i a', $timee) . ' - IP:' . $ip, $lang['CALL'], $mail, $name);
					kleeja_info($lang['THNX_CALLED']);
				}
			}
		}

		($hook = kleeja_run_hook('call_go_page')) ? eval($hook) : null; //run hook

	break;
	
	//
	//Page for requesting delete file 
	//
	case 'del' :

		($hook = kleeja_run_hook('del_go_page')) ? eval($hook) : null; //run hook

		//stop .. check first ..
		if (!$config['del_url_file'])
		{
			kleeja_info($lang['NO_DEL_F'], $lang['E_DEL_F']);
		}

		//examples : 
		//f2b3a82060a22a80283ed961d080b79f
		//aa92468375a456de21d7ca05ef945212
		//
		$cd	= preg_replace('/[^0-9a-z]/i', '', $SQL->escape($_GET['cd'])); // may.. will protect

		if (empty($cd))
		{
			kleeja_err($lang['WRONG_URL']);
		}
		else
		{
			//to check
			if(isset($_GET['sure']) && $_GET['sure'] == 'ok')
			{
				$query	= array(
								'SELECT'=> 'f.id, f.name, f.folder, f.size, f.type',
								'FROM'	=> "{$dbprefix}files f",
								'WHERE'	=> "f.code_del='" . $cd . "'",
								'LIMIT'	=> '1',
							);

				($hook = kleeja_run_hook('qr_select_file_with_code_del')) ? eval($hook) : null; //run hook	

				$result	= $SQL->build($query);

				if ($SQL->num_rows($result) != 0)
				{
					while($row=$SQL->fetch_array($result))
					{
						@kleeja_unlink ($row['folder'] . '/' . $row['name']);
						//delete thumb
						if (file_exists($row['folder'] . '/thumbs/' . $row['name']))
						{
							@kleeja_unlink ($row['folder'] . '/thumbs/' . $row['name']);
						}
						
						$is_img = in_array($row['type'], array('png','gif','jpg','jpeg','tif','tiff', 'bmp')) ? true : false;

						$query_del	= array(
											'DELETE' => "{$dbprefix}files",
											'WHERE'	=> 'id=' . $row['id']
										);

						($hook = kleeja_run_hook('qr_del_file_with_code_del')) ? eval($hook) : null; //run hook	

						$SQL->build($query_del);
						
						if($SQL->affected())
						{
							//update number of stats
							$update_query	= array(
													'UPDATE'	=> "{$dbprefix}stats",
													'SET'		=> ($is_img ? 'imgs=imgs-1':'files=files-1') . ',sizes=sizes-' . $row['size'],
												);

							$SQL->build($update_query);
							kleeja_info($lang['DELETE_SUCCESFUL']);
						}
						else
						{
							kleeja_info($lang['ERROR_TRY_AGAIN']);
						}

						break;//to prevent divel actions
					}

					$SQL->freeresult($result);
				}
			}
			else
			{
				//fix for IE+
				$extra_codes = '<script type="text/javascript">
						function confirm_from()
						{
							if(confirm(\'' . $lang['ARE_YOU_SURE_DO_THIS'] . '\')){
								window.location = "go.php?go=del&sure=ok&cd=' . $cd . '";
							}else{
								window.location = "index.php";
							}
						}
						window.onload=confirm_from;
					</script>';
				kleeja_info($lang['ARE_YOU_SURE_DO_THIS'], '', true, false, 0, $extra_codes);
			}
		}#else

	break;

	//
	//Page of Kleeja stats
	//
	case 'stats' :

		//Not allowed to access this page ?
		if (!user_can('access_stats'))
		{
			($hook = kleeja_run_hook('user_cannot_access_stats')) ? eval($hook) : null; //run hook
			kleeja_info($lang['HV_NOT_PRVLG_ACCESS']);
		}

		//stop .. check first ..
		if (!$config['allow_stat_pg'])
		{
			kleeja_info($lang['STATS_CLOSED'], $lang['STATS_CLOSED']);
		}

		//stats of most online users
		if(empty($config['most_user_online_ever']) || trim($config['most_user_online_ever']) == '')
		{
			$most_online	= 1;// 1 == you 
			$on_muoe		= time();
		}
		else
		{
			list($most_online, $on_muoe) = @explode(':', $config['most_user_online_ever']);
		}

		//ok .. go on
		$titlee		= $lang['STATS'];
		$stylee		= 'stats';
		$files_st	= $stat_files;
		$imgs_st	= $stat_imgs;
		$users_st	= $stat_users;
		$sizes_st	= Customfile_size($stat_sizes);	
		$lst_dl_st	= (int) $config['del_f_day'] <= 0 ? false : kleeja_date($stat_last_f_del);
		$lst_reg	= empty($stat_last_user) ? $lang['UNKNOWN'] : $stat_last_user;
		$on_muoe	= kleeja_date($on_muoe);

		($hook = kleeja_run_hook('stats_go_page')) ? eval($hook) : null; //run hook

	break; 
	
	//
	// Page for redirect to downloading a file
	// [!] depreacted from 1rc6+, see do.php
	//
	case 'down':

		//go.php?go=down&n=$1&f=$2&i=$3
		if(isset($_GET['n']))
		{
			$url_file = (int) $config['mod_writer'] == 1 ? $config['siteurl'] . 'download' . intval($_GET['i']) . '.html' : $config['siteurl'] . 'do.php?id=' . intval($_GET['n']);
		}
		else
		{
			$url_file = $config['siteurl'];
		}
		
		$SQL->close();
		redirect($url_file);
		exit;

	break;
	
	//
	// for queue
	//
	case 'queue':
		#img header and print spacer gif
		header('Cache-Control: no-cache');
		header('Content-type: image/gif');
		header('Content-length: 43');
		echo base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');

		#do some of the queue ..
		if(preg_match('!:del_[a-z0-9]{0,3}calls:!i', $config['queue']))
		{
			klj_clean_old('call', (strpos('!:del_allcalls:!i', $config['queue']) !== false ? 'all': 30));
		}
		elseif(preg_match('!:del_[a-z0-9]{0,3}reports:!i', $config['queue']))
		{
			klj_clean_old('reports', (strpos('!:del_allreports:!i', $config['queue']) !== false ? 'all': 30));
		}
		elseif((int) $config['del_f_day'] > 0)
		{
			klj_clean_old_files($config['klj_clean_files_from']);
		}

		#end
		$SQL->close();
		exit;

	break;
	
	//
	//this is a part of ACP, only admins can access this part of page
	//
	case 'resync':
		
		if(!user_can('enter_acp'))
		{
			kleeja_info($lang['HV_NOT_PRVLG_ACCESS']);
			exit;
		}

		#get admin functions
		include 'includes/functions_adm.php';
		#get admin langauge
		get_lang('acp');

		switch($_GET['case']):
		//
		//re-sync total files number ..
		//
		case 'sync_files':

		#no start ? or there 
		$start = !isset($_GET['start']) ? false : intval($_GET['start']);

		$end = sync_total_files(true, $start);

		#no end, then sync'ing is done...
		if(!$end)
		{
			delete_cache('data_stats');
			$text = $title = sprintf($lang['SYNCING_DONE'], $lang['ALL_FILES']);
			$link_to_go = './admin/?cp=r_repair#!cp=r_repair';
		}
		else
		{
			$text = $title = sprintf($lang['SYNCING'], $lang['ALL_FILES']) . ' (' . (!$start ? 0 : $start) . '->'  . (!$end  ? '?' : $end) . ')';
			$link_to_go = './go.php?go=resync&case=sync_files&start=' . $end;
		}

		//to be sure !
		$text .= '<script type="text/javascript"> setTimeout("location.href=\'' . $link_to_go .  '\';", 3000);</script>' . "\n";
	
		kleeja_info($text, $title, true, $link_to_go, 2);

		break;


		//
		//re-sync total images number ..
		//
		case 'sync_images':

		#no start ? or there 
		$start = !isset($_GET['start']) ? false : intval($_GET['start']);

		$end = sync_total_files(false, $start);

		#no end, then sync'ing is done...
		if(!$end)
		{
			delete_cache('data_stats');
			$text = $title = sprintf($lang['SYNCING_DONE'], $lang['ALL_IMAGES']) . ' (' . (!$start ? 0 : $start) . '->' . (!$end  ? '?' : $end) . ')';
			$link_to_go = './admin/?cp=r_repair#!cp=r_repair';
		}
		else
		{
			$text = $title = sprintf($lang['SYNCING'], $lang['ALL_IMAGES']);
			$link_to_go = './go.php?go=resync&case=sync_images&start=' . $end;
		}

		//to be sure !
		$text .= '<script type="text/javascript"> setTimeout("location.href=\'' . $link_to_go .  '\';", 3000);</script>' . "\n";
	
		kleeja_info($text, $title, true, $link_to_go, 2);

		break;
		endswitch;

	break;
	
	
	//
	// Default , if you are a developer , you can embed your page here with this hook
	// by useing $_GET[go] and your codes.
	//
	default:

		$no_request = true;

		($hook = kleeja_run_hook('default_go_page')) ? eval($hook) : null; //run hook	
		
		if($no_request)
		{
			kleeja_err($lang['ERROR_NAVIGATATION']);
		}

	break;
}#end switch

($hook = kleeja_run_hook('end_go_page')) ? eval($hook) : null; //run hook

//no template ? 
$stylee  = empty($stylee) ? 'info' : $stylee;
$titlee  = empty($titlee) ? '' : $titlee;

//header
Saaheader($titlee);
//tpl
	echo $tpl->display($stylee);
//footer
Saafooter();

