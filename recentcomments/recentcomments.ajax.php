<?php
/* ====================
[BEGIN_COT_EXT]
 * Hooks=ajax
[END_COT_EXT]
==================== */

/**
 * Recent comments on index page
 *
 * @package recentcomments
 * @version 1.1
 * @author Dayver
 * @copyright (c) Cotonti Team 2009
 * @license BSD
 */

defined('COT_CODE') or die('Wrong URL.');

require_once cot_langfile('recentcomments', 'plug');
require_once cot_incfile('comments', 'plug');

$comd = cot_import('comd', 'G', 'INT');
$comd = empty($comd) ? 0 : (int) $comd;

if(!$latestcomments && $cfg['plugin']['recentcomments']['commentsperpage'] > 0)
{
	$latestcomments = new XTemplate(cot_tplfile('recentcomments', 'plug'));

	$totalitems = $db->query("SELECT COUNT(DISTINCT com_code) FROM $db_com")->fetchColumn();

	if($cfg['plugin']['recentcomments']['ajax'] && $cfg['jquery'])
	{
		$pagnav = cot_pagenav('index', '', $comd, $totalitems, $cfg['plugin']['recentcomments']['commentsperpage'], "comd", "", true, "reloadcommblock");
	}
	else
	{
		$pagnav = cot_pagenav('index', '', $comd, $totalitems, $cfg['plugin']['recentcomments']['commentsperpage'], "comd");
	}

	$warnings = ($totalitems == 0) ? $L['plu_empty_comm'] : '';

	$sqlcom = $db->query("SELECT MAX(com_id) AS _id, MAX(com_date) AS _date FROM $db_com GROUP BY com_code ORDER BY _date DESC LIMIT $comd, ".$cfg['plugin']['recentcomments']['commentsperpage']);

	$com_latest = array();
	while($row = $sqlcom->fetch())
	{
		$com_latest[] = $row['_id'];
	}

	$sqlcom2 = $db->query("SELECT * FROM $db_com WHERE com_id IN('".implode("','",$com_latest)."') ORDER BY com_date DESC");

	$i = 0;

	while($row = $sqlcom2->fetch())
	{
		$com_code = $row['com_code'];
		$k = $com_code;

		switch($row['com_area'])
		{
			case 'page':
			//��� �� ��� ������������ ��� ��� ����� ��������-�������������� ->
			$sqlcom2_2 = $db->query("SELECT * FROM $db_pages WHERE page_id = $k LIMIT 1");
			while($row2 = $sqlcom2_2->fetch())
			{
				$page_title = $row2['page_title'];
				$page_alias = $row2['page_alias'];
				$pag['page_cat'] = $row2['page_cat'];
			}
			//��� �� ��� ������������ ��� ��� ����� ��������-�������������� <-
			$lnk = (empty($page_alias)) ? cot_url('page', "c={$pag['page_cat']}&id=".$k)."#c".$row['com_id'] : cot_url('page', "c={$pag['page_cat']}&al=".$page_alias)."#c".$row['com_id'];
			$comtitle = $page_title;
			$latestcomments -> assign(array(
				"RECENTCOMMENTS_ROW_PAGE_CAT" => cot_breadcrumbs(cot_structure_buildpath('page', $row2['page_cat'])),
				"RECENTCOMMENTS_ROW_PAGE_KEY" => $row2['page_key'],
				"RECENTCOMMENTS_ROW_PAGE_DESC" => $row2['page_desc'],
				"RECENTCOMMENTS_ROW_PAGE_ID" =>$k,
				"RECENTCOMMENTS_ROW_PAGE_ALIAS" => $page_alias
			));
			break;

			case 'polls':
			//��� �� ��� ������������ ��� ��� ����� ��������-�������������� ->
			$sqlcom2_2 = $db->query("SELECT * FROM $db_polls WHERE poll_id = $k LIMIT 1");
			while($row2 = $sqlcom2_2->fetch())
			{
				$poll_title = cot_parse(htmlspecialchars($row2['poll_text']), 1, 1, 1);
			}
			//��� �� ��� ������������ ��� ��� ����� ��������-�������������� <-
			$lnk = cot_url('polls', "id=".$k)."#c".$row['com_id'];
			$comtitle = $poll_title;
			/*$latestcomments -> assign(array(
				"RECENTCOMMENTS_ROW_POLL_" => $row3['poll_text']
			));*/
			break;
		}

		$latestcomments -> assign(array(
			"RECENTCOMMENTS_ROW_HREF" => $lnk,
			"RECENTCOMMENTS_ROW_ITEM_TITLE" => $comtitle,
			"RECENTCOMMENTS_ROW_DATE" => date($cfg['formatmonthdayhourmin'], $row['com_date'] + $usr['timezone'] * 3600),
			"RECENTCOMMENTS_ROW_AUTHORNAME" => $row['com_author'],
			"RECENTCOMMENTS_ROW_AUTHORID" => $row['com_authorid'],
			"RECENTCOMMENTS_ROW_AUTHOR" => cot_build_user($row['com_authorid'], htmlspecialchars($row['com_author'])),
			"RECENTCOMMENTS_ROW_COM_TEXT" => $row['com_text']
		));
		$latestcomments -> parse("RECENTCOMMENTS.COMM_ROW");

		$i++;
	}

	$latestcomments -> assign(array(
		"RECENTCOMMENTS_AJAX_OPENDIVID" => 'reloadcommblock',
		"RECENTCOMMENTS_WARNINGS" => $warnings,
		"RECENTCOMMENTS_PAGINATION_PREV" => $pagnav['prev'],
		"RECENTCOMMENTS_PAGNAV" => $pagnav['main'],
		"RECENTCOMMENTS_PAGINATION_NEXT" => $pagnav['next'],
		"RECENTCOMMENTS_TOTALITEMS" => $totalitems,
		"RECENTCOMMENTS_COUNTER_ROW" => $i
	));
	$latestcomments -> parse("RECENTCOMMENTS");
	$taglatestcomments = $latestcomments -> text("RECENTCOMMENTS");

	$cache && $cache->db->store('taglatestcomments', $taglatestcomments, COT_DEFAULT_REALM, 5);
}

cot_sendheaders();
echo $taglatestcomments;
ob_end_flush();

?>