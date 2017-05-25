<?php

class NP_View extends NucleusPlugin
{
	function getName()		{return 'View';}
	function getAuthor()	{return 'jun';}
	function getURL()		{return 'http://japan.nucleuscms.org/bb/viewtopic.php?t=709';}
	function getVersion()	{return '1.12';}
	function getDescription()	{$desc = 'This plugin displays the most viewed items by dairy, weekly, monthly, yearly.'
			  . 'Special thanks for Rodrigo Moraes (*NP_Views)'
			  . ' and Edmond Hui (*NP_MostViewed). http://www.tipos.com.br/'; 
		return  $desc;
	}
	function getEventList()	{return array('PostDeleteItem');}
	function getTableList()	{return array(sql_table('plugin_view'));}
	function supportsFeature($what)	{return (int) in_array($what,array('SqlApi','SqlTablePrefix'));}

	function install()
	{
		$query = 'CREATE TABLE IF NOT EXISTS ' . sql_table('plugin_view') . ' ('
			   . 'id      int(11)      unsigned not null, '
			   . 'view    int(11)      unsigned, '
			   . 'ip      char(11), '
			   . 'vtime   datetime	      not null, '
			   . 'week0   mediumint(8) unsigned not null, '
			   . 'week1   mediumint(8) unsigned not null, '
			   . 'week2   mediumint(8) unsigned not null, '
			   . 'week3   mediumint(8) unsigned not null, '
			   . 'week4   mediumint(8) unsigned not null, '
			   . 'week5   mediumint(8) unsigned not null, '
			   . 'week6   mediumint(8) unsigned not null, '
			   . 'month01 int(11)      unsigned not null, '
			   . 'month02 int(11)      unsigned not null, '
			   . 'month03 int(11)      unsigned not null, '
			   . 'month04 int(11)      unsigned not null, '
			   . 'month05 int(11)      unsigned not null, '
			   . 'month06 int(11)      unsigned not null, '
			   . 'month07 int(11)      unsigned not null, '
			   . 'month08 int(11)      unsigned not null, '
			   . 'month09 int(11)      unsigned not null, '
			   . 'month10 int(11)      unsigned not null, '
			   . 'month11 int(11)      unsigned not null, '
			   . 'month12 int(11)      unsigned not null, '
			   . 'PRIMARY KEY (id)'
				. ')';
		sql_query($query);
		$this->createOption('loggedin',      '1.  Count at loggedin?',		  'yesno', 'no');
		$this->createOption('ip_count',      '2.  Count at same IPadress?',	     'yesno', 'no');
		$this->createOption('del_item',      '3.  Delete View Counts on deleted item?', 'yesno', 'yes');
		$this->createOption('del_uninstall', '4.  Delete tables on uninstall?',	 'yesno', 'no');
		$this->createOption('item_except',   '5.  ItemID to except. (*ex. 3/15/120)',   'text',  '');
		$this->createOption('cat_except',    '6.  CategoryID to except. (*ex. 2/5/8)',  'text',  '');
		$this->createOption('blog_except',   '7.  BlogID to except. (*ex. 1/4)',	'text',  '');
		$this->createOption('month_format',  '8.  Last month format. (*ex. Y-m )',      'text',  'Y-m');
		$this->createOption('day_format',    '9.  Last day format. (*ex. Y-m-d )',      'text',  'Y-m-d');
		$this->createOption('week_format',   '10. Week or Year format. (*ex. -> )',     'text',  ' -> ');
		$this->createOption('box_format',    '11. Viewbody\'s CSS tag. (*ex1. div)(*ex2. ol)',  'text', 'div');
		$this->createOption('m_optimize',    '12. Optimize tables every month?',	'yesno', 'no');
		$this->createOption('d_optimize',    '13. Optimize tables every day?',	  'yesno', 'no');
		$this->createOption('s_format',      '14. Switch format. (*ex. <br />[switch]:)',
												'text', '<br />[switch]:');
		$this->createOption('s_main',	'15. Switch button. (*ex. Daily/Weekly/Monthly/Yearly/ Access Ranking)',
												'text', 'Daily/Weekly/Monthly/Yearly/ Access Ranking');
	}

	function unInstall()
	{
		if($this->getOption('del_uninstall') == 'yes') {
			sql_query("DROP table " . sql_table('plugin_view'));
		}
		$this->deleteOption('loggedin');
		$this->deleteOption('ip_count');
		$this->deleteOption('del_item');
		$this->deleteOption('del_uninstall');
		$this->deleteOption('item_except');
		$this->deleteOption('cat_except');
		$this->deleteOption('blog_except');
		$this->deleteOption('month_format');
		$this->deleteOption('day_format');
		$this->deleteOption('week_format');
		$this->deleteOption('box_format');
		$this->deleteOption('m_optimize');
		$this->deleteOption('d_optimize');
		$this->deleteOption('s_format');
		$this->deleteOption('s_main');
	}



	function event_PostDeleteItem($data)
	{
		$itemid = intval($data['itemid']);
		if ($this->getOption('del_item') == 'no') {
			return;
		}
		$query = 'DELETE FROM %s WHERE id = %d';
		sql_query(sprintf($query, sql_table('plugin_view'), $itemid));
		return;
	}

	function doTemplateVar(&$item, $what = '', $num = '')
	{
		$viewTable = sql_table('plugin_view');
		$itemid    = intval($item->itemid);
		$num       = explode('/', $num);
		$num[0]    = (getVar('vnum')) ? getVar('vnum') : $num[0];
		$vtime     = date("Y-m-d H:i:s");

		sscanf($vtime, '%d-%d-%d', $y, $m, $d);
		if ($what == 'day') {
			$t0 = mktime(0, 0, 0, $m,	 $d-$num[0], $y);
		}elseif($what == 'month') {
			$t0 = mktime(0, 0, 0, $m-$num[0], $d,	 $y);
		}else {
			$t0 = mktime(0, 0, 0, $m,	 $d,	 $y);
		}

		$t0m    = date("m", $t0);
		$vday   = 'week'  . strftime("%w", $t0);
		$vmonth = 'month' . $t0m;

		$query  = 'SELECT view,'
				. ' %s					      as vday,'
				. ' week0+week1+week2+week3+week4+week5+week6       as vweek,'
				. ' %s					      as vmonth,'
				. ' month01+month02+month03+month04+month05+month06'
				. '+month07+month08+month09+month10+month11+month12 as vyear,'
				. ' vtime, ip FROM %s WHERE id = %d';
		$query  = sprintf($query, $vday, $vmonth, $viewTable, $itemid);
		$q1     = sql_query($query);

		while ($row = sql_fetch_assoc($q1)) {
			if (!$what || $what == 'view') {
				$view = $row['view'];
			}
			$getVmode = getVar('vmode');
			$notVmode = (!$getVmode && $num[2] == 'a');
			$notGet   = ($num[1] != 'get');
			$dairy    = ($getVmode == 'day');
			$weekly   = ($getVmode == 'week');
			$monthly  = ($getVmode == 'month');
			$yearly   = ($getVmode == 'year');
			if ($what == 'day' && ($notGet || $dairy || $notVmode)) {
				if ($num[1] == 'a') {
					$view = date($this->getOption('day_format'), $t0);
				}
				$view .= $row['vday'];
				if ($num[1] == 'num') {
					$query = 'SELECT count("%s") as result'
						   . ' FROM %s'
						   . ' WHERE %s >= %s'
						   . ' ORDER BY %s';
					$query = sprintf($query, $vday, $viewTable, $vday, $view, $vday);
					$view  = quickQuery($query);
				}
			}

			if ($what == 'week' && ($notGet || $weekly || $notVmode)) {
				$view = $row['vweek'];
			}

			if ($what == 'month' && ($notGet || $monthly || $notVmode)) {
				if ($num[1] == 'a') {
					$view = date($this->getOption('month_format'), $t0);
				}
				$view .= $row['vmonth'];
				if ($num[1] == 'num') {
					$query = 'SELECT count("%s") as result'
						   . ' FROM %s'
						   . ' WHERE %s >= %s';
					$query = sprintf($query, $vmonth, $viewTable, $vmonth, $view);
					$view  = quickQuery($query);
				}
			}

			if ($what == 'year' && ($notGet || $yearly || $notVmode)) {
				$view = $row['vyear'];
			}

			if ($what == 'time') {
				$view = $row['vtime'];
			}

			if ($what == 'ip') {
				$view = $row['ip'];
			}
		}

		$itemTable = sql_table('item');
		$blogTable = sql_table('blog');
		$query = 'SELECT %s as result'
			   . ' FROM'
			   . ' %s,'
			   . ' %s'
			   . ' WHERE'
			   . '     inumber = %d'
			   . ' and bnumber = iblog';
		if ($what == 'bname') {
			$vquery = sprintf($query, 'bname', $blogTable, $itemTable, $itemid);
			$view   = quickQuery($vquery);
		} elseif($what == 'bid') {
			$vquery = sprintf($query, 'bnumber', $blogTable, $itemTable, $itemid);
			$view   = quickQuery($vquery);
		}
		echo htmlspecialchars($view, ENT_QUOTES, _CHARSET);
	}

	function doSkinVar($skinType,
					   $template = 'default/index',
					   $vitem    = '',
					   $vmode    = '',
					   $vblog    = '',
					   $vcat     = '',
					   $num      = '',
					   $boxTag   = ''
					  )
	{
		global $CONF, $manager, $blog, $blogid, $catid, $itemid;
		global $member, $memberid, $MYSQL_DATABASE;

		if (!is_numeric($blogid)) {
			if ($blogid) {
				$blogid = getBlogidFromName($blogid);
			} else {
				$blogid = getBlogidFromName($CONF['DefaultBlog']);
			}
		}
		$blogid = intval($blogid);
		if ($blog) {
			$b =& $blog;
		} else {
			$b =& $manager->getBlog($blogid);
		}
		$vmode = htmlspecialchars($vmode, ENT_QUOTES, _CHARSET);
		$vmode = explode('/', $vmode);
		$num   = explode('/', $num);
		foreach ($num as $key => $val) {
			$num[$key] = intval($val);
		}
		$ip    = ServerVar('REMOTE_ADDR');
		$ip    = htmlspecialchars($ip, ENT_QUOTES, _CHARSET);
		$vtime = date("Y-m-d H:i:s");

		$itemTbale = sql_table('item');
		$catTable  = sql_table('category');
		$viewTable = sql_table('plugin_view');

		if ($vitem) {
			$num[0]   = (getVar('vnum'))  ? getVar('vnum')  : $num[0];
			$vmode[0] = (getVar('vmode')) ? getVar('vmode') : $vmode[0];
		}
		sscanf($vtime, '%d-%d-%d', $y, $m, $d);
		if ($vmode[0] == 'day') {
			$t0 = mktime(0, 0, 0, $m,	   $d-$num[0],   $y);
			$tp = mktime(0, 0, 0, $m,	   $d-$num[0]-1, $y);
			$tn = mktime(0, 0, 0, $m,	   $d-$num[0]+1, $y);
		} elseif ($vmode[0] == 'month') {
			$t0 = mktime(0, 0, 0, $m-$num[0],   $d,	   $y);
			$tp = mktime(0, 0, 0, $m-$num[0]-1, $d,	   $y);
			$tn = mktime(0, 0, 0, $m-$num[0]+1, $d,	   $y);
		} else {
			$t0 = mktime(0, 0, 0, $m,	   $d,	   $y);
			$tw = mktime(0, 0, 0, $m,	   $d-6,	 $y);
			$ty = mktime(0, 0, 0, $m+1,	 $d,	   $y-1);
		}
		$t0y    = date("y", $t0);
		$t0m    = date("m", $t0);
		$t0d    = date("d", $t0);
		$vday   = 'week'  . strftime("%w", $t0);
		$vmonth = 'month' . $t0m;
		if (!$vitem) {
// category display
			if ($template == 'cid') {
				$query = 'SELECT icat as result'
					   . ' FROM %s'
					   . ' WHERE inumber = %d';
				$view  = quickQuery(sprintf($query, $itemTable, $itemid));
				echo htmlspecialchars($view, ENT_QUOTES, _CHARSET);
			} elseif ($template == 'cname') {
				$query = 'SELECT cname as result'
					   . ' FROM %s,'
					   . '      %s'
					   . ' WHERE inumber = %d'
					   . '   and catid = icat';
				$query = sprintf($query, $itemTable, $catTable, $itemid);
				$view  = quickQuery($query);
				echo htmlspecialchars($view, ENT_QUOTES, _CHARSET);
// count
			} elseif ($member->isLoggedIn() && $this->getOption('loggedin') == 'no') {
				return;
			} elseif ($itemid) {
				$query = 'SELECT vtime as result'
					   . ' FROM %s'
					   . ' ORDER BY vtime DESC LIMIT 1';
				$time1 = quickQuery(sprintf($query, $viewTable));
				sscanf($time1, '%d-%d-%d', $y, $m, $d);
				$t1  = mktime(0, 0, 0, $m, $d, $y);
				$t1y = date("y", $t1);
				$t1m = date("m", $t1);
				$t1d = date("d", $t1);
				if (!($t0y == $t1y && $t0m == $t1m && $t0d == $t1d)) {
// optimize table
					$result = mysql_list_tables($MYSQL_DATABASE);
					$i      = 0;
					$qq     = 'OPTIMIZE TABLE';
					while ($i < mysql_num_rows($result)) {
						$tb_names[$i] = mysql_tablename($result, $i);
						$qq	  .= ' `' . $tb_names[$i] . '`,';
						$i++;
					}
					$number = strlen($qq);
					$qq     = substr($qq, 0, $number-1);
// change field
					$dquery = 'ALTER TABLE %s DROP %s';
					$aquery = 'ALTER TABLE %s ADD %s mediumint(8) unsigned not null';
					sql_query(sprintf($dquery, $viewTable, $vday));
					sql_query(sprintf($aquery, $viewTable, $vday));
					if ($this->getOption('d_optimize') == 'yes') {
						$qp = sql_query($qq);
					}
					if ($t0m != $t1m) {
						$dquery = 'ALTER TABLE %s DROP %s';
						$aquery = 'ALTER TABLE %s ADD %s int(15) unsigned not null';
						sql_query(sprintf($dquery, $viewTable, $vmonth));
						sql_query(sprintf($aquery, $viewTable, $vmonth));
						if ($this->getOption('m_optimize') == 'yes' && !$qp) {
							sql_query($qq);
						}
					}
				}
// countup
				$query = 'SELECT view, ip, %s, %s FROM %s WHERE id = %d';
				$query = sprintf($query, $vday, $vmonth, $viewTable, $itemid);
				$res   = sql_query($query);
				$row   = sql_fetch_object($res);
				$view  = intval($row->view);
				$wview = intval($row->$vday);
				$mview = intval($row->$vmonth);
				if (sql_num_rows($res) == 0) {
					$query = 'INSERT INTO %s'
						   . ' (id, view, %s, %s, ip, vtime)'
						   . ' VALUES ("%d", "1", "1", "1", "%s", "%s")';
					$query = sprintf($query,
									 $viewTable,
									 $vday, $vmonth,
									 $itemid, $ip, $vtime);
					sql_query($query);
				} elseif (!($ip == $row->ip && $this->getOption('ip_count') == 'no')) {
					$view++;
					$wview++;
					$mview++;
					$tquery = 'UPDATE %s'
							. ' SET view = "%s", %s = "%s", %s = "%s",'
							. ' ip = "%s",'
							. ' vtime = "%s"'
							. ' WHERE id = %d';
					$query  = sprintf($tquery,
									  $viewTable,
									  $view, $vday, $wview, $vmonth, $mview,
									  $ip,
									  $vtime,
									  $itemid);
					sql_query($query);
				}
			}
// display
		} else {
			$getVmode = getVar('vmode');
			$arrVmode = $vmode[0];
			$dairy    = ($getVmode == 'day');
			$weekly   = ($getVmode == 'week');
			$monthly  = ($getVmode == 'month');
			$yearly   = ($getVmode == 'year');
			$vDairy   = ($arrVmode == 'day');
			$vWeekly  = ($arrVmode == 'week');
			$vMonthly = ($arrVmode == 'month');
			$vYearly  = ($arrVmode == 'year');
			if ($vDairy || $vWeekly || $dairy || $weekly) {
				$date_f = $this->getOption('day_format');
			} else {
				$date_f = $this->getOption('month_format');
			}
			$query  = 'SELECT'
//		  . ' DISTINCT i.ititle as title,'
					. ' i.ititle		as title,'
					. ' i.iblog		 as blog,'
					. ' i.inumber	       as itemid,'
					. ' i.ibody		 as body,'
					. ' i.imore		 as more,'
					. ' UNIX_TIMESTAMP(i.itime) as timestamp,'
					. ' i.itime,'
					. ' c.cname		 as category,'
					. ' c.catid		 as catid,'
					. ' m.mname		 as author,'
					. ' m.mrealname	     as authorname,'
					. ' m.mnumber	       as authorid,'
					. ' week0+week1+week2+week3+week4+week5+week6       as vweek,'
					. ' month01+month02+month03+month04+month05+month06'
					. '+month07+month08+month09+month10+month11+month12 as vyear'
					. ' FROM '
					. sql_table('member') . '      as m, '
					. sql_table('category') . '    as c, '
					. sql_table('item') . '	as i, '
					. sql_table('blog') . '	as b, '
					. sql_table('plugin_view') . ' as v'
					. ' WHERE v.id = i.inumber'
					. ' and c.catid = i.icat'
					. ' and b.bnumber = c.cblog'
					. ' and i.iauthor = m.mnumber';
			$ihide  = explode('/', $this->getOption('item_except'));
			$chide  = explode('/', $this->getOption('cat_except'));
			$bhide  = explode('/', $this->getOption('blog_except'));
// item
			if ($ihide[0]) {
				foreach ($ihide as $ihides) {
					$query .= ' and i.inumber != ' . intval($ihides);
				}
			}
// category
			if ($chide[0]) {
				foreach ($chide as $chides) {
					$query .= ' and i.icat != ' . intval($chides);
				}
			}
			if ($vcat && $vcat != 'b') {
				$vcat = implode(', ', $vcat);
				$query .= ' and i.icat in (' . $vcat . ')';
			}
// blog
			if ($vblog == 'all') {
				if ($bhide[0]) {
					foreach($bhide as $bhides){
						$query .= ' and i.iblog != ' . intval($bhides);
					}
				}
			} elseif ($vblog == '' || $vblog == 0) {
				if ($vcat == 'b') {
					$query .= ' and i.iblog = ' . intval($blogid);
				} else {
					if ($catid) {
						$query .= ' and i.icat = '  . intval($catid);
					} else {
						$query .= ' and i.iblog = ' . intval($blogid);
					}
				}
			} else {
				$vblog  = str_replace('/', ', ', $vblog);
				$query .= ' and i.iblog in (' . $vblog . ')';
			}
// mode
			switch ($arrVmode) {
				case 'm':
					$memid    = $member->getID();
					$query   .= ' and i.iauthor = ' . intval($memid);
					break;
				case 'mem':
					$memberID = ($vmode[1]) ? $vmode[1] : $memberid;
					$query   .= ' and i.iauthor = ' . intval($memberID);
					break;
				case 'time':
					$query   .= ' ORDER BY v.vtime';
					break;
				case 'day':
					$query   .= ' ORDER BY ' . $vday;
					break;
				case 'week':
					$query   .= ' ORDER BY vweek';
					break;
				case 'month':
					$query   .= ' ORDER BY ' . $vmonth;
					break;
				case 'year':
					$query   .= ' ORDER BY vyear';
					break;
				default:
					$query   .= ' ORDER BY v.view';
					break;
			}
			$vitem  = ($vitem > 100) ? 100 : $vitem;
			$query .= ' DESC, vweek DESC LIMIT ' . $vitem;
//print
			if ($num[1]) {
				echo '<div class="viewtitle">';
			}

			$usePathInfo = ($CONF['URLMode'] == 'pathinfo');

			$httpHost   = serverVar('HTTP_HOST');
			$requestURI = serverVar('REQUEST_URI');
			$uri	= sprintf('%s%s%s', 'http://', $httpHost, $requestURI);
			$vl	 = explode('&', $uri);
			$vlink      = '<a href="';
			if ($usePathInfo) {
				$vlink .= serverVar('PHP_SELF') . '?';
			} else {
				$vlink .= $vl[0] . '&amp;';
			}
			$vlink .= 'vmode=';
			if (getVar('vmode') == 'day') {
				$j0 = 'week'  . strftime("%w", $tp);
			} else {
				$j0 = 'month' . strftime("%m", $tp);
			}
			$tquery = 'SELECT SUM(%s) as result FROM %s GROUP BY id';
			$vquery = sprintf($tquery, $j0, $viewTable);
			$j1     = quickQuery($vquery);

			if ($num[1] == 'b' && !$weekly && !$yearly && $j1) {
				$printData = $vlink . $arrVmode . '&amp;vnum=' . ($num[0]+1) . '">'
						   . date($date_f, $tp) . '</a> &laquo; ';
				echo $printData;
			}
			if ($vDairy && $num[1]) {
				$printData = date($date_f, $t0);
				echo $printData;
			}
			if ($vWeekly && $num[1]) {
				$printData = date($date_f, $tw)
						   . $this->getOption('week_format')
						   . date($date_f, $t0);
				echo $printData;
			}
			if ($vMonthly && $num[1]) {
				$printData = date($date_f, $t0);
				echo $printData;
			}
			if ($vYearly && $num[1]) {
				$printData = date($date_f, $ty)
						   . $this->getOption('week_format')
						   . date($date_f, $t0);
				echo $printData;
			}
			if ($num[1] == 'b' && $num[0] > 0 && !$weekly && !$yearly) {
				$printData = ' &raquo; ' . $vlink . $arrVmode
						   . '&amp;vnum=' . ($num[0]-1) . '">'
						   . date($date_f, $tn) . '</a>';
				echo $printData;
			}
			if ($num[1] == 'b') {
				$s_main = explode('/', $this->getOption('s_main'));
				echo $this->getOption('s_format');
				if ($s_main[0]) {
					$printData = $vlink . 'day"'
							   . ' title="' . $s_main[0] . $s_main[4] . '">'
							   . $s_main[0] . '</a> ';
					echo $printData;
				}
				if ($s_main[1]) {
					$printData = $vlink . 'week"'
							   . ' title="' . $s_main[1] . $s_main[4] . '">'
							   . $s_main[1] . '</a> ';
					echo $printData;
				}
				if ($s_main[2]) {
					$printData = $vlink . 'month"'
							   . ' title="' . $s_main[2] . $s_main[4] . '">'
							   . $s_main[2] . '</a> ';
					echo $printData;
				}
				if ($s_main[3]) {
					$printData = $vlink . 'year"'
							   . ' title="' . $s_main[3] . $s_main[4] . '">'
							   . $s_main[3] . '</a> ';
					echo $printData;
				}
			}
			if ($num[1]) {
				echo '</div>';
			}
			if (!$boxTag) {
				$boxTag = $this->getOption('box_format');
			}
			echo '<' . $boxTag . ' class="viewbody">';
			$b->showUsingQuery($template, $query, 0, 1, 1);
			echo '</' . $boxTag . '>';
		}
	}
}
