<?php
/**
 * Process HTML
 */
# Preload Stuff
include_once 'config.php';
include_once 'lib/hquery.php';
	use duzun\hQuery;
	hQuery::$cache_path = "cache";

if (isset($argv[1])) { $site = $argv[1]; }
else { $site = $_GET["site"]; }

global $debug;
if (isset($argv[2])) { $debug = $argv[2]; }
elseif (isset($_GET["debug"])) { $debug = $_GET["debug"]; }
else { $debug = 0; }

# Open MySQL Connection
$mysql = new mysqli('localhost', DB_USER, DB_PASSWORD, DB_NAME);
if (mysqli_connect_errno()) {
	die("Could not connect: ".mysqli_connect_error());
}

echo "DB \o/<br>";

# Site Specific Stuff
# pbia
if ($site == 'pbia' || $site == 'all') {
	$sum = 0;
	echo "<h1>Instructors</h1>\n";
	# get full list of instructors
	$doc = hQuery::fromUrl('http://playbetterbilliards.com/instructors?state=&country=&active=1',['Accept' => 'txt/html,application/xhtml+xml;q=0.9,*/*;q=0.8']);

	$entries = $doc->find('tr');
	$links = array();
	$first = array();
	$last  = array();

	if ($entries) {
		foreach($entries as $pos => $tr) {
			$first[$pos] = $mysql->real_escape_string($tr->find('td')[1]);
			$last[$pos]  = $mysql->real_escape_string($tr->find('td')[2]);

			$link = $tr->attr('onclick');
			$link = str_replace("location.href='", "", $link);
			$link = str_replace("';", "", $link);
			$links[$pos] = $link;
		}
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
			$ins_update = "`first`='$first[$pos]',`last`='$last[$pos]'";

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
					$data = $mysql->real_escape_string($data);

					if ($title == "Membership") {
						$ins_field .= ",`membership`";
						$ins_data  .= ",'$data'";
						$ins_update .= ",`membership`='$data'";
					}
					if ($title == "E-Mail Address") {
						$ins_field .= ",`email`";
						$ins_data  .= ",'$data'";
						$ins_update .= ",`email`='$data'";
					}
					if ($title == "City") {
						$ins_field .= ",`city`";
						$ins_data  .= ",'$data'";
						$ins_update .= ",`city`='$data'";
					}
					if ($title == "State") {
						$ins_field .= ",`state`";
						$ins_data  .= ",'$data'";
						$ins_update .= ",`state`='$data'";
					}
					if ($title == "Postal Code") {
						$ins_field .= ",`zip`";
						$ins_data  .= ",'$data'";
						$ins_update .= ",`zip`='$data'";
					}
					if ($title == "Country") {
						$ins_field .= ",`country`";
						$ins_data  .= ",'$data'";
						$ins_update .= ",`country`='$data'";
					}
					if ($title == "Area Code") {
						$phone = $data;
					}
					if ($title == "Phone") {
						$phone .= str_replace("-", "", $data);
						$ins_field .= ",`phone`";
						$ins_data  .= ",'$phone'";
						$ins_update .= ",`phone`='$phone'";
					}
					if ($title == "Mobile Area Code") {
						$mobile = $data;
					}
					if ($title == "Mobile") {
						$mobile .= str_replace("-", "", $data);
						$ins_field .= ",`mobile`";
						$ins_data  .= ",'$mobile'";
						$ins_update .= ",`mobile`='$mobile'";
					}
					if ($title == "Personal Web site") {
						$ins_field .= ",`website`";
						$ins_data  .= ",'$data'";
						$ins_update .= ",`website`='$data'";
					}
					if ($title == "Background Verified") {
						$ins_field .= ",`verified`";
						$verified = 0;
						if ($data == "Yes") $verified = 1;
						$ins_data  .= ",'$verified'";
						$ins_update .= ",`verified`='$verified'";
					}
					if ($title == "Active") {
						$ins_field .= ",`active`";
						$active = 0;
						if ($data == "Yes") $active = 1;
						$ins_data  .= ",'$active'";
						$ins_update .= ",`active`='$active'";
					}
				}
			}
			$sql = "INSERT INTO `obq_instructors` ($ins_field) VALUES ($ins_data) ON DUPLICATE KEY UPDATE $ins_update";
			$result = $mysql->query($sql);
			if (!$result) {
				die("Invalid query: ".mysqli_error($mysql));
			}

			$sum++;
			if ($debug) { 
				echo "$sum done\r"; 
			}
		}
	}
	# output
	if ($debug) { echo "\n"; }
	echo "$sum instructors<br>\n";
}

# Meucci Cues uses a javascript-based map... fortunately, we can just get data directly from them.

if ($site == 'meucci' || $site == 'all') {
	# pull in data from meucci's js file
	$file = file_get_contents('http://meuccicues.com/map/map-config.js');

	preg_match_all('/\'data\'\:\'(.+)\'/', $file, $lines);

	foreach ($lines[1] as $line) {
		# if the data says no dealers, or is a "data" file, move to the next section
		# if not, take the data, and then run it through the meucci function
		if (!preg_match('/SORRY, NO DEALERS HERE/',$line)) {

			# now process the line, and pull out some data...
			$data = explode("<br>", $line);

			$phone = '';
			$city = '';
			$state = '';
			$zip = '';
			$name = '';
			$ins_field = '';
			$ins_data = '';
			$ins_update = '';
			$insert = 0;	

			# now that the data is in an array, let's go through each line of the array...
			foreach ($data as $data_line) {
				echo "$data_line\n";
				# phone
				if(preg_match('/\d{3}\-\d{3}\-\d{4}/', $data_line, $phone)) {
					$ins_field  .= ",`phone`";
					$ins_data   .= ",'".str_replace("-", "", $phone[0])."'";
					$ins_update .= ",`phone`='".str_replace("-", "", $phone[0])."'";
				}
				# city, state, zip
				elseif (preg_match('/^([^,]+),\s([A-Z]{2})\s(\d+)/', $data_line, $matches)) {
					$city  = $mysql->real_escape_string($matches[1]);
					$state = $matches[2];
					$zip   = $matches[3];
					$ins_field  .= ",`city`,`state`,`zip`";
					$ins_data   .= ",'$city','$state','$zip'";
					$ins_update .= ",`city`='$city',`state`='$state',`zip`='$zip'";
				}
				# dealer name
				elseif (preg_match('/^[A-Z. ]+/', $data_line, $dealer)) {
					$name = $mysql->real_escape_string($dealer[0]);
					echo "$name\n";
					$ins_field  = "`name`";
					$ins_data   = "'$name'";
					$ins_update = "`name`='$name'";
				}
				# if a blank line, put data into data base
				elseif (empty($data_line)) {
					# for the foreign locations, the dealer name gets mangled... we can easily filter
					if (isset($city) && isset($state) && isset($zip) && $insert == 0) {
						dealer_insert($name,$ins_field,$ins_data,$ins_update,$mysql);
						$city = '';
						$state = '';
						$zip = '';
						$insert = 1;
					}
				}
			}

			# we can assume the last item in the array was extracted, shove it into the database too
			if (isset($city) && isset($state) && isset($zip)) {
				dealer_insert($name,$ins_field,$ins_data,$ins_update,$mysql);
				$city = '';
				$state = '';
				$zip = '';
			}
		}
	}
}

# McDermott and Viking use the same system to locate their dealers, 
# so most of this code will be shared between the two. It'll be a 
# function and will be run according to the vendor they're using.

if ($site == 'mcd' || $site == 'all') ult_loc('mcd',$mysql);
if ($site == 'viking' || $site == 'all') ult_loc('viking',$mysql);

function ult_loc($loc,$mysql) {
	global $debug;
	echo "<h1>$loc</h1>\n";
	if ($loc == 'mcd') { $url = 'http://www.mcdermottcue.com/locator/results_list.php?pageno='; }
	else { $url = 'http://shop.vikingcue.com/locator/results_list.php?pageno='; }
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
				$ins_data  = "'".$mysql->real_escape_string($name)."'";
				$ins_update = "`name`='".$mysql->real_escape_string($name)."'";

				$phone = $doc->find('li','id=phone');
				if ($phone) {
					$phone = str_replace(array('(',')','-',' '), "", $phone);
					$ins_field .= ",`phone`";
					$ins_data  .= ",'$phone'";
					$ins_update .= ",`phone`='$phone'";
				}

				$fax = $doc->find('li','id=fax');
				if ($fax) {
					$fax = str_replace(array('(',')','-',' '), "", $fax);
					$ins_field .= ",`fax`";
					$ins_data  .= ",'$fax'";
					$ins_update .= ",`fax`='$fax'";
				}
			
				$website = $doc->find('li','id=website');
				if ($website) {
					$link = $website->find('a');
					if ($loc == 'mcd') $website = $website->text();
					if ($loc == 'viking') $website = $link->attr('href');
					$ins_field .= ",`website`";
					$ins_data  .= ",'".$mysql->real_escape_string($website)."'";	
					$ins_update .= ",`website`='".$mysql->real_escape_string($website)."'";
				}

				$email = $doc->find('li','id=email');
				if ($email && $loc != 'viking') {
					$email = $email->text();
					$ins_field .= ",`email`";
					$ins_data  .= ",'".$mysql->real_escape_string($email)."'";
					$ins_update .= ",`email`='".$mysql->real_escape_string($email)."'";
				}

				$address = $doc->find('span.resultInfo');
				if ($address) {
					$i = 1;
					foreach ($address as $line) {
						$ins_field .= ",`addr$i`";
						$ins_data  .= ",'".$mysql->real_escape_string($line)."'";
						$ins_update .= ",`addr$i`='".$mysql->real_escape_string($line)."'";
						$i++;
					}

					$last = count($address);
					preg_match('/([^,]+),\s([A-Z]{2}.)\s(\d+)/', $address[$last-1], $address2);

					$addr1 = isset($address2[1]) ? $address2[1] : '';
					$addr2 = isset($address2[2]) ? $address2[2] : '';
					$addr3 = isset($address2[3]) ? $address2[3] : '';

					$ins_field .= ",`city`,`state`,`zip`";
					$ins_data  .= ",'".$mysql->real_escape_string($addr1)."','".str_replace('.','',$addr2)."','$addr3'";
					$ins_update .= ",`city`='".$mysql->real_escape_string($addr1)."',`state`='".str_replace('.','',$addr2)."',`zip`='$addr3'";
				}

				dealer_insert($name,$ins_field,$ins_data,$ins_update,$mysql);
			}
		}

		$pageno++;
	}

	if ($debug) { echo "\n"; }
	echo "$sum entries<br>\n";

	# take the data collected shove it into the database
}

function dealer_insert($name,$ins_field,$ins_data,$ins_update,$mysql) {
	global $debug;
	global $sum;
	
	$result = $mysql->query("SELECT * FROM `obq_dealers` WHERE `name`='".$mysql->real_escape_string($name)."'");
		$count = mysqli_num_rows($result);
		if ($count > 0) {
			if ($debug) {
				echo "Entry Exists, Updating\r";
			}
			$sql = "UPDATE `obq_dealers` SET $ins_update WHERE `name`='".$mysql->real_escape_string($name)."'";
			$sum++;
		} else {
			if ($debug) {
				echo "New Entry\r";
			}
			$sql = "INSERT INTO `obq_dealers` ($ins_field) VALUES ($ins_data)";
			$sum++;
		}				
		$result = $mysql->query($sql);
		if (!$result) {
			echo "Query: $sql\n";
			die ("Invalid Query: ".mysqli_error($mysql));
		}
		if ($debug) { 
			echo "$sum Done. "; 
		}
}

$mysql->close();

?>

<a href="index.php">Back</a>

<?php

echo '\o/';
?>
