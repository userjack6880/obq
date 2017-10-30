<?php
/**
 * View Instructors
 */
# Preload Stuff
include_once 'config.php';
include_once 'lib/hquery.php';
	use duzun\hQuery;
	hQuery::$cache_path = "cache";

$sort = $_GET["sort"];
$offset = $_GET["offset"];

# Open MySQL Connection
$mysql = mysql_connect('localhost', DB_USER, DB_PASSWORD);
if (!$mysql) {
	die("Could not connect: ".mysql_error());
}

$db_selected = mysql_select_db(DB_NAME, $mysql);
if (!$db_selected) {
	die("Can't use DB: ".mysql_error());
}

echo '<h1>Instructors</h1>';

# let's get some pagination stuff

$sql = "SELECT COUNT(*) FROM `obq_instructors`";

$result = mysql_query($sql);
if (!$result) {
	die('Invalid query: '.mysql_error());
}

$count = mysql_fetch_assoc($result)['COUNT(*)'];

echo $count." entries<br>";

# select all the data from the database...

$sql = "SELECT * FROM `obq_instructors`";

# determine how its sorted

if ($sort) {
	$sql .= " ORDER BY `$sort` asc";
} else {
	$sql .= " ORDER BY `last` asc";
	$sort = "last";
}

# get the offset
if ($offset) {
	$sql .= " LIMIT 10 OFFSET $offset";
} else {
	$sql .= " LIMIT 10";
	$offset = 0;
}

$result = mysql_query($sql);
if (!$result) {
	die('Invalid query: '.mysql_error());
}

# print pagination

# keep counting until we're up to the last few
$curr = 1;
$offs = 0;
$cnt2 = $count;
while ($cnt2 > 0) {
	# if current page
	if ($curr*10 == $offset+10) {
		echo "$curr ";
	} else {
		?><a href="instructors.php?sort=<?php echo $sort; ?>&offset=<?php echo $offs; ?>"><?php echo $curr; ?></a> <?php
	}
	$curr++;
	$offs = $offs+10;
	$cnt2 = $cnt2-10;
}
echo "<br>";

# print results
while ($row = mysql_fetch_assoc($result)) {
	foreach($row as $field => $value) {
		echo "$field: $value<br>";
	}
}

# print pagination

# keep counting until we're up to the last few
$curr = 1;
$offs = 0;
$cnt2 = $count;
while ($cnt2 > 0) {
	# if current page
	if ($curr*10 == $offset+10) {
		echo "$curr ";
	} else {
		?><a href="instructors.php?sort=<?php echo $sort; ?>&offset=<?php echo $offs; ?>"><?php echo $curr; ?></a> <?php
	}
	$curr++;
	$offs = $offs+10;
	$cnt2 = $cnt2-10;
}
echo "<br>";

mysql_close($mysql);
echo '\o/';
?>
