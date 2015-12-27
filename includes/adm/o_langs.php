<?php
/**
*
* @package adm
* @version $Id: o_langs.php 2065 2012-10-20 00:17:58Z saanina $
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/


// not for directly open
if (!defined('IN_ADMIN'))
{
	exit();
}

//english as default
if(!isset($_REQUEST['lang']))
{
	$_REQUEST['lang'] = 'en';
}

$lang_id = preg_replace('![^a-z]!', '', $_REQUEST['lang']);


//for style ..
$stylee 	= "admin_langs";
$action 	= basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php') . '&amp;page=' .  (isset($_GET['page']) ? intval($_GET['page']) : 1) . '&amp;lang=' . $lang_id;
$action2 	= basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php');
$H_FORM_KEYS= kleeja_add_form_key('adm_langs');


//
// Check form key
//
if (isset($_POST['submit']))
{
	if(!kleeja_check_form_key('adm_langs'))
	{
		kleeja_admin_err($lang['INVALID_FORM_KEY'], true, $lang['ERROR'], true, $action, 3);
	}
}

//get languages
$lngfiles = '';
if ($dh = @opendir(PATH . 'lang'))
{
	while (($file = readdir($dh)) !== false)
	{
		if(strpos($file, '.') === false && $file != '..' && $file != '.')
		{
			$lngfiles .= '<option ' . ($lang_id == $file ? 'selected="selected"' : '') . ' value="' . $file . '">' . $file . '</option>' . "\n";
		}
	}
	closedir($dh);
}

$query = array(
				'SELECT'	=> '*',
				'FROM'		=> "{$dbprefix}lang",
				'WHERE'		=> "lang_id='" .  $lang_id . "'",
				'ORDER BY'	=> 'word DESC'
		);

$result = $SQL->build($query);

//pagination
$nums_rows		= $SQL->num_rows($result);
$currentPage	= isset($_GET['page']) ? intval($_GET['page']) : 1;
$Pager			= new SimplePager($perpage, $nums_rows, $currentPage);
$start			= $Pager->getStartRow();

$no_results = false;

if ($nums_rows > 0)
{
	$query['LIMIT']	= "$start, $perpage";

	$result = $SQL->build($query);

	while($row=$SQL->fetch_array($result))
	{
		$transs[$row['word']]	= isset($_POST['t_' . $row['word']]) ? $_POST['t_' . $row['word']] : $row['trans'];
		$del[$row['word']]		= isset($_POST['del_' . $row['word']]) ? $_POST['del_' . $row['word']] : '';

		//make new lovely arrays !!
		$arr[]	= array(
						'lang_id'	=> $row['lang_id'],
						'word'		=> $row['word'],
						'trans'		=> $transs[$row['word']],
					);

		//when submit
		if (isset($_POST['submit']))
		{
			//del
			if ($del[$row['word']])
			{
				$query_del = array(
										'DELETE'	=> "{$dbprefix}lang",
										'WHERE'		=>	"word='" . $SQL->escape($row['word']) . "' AND lang_id='" .  $lang_id . "'"
									);

				$SQL->build($query_del);
			}
			//update
			$update_query = array(
									'UPDATE'	=> "{$dbprefix}lang",
									'SET'		=> 	"trans = '" . $SQL->escape($transs[$row['word']]) . "'",
									'WHERE'		=>	"word='" . $SQL->escape($row['word']) . "' AND lang_id='" .  $lang_id . "'"
								);

			$SQL->build($update_query);
		}
	}

	$SQL->freeresult($result);
}
else
{
	//no result ...
	$no_results = true;
}

//pages
$total_pages 	= $Pager->getTotalPages(); 
$page_nums 		= $Pager->print_nums(basename(ADMIN_PATH) . '?cp=' . basename(__file__, '.php'),
								'onclick="javascript:get_kleeja_link($(this).attr(\'href\'), \'#content\'); return false;"');

//after submit 
if (isset($_POST['submit']))
{
	$text = $lang['NO_UP_CHANGE_S'];
	if($SQL->affected())
	{
		delete_cache('data_lang');
		$text = $lang['WORDS_UPDATED'];
	}
	
	$text	.= '<script type="text/javascript"> setTimeout("get_kleeja_link(\'' . $action .  '\');", 2000);</script>' . "\n";
	$stylee	= "admin_info";
}
