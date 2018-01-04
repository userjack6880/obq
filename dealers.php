<?php
/**
 * View Dealers
 */
# Preload Stuff
include_once 'config.php';

# Open MySQL Connection
$mysql = new mysqli('localhost', DB_USER, DB_PASSWORD, DB_NAME);
if (mysqli_connect_errno()) {
	die("Could not connect: ".mysqli_connect_error());
}

$offset   = 0;
$s_dealer = '';
$s_city   = '';
$s_state  = '';
$s_zip    = '';
$s_vendor = '';

if (isset($_GET["offset"])) $offset   = mysql_real_escape_string($_GET["offset"]);
if (isset($_GET["dealer"])) $s_dealer = mysql_real_escape_string($_GET["dealer"]);
if (isset($_GET["city"]))   $s_city   = mysql_real_escape_string($_GET["city"]);
if (isset($_GET["state"]))  $s_state  = mysql_real_escape_string($_GET["state"]);
if (isset($_GET["zip"]))    $s_zip    = mysql_real_escape_string($_GET["zip"]);
if (isset($_GET["vendor"])) $s_vendor = mysql_real_escape_string($_GET["vendor"]);

echo "<a href=\"dealers.php\"><h1>Dealers</h1></a>\n";

# select all the data from the database...

$sql = "SELECT * FROM `obq_dealers`";

# if searches are done, add appropriate lines

if ($s_dealer || $s_city || $s_state || $s_zip || $s_vendor) {
	$and = 0;
	$sql .= " WHERE ";
	if ($s_dealer) {
		$sql .= "`name` LIKE '%$s_dealer%'";
		$and = 1;
	}
	if ($s_city) {
		if ($and) $sql .= " AND ";
		$sql .= "`city` LIKE '%$s_city%'";
		$and = 1;
	}
	if ($s_state) {
		if ($and) $sql .= " AND ";
		$sql .= "`state` LIKE '%$s_state%'";
		$and = 1;
	}
	if ($s_zip) {
		if ($and) $sql .= " AND ";
		$sql .= "`zip` LIKE '%$s_zip%'";
		$and = 1;
	}
	if ($s_vendor) {
		if ($and) $sql .= " AND ";
		$sql .= "`vendor` LIKE '%$s_vendor%'";
		$and = 1;
	}
}

# just sort it by name - he wants a CSV anyways
$sql .= " ORDER BY `name` asc";

$result = $mysql->query($sql);
if (!$result) {
	die('Invalid query: '.mysql_error());
}

$count = mysqli_num_rows($result);

# get the offset
if ($offset) {
	$sql .= " LIMIT 20 OFFSET $offset";
} else {
	$sql .= " LIMIT 20";
}

$result = $mysql->query($sql);
if (!$result) {
	die('Invalid query: '.mysqli_error($mysql));
}

# print pagination

echo "$count entries<br>\n";

# keep counting until we're up to the last few
$curr = 1;
$offs = 0;
$cnt2 = $count;
while ($cnt2 > 0) {
	# if current page
	if ($curr*20 == $offset+20) {
		echo "$curr ";
	} else {
		?><a href="dealers.php?offset=<?php echo $offs;

		if ($s_dealer) echo "&dealer=$s_dealer";
		if ($s_city) echo "&city=$s_city";
		if ($s_state) echo "&state=$s_state";
		if ($s_zip) echo "&zip=$s_zip";
		#if ($s_vendor) echo "&vendor=$s_vendor";
		?>"><?php echo $curr; ?></a> <?php
	}
	$curr++;
	$offs = $offs+20;
	$cnt2 = $cnt2-20;
}
echo "<br>\n";

# print results
?>
<br>
<form action="dealers.php" method="get">
Dealer:<input type="text" name="dealer">
City:<input type="text" name="city">
State:<input type="text" name="state">
Zip:<input type="text" name="zip">
Vendor:<input type="text" name="vendor">
<input type="hidden" name="hide" value="<?php echo $_GET['hide']; ?>">
<input type="submit" value="Search">
</form>

<table>
	<tr>
		<td>Dealer</td>
		<td>Phone</td>
		<td>Website</td>
		<td>Email</td>
		<td>City</td>
		<td>State</td>
		<td>Zip</td>
		<td>Vendors</td>
	</tr>
<?php

while ($row = $result->fetch_assoc()) {
	echo '<tr>';
	foreach($row as $field => $value) {
		if ($field == 'id' || $field == 'fax' || $field == 'addr1' || $field == 'addr2' || $field == 'addr3') continue;
		echo "<td>$value</td>\n";
	}

	# now we need to get the vendors for the dealer
	$sql = "SELECT * FROM `obq_vendors` WHERE `name`='".$mysql->real_escape_string($row['name'])."'";

	echo "<td>";
	$result2 = $mysql->query($sql);
	while ($row2 = $result2->fetch_assoc()) {
		echo $row2['vendor']."<br>\n";
	}
	echo "</td>\n";

	echo "</tr>\n";
}

echo "</table>\n";

# print pagination

$mysql->close();
echo '\o/';
?>
