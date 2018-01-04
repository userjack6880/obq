<?php
/**
 * View Dealers
 */
# Preload Stuff
include_once 'config.php';

# Tell the browser it's a CSV file
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=dealers.csv');

# create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

# output column headings
fputcsv($output, array('name','phone','fax','website','email','addr1','addr2','addr3','city','state','zip','vendors'));

# Open MySQL Connection
$mysql = new mysqli('localhost', DB_USER, DB_PASSWORD, DB_NAME);
if (mysqli_connect_errno()) {
	die("Could not connect: ".mysqli_connect_error());
}

# select all the data from the database...

$sql = "SELECT * FROM `obq_dealers` ORDER BY `name` asc";

$result = $mysql->query($sql);
if (!$result) {
	die('Invalid query: '.mysqli_error($mysql));
}

while ($row = $result->fetch_assoc()) {
	$row_out = [];
	foreach($row as $field => $value) {
		if ($field == 'id') continue;
		$row_out[] = $value;
	}

	# now we need to get the vendors for the dealer
	$sql = "SELECT * FROM `obq_vendors` WHERE `name`='".$mysql->real_escape_string($row['name'])."'";

	$result2 = $mysql->query($sql);
	$first = 1;
	$vendor = '';
	while ($row2 = $result2->fetch_assoc()) {
		# first entry
		if ($first == 1) {
			$vendor = $row2['vendor'];
			$first = 0;
		# every other entry...
		} else {
			$vendor .= " ".$row2['vendor'];
		}
	}
	$row_out[] = $vendor;
	fputcsv($output,$row_out);
}

$mysql->close();
?>
