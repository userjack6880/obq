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
	echo "loading site\n";

#	$links = array();

	$entries = $doc->find('tr');

	if ($entries) {
	echo "entries found\n";
		foreach($entries as $pos -> $tr) {
			echo "printing entry\n";
#			echo $tr->attr{'onclick');
		}
	}
	# pull up each instructor's page and pull the data
	# output
}

# Functions

echo '\o/';
?>
