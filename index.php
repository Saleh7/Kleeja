<?php
/**
*
* @package Kleeja
* @version $Id: index.php 2121 2013-01-04 23:53:36Z saanina $
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/


define ('IN_INDEX', true);
define ('IN_REAL_INDEX', true);
define ('IN_SUBMIT_UPLOADING' , (isset($_POST['submitr']) || isset($_POST['submittxt'])));

include ('includes/common.php');

($hook = kleeja_run_hook('begin_index_page')) ? eval($hook) : null; //run hook

//
//Is kleeja only for memebers ?!
//
if(empty($d_groups[2]['exts']) && !$usrcp->name())
{
	// Send a 503 HTTP response code to prevent search bots from indexing this message
	//header('HTTP/1.1 503 Service Temporarily Unavailable');
	kleeja_info($lang['SITE_FOR_MEMBER_ONLY'], $lang['HOME']);
}

//
//Type of how will decoding name ..
//
$decode = 'none';
switch(intval($config['decode'])):
	case 1:	$decode = 'time';	break;
	case 2:	$decode = 'md5';	break;
	default:
		//add you own decode
		($hook = kleeja_run_hook('decode_config_default')) ? eval($hook) : null; //run hook
	break;
endswitch;

//
//start uploader class ..
//
$kljup->decode		= $decode;
$kljup->folder		= $config['foldername'];
$kljup->prefix		= $config['prefixname'];
$kljup->action		= $action = "index.php";
$kljup->filesnum	= $config['filesnum'];
//--------------------- start user system part
$kljup->types		= $d_groups[$userinfo['group_id']]['exts'];
$kljup->id_user		= ($usrcp->name()) ? $usrcp->id() : '-1';
$kljup->user_is_adm = user_can('enter_acp');
$kljup->safe_code	= $config['safe_code'];
//--------------------- end user system part
$kljup->process();

//add from 1rc6
$FILES_NUM_LOOP = array();

if($config['filesnum'] > 0)
{
    foreach(range(1, $config['filesnum']) as $i)
    {
    	$FILES_NUM_LOOP[] = array('i' => $i, 'show'=>($i == 1 || (!empty($config['filesnum_show']) && (int) $config['filesnum_show'] == 1) ? '' : 'display: none'));
    }
}
else
{
    $text = $lang['PLACE_NO_YOU'];
}

//show errors and info
$info = array();
foreach($kljup->messages as $t=>$s)
{
	$info[] = array('t'=>$s[1], 'i' => $s[0]);
}

//some words for template
$welcome_msg	= $config['welcome_msg'];
$filecp_link	= $usrcp->id() ? $config['siteurl'] . ($config['mod_writer'] ? 'filecp.html' : 'ucp.php?go=filecp') : false;
$terms_msg		= sprintf($lang['AGREE_RULES'], '<a href="' . ($config['mod_writer'] ? 'rules.html' : 'go.php?go=rules') . '">' , '</a>');
$link_avater		= sprintf($lang['EDIT_U_AVATER_LINK'], '<a href="http://www.gravatar.com/">' , '</a>');
//
//For who online now..
//I dont like this feature and I prefer to disable it
//
$show_online = $config['allow_online'] == 1 ? true : false;
if ($show_online)
{
	$usersnum	=	0;
	$online_names	= array();
	$timeout		= 30; //30 second
	$timeout2		= time()-$timeout;

	//put another bot name
	($hook = kleeja_run_hook('anotherbots_online_index_page')) ? eval($hook) : null; //run hook

	$query = array(
					'SELECT'	=> 'u.name',
					'FROM'		=> "{$dbprefix}users u",
					'WHERE'		=> "u.last_visit > $timeout2"
				);

	($hook = kleeja_run_hook('qr_select_online_index_page')) ? eval($hook) : null; //run hook

	$result	= $SQL->build($query);

	while($row=$SQL->fetch_array($result))
	{
		($hook = kleeja_run_hook('while_qr_select_online_index_page')) ? eval($hook) : null; //run hook

		$usersnum++;
		$online_names[$row['name']] = $row['name'];
	}#while

	$SQL->freeresult($result);

	//make names as array to print them in template
	$shownames = array();
	$shownames_sizeof =  sizeof($shownames);

	foreach ($online_names as $k)
	{
		$shownames[] = array('name' => $k, 'seperator' => $shownames_sizeof ? ',' : '');
	}

	//some variables must be destroyed here
	unset($online_names, $timeout, $timeout2);

	//check & update most ever users and vistors was online
	if(empty($config['most_user_online_ever']) || trim($config['most_user_online_ever']) == '')
	{
		$most_online	= $usersnum;
		$on_muoe		= time();
	}
	else
	{
		list($most_online, $on_muoe) = @explode($config['most_user_online_ever']);
	}

	if((int) $most_online < $allnumbers || (empty($config['most_user_online_ever']) || trim($config['most_user_online_ever']) == ''))
	{
		update_config('most_user_online_ever', $usersnum . ':' . time());
	}

	$on_muoe = date('d-m-Y h:i a', $on_muoe);

	if(!$usersnum)
	{
		$show_online = false;
	}


	($hook = kleeja_run_hook('if_online_index_page')) ? eval($hook) : null; //run hook
}#allow_online


($hook = kleeja_run_hook('end_index_page')) ? eval($hook) : null; //run hook


//header
Saaheader();
//index
echo $tpl->display(($config['filesnum'] > 0 ? "index_body" : "info"));
//footer
Saafooter();

//<-- EOF
