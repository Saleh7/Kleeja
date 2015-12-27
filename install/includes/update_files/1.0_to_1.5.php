<?php
// not for directly open
if (!defined('IN_COMMON'))
{
	exit();
}

//
//db version when this update was released
//
define ('DB_VERSION' , '8');


#configs, groups_data .. things are not quite simple here
include dirname(__file__) . '/../default_values.php';

#new tables
$update_sqls['filters'] = "
CREATE TABLE `{$dbprefix}filters` (
  `filter_id` int(11) NOT NULL AUTO_INCREMENT,
  `filter_uid` varchar(30) COLLATE utf8_bin NOT NULL,
  `filter_type` varchar(20) COLLATE utf8_bin NOT NULL,
  `filter_value` varchar(255) COLLATE utf8_bin NOT NULL,
  `filter_time` int(11) unsigned NOT NULL,
  `filter_user` int(11) unsigned NOT NULL DEFAULT '0',
  `filter_status` varchar(50) COLLATE utf8_bin NOT NULL DEFAULT '',
  PRIMARY KEY (`filter_id`),
  KEY `filter_user` (`filter_user`),
  KEY `filter_uid` (`filter_uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;
";

$update_sqls['groups'] = "
CREATE TABLE `{$dbprefix}groups` (
  `group_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `group_name` varchar(255) COLLATE utf8_bin NOT NULL,
  `group_is_default` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `group_is_essential` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`group_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
";

$update_sqls['groups_data'] = "
CREATE TABLE `{$dbprefix}groups_data` (
  `group_id` int(11) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8_bin NOT NULL,
  `value` varchar(255) COLLATE utf8_bin NOT NULL,
  KEY `group_id` (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
";

$update_sqls['groups_acl'] = "
CREATE TABLE `{$dbprefix}groups_acl` (
  `acl_name` varchar(255) COLLATE utf8_bin NOT NULL,
  `group_id` int(11) unsigned NOT NULL,
  `acl_can` tinyint(1) unsigned NOT NULL DEFAULT '0',
  KEY `group_id` (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
";


$update_sqls['configs_2_groups'] = "UPDATE  `{$dbprefix}config` SET  `type` =  'groups' WHERE `name` IN('language', 'enable_userfile', 'filesnum', 'sec_down', 'write_imgs', 'usersectoupload');";

foreach($config_values as $m=>$t)
{
	if($t[4] === 'groups')
	{
		$update_sqls['groups_data_' . $t[0]] = "INSERT INTO `{$dbprefix}groups_data` (`name`, `value`, `group_id`) VALUES ('" . $t[0] . "', '" . $t[1] . "', 1), ('" . $t[0] . "', '" . $t[1] . "', 2), ('" . $t[0] . "', '" . $t[1] . "', 3);";
	}
}


#new configs
$update_sqls['config_default_group'] = "INSERT INTO `{$dbprefix}config` (`name`, `value`, `option`, `display_order`, `type`, `plg_id`, `dynamic`) VALUES ('default_group', '3', '', '', '0', '0', '0');";
$update_sqls['time_zone'] = "INSERT INTO `{$dbprefix}config` (`name`, `value`, `option`, `display_order`, `type`, `plg_id`, `dynamic`) VALUES ('time_zone', '3', '<select name=\"time_zone\" id=\"time_zone\">\r\n {time_zones}\r\n </select>', '10', 'general', '0', '0');";

#remove plugin kleeja-live-folder-name
if(($klive_id = kleeja_plugin_exists('kleeja-live-folder-name')))
{
	$update_sqls['klive_delete1'] = "DELETE FROM `{$dbprefix}hooks` WHERE plg_id = $klive_id ;";
	$update_sqls['klive_delete2'] = "DELETE FROM `{$dbprefix}plugins` WHERE plg_id = $klive_id ;";
}
else
{
	$update_sqls['klive1'] = "INSERT INTO `{$dbprefix}config` (`name`, `value`, `option`, `display_order`, `type`, `plg_id`, `dynamic`) VALUES ('imagefolder', 'uploads', '<input type=\"text\" id=\"imagefolder\" name=\"imagefolder\" value=\"{con.imagefolder}\" size=\"40\">', '10', 'KLIVE', '0', '0');";
	$update_sqls['klive2'] = "INSERT INTO `{$dbprefix}config` (`name`, `value`, `option`, `display_order`, `type`, `plg_id`, `dynamic`) VALUES ('imagefolderexts', '', '<input type=\"text\" id=\"imagefolderexts\" name=\"imagefolderexts\" value=\"{con.imagefolderexts}\" size=\"80\">', '20', 'KLIVE', '0', '0');";
	$update_sqls['klive3'] = "INSERT INTO `{$dbprefix}config` (`name`, `value`, `option`, `display_order`, `type`, `plg_id`, `dynamic`) VALUES ('imagefoldere', '1', '<label>{lang.YES}<input type=\"radio\" id=\"imagefoldere\" name=\"imagefoldere\" value=\"1\"  <IF NAME=\"con.imagefoldere\"> checked=\"checked\"</IF>></label><label>{lang.NO}<input type=\"radio\" id=\"imagefoldere\" name=\"imagefoldere\" value=\"0\"  <IF NAME=\"con.imagefoldere\"> <ELSE> checked=\"checked\"</IF>></label>', '30', 'KLIVE', '0', '0');";
}


#register time to users table
$update_sqls['register_time'] = "ALTER TABLE  `{$dbprefix}users` ADD  `register_time` INT( 11 ) UNSIGNED NOT NULL DEFAULT  '0' AFTER  `last_visit`;";

#alter stats table
$update_sqls['alter_stats_add_imgs'] = "ALTER TABLE  `{$dbprefix}stats` ADD  `imgs` INT( 11 ) UNSIGNED NOT NULL DEFAULT  '0' AFTER  `files`;";

#reset stats
$update_sqls['stats_to_0'] = "UPDATE  `{$dbprefix}stats` SET  `files` =  '0', `imgs` =  '0';";


$update_sqls['groups_insert'] = "INSERT INTO `{$dbprefix}groups` (`group_id`, `group_name`, `group_is_default`, `group_is_essential`) VALUES
(1, '{lang.ADMINS}', 0, 1),
(2, '{lang.GUESTS}', 0, 1),
(3, '{lang.USERS}', 1, 1)";

$update_sqls['admin2founder'] = "ALTER TABLE  `{$dbprefix}users` CHANGE  `admin`  `founder` TINYINT( 1 ) NOT NULL DEFAULT  '0'";
$update_sqls['group_id4users'] = "ALTER TABLE  `{$dbprefix}users` ADD  `group_id` INT( 11 ) UNSIGNED NOT NULL DEFAULT  '3' AFTER  `name` , ADD INDEX (  `group_id` )";
$update_sqls['group_id4adm'] = "UPDATE  `{$dbprefix}users` SET  `group_id` =  '1' WHERE  `founder`= '1';";

$update_sqls['stats_yahoo'] = "ALTER TABLE  `{$dbprefix}stats` CHANGE  `last_yahoo`  `last_bing` INT( 11 ) UNSIGNED NOT NULL;";
$update_sqls['stats_yahoo2'] = "ALTER TABLE  `{$dbprefix}stats` CHANGE  `yahoo_num`  `bing_num`  INT( 11 ) UNSIGNED NOT NULL;";
#Acls!!
#1 = admin, 2 = guests, 3 = users [ 1 = true, 0  = false ]
$update_sqls['groups_acls_enter_acp'] = "INSERT INTO `{$dbprefix}groups_acl` (`acl_name`, `group_id`, `acl_can`) VALUES ('enter_acp', 1, 1), ('enter_acp', 2, 0), ('enter_acp', 3, 0);";
$update_sqls['groups_acls_access_fileuser'] = "INSERT INTO `{$dbprefix}groups_acl` (`acl_name`, `group_id`, `acl_can`) VALUES ('access_fileuser', 1, 1), ('access_fileuser', 2, 0), ('access_fileuser', 3, 1);";
$update_sqls['groups_acls_access_fileusers'] = "INSERT INTO `{$dbprefix}groups_acl` (`acl_name`, `group_id`, `acl_can`) VALUES ('access_fileusers', 1, 1), ('access_fileusers', 2, 1), ('access_fileusers', 3, 1);";
$update_sqls['groups_acls_access_stats'] = "INSERT INTO `{$dbprefix}groups_acl` (`acl_name`, `group_id`, `acl_can`) VALUES ('access_stats', 1, 1), ('access_stats', 2, 1), ('access_stats', 3, 1);";
$update_sqls['groups_acls_access_call'] = "INSERT INTO `{$dbprefix}groups_acl` (`acl_name`, `group_id`, `acl_can`) VALUES ('access_call', 1, 1), ('access_call', 2, 1), ('access_call', 3, 1);";
$update_sqls['groups_acls_access_report'] = "INSERT INTO `{$dbprefix}groups_acl` (`acl_name`, `group_id`, `acl_can`) VALUES ('access_report', 1, 0), ('access_report', 2, 0), ('access_report', 3, 0);";

#exts !!
$update_sqls['groups_exts'] = "
CREATE TABLE `{$dbprefix}groups_exts` (
  `ext_id` int(11) NOT NULL AUTO_INCREMENT,
  `ext` varchar(20) COLLATE utf8_bin NOT NULL,
  `group_id` int(11) unsigned NOT NULL DEFAULT '0',
  `size` int(11) NOT NULL,
  PRIMARY KEY (`ext_id`),
  KEY `group_id` (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1;
";

$update_sqls['exts2groups_exts'] = "INSERT INTO `{$dbprefix}groups_exts` (`ext_id`, `ext`, `group_id`, `size`) VALUES
(1, 'gif', 1, 2097152),
(2, 'png', 1, 2097152),
(3, 'jpg', 1, 2097152),
(4, 'jpeg', 1, 2097152),
(5, 'bmp', 1, 2097152),
(6, 'zip', 1, 2097152),
(7, 'rar', 1, 2097152),
(8, 'gif', 2, 2097152),
(9, 'png', 2, 2097152),
(10, 'jpg', 2, 2097152),
(11, 'jpeg', 2, 2097152),
(12, 'bmp', 2, 2097152),
(13, 'zip', 2, 2097152),
(14, 'rar', 2, 2097152),
(15, 'gif', 3, 2097152),
(16, 'png', 3, 2097152),
(17, 'jpg', 3, 2097152),
(18, 'jpeg', 3, 2097152),
(19, 'bmp', 3, 2097152),
(20, 'zip', 3, 2097152),
(21, 'rar', 3, 2097152);";


