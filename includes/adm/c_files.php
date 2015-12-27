<?php
/**
*
* @package adm
* @version $Id: c_files.php 2061 2012-10-17 04:21:02Z saanina $
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/


// not for directly open
if (!defined('IN_ADMIN'))
{
	exit();
}

//for style ..
$stylee		= "admin_files";

$url_or		= isset($_REQUEST['order_by']) ? '&amp;order_by=' . htmlspecialchars($_REQUEST['order_by']) . (isset($_REQUEST['order_way']) ? '&amp;order_by=1' : '') : '';
$url_or2	= isset($_REQUEST['order_by']) ? '&amp;order_by=' . htmlspecialchars($_REQUEST['order_by'])  : '';
$url_lst	= isset($_REQUEST['last_visit']) ? '&amp;last_visit=' . htmlspecialchars($_REQUEST['last_visit']) : '';
$url_sea	= isset($_GET['search_id']) ? '&amp;search_id=' . htmlspecialchars($_GET['search_id']) : '';
$url_pg		= isset($_GET['page']) ? '&amp;page=' . intval($_GET['page']) : '';
$page_action	= basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php')  . $url_or . $url_sea . $url_lst;
$ord_action		= basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . $url_pg . $url_sea . $url_lst;
$page2_action	= basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . $url_or2 . $url_sea . $url_lst;
$action			= $page_action . $url_pg;
$is_search		= $affected = false;
$H_FORM_KEYS	= kleeja_add_form_key('adm_files');

//
// Check form key
//

if (isset($_POST['submit']))
{
	#wrong form
	if(!kleeja_check_form_key('adm_files'))
	{
		kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, $action, 1);
	}

	#gather to-be-deleted file ids
	foreach ($_POST as $key => $value) 
    {
        if(preg_match('/del_(?P<digit>\d+)/', $key))
        {
            $del[$key] = $value;
        }
    }
   
   #delete them once by once
   $ids = array();
   $files_num = $imgs_num = 0;

    foreach ($del as $key => $id)
    {
        $query	= array(
						'SELECT'	=> 'f.id, f.name, f.folder, f.size, f.type',
						'FROM'			=> "{$dbprefix}files f",
						'WHERE'			=> 'f.id = ' . intval($id),
					);

		$result = $SQL->build($query);

		while($row=$SQL->fetch_array($result))
		{
			//delete from folder ..
			@kleeja_unlink (PATH . $row['folder'] . '/' . $row['name']);
			//delete thumb
			if (file_exists(PATH . $row['folder'] . '/thumbs/' . $row['name'] ))
			{
				@kleeja_unlink (PATH . $row['folder'] . '/thumbs/' . $row['name'] );
			}

			$is_image = in_array(strtolower(trim($row['type'])), array('gif', 'jpg', 'jpeg', 'bmp', 'png')) ? true : false;

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
   
	$SQL->freeresult($result);
  
	//no files to delete
	if(isset($ids) && sizeof($ids))
	{
		$query_del = array(
								'DELETE'	=> "{$dbprefix}files",
								'WHERE'	=> "`id` IN (" . implode(',', $ids) . ")"
							);
			
		$SQL->build($query_del);

		//update number of stats
		$update_query	= array(
									'UPDATE'	=> "{$dbprefix}stats",
									'SET'		=> "sizes=sizes-$sizes, files=files-$files_num, imgs=imgs-$imgs_num",
								);

		$SQL->build($update_query);
		if($SQL->affected())
		{
			delete_cache('data_stats');
			$affected = true;
		}
	}
	
	#show msg now
	$text	= ($affected ? $lang['FILES_UPDATED'] : $lang['NO_UP_CHANGE_S']) .
				'<script type="text/javascript"> setTimeout("get_kleeja_link(\'' . str_replace('&amp;', '&', $action) .  '\');", 2000);</script>' . "\n";
	$stylee	= "admin_info";
}
else
{

//
//Delete all user files [only one user]			
//
if(isset($_GET['deletefiles']))
{
	$query	= array(
					'SELECT'	=> 'f.id, f.size, f.name, f.folder',
					'FROM'		=> "{$dbprefix}files f",
				);

	#get search filter
	$filter = get_filter($_GET['search_id'], 'filter_uid');
	
	if(!$filter)
	{
		kleeja_admin_err($lang['ADMIN_DELETE_FILES_NOF']);
	}

	$query['WHERE'] = build_search_query(unserialize(htmlspecialchars_decode($filter['filter_value'])));

	if($query['WHERE'] == '')
	{
		kleeja_admin_err($lang['ADMIN_DELETE_FILES_NOF']);
	}

	$result = $SQL->build($query);
	$sizes  = false;
	$ids = array();
	$files_num = $imgs_num = 0;
	while($row=$SQL->fetch_array($result))
	{
		//delete from folder ..
		@kleeja_unlink (PATH . $row['folder'] . "/" . $row['name']);

		//delete thumb
		if (file_exists(PATH . $row['folder'] . "/thumbs/" . $row['name']))
		{
			@kleeja_unlink (PATH . $row['folder'] . "/thumbs/" . $row['name']);
		}

		$is_image = in_array(strtolower(trim($row['type'])), array('gif', 'jpg', 'jpeg', 'bmp', 'png')) ? true : false;

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

	$SQL->freeresult($result);

	if(($files_num + $imgs_num) == 0)
	{
		kleeja_admin_err($lang['ADMIN_DELETE_FILES_NOF']);
	}
	else
	{
		//update number of stats
		$update_query	= array(
								'UPDATE'	=> "{$dbprefix}stats",
								'SET'		=> "sizes=sizes-$sizes, files=files-$files_num, imgs=imgs-$imgs_num",
						);

		$SQL->build($update_query);
		if($SQL->affected())
		{
			delete_cache('data_stats');
		}

		//delete all files in just one query
		$query_del	= array(
							'DELETE'	=> "{$dbprefix}files",
							'WHERE'	=> "`id` IN (" . implode(',', $ids) . ")"
						);

		$SQL->build($query_del);

		kleeja_admin_info(sprintf($lang['ADMIN_DELETE_FILES_OK'], $num));
	}
}

//
//begin default files page
//

$query	= array(
				'SELECT'	=> 'COUNT(f.id) AS total_files',
				'FROM'		=> "{$dbprefix}files f",
				'ORDER BY'	=> 'f.id '
		);

#if user system is default, we use users table
if((int) $config['user_system'] == 1)
{
	$query['JOINS']	=	array(
								array(
									'LEFT JOIN'	=> "{$dbprefix}users u",
									'ON'		=> 'u.id=f.user'
								)
							);
}

$do_not_query_total_files = false;

//posts search ..
if(isset($_GET['search_id']))
{
	#get search filter 
	$filter = get_filter($_GET['search_id'], 'filter_uid');
	$deletelink = basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&deletefiles=' . htmlspecialchars($_GET['search_id']);
	$is_search	= true;
	$query['WHERE'] = build_search_query(unserialize(htmlspecialchars_decode($filter['filter_value'])));
}
else if(isset($_REQUEST['last_visit']))
{
	$query['WHERE']	= "f.time > " . intval($_REQUEST['last_visit']);
}

if(isset($_REQUEST['order_by']) && in_array($_REQUEST['order_by'], array('real_filename', 'size', 'user', 'user_ip', 'uploads', 'time', 'type', 'folder', 'report')))
{
	$query['ORDER BY'] = "f." . $SQL->escape($_REQUEST['order_by']);
}
else
{
	$do_not_query_total_files = true;
}

if(!isset($_GET['search_id']))
{
	//display files or display pics and files only in search
	$img_types = array('gif','jpg','png','bmp','jpeg','GIF','JPG','PNG','BMP','JPEG');
	$query['WHERE'] = $query['WHERE'] . (empty($query['WHERE']) ? '' : ' AND ') . "f.type NOT IN ('" . implode("', '", $img_types) . "')";
}

$query['ORDER BY'] .= (isset($_REQUEST['order_way']) && (int) $_REQUEST['order_way'] == 1) ? ' ASC' : ' DESC';

$nums_rows = 0;
if($do_not_query_total_files)
{
	$nums_rows = get_actual_stats('files');
}
else
{
	$result_p = $SQL->build($query);
	$n_fetch = $SQL->fetch_array($result_p);
	$nums_rows = $n_fetch['total_files'];
	$SQL->freeresult($result_p);
}


//pager 
$currentPage= isset($_GET['page']) ? intval($_GET['page']) : 1;
$Pager		= new SimplePager($perpage, $nums_rows, $currentPage);
$start		= $Pager->getStartRow();

$no_results = false;
	
if ($nums_rows > 0)
{
	$query['SELECT'] = 'f.*' . ((int) $config['user_system'] == 1 ? ', u.name AS username' : '');
	$query['LIMIT']	= "$start, $perpage";
	$result = $SQL->build($query);
	$sizes = false;
	$num = 0;
	#if Kleeja integtared we dont want make alot of queries
	$ids_and_names = array();

	while($row=$SQL->fetch_array($result))
	{
		$userfile =  $config['siteurl'] . ($config['mod_writer'] ? 'fileuser-' . $row['user'] . '.html' : 'ucp.php?go=fileuser&amp;id=' . $row['user']);

		#for username in integrated user system
		if($row['user'] != '-1' and (int) $config['user_system'] != 1)
		{
			if(!in_array($row['user'], $ids_and_names))
			{
				$row['username'] = $usrcp->usernamebyid($row['user']);
				$ids_and_names[$row['user']] = $row['username'];
			}
			else
			{
				$row['username'] = $ids_and_names[$row['user']];	
			}
		}

		//make new lovely arrays !!
		$arr[]	= array(
						'id'		=> $row['id'],
						'name'		=> "<a title=\" " . ($row['real_filename'] == '' ? $row['name'] : $row['real_filename']) . "\" href=\"./" . PATH . $row['folder'] . "/" . $row['name'] . "\" target=\"blank\">" . ($row['real_filename'] == '' ? ((strlen($row['name']) > 20) ? substr($row['name'], 0, 20) . '...' : $row['name']) : ((strlen($row['real_filename']) > 20) ? substr($row['real_filename'], 0, 20) . '...' : $row['real_filename'])) . "</a>",
						'size'		=> Customfile_size($row['size']),
						'ups'		=> $row['uploads'],
						'direct'	=> $row['id_form'] == 'direct' ? true : false,
						'time_human'=> kleeja_date($row['time']),
						'time'		=> kleeja_date($row['time'], false),
						'type'		=> $row['type'],
						'typeicon'	=> file_exists(PATH . "images/filetypes/".  $row['type'] . ".png") ? PATH . "images/filetypes/" . $row['type'] . ".png" : PATH. 'images/filetypes/file.png',
						'folder'	=> $row['folder'],
						'report'	=> ($row['report'] > 4) ? "<span style=\"color:red;font-weight:bold\">" . $row['report'] . "</span>":$row['report'],
						'user'		=> ($row['user'] == '-1') ? $lang['GUST'] :  '<a href="' . $userfile . '" target="_blank">' . $row['username'] . '</a>',
						'ip'		=> '<a href="http://www.ripe.net/whois?form_type=simple&amp;full_query_string=&amp;searchtext=' . $row['user_ip'] . '&amp;do_search=Search" target="_new">' . $row['user_ip'] . '</a>',
						'showfilesbyip' => basename(ADMIN_PATH) . '?cp=h_search&amp;s_input=1&amp;s_value=' . $row['user_ip']
					);

		$del[$row['id']] = isset($_POST['del_' . $row['id']]) ? $_POST['del_' . $row['id']] : '';
	}

	$SQL->freeresult($result);
}
else
{
	//no result ..
	$no_results = true;
}


#update f_lastvisit
if(!$is_search)
{
	if(filter_exists('f_lastvisit', 'filter_uid'))
	{
		update_filter('f_lastvisit', time());
	}
	else
	{
		insert_filter('lastvisit', time(), false, false, '', 'f_lastvisit');
	}
}


//some vars
$total_pages	= $Pager->getTotalPages(); 
$page_nums 		= $Pager->print_nums($page_action, 'onclick="javascript:get_kleeja_link($(this).attr(\'href\'), \'#content\'); return false;"'); 
$current_page	= $Pager->currentPage;
}

