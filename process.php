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
			preg_match('/\d+/', $link, $id);
			echo "id: ".$id[0]."<br>";			

			$doc = hQuery::fromUrl($link,['Accept' => 'txt/html,application/xhtml+xml;q=0.9,*/*;q=0.8']);

			$instructor = $doc->find('h2');

			echo "first: ".$first[$pos]."<br>";
			echo "last: ".$last[$pos]."<br>";

			$metadata = $doc->find('tr');

			if ($instructor) {
				foreach($metadata as $pos => $meta) {
					$title = $meta->find('th')[0];
					$data  = $meta->find('td')[0];

					# clean up the silly checkboxes they used
					$data  = str_replace("&#x2610; ", "", $data);
					$data  = str_replace("&#x2611; ", "", $data);
					$title = str_replace(":", "", $title);

					if ($title == "Membership")     echo "membership: ".$data."<br>";
					if ($title == "E-Mail Address") echo "email: ".$data."<br>";
					if ($title == "City")           echo "city: ".$data."<br>";
					if ($title == "State")          echo "state: ".$data."<br>";
					if ($title == "Postal Code")    echo "zip: ".$data."<br>";
					if ($title == "Country")        echo "country: ".$data."<br>";
					if ($title == "Area Code") {
						$phone = $data;
					}
					if ($title == "Phone") {
						$phone .= str_replace("-", "", $data);
						echo "phone: ".$phone."<br>";
					}
					if ($title == "Mobile Area Code") {
						$mobile = $data;
					}
					if ($title == "Mobile") {
						$phone .= str_replace("-", "", $data);
						echo "mobile: ".$mobile."<br>";
					}
					if ($title == "Personal Web site") echo "website: ".$data."<br>";
					if ($title == "Background Verified") {
						$verified = 0;
						if ($data == "Yes") $verified = 1;
						echo "verified: ".$verified."<br>";
					}
					if ($title == "Active") {
						$active = 0;
						if ($data == "Yes") $active = 1;
						echo "active: ".$active."<br>";
					}
				}
			}
#		$temp++;
		}
		$sum++;
	}
	# output
	echo $sum." instructors<br>";
}

# Functions

echo '\o/';
?>
