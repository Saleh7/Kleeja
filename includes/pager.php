<?php
/**
*
* @package Kleeja
* @version $Id: pager.php 2128 2013-02-14 12:01:02Z saanina $
* @copyright (c) 2007 Kleeja.com
* @license ./docs/license.txt
*
*/


//no for directly open
if (!defined('IN_COMMON'))
{
	exit();
}

class SimplePager
{
	var $totalPages, $startRow , $currentPage;

	/**
	*Construct function ..
	*
	*/
	function SimplePager($rowsPerPage, $numRows, $currentPage = 1)
	{ 
		// Calculate the total number of pages 
		$this->totalPages = ceil($numRows/$rowsPerPage); 

		// Check that a valid page has been provided 
		$this->currentPage = $currentPage < 1 ? 1 :  ($currentPage > $this->totalPages ? $this->totalPages : $currentPage); 

		// Calculate the row to start the select with 
		$this->startRow = ($this->currentPage - 1) * $rowsPerPage; 
	}

	function getTotalPages ()
	{
		return $this->totalPages;
	} 

	function getStartRow()
	{
		return $this->startRow;
	}

	function print_nums($link, $link_plus = '')
	{
		global $lang, $config;

		//if no page
		if($this->totalPages <= 1)
		{
			return;
		}

		$link_plus .= $link_plus != '' ? ' ' : '';

		$re = '<div id="pagination">';

		// Add a previous page link
		if ($this->totalPages > 1 && $this->currentPage > 1)
		{
			$re .= ($config['mod_writer'] && !defined('IN_ADMIN')) ? '<a class="paginate phover" href="' . $link . '-' . ($this->currentPage-1) . '.html"' . $link_plus . '><span>' . $lang['PREV'] . '</span></a>' : '<a class="paginate phover" href="' . $link . '&amp;page=' . ($this->currentPage-1) . '"' . $link_plus . '><span>' . $lang['PREV'] . '</span></a>';
		}
		if ($this->currentPage > 3)
		{
			$re .= ($config['mod_writer'] && !defined('IN_ADMIN')) ? '<a class="paginate" href="' . $link . '-1.html"' . $link_plus . '><span>1</span></a>' . (($this->currentPage > 5) ? '<a class="paginate dots"><span>...</span></a>' : '') : '<a class="paginate" href="' . $link . '&amp;page=1"' . $link_plus . '><span>1</span></a>' . (($this->currentPage > 5) ? '<a class="paginate dots"><span>...</span></a>' : '');
		}

		for ($current = ($this->currentPage == 5) ? $this->currentPage - 3 : $this->currentPage - 2, $stop = ($this->currentPage + 4 == $this->totalPages) ? $this->currentPage + 4 : $this->currentPage + 3; $current < $stop; ++$current)
		{
			if ($current < 1 || $current > $this->totalPages)
			{
				continue;
			}
			else if ($current != $this->currentPage)
			{
				$re .= ($config['mod_writer'] && !defined('IN_ADMIN')) ? '<a class="paginate" href="' . $link . '-' . $current . '.html"' . $link_plus . '><span>' . $current . '</span></a>' : '<a class="paginate" href="' . $link . '&amp;page=' . $current . '"' . $link_plus . '><span>' . $current . '</span></a>';
			}
			else
			{
				$re .= '<a class="paginate current"><span>' . $current . '</span></a>';
			}
		}

		if ($this->currentPage <= ($this->totalPages-3))
		{
			if ($this->currentPage != ($this->totalPages-3) && $this->currentPage != ($this->totalPages-4))
			{
				$re .= '<a class="paginate dots"><span>...</span></a>';
			}

			$re .= ($config['mod_writer'] && !defined('IN_ADMIN')) ? '<a class="paginate" href="' . $link . '-' . $this->totalPages . '.html"' . $link_plus . '><span>' . $this->totalPages . '</span></a>' : '<a class="paginate" href="' . $link . '&amp;page=' . $this->totalPages . '"' . $link_plus . '><span>' . $this->totalPages . '</span></a>';
		}

		// Add a next page link
		if ($this->totalPages > 1 && $this->currentPage < $this->totalPages)
		{
			$re .= ($config['mod_writer'] && !defined('IN_ADMIN')) ? '<a class="paginate phover" href="' . $link . '-' . ($this->currentPage+1) . '.html"' . $link_plus . '><span>' . $lang['NEXT'] . '</span></a>' :  '<a class="paginate phover" href="' . $link . '&amp;page=' . ($this->currentPage+1) . '"' . $link_plus . '><span>' . $lang['NEXT'] . '</span></a>';
		}

		$re .= '</div>'; 

		return $re;
	}
}

#<-- EOF
