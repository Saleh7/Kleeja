<?php
/**
*
* @package auth
* @version $Id: mysmartbb.php 2091 2012-11-12 16:32:21Z saanina $
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/


//no for directly open
if (!defined('IN_COMMON'))
{
	exit();
}

//
//Path of config file in MySBB
//
if(!defined('SCRIPT_CONFIG_PATH'))
{
	define('SCRIPT_CONFIG_PATH', '/engine/config.php');
}

function kleeja_auth_login ($name, $pass, $hashed = false, $expire, $loginadm = false, $return_name = false)
{
	global $lang, $config, $usrcp, $userinfo;
	global $script_path, $script_encoding, $script_srv, $script_db, $script_user, $script_pass, $script_prefix;

	if(isset($script_path))
	{
		//check for last slash / 
		if(isset($script_path[strlen($script_path)]) && $script_path[strlen($script_path)] == '/')
		{
			$script_path = substr($script_path, 0, strlen($script_path));
		}

		//get database data from mysmartbb config file
		if(file_exists(PATH . $script_path . SCRIPT_CONFIG_PATH)) 
		{
			require_once (PATH . $script_path . SCRIPT_CONFIG_PATH);
			$forum_srv	= $config['db']['server'];
			$forum_db	= $config['db']['name'];
			$forum_user	= $config['db']['username'];
			$forum_pass	= $config['db']['password'];
			$forum_prefix = $config['db']['prefix'];
		} 
		else
		{
			big_error('Forum path is not correct', sprintf($lang['SCRIPT_AUTH_PATH_WRONG'], 'MySmartBB'));
		}
	}
	else
	{
		$forum_srv	= $script_srv;
		$forum_db	= $script_db;
		$forum_user	= $script_user;
		$forum_pass	= $script_pass;
		$forum_prefix = $script_prefix;
	}

	if(empty($forum_srv) || empty($forum_user) || empty($forum_db))
	{
		return;
	}

	$SQLMS	= new SSQL($forum_srv, $forum_user, $forum_pass, $forum_db, true);

	$SQLVB->set_names('latin1');
	
	$pass = $usrcp->kleeja_utf8($pass, false);
	$name = $usrcp->kleeja_utf8($name, false);

	$query = array(
					'SELECT'	=> '*',
					'FROM'	=> "`{$forum_prefix}member`",
				);
	
	$query['WHERE'] = $hashed ?  "id=" . intval($name) . " AND password='" . $SQLMS->real_escape($pass) . "'" : "username='" . $SQLMS->real_escape($name) . "' AND password='" . md5($pass) . "'";
	
	//if return only name let's ignore the obove
	if($return_name)
	{
		$query_salt['SELECT']	= "username";
		$query_salt['WHERE']	= "id=" . intval($name);
	}
	
	($hook = kleeja_run_hook('qr_select_usrdata_mysbb_usr_class')) ? eval($hook) : null; //run hook	
	$result = $SQLMS->build($query);


	if ($SQLMS->num_rows($result) != 0) 
	{
		while($row=$SQLMS->fetch_array($result))
		{
			if($return_name)
			{
				return $row['username'];
			}
			
			if(!$loginadm)
			{
				define('USER_ID',$row['id']);
				define('GROUP_ID', ($row['usergroup'] == 1 ? 1 : 3));
				define('USER_NAME', $usrcp->kleeja_utf8($row['username']));
				define('USER_MAIL',$row['email']);
				define('USER_ADMIN',($row['usergroup'] == 1 ? 1 : 0));
			}

			$userinfo = $row;
			$userinfo['group_id'] = GROUP_ID;
			$user_y = kleeja_base64_encode(serialize(array('id'=>$row['id'], 'name'=>$usrcp->kleeja_utf8($row['username']), 'mail'=>$row['email'], 'last_visit'=>time())));

			$hash_key_expire = sha1(md5($config['h_key'] . $row['password']) .  $expire);
			if(!$hashed && !$loginadm)
			{
				$usrcp->kleeja_set_cookie('ulogu', $usrcp->en_de_crypt($row['id'] . '|' . $row['password'] . '|' . $expire . '|' . $hash_key_expire . '|' . GROUP_ID . '|' . $user_y), $expire);
			}

			($hook = kleeja_run_hook('qr_while_usrdata_mysbb_usr_class')) ? eval($hook) : null; //run hook
			
		}

		$SQLMS->freeresult($result); 
		unset($pass);
		$SQLMS->close();

		return true;
	}
	else
	{
		$SQLMS->close();
		return false;
	}
}	

function kleeja_auth_username ($user_id)
{
	return kleeja_auth_login ($user_id, false, false, 0, false, true);
}
