<?php
/**
 * Process HTML
 */
# Preload Stuff
include_once 'lib/hquery.php';
	use duzun\hQuery;
	hQuery::$cache_path = "cache";

$site = $_GET["site"];

# Site Specific Stuff
# pbia
if ($site == 'pbia' || $site == 'all') {
	# get full list of instructors
	$doc = hQuery::fromUrl('http://playbetterbilliards.com/instructors?state=&country=&active=1',['Accept' => 'txt/html,application/xhtml+xml;q=0.9,*/*;q=0.8']);

	$entries = $doc->find('tr');
	$links = array();

	if ($entries) {
		foreach($entries as $pos => $tr) {
			$link = $tr->attr('onclick');
			$link = str_replace("location.href='", "", $link);
			$link = str_replace("';", "", $link);
			$links[$pos] = $link;
		}
	}

	# pull up each instructor's page and pull the data
	$temp = 1;
	foreach($links as $link) {
		if ($link && $temp < 3) {
			preg_match('/\d+/', $link, $id);
			$doc = hQuery::fromUrl($link,['Accept' => 'txt/html,application/xhtml+xml;q=0.9,*/*;q=0.8']);

			$instructor = $doc->find('h2');
			$instructor = trim($instructor->text());

			$metadata = $doc->find('tr');

			if ($instructor) {
				foreach($metadata as $pos => $meta) {
					$title = $meta->find('th')[0];
					$data  = $meta->find('td')[0];

					# clean up the silly checkboxes they used
					$data  = str_replace("&#x2610; ", "", $data);
					$data  = str_replace("&#x2611; ", "", $data);
					$title = str_replace(":", "", $title);
				}
			}
		$temp++;
		}
	}
	# output
}

# Functions

echo '\o/';
?>
