<?php
/**
*
* @package adm
* @version $Id: d_img_ctrl.php 2058 2012-10-17 04:12:42Z saanina $
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/

// not for directly open
if (!defined('IN_ADMIN'))
{
	exit();
}

//number of images in each page 
if(!isset($images_cp_perpage) || !$images_cp_perpage)
{
	// you can add this varibale to config.php
	$images_cp_perpage = 25;
}

//for style ..
$stylee	= "admin_img";
$action	= basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php')  . (isset($_GET['page']) ? '&amp;page=' . intval($_GET['page']) : '') . 
			(isset($_GET['last_visit']) ? '&amp;last_visit='.intval($_GET['last_visit']) : '');
$action_search	= basename(ADMIN_PATH) . "?cp=h_search#!cp=h_search";
$H_FORM_KEYS	= kleeja_add_form_key('adm_img_ctrl');
$is_search		= false;

//
// Check form key
//
if (isset($_POST['submit']))
{
	if(!kleeja_check_form_key('adm_img_ctrl'))
	{
		kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, $action, 1);
	}

	foreach ($_POST as $key => $value) 
    {
        if(preg_match('/del_(?P<digit>\d+)/', $key))
        {
            $del[$key] = $value;
        }
    }
    
    foreach ($del as $key => $id)
    { 
        $query	= array(
						'SELECT'	=> '*',
						'FROM'		=> "{$dbprefix}files",
						'WHERE'		=> '`id` = ' . intval($id),
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
			$ids[] = $row['id'];
			$num++;		
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
									'SET'		=> "sizes=sizes-$sizes, imgs=imgs-$num",
								);

		$SQL->build($update_query);
		if($SQL->affected())
		{
			delete_cache('data_stats');
			$affected = true;
		}
	}
        
    //after submit 
	$text	= ($affected ? $lang['FILES_UPDATED'] : $lang['NO_UP_CHANGE_S']) .
				'<script type="text/javascript"> setTimeout("get_kleeja_link(\'' . basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . 
				'&page=' . (isset($_GET['page']) ? intval($_GET['page']) : '1') . '\');", 2000);</script>' . "\n";

	$stylee	= "admin_info";
}
else
{

$query	= array(
					'SELECT'	=> 'COUNT(f.id) AS total_files',
					'FROM'		=> "{$dbprefix}files f",
					'ORDER BY'	=> 'f.id DESC'
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

$img_types = array('gif','jpg','png','bmp','jpeg','GIF','JPG','PNG','BMP','JPEG');

#
# There is a bug with IN statment in MySQL and they said it will solved at 6.0 version
# forums.mysql.com/read.php?10,243691,243888#msg-243888
# $query['WHERE']	= "f.type IN ('" . implode("', '", $img_types) . "')";
#

$query['WHERE'] = "(f.type = '" . implode("' OR f.type = '", $img_types) . "')";

$do_not_query_total_files = false;

if(isset($_GET['last_visit']))
{
	$query['WHERE']	.= " AND f.time > " . intval($_GET['last_visit']);
}
else
{
	$do_not_query_total_files = true;
}

$nums_rows = 0;
if($do_not_query_total_files)
{
	$nums_rows = get_actual_stats('imgs');
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
$Pager		= new SimplePager($images_cp_perpage, $nums_rows, $currentPage);
$start		= $Pager->getStartRow();


$no_results = $affected = $sizes = false;
if ($nums_rows > 0) 
{

	$query['SELECT'] = 'f.*' . ((int) $config['user_system'] == 1 ? ', u.name AS username' : '');
	$query['LIMIT']	= "$start, $images_cp_perpage";
	$result = $SQL->build($query);

	$tdnum = $num = 0;
	#if Kleeja integtared we dont want make alot of queries
	$ids_and_names = array();

	while($row=$SQL->fetch_array($result))
	{
		//thumb ?
		$is_there_thumb = file_exists(PATH . $row['folder'] . '/thumbs/' . $row['name']) ? true : false;
		
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
						'tdnum'		=> $tdnum == 0 ? '<ul>': '',
						'tdnum2'	=> $tdnum == 4 ? '</ul>' : '',
						'name'		=> ($row['real_filename'] == '' ? ((strlen($row['name']) > 25) ? substr($row['name'], 0, 20) . '...' : $row['name']) : ((strlen($row['real_filename']) > 20) ? str_replace('\'', "\'", substr($row['real_filename'], 0, 20)) . '...' : str_replace('\'', "\'", $row['real_filename']))),
						'ip' 		=> htmlspecialchars($row['user_ip']),
						'href'		=> PATH . $row['folder'] . '/' . $row['name'],
						'size'		=> Customfile_size($row['size']),
						'ups'		=> $row['uploads'],
						'time'		=> date('d-m-Y h:i a', $row['time']),
						'user'		=> (int) $row['user'] == -1 ? $lang['GUST'] : $row['username'],
						'is_user'	=> (int) $row['user'] == -1 ? 0 : 1,
						'is_thumb'	=> $is_there_thumb,
						'thumb_link'=> $is_there_thumb ? PATH . $row['folder'] . '/thumbs/' . $row['name'] :  PATH . $row['folder'] . '/' . $row['name'],
					);

		//fix ... 
		$tdnum = $tdnum == 4 ? 0 : $tdnum+1; 

		$del[$row['id']] = isset($_POST['del_' . $row['id']]) ? $_POST['del_' . $row['id']] : '';
/*
		//when submit !!
		if (isset($_POST['submit']))
		{
			if ($del[$row['id']])
			{
				//delete from folder ..
				@kleeja_unlink (PATH . $row['folder'] . '/' . $row['name']);
				//delete thumb
				if (file_exists(PATH . $row['folder'] . '/thumbs/' . $row['name'] ))
				{
					@kleeja_unlink (PATH . $row['folder'] . '/thumbs/' . $row['name'] );
				}
				$ids[] = $row['id'];
				$num++;		
				$sizes += $row['size'];	
			}
		}
*/
	}

	$SQL->freeresult($result);
	
/*
	if (isset($_POST['submit']))
	{
		//no files to delete
		if(isset($ids) && sizeof($ids))
		{
			$query_del = array(
								'DELETE'	=> "{$dbprefix}files",
								'WHERE'	=> "id IN (" . implode(',', $ids) . ")"
							);
			
			$SQL->build($query_del);

			//update number of stats
			$update_query	= array(
									'UPDATE'	=> "{$dbprefix}stats",
									'SET'		=> "sizes=sizes-$sizes, files=files-$num",
								);

			$SQL->build($update_query);
			if($SQL->affected())
			{
				delete_cache('data_stats');
				$affected = true;
			}
		}
	}
*/

}
else
{
	$no_results = true;
}

#update f_lastvisit
if(!$is_search)
{
	if(filter_exists('i_lastvisit', 'filter_uid'))
	{
		update_filter('i_lastvisit', time());
	}
	else
	{
		insert_filter('lastvisit', time(), false, false, '', 'i_lastvisit');
	}
}

//pages
$total_pages 	= $Pager->getTotalPages(); 
$page_nums 		= $Pager->print_nums(basename(ADMIN_PATH). '?cp=' . basename(__file__, '.php') . (isset($_GET['last_visit']) ? '&last_vists=' . intval($_GET['last_visit']) : '')
						, 'onclick="javascript:get_kleeja_link($(this).attr(\'href\'), \'#content\'); return false;"'); 
$current_page	= $Pager->currentPage;
}

/*
//after submit 
if(isset($_POST['submit']))
{
	$text	= ($affected ? $lang['FILES_UPDATED'] : $lang['NO_UP_CHANGE_S']) .
				'<script type="text/javascript"> setTimeout("get_kleeja_link(\'' . basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . 
				'&amp;page=' . (isset($_GET['page']) ? intval($_GET['page']) : '1') . '\');", 2000);</script>' . "\n";

	$stylee	= "admin_info";
}*/
