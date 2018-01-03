<?php
/**
 * View Dealers
 */
# Preload Stuff
include_once 'config.php';

# Open MySQL Connection
$mysql = mysql_connect('localhost', DB_USER, DB_PASSWORD);
if (!$mysql) {
	die("Could not connect: ".mysql_error());
}

$db_selected = mysql_select_db(DB_NAME, $mysql);
if (!$db_selected) {
	die("Can't use DB: ".mysql_error());
}

$sort     = mysql_real_escape_string($_GET["sort"]);
$offset   = mysql_real_escape_string($_GET["offset"]);
$hide     = mysql_real_escape_string($_GET["hide"]);
$s_dealer = mysql_real_escape_string($_GET["dealer"]);
$s_city   = mysql_real_escape_string($_GET["city"]);
$s_state  = mysql_real_escape_string($_GET["state"]);
$s_zip    = mysql_real_escape_string($_GET["zip"]);
$s_vendor = mysql_real_escape_string($_GET["vendor"]);

echo '<a href="dealers.php"><h1>Dealers</h1></a>';

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

# determine how its sorted

if ($sort) {
	$sql .= " ORDER BY `$sort` asc";
} else {
	$sql .= " ORDER BY `name` asc";
	$sort = "name";
}

$result = mysql_query($sql);
if (!$result) {
	die('Invalid query: '.mysql_error());
}

$count = mysql_num_rows($result);

# get the offset
if ($offset) {
	$sql .= " LIMIT 20 OFFSET $offset";
} else {
	$sql .= " LIMIT 20";
	$offset = 0;
}

# break hide into an array
if ($hide) {
	$hide = explode("+", $hide);
} else {
	$hide = array('addr1','addr2','addr3','fax');
}

$result = mysql_query($sql);
if (!$result) {
	die('Invalid query: '.mysql_error());
}

# print pagination

echo "$count entries<br>";

# keep counting until we're up to the last few
$curr = 1;
$offs = 0;
$cnt2 = $count;
while ($cnt2 > 0) {
	# if current page
	if ($curr*20 == $offset+20) {
		echo "$curr ";
	} else {
		?><a href="dealers.php?sort=<?php echo $sort; ?>&offset=<?php echo $offs; ?>&hide=<?php echo $_GET["hide"]; ?><?php
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
echo "<br>";

# print results
?>
<br>
<a href="dealers.php?sort=<?php echo $sort; ?>&offset=<?php echo $offset ?>&hide=none<?php
if ($s_dealer) echo "&dealer=$s_dealer";
if ($s_city) echo "&city=$s_city";
if ($s_state) echo "&state=$s_state";
if ($s_zip) echo "&zip=$s_zip";
#if ($s_vendor) echo "&vendor=$s_vendor";
?>">Show All Fields</a>
<br>
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
<?php
		if (!in_array('name',    $hide)) echo "<td>Dealer</td>";
		if (!in_array('phone',   $hide)) echo "<td>Phone</td>";
		if (!in_array('fax',     $hide)) echo "<td>Fax</td>";
		if (!in_array('website', $hide)) echo "<td>Website</td>";
		if (!in_array('email',   $hide)) echo "<td>Email</td>";
		if (!in_array('addr1',   $hide)) echo "<td>Address1</td>";
		if (!in_array('addr2',   $hide)) echo "<td>Address2</td>";
		if (!in_array('addr3',   $hide)) echo "<td>Address3</td>";
		if (!in_array('city',    $hide)) echo "<td>City</td>";
		if (!in_array('state',   $hide)) echo "<td>State</td>";
		if (!in_array('zip',     $hide)) echo "<td>Zip</td>";
		if (!in_array('vendor',  $hide)) echo "<td>Vendor</td>";
?>
	</tr>
<?php

while ($row = mysql_fetch_assoc($result)) {
	echo '<tr>';
	foreach($row as $field => $value) {
		if ($field == 'id' || in_array($field, $hide)) continue;
		echo "<td>$value</td>";
	}
	echo '</tr>';
}

echo '</table>';

# print pagination

# keep counting until we're up to the last few
$curr = 1;
$offs = 0;
$cnt2 = $count;
while ($cnt2 > 0) {
	# if current page
	if ($curr*20 == $offset+20) {
		echo "$curr ";
	} else {
	?><a href="dealers.php?sort=<?php echo $sort; ?>&offset=<?php echo $offs; ?>&hide=<?php echo $_GET["hide"]; ?><?php
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
echo "<br>";

mysql_close($mysql);
echo '\o/';
?>
