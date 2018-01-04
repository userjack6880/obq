<?php
/**
 * View Instructors
 */
# Preload Stuff
include_once 'config.php';

# Tell the browser it's a CSV file
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=instructors.csv');

# create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

# output column headings
fputcsv($output, array('first','last','membership','email','city','state','zip','country','phone','mobile','website','verified','active'));

# Open MySQL Connection
$mysql = new mysqli('localhost', DB_USER, DB_PASSWORD, DB_NAME);
if (mysqli_connect_errno()) {
	die("Could not connect: ".mysqli_connect_error());
}

# select all the data from the database...

$sql = "SELECT * FROM `obq_instructors` ORDER BY `last` asc";

$result = $mysql->query($sql);
if (!$result) {
	die('Invalid query: '.mysqli_error($mysql));
}

while ($row = $result->fetch_assoc()) {
	$row_out = [];
	foreach($row as $field => $value) {
		if ($field == 'id') continue;
		if ($field == 'verified' || $field == 'active') {
			if ($value == 0) { $row_out[] = "no";	} else { $row_out[] = "yes"; }
		} else { $row_out[] = $value; }
	}
	fputcsv($output, $row_out);
}

$mysql->close();
?>
