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
			$first[$pos] = mysql_real_escape_string($tr->find('td')[1]);
			$last[$pos]  = mysql_real_escape_string($tr->find('td')[2]);

			$link = $tr->attr('onclick');
			$link = str_replace("location.href='", "", $link);
			$link = str_replace("';", "", $link);
			$links[$pos] = $link;
		}
	}

	# clear the table for new data

	$result = mysql_query("TRUNCATE TABLE `obq_instructors`");
	if (!$result) {
		die("Invalid query: ".mysql_error());
	}

	# pull up each instructor's page and pull the data
	foreach($links as $pos => $link) {
		if ($link) {
			preg_match('/\d+/', $link, $id);
			$ins_field = "`id`";
			$ins_data  = "'$id[0]'";			

			$doc = hQuery::fromUrl($link,['Accept' => 'txt/html,application/xhtml+xml;q=0.9,*/*;q=0.8']);

			$instructor = $doc->find('h2');

			$ins_field .= ",`first`,`last`";
			$ins_data  .= ",'$first[$pos]','$last[$pos]'";

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

					# sanitize data
					$data = mysql_real_escape_string($data);

					if ($title == "Membership") {
						$ins_field .= ",`membership`";
						$ins_data  .= ",'$data'";
					}
					if ($title == "E-Mail Address") {
						$ins_field .= ",`email`";
						$ins_data  .= ",'$data'";
					}
					if ($title == "City") {
						$ins_field .= ",`city`";
						$ins_data  .= ",'$data'";
					}
					if ($title == "State") {
						$ins_field .= ",`state`";
						$ins_data  .= ",'$data'";
					}
					if ($title == "Postal Code") {
						$ins_field .= ",`zip`";
						$ins_data  .= ",'$data'";
					}
					if ($title == "Country") {
						$ins_field .= ",`country`";
						$ins_data  .= ",'$data'";
					}
					if ($title == "Area Code") {
						$phone = $data;
					}
					if ($title == "Phone") {
						$phone .= str_replace("-", "", $data);
						$ins_field .= ",`phone`";
						$ins_data  .= ",'$phone'";
					}
					if ($title == "Mobile Area Code") {
						$mobile = $data;
					}
					if ($title == "Mobile") {
						$mobile .= str_replace("-", "", $data);
						$ins_field .= ",`mobile`";
						$ins_data  .= ",'$mobile'";
					}
					if ($title == "Personal Web site") {
						$ins_field .= ",`website`";
						$ins_data  .= ",'$data'";
					}
					if ($title == "Background Verified") {
						$ins_field .= ",`verified`";
						$verified = 0;
						if ($data == "Yes") $verified = 1;
						$ins_data  .= ",'$verified'";
					}
					if ($title == "Active") {
						$ins_field .= ",`active`";
						$active = 0;
						if ($data == "Yes") $active = 1;
						$ins_data  .= ",'$active'";
					}
				}
			}
		$sql = "INSERT INTO `obq_instructors` ($ins_field) VALUES ($ins_data)";
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

# McDermott and Viking use the same system to locate their dealers, 
# so most of this code will be shared between the two. It'll be a 
# function and will be run according to the vendor they're using.

if ($site == 'mcd' || $site == 'all') ult_loc('mcd');
if ($site == 'viking' || $site == 'all') ult_loc('viking');

function ult_loc($loc) {
	echo "<h1>$loc</h1>";
	if ($loc == 'mcd') {
		$url = 'http://www.mcdermottcue.com/locator/results_list.php?pageno=';
		$result = mysql_query("DELETE FROM `obq_vendors` WHERE `vendor` LIKE 'McDermott'");
		if (!$result) {
			die ("Invalid Query: ".mysql_error());
		}
	} else {
		$url = 'http://shop.vikingcue.com/locator/results_list.php?pageno=';
		$result = mysql_query("DELETE FROM `obq_vendors` WHERE `vendor` LIKE 'Viking'");
		if (!$result) {
			die ("Invalid Query: ".mysql_error());
		}
	}
	$pageno = 1;

	# get the number of pages involved and start the itteration
	$doc = hQuery::fromUrl($url.$pageno,['Accept' => 'txt/html,application/xhtml+xml;q=0.9,*/*;q=0.8']);

	$pagetot = $doc->find('div.pagination');

	preg_match('/\(\d+\)/', $pagetot, $pagetot);
	$pagetot = str_replace(array('(',')'), "", $pagetot);

	$sum = 0;

	# now run through each page
	while ($pageno <= $pagetot[0]) {
		$doc = hQuery::fromUrl($url.$pageno,['Accept' => 'txt/html,application/xhtml+xml;q=0.9,*/*;q=0.8']);

		# get links for each page, then open them up, load the data, and save it... it's not the best, but there's not a much better way
		$links = $doc->find('span > a');

		if ($links) {
			foreach ($links as $pos => $link) {
				$url2 = $link->attr('href');

				$doc = hQuery::fromUrl($url2,['Accept' => 'txt/html,application/xhtml+xml;q=0.9,*/*;q=0.8']);

				if (!$doc) continue;	# sometimes we get a page that just won't load...

				$name = strtoupper(trim($link->text()));
				$ins_field = "`name`";
				$ins_data  = "'".mysql_real_escape_string($name)."'";

				if ($loc == 'mcd') {
					$ins_field .= ",`vendor`";
					$ins_data  .= ",'McDermott'";
				}
				if ($loc == 'viking') {
					$ins_field .= ",`vendor`";
					$ins_data  .= ",'Viking'";
				}

				$phone = $doc->find('li','id=phone');
				if ($phone) {
					$phone = str_replace(array('(',')','-',' '), "", $phone);
					$ins_field .= ",`phone`";
					$ins_data  .= ",'$phone'";
				}

				$fax = $doc->find('li','id=fax');
				if ($fax) {
					$fax = str_replace(array('(',')','-',' '), "", $fax);
					$ins_field .= ",`fax`";
					$ins_data  .= ",'$fax'";
				}
			
				$website = $doc->find('li','id=website');
				if ($website) {
					$link = $website->find('a');
					if ($loc == 'mcd') $website = $website->text();
					if ($loc == 'viking') $website = $link->attr('href');
					$ins_field .= ",`website`";
					$ins_data  .= ",'".mysql_real_escape_string($website)."'";	
				}

				$email = $doc->find('li','id=email');
				if ($email && $loc != 'viking') {
					$email = $email->text();
					$ins_field .= ",`email`";
					$ins_data  .= ",'".mysql_real_escape_string($email)."'";
				}

				$address = $doc->find('span.resultInfo');
				if ($address) {
					$i = 1;
					foreach ($address as $line) {
						$ins_field .= ",`addr$i`";
						$ins_data  .= ",'".mysql_real_escape_string($line)."'";
						$i++;
					}

					$last = count($address);
					preg_match('/([^,]+),\s([A-Z]{2}.)\s(\d+)/', $address[$last-1], $address2);

					$ins_field .= ",`city`,`state`,`zip`";
					$ins_data  .= ",'".mysql_real_escape_string($address2[1])."','".str_replace('.','',$address2[2])."','$address2[3]'";
				}
				
				$sql = "INSERT INTO `obq_vendors` ($ins_field) VALUES ($ins_data)";
				
				$result = mysql_query($sql);
				if (!$result) {
					die ("Invalid Query: ".mysql_error());
				}

				$sum++;
			}
		}

		$pageno++;
	}

	echo $sum." entries<br>";

	# take the data collected shove it into the database
}

mysql_close($mysql);

?>

<a href="index.php">Back</a>

<?php

echo '\o/';
?>
