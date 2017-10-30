<?php
/**
 * Process HTML
 */
# Preload Stuff
include_once 'config.php';
include_once 'lib/hquery.php';
	use duzun\hQuery;
	hQuery::$cache_path = "cache";

$site = $_GET["site"];

# Open MySQL Connection
$mysql = mysql_connect('localhost', DB_USER, DB_PASSWORD);
if (!$mysql) {
	die("Could not connect: ".mysql_error());
}

$db_selected = mysql_select_db(DB_NAME, $mysql);
if (!$db_selected) {
	die("Can't use DB: ".mysql_error());
}

echo "DB \o/<br>";

# Site Specific Stuff
# pbia
if ($site == 'pbia' || $site == 'all') {
	$sum = 0;
	echo "<h1>Instructors</h1>";
	# get full list of instructors
	$doc = hQuery::fromUrl('http://playbetterbilliards.com/instructors?state=&country=&active=1',['Accept' => 'txt/html,application/xhtml+xml;q=0.9,*/*;q=0.8']);

	$entries = $doc->find('tr');
	$links = array();
	$first = array();
	$last  = array();

	if ($entries) {
		foreach($entries as $pos => $tr) {
			$first[$pos] = $tr->find('td')[1];
			$last[$pos]  = $tr->find('td')[2];

			$link = $tr->attr('onclick');
			$link = str_replace("location.href='", "", $link);
			$link = str_replace("';", "", $link);
			$links[$pos] = $link;
		}
	}

	# pull up each instructor's page and pull the data
	$temp = 1;
	foreach($links as $pos => $link) {
		if ($link) {
			$sql = "INSERT INTO `obq_instructors` (`id`, `first`, `last`, `membership`, `email`, `city`, `state`, `zip`, `country`, `phone`, `mobile`, `website`, `verified`, `active`) VALUES ('";
			preg_match('/\d+/', $link, $id);
			$sql .= $id[0]."', '";			

			$doc = hQuery::fromUrl($link,['Accept' => 'txt/html,application/xhtml+xml;q=0.9,*/*;q=0.8']);

			$instructor = $doc->find('h2');

			$sql .= $first[$pos]."', '";
			$sql .= $last[$pos]."', '";

			$metadata = $doc->find('tr');

			if ($instructor) {
				foreach($metadata as $pos => $meta) {
					$title = $meta->find('th')[0];
					$data  = $meta->find('td')[0];

					# make sure the data is text-only
					$data = trim($data->text());

					# clean up the silly checkboxes they used
					$data  = str_replace("&#x2610; ", "", $data);
					$data  = str_replace("&#x2611; ", "", $data);
					$title = str_replace(":", "", $title);

					if ($title == "Membership")     $sql .= $data."', '";
					if ($title == "E-Mail Address") $sql .= $data."', '";
					if ($title == "City")           $sql .= $data."', '";
					if ($title == "State")          $sql .= $data."', '";
					if ($title == "Postal Code")    $sql .= $data."', '";
					if ($title == "Country")        $sql .= $data."', '";
					if ($title == "Area Code") {
						$phone = $data;
					}
					if ($title == "Phone") {
						$phone .= str_replace("-", "", $data);
						$sql .= $phone."', '";
					}
					if ($title == "Mobile Area Code") {
						$mobile = $data;
					}
					if ($title == "Mobile") {
						$mobile .= str_replace("-", "", $data);
						$sql .= $mobile."', '";
					}
					if ($title == "Personal Web site") $sql .= $data."', '";
					if ($title == "Background Verified") {
						$verified = 0;
						if ($data == "Yes") $verified = 1;
						$sql .= $verified."', '";
					}
					if ($title == "Active") {
						$active = 0;
						if ($data == "Yes") $active = 1;
						$sql .= $active."')";
					}
				}
			}
		$sql .= "ON DUPLICATE KEY UPDATE first=VALUES(first), last=VALUES(last), membership=VALUES(membership), email=VALUES(email), city=VALUES(city), state=VALUES(state), zip=VALUES(zip), country=VALUES(country), phone=VALUES(phone), mobile=VALUES(mobile), verified=VALUES(verified), active=VALUES(active);";
		$result = mysql_query($sql);
		if (!$result) {
			die("Invalid query: ".mysql_error());
		}
		$sum++;
		}
	}
	# output
	echo $sum." instructors<br>";
}

# Functions

mysql_close($mysql);
echo '\o/';
?>
