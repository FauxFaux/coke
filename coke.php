<?

function gcc($url) {
	$hour = 60 * 60;
	$cachetime = 5 * $hour;
	$cachefile = preg_replace("/[^a-z]/", "", $url);

	if (file_exists($cachefile) && time() - $cachetime < filemtime($cachefile))
		return file_get_contents($cachefile);
	$contents = file_get_contents($url);
	file_put_contents($cachefile, $contents);
	return $contents;
}
class Unit {
	public $shop, $name, $form, $units, $ml, $cost, $url;
	public function __toString() {
		return $this->ml . "ml in $this->form for $this->cost at $url";
	}
}

function parseTesco($content) {
	$ret = array();
	// title, offer multiplier, offer ammount, price
	foreach (array(
		'|-title">.*?</span>(.*?)</a>.*?<em>Any (\d+) for ..(.*?)()</em>|',
		'|-title">.*?</span>(.*?)</a>()().*?"linePrice">..(.*?)<|s'
	) as $regex) {
		preg_match_all($regex, $content, $regs);
		foreach ($regs[1] as $k => $title) {
			$u = new Unit();
			$u->shop = 'tesco';
			$u->name = $title;
			if (preg_match('/(?:(\d+) ?X ?)?([\d.]+) ?(Mi?l(?:lilitre)?|Li?te?r)/i', $title, $r)) {
				$u->form = $r[2] . strtolower($r[3]);
				$u->units = max(1,$r[1]);
				$q = strtolower($r[3]);
				$u->ml = $u->units * $r[2] * ($q == 'ml' || $q == 'millilitre' ? 1 : 1000);
			}
			$u->cost = $regs[4][$k];
			$mul = $regs[2][$k];
			if ($mul != '') {
				$u->ml *= $mul;
				$u->units *= $mul;
				$u->cost = $regs[3][$k]; 
			}
			if ($u->ml == 0)
				echo htmlentities($title);
			$ret[] = $u;
		}
	}
	return $ret;
}

function cmp($a, $b) {
	return ($a->cost/$a->ml) > ($b->cost/$b->ml);
}
$url = 'http://www.tesco.com/groceries/product/search/default.aspx?searchBox=regular%20coca%20cola';
$tesco = parseTesco(gcc($url));
uasort($tesco, 'cmp');
reset($tesco);
?>
<html>
<head>
<title>COOOOOKE</title>
<link rel="stylesheet" href="/style.css"/>
</head>
<body>
<p>Data sourced from <a href="<?=$url?>">Tesco</a>.</p>
<p>All units in SI cans.</p>
<p id="cans">Cans are currently 
<?
function cans($lis) {
	foreach ($lis as $t) {
		if (preg_match('/330 ?ml/i', $t->name))
			return $t;
	}
}
echo number_format(percan(cans($tesco))*100,1)
?>
p.</p>
<table>
<?

function percan($t) {
	return 330*$t->cost/$t->ml;
}

foreach ($tesco as $t) {
	$can = percan($t);
	echo "<tr>" .
		"<td>" . preg_replace('/Coca Cola Regular/i', '', $t->name) . "</td>" .
		"<td>£$t->cost</td>" .
		"<td>£" . number_format($t->cost/$t->units, 2) . "/object</td>" .
		"<td>" . number_format(100*$can,1) . "p/can</td>" .
		"</tr>\n";
}
?>
</table>
</body>
</html>

