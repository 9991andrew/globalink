<?php
/**
	This script imports all existing MEGA World v2 data into the new database. If there is already data in the v3 database,
	running this will completely erase all data.

	*****************************************************
	 To run this script enter the upgrade password below
    *****************************************************
*/
const UPGRADE_PASSWORD = 'deleteMEGAWorldv3';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database configuration info is in this file.
include_once '../classes/config.class.php';


?>
<html>

<head>

<title>MEGA World Database Upgrade</title>

<style>

body {
	font-family: sans-serif;
}

table {
	margin-top: 20px;
}

table td, table th {
	border: 1px solid black;
}

table h3 {
	text-align: center;
	vertical-align: middle;
	margin:5px;
}

.status {
	color:green;
}

.deleted {
	color: red;
	display:block;
}

.inserted {
	color: green;
	display:block;
}

.warn {
	border: 2px solid red;
	padding: 10px;

}

.warn strong {
	font-size:22px;
}


@media (prefers-color-scheme: dark) {
	body {
		background-color: #111;
		color: lightgray;
	}


	table td, table th {
		border-color:white;
	}
}

/* Dark Mode for Apps. Automatically is used when client OS is set to dark mode */

:root {
	/* This tells Safari that it can use Dark mode defaults */
	color-scheme: light dark;
}


</style>

</head>

<body>

<?php

if (!isset($_POST['confirmation']) OR $_POST['confirmation']!=UPGRADE_PASSWORD) {

?>

<div class="warn">
	<p>This script is used to upgrade a legacy MEGA World v2 installation database (on PostgreSQL) to the new v3 format (on MySQL/MariaDB).</p>

	<p><strong>Running this script will permanently delete all data for MEGA World v3 and replace it with old data!</strong></p>

	<p>If you are sure you want to do this, enter the correct upgrade password to continue.</p>
	<form method="post">
		<label for="confirmation">Password: <input type="password" id="confirmation" name="confirmation" autofocus /></label> <input type="submit" />
	</form>


</div>

<?php

exit('No valid upgrade password was entered. Exiting.');


}

// Because this script is very dangerous and there's little need for it now, I've disabled it here.
exit('Uncomment this line to enable the database upgrade script.');

// Add some kind of a form requiring password input before this form will be run.
// More to prevent accidentally blowing away the DB than real security

// Must allow more memory for this to run with larger databases. Adjust this as necessary for the environment.
ini_set("memory_limit", "256M");

// Show all records that get inserted if debug == true;
$debug = false;


/* This script will convert MEGA World v2 databases to MEGA World v2 on MySQL.

The database format is substantially different, so this script will import the old data into the new format.

Hold on tight, this will be a long bumpy ride.

*/

// Connect to the original megaworld DB. It's likely the port number is actually 6432, rather than the default 5432.
function connect2() {
	$host = "localhost";
	$port = "5432";
	$dbname = "mega";
	$user = "mega";
	$password = "ENTER_OLD_PGSQL_PASSWORD_HERE";

	$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

	try {
        $pdo = new PDO($dsn, $user, $password);
        $pdo->setAttribute(PDO::FETCH_ASSOC, PDO::ATTR_DEFAULT_FETCH_MODE);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	    return $pdo;
    } catch (PDOException $e) {
	    echo $dsn;
	    echo ' Connection failed: '.$e->getMessage();
	    exit;
    }

}


// Connect to the new megaworld v3 DB on MySQL.
function connect3() {
    $dbconfig = parse_ini_file('../'.Config::DATABASE_CONFIG_FILE??'../../.megadb.ini');
	// Using "localhost" doesn't seem to work here.
	$host = $dbconfig['DB_HOST'];
	$port = $dbconfig['DB_PORT'];
	$dbname = $dbconfig['DB_NAME'];
	$user = $dbconfig['DB_NAME'];
	$password = $dbconfig['DB_PASSWORD'];

	$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

	try {
        $pdo = new PDO($dsn, $user, $password);
        $pdo->setAttribute(PDO::FETCH_ASSOC, PDO::ATTR_DEFAULT_FETCH_MODE);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	    return $pdo;
    } catch (PDOException $e) {
        echo $dsn;
	    echo ' Connection failed: '.$e->getMessage();
	    exit;
    }
}

/**
 * Accepts a connection, table name and a (likely very long) SQL query and attempts to run it, handling any errors as necessary.
 * requires a $conn3 that is a connection to the new MySQL database.
 */
function resetTable($pdo, $newTable) {
	// Make sure new table is empty. Not sure if resetting auto_increment will be problematic
	$stmt = $pdo->prepare("DELETE FROM $newTable;");
	$stmt->execute();
	echo '<span class="deleted">'.$stmt->rowCount()." rows deleted from $newTable.</span>";

	// reset AUTO_INCREMENT to 1 now that the table is empty. ALTER permissions are needed
	$stmt = $pdo->prepare("ALTER TABLE $newTable AUTO_INCREMENT = 1;");
	$stmt->execute();
}

// As above but without resetting the table first. Used when it makes sense to insert one by one
// like for very problematic tables
function insert($pdo, $newTable, $sql) {
	$totalRows = 0;
	// Transaction based mode
	$pdo->beginTransaction();
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	do {
		$rowCount = $stmt->rowCount();
		if ($rowCount > 2) echo '<span class="inserted">'.$stmt->rowCount()." rows affected in $newTable.</span>";
		else if ($rowCount == 1) $totalRows++;

	} while ($stmt->nextRowset());
	$pdo->commit();	

	if ($totalRows > 0) echo '<span class="inserted">'.$totalRows." rows affected in $newTable.</span>";
}

function delAndInsert($pdo, $newTable, $sql) {
	resetTable($pdo, $newTable);
	insert($pdo, $newTable, $sql);
}


// Connect to Old Postgre Database
$conn2 = connect2();

// Connect to new MySQL DB.
$conn3 = connect3();


// Do some cleanup to prevent FK constraint violations
// resetTable($conn3, 'protect_detail');
resetTable($conn3, 'npc_items');
resetTable($conn3, 'player_quest_variables');
resetTable($conn3, 'player_quests');
resetTable($conn3, 'quest_variables');
resetTable($conn3, 'quest_answers');
resetTable($conn3, 'quest_tool_locations');
resetTable($conn3, 'quest_tools');
resetTable($conn3, 'quest_reward_items');
resetTable($conn3, 'quest_items');
resetTable($conn3, 'quest_prerequisite_quests');
resetTable($conn3, 'quest_prerequisite_professions');
resetTable($conn3, 'quests');
resetTable($conn3, 'player_items');
//resetTable($conn3, 'item_stats');
resetTable($conn3, 'items');
resetTable($conn3, 'item_icons');
resetTable($conn3, 'npc_portals');
resetTable($conn3, 'npc_professions');
resetTable($conn3, 'npcs');
resetTable($conn3, 'npc_icons');
resetTable($conn3, 'player_professions');
resetTable($conn3, 'player_skills');
resetTable($conn3, 'player_stats');
resetTable($conn3, 'player_base_attributes');
resetTable($conn3, 'players');
resetTable($conn3, 'birthplaces');
resetTable($conn3, 'map_tiles');
resetTable($conn3, 'profession_prerequisites');
resetTable($conn3, 'buildings');
resetTable($conn3, 'stat_calculation_rules');
resetTable($conn3, 'race_attributes');
resetTable($conn3, 'maps');


$tableName = 'users';
$rows2 = $conn2->query("SELECT * FROM $tableName WHERE userid IN (SELECT userid FROM users WHERE lower(username) IN (SELECT lower(username) as username FROM users GROUP BY lower(username) HAVING COUNT(*) = 1))")->fetchAll();

echo '<table>';
echo '<tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "SET sql_mode='NO_AUTO_VALUE_ON_ZERO'; ";
$sql .= "INSERT INTO users (id, username, password) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	// Construct insert statement for record into new MySQL DB.
	$sql .= "(".$row['userid'].", ";
	$sql .= $conn3->quote($row['username']);
	$sql .= ", ";
	$sql .= $conn3->quote($row['passwordhash']);
	$sql .= ")";
	
	if ($debug == true) {
		echo '<tr>
			<td class="status">&check;</td>
			<td>'.$row["userid"].'</td>
			<td>'.$row["username"].'</td>
			<td>'.$row['passwordhash'].'</td>
			<td>'.$row['isadmin'].'</td>
		</tr>';
	}

}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

delAndInsert($conn3, $tableName, $sql);



$tableName = 'management_users';
$rows2 = $conn2->query("SELECT * FROM managementusers")->fetchAll();

echo '<table>';
echo '<tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "SET sql_mode='NO_AUTO_VALUE_ON_ZERO'; ";
$sql .= "INSERT INTO $tableName (name, time_zone, password_old_sha1) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	// Construct insert statement for record into new MySQL DB.
	$sql .= "(";
	$sql .= $conn3->quote($row['userid']);
	$sql .= ", ";
    $sql .= $conn3->quote('America/Edmonton');
    $sql .= ", ";
	$sql .= $conn3->quote($row['passwordhash']);
	$sql .= ")";
	
	if ($debug == true) {
		echo '<tr>
			<td class="status">&check;</td>
			<td>'.$row["username"].'</td>
			<td>'.$row['passwordhash'].'</td>
		</tr>';
	}

}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

delAndInsert($conn3, $tableName, $sql);



// Icons are now split into item_icons and npc_icons
$tableName = 'item_icons';
$rows2 = $conn2->query("SELECT * FROM icons WHERE icontype='item'")->fetchAll();

echo '<table>';
echo '<tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "SET sql_mode='NO_AUTO_VALUE_ON_ZERO'; ";
$sql .= "INSERT INTO $tableName (id, name, author) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	// Construct insert statement for record into new MySQL DB.
	$sql .= "(".$row['iconid'];
	$sql .= ", ".$conn3->quote($row['iconname']);
	$sql .= ", ".$conn3->quote($row['iconauthor']);
	$sql .= ")";
	
	if ($debug == true) {
		echo '<tr>
			<td class="status">&check;</td>
			<td>'.$row["iconid"].'</td>
			<td>'.$row["iconname"].'</td>
			<td>'.$row['iconauthor'].'</td>
		</tr>';
	}

}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

delAndInsert($conn3, $tableName, $sql);



$tableName = 'npc_icons';
$rows2 = $conn2->query("SELECT * FROM icons WHERE icontype='npc'")->fetchAll();

echo '<table>';
echo '<tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "SET sql_mode='NO_AUTO_VALUE_ON_ZERO'; ";
$sql .= "INSERT INTO $tableName (id, name, author) VALUES";
foreach($rows2 as $row) {
    if ($ct++ > 0) $sql .= ", ";
    // Construct insert statement for record into new MySQL DB.
    $sql .= "(".$row['iconid'];
    $sql .= ", ".$conn3->quote($row['iconname']);
    $sql .= ", ".$conn3->quote($row['iconauthor']);
    $sql .= ")";

    if ($debug == true) {
        echo '<tr>
			<td class="status">&check;</td>
			<td>'.$row["iconid"].'</td>
			<td>'.$row["iconname"].'</td>
			<td>'.$row['iconauthor'].'</td>
		</tr>';
    }

}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

delAndInsert($conn3, $tableName, $sql);


// Add some new NPC icons. These are supposed to be ID 383-412.
// This may fail if new icons have been added since I got the origial mega database
$sql="INSERT INTO npc_icons (id, name, author) VALUES
(500, 'Old caucasian man with beard', 'YusufArtun'),
(501, 'Young caucasian woman with short black hair', 'YusufArtun'),
(502, 'Red haired woman', 'YusufArtun'),
(503, 'Blonde elf woman', 'YusufArtun'),
(504, 'Young Asian woman with green hair', 'YusufArtun'),
(505, 'Blonde woman with scar over eye', 'YusufArtun'),
(506, 'Woman with purple hood and veil', 'YusufArtun'),
(507, 'Blonde woman with big coat', 'YusufArtun'),
(508, 'Black-haired elf noblewoman', 'YusufArtun'),
(509, 'Black knight woman with green eyes', 'YusufArtun'),
(510, 'Auburn woman with red hood', 'YusufArtun'),
(511, 'Asian woman in armour', 'YusufArtun'),
(512, 'Blue-eyed woman in helmet', 'YusufArtun'),
(513, 'Dark-haired man with goatee', 'YusufArtun'),
(514, 'Long-bearded man with tousled hair and armour', 'YusufArtun'),
(515, 'Bald man with goatee in leather armour', 'YusufArtun'),
(516, 'Full-face armour suit with plume', 'YusufArtun'),
(517, 'Flat-top full-faced helmet', 'YusufArtun'),
(518, 'Old man with goatee in green hood', 'YusufArtun'),
(519, 'Blond elf man in armour', 'YusufArtun'),
(520, 'Black man with dreadlocks and painted face', 'YusufArtun'),
(521, 'Man in black and gold hood', 'YusufArtun'),
(522, 'Man with gray beard in helmet', 'YusufArtun'),
(523, 'Man with braided gray beard and eyepatch', 'YusufArtun'),
(524, 'Man with turban', 'YusufArtun'),
(525, 'Man brown beard in leather helmet', 'YusufArtun'),
(526, 'Blond, bearded man in armour', 'YusufArtun'),
(527, 'Man clean-shaven in green coat', 'YusufArtun'),
(528, 'Man in burgundy hood', 'YusufArtun'),
(529, 'Man with goatee in blue tunic', 'YusufArtun'),
(530, 'Young blonde girl in red scarf', 'JD Lien'),
(531, 'Young man in purple tunic', 'JD Lien'),
(532, 'Blond man in green tunic', 'JD Lien'),
(533, 'Old Asian man in blue and white shirt.', 'JD Lien');";
$stmt = $conn3->prepare($sql); $stmt->execute();

$sql = "INSERT INTO item_icons (id, name, author) VALUES
(1000, 'Purple Orb', 'a-ravlik'),
(1001, 'Root', 'a-ravlik'),
(1002, 'Leaves', 'a-ravlik'),
(1003, 'Green Artefact', 'a-ravlik'),
(1004, 'Purple Roll', 'a-ravlik'),
(1005, 'Wooden Chest', 'a-ravlik'),
(1006, 'Sharp Tools', 'a-ravlik'),
(1007, 'Pentagram Book', 'a-ravlik'),
(1008, 'Purple Bookmark', 'a-ravlik'),
(1009, 'Egg', 'a-ravlik'),
(1010, 'Green Berries', 'a-ravlik'),
(1011, 'Vase', 'a-ravlik'),
(1012, 'Green Amulet', 'a-ravlik'),
(1013, 'Red Orb', 'a-ravlik'),
(1014, 'Red Thread', 'a-ravlik'),
(1015, 'Horn', 'a-ravlik'),
(1016, 'Spear', 'a-ravlik'),
(1017, 'Green Slab', 'a-ravlik'),
(1018, 'Broken Tablet', 'a-ravlik'),
(1019, 'Pink Claw', 'a-ravlik'),
(1020, 'Ruby Armband', 'a-ravlik'),
(1021, 'Sapphire', 'a-ravlik'),
(1022, 'Assorted Tools', 'a-ravlik'),
(1023, 'Corked Flask', 'a-ravlik'),
(1024, 'Chain with Hook', 'a-ravlik'),
(1025, 'Roll of Cloth', 'a-ravlik'),
(1026, 'Dragon Claws', 'a-ravlik'),
(1027, 'Gold Bar', 'a-ravlik'),
(1028, 'Herbs', 'a-ravlik'),
(1029, 'Fish Bone', 'a-ravlik'),
(1030, 'Dragon Wing Ornament', 'a-ravlik'),
(1031, 'Leather Satchel', 'a-ravlik'),
(1032, 'Horn', 'a-ravlik'),
(1033, 'Purple Minerals', 'a-ravlik'),
(1034, 'Green Orb', 'a-ravlik'),
(1035, 'Old Scroll', 'a-ravlik'),
(1036, 'Decorative Scroll', 'a-ravlik'),
(1037, 'Sealed Scroll', 'a-ravlik'),
(1038, 'Orc Skull', 'a-ravlik'),
(1039, 'Blue Ornament', 'a-ravlik'),
(1040, 'Green Book with Star', 'a-ravlik'),
(1041, 'Green & Leather Book', 'a-ravlik'),
(1042, 'Rocks', 'a-ravlik'),
(1043, 'Leather Book with Jewel', 'a-ravlik'),
(1044, 'Purple Portfolio', 'a-ravlik'),
(1045, 'Rune with Eye Symbol', 'a-ravlik'),
(1046, 'Purple Gem 1', 'a-ravlik'),
(1047, 'Gift Box Hexagonal', 'a-ravlik'),
(1048, 'Red Droplet	', 'a-ravlik'),
(1049, 'Dragon Teeth', 'a-ravlik'),
(1050, 'Purple Gem 2', 'a-ravlik'),
(1051, 'Blue Gift Box with Stars', 'a-ravlik'),
(1052, 'Bird Tablet', 'a-ravlik'),
(1053, 'Green Droplet', 'a-ravlik'),
(1054, 'Blue A Token', 'a-ravlik'),
(1055, 'Bones', 'a-ravlik'),
(1056, 'Horn with Strings', 'a-ravlik'),
(1057, 'Gift Box Turquoise Round', 'a-ravlik'),
(1058, 'Rock Angular', 'a-ravlik'),
(1059, 'Skull Green Jewel Necklace', 'a-ravlik'),
(1060, 'Iron Bars', 'a-ravlik'),
(1061, 'Gift Box Red Green Bow', 'a-ravlik'),
(1062, 'Rock Rounded', 'a-ravlik'),
(1063, 'Gift Box Pink with Flowers', 'a-ravlik'),
(1064, 'Gold Medalion', 'a-ravlik'),
(1065, 'Fabric Gold & Purple', 'a-ravlik'),
(1066, 'Teeth Necklace', 'a-ravlik'),
(1067, 'Token', 'a-ravlik'),
(1068, 'Symbol Book ', 'a-ravlik'),
(1069, 'Spine', 'a-ravlik'),
(1070, 'Green Gem', 'a-ravlik'),
(1071, 'Gift Box Green with Red Bow', 'a-ravlik'),
(1072, 'Sharp Rock', 'a-ravlik'),
(1073, 'Open Book', 'a-ravlik'),
(1074, 'Blue Rocks', 'a-ravlik'),
(1075, 'Gold Ore', 'a-ravlik'),
(1076, 'Sealed Letter', 'a-ravlik'),
(1077, 'Red Rocks', 'a-ravlik'),
(1078, 'Fish Catfish', 'a-ravlik'),
(1079, 'Fish 2', 'a-ravlik'),
(1080, 'Fish 3', 'a-ravlik'),
(1081, 'Fish Perch', 'a-ravlik'),
(1082, 'Fish 5', 'a-ravlik'),
(1083, 'Fish 6', 'a-ravlik'),
(1084, 'Fish 7', 'a-ravlik'),
(1085, 'Fish 8', 'a-ravlik'),
(1086, 'Fish 9', 'a-ravlik'),
(1087, 'Fish Pike', 'a-ravlik'),
(1088, 'Fish 10', 'a-ravlik'),
(1089, 'Fish 11', 'a-ravlik'),
(1090, 'Meat Ham', 'a-ravlik'),
(1091, 'Vegetables', 'a-ravlik'),
(1092, 'Meat Steak', 'a-ravlik'),
(1093, 'Fish 12', 'a-ravlik'),
(1094, 'Wooden Hatch', 'a-ravlik'),
(1095, 'Metal Hammer', 'a-ravlik'),
(1096, 'Pick Bone', 'a-ravlik'),
(1097, 'Pick Wood', 'a-ravlik'),
(1098, 'Wooden Hammer', 'a-ravlik'),
(1099, 'Metal Pick', 'a-ravlik'),
(1100, 'Lether Boots', 'Horski'),
(1101, 'Leather Boots Buckle', 'Horski'),
(1102, 'Armour Boots', 'Horski'),
(1103, 'Silver Armour Boots', 'Horski'),
(1104, 'Gold Armour Boots', 'Horski'),
(1105, 'Red Claw Boots', 'Horski'),
(1106, 'Turqoise Claw Boots', 'Horski'),
(1107, 'Blue Boots Spikes', 'Horski'),
(1108, 'Red Boots', 'Horski'),
(1109, 'Green Boots', 'Horski'),
(1110, 'Tall Green Boots', 'Horski'),
(1111, 'Leather Shoes', 'Horski'),
(1112, 'Red Armour Boots', 'Horski'),
(1113, 'Purple Armour Boots', 'Horski'),
(1114, 'Pointy Armoured Boots', 'Horski'),
(1115, 'Leather Gauntlet', 'Horski'),
(1116, 'Metal Bracer', 'Horski'),
(1117, 'Metal Gauntlet', 'Horski'),
(1118, 'Gold Bracer', 'Horski'),
(1119, 'Metal Bracer', 'Horski'),
(1120, 'Pointy Bracer', 'Horski'),
(1121, 'Tooth Bracer', 'Horski'),
(1122, 'Green Bracer', 'Horski'),
(1123, 'Red Bracer', 'Horski'),
(1124, 'Green Bracer', 'Horski'),
(1125, 'Purple Gauntlet', 'Horski'),
(1126, 'Red Gauntlet', 'Horski'),
(1127, 'Crimson Bracer', 'Horski'),
(1128, 'Red Decorative Bracer', 'Horski'),
(1129, 'Leather Vest', 'Horski'),
(1130, 'Green Chest Armour', 'Horski'),
(1131, 'Chest Plate Armor', 'Horski'),
(1132, 'Body Armour', 'Horski'),
(1133, 'Gold Body Armour', 'Horski'),
(1134, 'Red Body Armour', 'Horski'),
(1135, 'Blue Body Armor', 'Horski'),
(1136, 'Purple Robe', 'Horski'),
(1137, 'Red Robe', 'Horski'),
(1138, 'Spiked Blue Armor', 'Horski'),
(1139, 'Spiked Red Armor', 'Horski'),
(1140, 'Purple Robe 2', 'Horski'),
(1141, 'Red Robe 3', 'Horski'),
(1142, 'Leather Body Armour', 'Horski'),
(1143, 'Plated Mail Armour', 'Horski'),
(1144, 'Blue Ornamental Armour', 'Horski'),
(1145, 'Gold Body Armour', 'Horski'),
(1146, 'Green Body Armour', 'Horski'),
(1147, 'Gold Wing Helmet', 'Horski'),
(1148, 'Red Winged Helmet', 'Horski'),
(1149, 'Blue Helmet', 'Horski'),
(1150, 'Metal Nasal Helmet', 'Horski'),
(1151, 'Red Helmet', 'Horski'),
(1152, 'Turquoise Ornamental Helmet', 'Horski'),
(1153, 'Open Helmet', 'Horski'),
(1154, 'Blue Helmet with Plume', 'Horski'),
(1155, 'Red Helmet with Plume', 'Horski'),
(1156, 'Horned Nasal Helmet', 'Horski'),
(1157, 'Green Hood', 'Horski'),
(1158, 'Purple Hood', 'Horski'),
(1159, 'Blue Ornamental Helmet', 'Horski'),
(1160, 'Red Pointy Hat', 'Horski'),
(1161, 'Green Pointy Hat', 'Horski'),
(1162, 'Blue Sword', 'Horski'),
(1163, 'Bronze Sword', 'Horski'),
(1164, 'Steel Sword', 'Horski'),
(1165, 'Green Sword', 'Horski'),
(1166, 'Heavy Sword', 'Horski'),
(1167, 'Large Sword Blue Stripe', 'Horski'),
(1168, 'Large Red Sword ', 'Horski'),
(1169, 'Purple Sword', 'Horski'),
(1170, 'Golden Sword', 'Horski'),
(1171, 'Evil Dagger', 'Horski'),
(1172, 'Barbed Dagger', 'Horski'),
(1173, 'Golden Dagger', 'Horski'),
(1174, 'Steel Dagger', 'Horski'),
(1175, 'Blue Dagger', 'Horski'),
(1176, 'Green Ceremonial Knife', 'Horski'),
(1177, 'Pink Wand', 'Horski'),
(1178, 'Plant Wand', 'Horski'),
(1179, 'Green Tree Wand', 'Horski'),
(1180, 'Blue Wand', 'Horski'),
(1181, 'Fire Wand', 'Horski'),
(1182, 'Red Orb Wand', 'Horski'),
(1183, 'Ram Skull Wand', 'Horski'),
(1184, 'Green Wand', 'Horski'),
(1185, 'Green Bow', 'Horski'),
(1186, 'Blue Bow', 'Horski'),
(1187, 'Red Bow', 'Horski'),
(1188, 'Simple Bow', 'Horski'),
(1189, 'Dragon Eye Ring', 'Horski'),
(1190, 'Gold Ornamental Ring', 'Horski'),
(1191, 'Blue Eye Ring', 'Horski'),
(1192, 'Blue Gem Ring', 'Horski'),
(1193, 'Large Ring', 'Horski'),
(1194, 'Bronze Ring', 'Horski'),
(1195, 'Iron Ring', 'Horski'),
(1196, 'Ruby Iron Ring', 'Horski'),
(1197, 'Purple Gem Ring', 'Horski'),
(1198, 'Large Purple Gem Ring', 'Horski'),
(1199, 'Large Ruby Ring', 'Horski'),
(1200, 'Blue Ring', 'Horski'),
(1201, 'Pearl Ring', 'Horski'),
(1202, 'Flower Ring', 'Horski'),
(1203, 'Heart Ring', 'Horski'),
(1204, 'Fire Ring', 'Horski'),
(1205, 'Pink Gem Ring', 'Horski'),
(1206, 'Green Gem Amulet', 'Horski'),
(1207, 'Blue Teeth Amulet', 'Horski'),
(1208, 'Blue Cross Amulet', 'Horski'),
(1209, 'Devil Skull Amulet', 'Horski'),
(1210, 'Green Drop Amulet', 'Horski'),
(1211, 'Pink Gold Amulet', 'Horski'),
(1212, 'Red Drop Amulet', 'Horski'),
(1213, 'Green Pearls Amulet', 'Horski'),
(1214, 'Red Gem Amulet', 'Horski'),
(1215, 'Bread Loaf French', 'JD Lien')";

$stmt = $conn3->prepare($sql); $stmt->execute();


$mapTypes = [];
// The list of map types may change for each installation of megaworld. This is important because
// Each map type has a couple of associated databases.
$tableName = 'map_types';
$rows2 = $conn2->query("SELECT * FROM map_type")->fetchAll();

echo '<table>';
echo '<tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "SET sql_mode='NO_AUTO_VALUE_ON_ZERO'; ";
$sql .= "INSERT INTO $tableName (name, description) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	// Construct insert statement for record into new MySQL DB.
	$sql .= "(";
	$sql .= $conn3->quote($row['map_type_name']);
	$sql .= ", ";
	$sql .= $conn3->quote($row['map_description']);
	$sql .= ")";
	
	if ($debug == true) {
		echo '<tr>
			<td class="status">&check;</td>
			<td>'.$row["map_type_name"].'</td>
			<td>'.$row['map_description'].'</td>
		</tr>';
	}

	// Add the map_type_name into the array of $mapTypes that we use often later
	array_push($mapTypes, $row['map_type_name']);

}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

delAndInsert($conn3, $tableName, $sql);




/**
 * Maps conversion
 * This one will be tricky because lots of things refer to it and I'll have to change the IDs so there's no key conflicts.
 * I'll temporarily store the original ID in the table.
 *
 * Note that map_types should already be populated: 1=village, 2=Gludio, 3=city
 */

$tableName = 'maps';

$sql2 = "";
// Loop through each type of maps and attempt selecting from all the tables
$typeCount=0;
foreach($mapTypes as $type) {
	if ($typeCount++ > 0) $sql2 .= " UNION ";
	$sql2 .= "SELECT $typeCount AS map_type_id, ".$type."id AS old_map_id, ".$type."name as map_name FROM ".$type."s ";
}


$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table>';
echo '<tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "SET sql_mode='NO_AUTO_VALUE_ON_ZERO'; ";
$sql .= "INSERT INTO maps (map_type_id, name, old_map_id) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	// Construct insert statement for record into new MySQL DB.
	$sql .= "(".$row['map_type_id'];
	$sql .= ", ";
	$sql .= $conn3->quote($row['map_name']);
	$sql .= ", ";
	$sql .= $row['old_map_id'];
	$sql .= ")";
	
	if ($debug == true) {
		echo '<tr>
			<td class="status">&check;</td>
			<td>'.$row["map_type_id"].'</td>
			<td>'.$row["old_map_id"].'</td>
			<td>'.$row['map_name'].'</td>
		</tr>';
	}

}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

delAndInsert($conn3, $tableName, $sql);



/**
 * map_tile_types
 */
$tableName = 'map_tile_types';
$sql2 = "SELECT * FROM maptiletypes ty
LEFT OUTER JOIN maptilegraphics g ON g.maptilegraphicid=ty.maptiletypeid;";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "SET sql_mode='NO_AUTO_VALUE_ON_ZERO'; ";
$sql .= "INSERT INTO $tableName (id, name, movement_req, skill_id_req, author) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	// Construct insert statement for record into new MySQL DB.
	$sql .= "(".$row['maptiletypeid'];
	$sql .= ", ";
	$sql .= $conn3->quote($row['maptiletypename']);
	$sql .= ", ";
	$sql .= $conn3->quote($row['movementreq']);
	$sql .= ", ";
    $sql .= ($row['skillidreq']==0)?'NULL':$row['skillidreq'];
	$sql .= ", ";
	$sql .= $conn3->quote($row['iconauthor']);
	$sql .= ")";
	
}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

delAndInsert($conn3, $tableName, $sql);


// Upgrade map_tiles to high-resolution icons created by JD Lien and homogenize the naming
$sql="";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Plains' WHERE id=1;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Village' WHERE id=2;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='City Street' WHERE id=3;";
$sql.="UPDATE map_tile_types SET map_tile_type_id_image=2, author='JD Lien', name='Village' WHERE id=4;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Armour Store' WHERE id=5;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Bulletin Board' WHERE id=6;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Plaza' WHERE id=7;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Camp' WHERE id=8;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Town Centre' WHERE id=9;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Grocery' WHERE id=10;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Harbour' WHERE id=11;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='House' WHERE id=12;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Lake' WHERE id=13;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Library' WHERE id=14;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Meeting' WHERE id=15;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Mountains' WHERE id=16;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Ocean' WHERE id=17;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Doorway' WHERE id=18;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Restaurant' WHERE id=19;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='River' WHERE id=20;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='School' WHERE id=21;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Weapon Store' WHERE id=22;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Workshop' WHERE id=23;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Harbour' WHERE id=24;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Harbour' WHERE id=25;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Lake' WHERE id=26;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Lake' WHERE id=27;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Lake' WHERE id=28;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Path' WHERE id=29;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Path' WHERE id=30;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Path Corner' WHERE id=31;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Path Corner' WHERE id=32;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Path Corner' WHERE id=33;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Path Corner' WHERE id=34;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Crossroads' WHERE id=35;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='River' WHERE id=36;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='River' WHERE id=37;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Riverbend' WHERE id=38;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='River End' WHERE id=39;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='River End' WHERE id=40;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='River End' WHERE id=41;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='River End' WHERE id=42;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Riverbend' WHERE id=43;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Riverbend' WHERE id=44;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Riverbend' WHERE id=45;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='City Street' WHERE id=46;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Village Houses' WHERE id=48;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Construction Site' WHERE id=49;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Brick House' WHERE id=50;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Cottage' WHERE id=51;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Castle' WHERE id=52;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Castle Wall' WHERE id=53;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Castle Wall' WHERE id=54;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Castle Wall' WHERE id=55;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Castle Wall' WHERE id=56;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Castle Wall' WHERE id=57;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Castle Wall' WHERE id=58;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Castle Gate' WHERE id=59;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Castle Gate' WHERE id=60;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Castle Tower' WHERE id=61;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Castle Tower' WHERE id=62;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Dungeon' WHERE id=63;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Dungeon' WHERE id=64;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Garden' WHERE id=66;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Garden' WHERE id=67;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Garden' WHERE id=68;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Garden' WHERE id=69;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='House' WHERE id=70;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='House' WHERE id=71;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Stable' WHERE id=72;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Stable' WHERE id=73;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Stone Pillar' WHERE id=74;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Stone Pillar' WHERE id=75;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Stone Pillar' WHERE id=76;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Stone Pillar' WHERE id=77;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Stone Wall' WHERE id=78;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Stone Wall' WHERE id=79;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Stone Wall' WHERE id=80;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Stone Wall' WHERE id=81;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Fountain' WHERE id=82;";
$sql.="UPDATE map_tile_types SET author='JD Lien', name='Ruins' WHERE id=83;";
insert($conn3, 'map_tile_types', $sql);



// I'll create an associative array mapping mapTypeNames to the new mapIDs
$mapTypeIds = $conn3->query("SELECT name, id FROM map_types;")->fetchAll(PDO::FETCH_KEY_PAIR);

// Create a multidimensional associative array that map the old map ids to new ones.
// Need to start them with non-numeric so I can refer to them without cast to int

// 0 is an empty array so that the index matches the mapid.
$mapIds = array([]);

$typeCount = 0;
foreach($mapTypes as $type) {
	$typeCount++;
	array_push( $mapIds, $conn3->query("SELECT CONCAT('id',old_map_id) AS old_id, id FROM maps WHERE map_type_id=$typeCount")->fetchAll(PDO::FETCH_KEY_PAIR) );
}

echo "<pre><code>";
var_export($mapTypeIds);
echo "</code></pre>";



/**
 * map_tiles
 * This query will have to be performed for each type of map.
 *
 * I get the new map id from the associative array for each type of map defined above.
*/

$tableName = 'map_tiles';
$typeCount = 0;
resetTable($conn3, $tableName);
foreach($mapTypes as $type) {
	$typeCount++;

	$sql2 = "SELECT DISTINCT ".$type."id AS old_map_id, x, y, COALESCE(maptiletypeid,1) AS maptiletypeid, COALESCE(maptilegraphicid,1) AS maptilegraphicid FROM ".$type."_map";
	$rows2 = $conn2->query($sql2)->fetchAll();

	echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
	$ct = 0;
	// We have unique constraint violations, can we just select that aren't duplicate?
	// It seems that the duplicates are *exact* duplicates of the whole row.
	// We can solve this with DISTINCT
	// It also seems that in addition to duplicates, we are referencing stuff that no longer exists!
	$sql = "INSERT INTO $tableName (map_id, x, y, map_tile_type_id) VALUES";
	foreach($rows2 as $row) {
		if ($ct++ > 0) $sql .= ", ";
		// Construct insert statement for record into new MySQL DB.
		$oldMapID = 'id'.$row['old_map_id'];
		$newMapID = $mapIds[$typeCount]["$oldMapID"];

		$sql .= "(".$newMapID;
		$sql .= ", ";
		$sql .= $conn3->quote($row['x']);
		$sql .= ", ";
		$sql .= $conn3->quote($row['y']);
		$sql .= ", ";
		$sql .= strlen($row['maptiletypeid'])?$conn3->quote($row['maptiletypeid']):'NULL';
		//$sql .= ", ";
		//$sql .= strlen($row['maptilegraphicid'])?$conn3->quote($row['maptilegraphicid']):'NULL';
		$sql .= ")";
		// $debug=true;
		if ($debug == true) {
			echo '<tr>
				<td class="status">&check;</td>
				<td>'.$row['old_map_id'].'('.$newMapID.') </td>
				<td>'.$row['x'].'</td>
				<td>'.$row['y'].'</td>
				<td>'.$row['maptiletypeid'].'</td>
			</tr>';
		}
		// echo $sql;
	}
	echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
	echo '</table>';


	$sql.=";"; // End SQL statement
	// insert all kinds of map tiles into the one table
	insert($conn3, $tableName, $sql);

}




/**
 * professions
 */
$tableName = 'professions';
$sql2 = "SELECT * FROM profession";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "SET sql_mode='NO_AUTO_VALUE_ON_ZERO'; ";
$sql .= "INSERT INTO $tableName (id, name, require_all_prerequisites) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	// Construct insert statement for record into new MySQL DB.
	$sql .= "(".$row['profession_id'];
	$sql .= ", ";
	$sql .= $conn3->quote($row['profession_name']);
	// Moved this column into the prerequisites table
	//$sql .= ", ";
	//$sql .= $conn3->quote($row['profession_xp']);
	$sql .= ", ";
	$sql .= "0";
	$sql .= ")";
	
	if ($debug == true) {
		echo '<tr>
			<td class="status">&check;</td>
			<td>'.$row["profession_id"].'</td>
			<td>'.$row['profession_name'].'</td>
			<td>'.$row['profession_xp'].'</td>
			<td>'.$row['previously_profession_id'].'</td>
		</tr>';
	}

}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

delAndInsert($conn3, $tableName, $sql);


/**
 * profession_prerequisites
 * This table is new, it breaks a single field from profession out into a new table
 * so this can now be a many to one relationship.
 */
$tableName = 'profession_prerequisites';
$sql2 = "SELECT * FROM profession";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "SET sql_mode='NO_AUTO_VALUE_ON_ZERO'; ";
$sql .= "INSERT INTO $tableName (profession_id, profession_id_req, profession_xp_req) VALUES";
foreach($rows2 as $row) {
	if ($row['previously_profession_id'] == 0) continue;
	if ($ct++ > 0) $sql .= ", ";
	// Construct insert statement for record into new MySQL DB.
	$sql .= "(".$row['profession_id'];
	$sql .= ", ";
	$sql .= $conn3->quote($row['previously_profession_id']);
    $sql .= ", ";
    $sql .= $conn3->quote($row['profession_xp']);
	$sql .= ")";
	
	if ($debug == true) {
		echo '<tr>
			<td class="status">&check;</td>
			<td>'.$row["profession_id"].'</td>
			<td>'.$row['profession_name'].'</td>
			<td>'.$row['profession_xp'].'</td>
			<td>'.$row['previously_profession_id'].'</td>
		</tr>';
	}

}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

delAndInsert($conn3, $tableName, $sql);


// I need a function that accepts a map type name and old id and converts it into a new id.
function getNewMapID($mapTypeName, $oldMapId) {
	if (!isset($mapTypeName) || !isset($oldMapId) || strlen($mapTypeName) == 0 || strlen($oldMapId) == 0) return 'NULL';
	$oldMapId = 'id'.$oldMapId;
	$mapTypeIds = $GLOBALS["mapTypeIds"];
	$mapIds = $GLOBALS["mapIds"];
	$typeId = $mapTypeIds[$mapTypeName];
	if (array_key_exists($oldMapId, $mapIds[$typeId]))
		return $mapIds[$typeId][$oldMapId];
	// Return NULL if the id wasn't found. Otherwise we get an FK violation
	return 'NULL';
}


/**
 * buildings
 */
$tableName = 'buildings';
$sql2 = "SELECT * FROM buildings";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "SET sql_mode='NO_AUTO_VALUE_ON_ZERO'; ";
// Note that the dest_map_id, dest_x, dest_y fields are blank, those get filled in in another step
$sql .= "INSERT INTO $tableName (id, name, level, map_id, x, y, external_link) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	$oldMapID = 'id'.$row['mapid'];
	// Construct insert statement for record into new MySQL DB.
	$sql .= "(".$row['building_id'];
	$sql .= ", ";
	$sql .= $conn3->quote($row['building_name']);
	$sql .= ", ";
	$sql .= $conn3->quote($row['building_level']);
	$sql .= ", ";
	// Get the new map_id based on the old one.
	$sql .= getNewMapID($row['map_type_name'], $row['mapid']);
	$sql .= ", ";
	$sql .= $conn3->quote($row['x']);
	$sql .= ", ";
	$sql .= $conn3->quote($row['y']);
	$sql .= ", ";
	$sql .= $conn3->quote($row['external_link']);

	$sql .= ")";
	
	if ($debug == true) {
		echo '<tr>
			<td class="status">&check;</td>
			<td>'.$row["building_id"].'</td>
			<td>'.$row['building_name'].'</td>
			<td>'.$row['map_type_name'].'</td>
			<td>'.$row['mapid'].'</td>
		</tr>';
	}

}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

delAndInsert($conn3, $tableName, $sql);

// Ensure empty fields have proper NULL values
$sql="UPDATE buildings SET external_link=NULL WHERE external_link='null' OR external_link='';";
$stmt = $conn3->prepare($sql); $stmt->execute();



/**
 * buildings - update destination values for portals
 * This incorporates the values previously in building_portal
 */
$tableName = 'buildings';
$sql2 = "SELECT * FROM building_portal";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;

$sql = "";
// Note that the dest_map_id, dest_x, dest_y fields are blank, those get filled in on another step
foreach($rows2 as $row) {
	$ct++;
	$sql .= "UPDATE $tableName SET ";
	$sql .= "dest_map_id=";
	// Get the new map_id based on the old one.
	$sql .= getNewMapID($row['map_type_name'], $row['map_id']);	$sql .= ", ";
	$sql .= "dest_x=".$conn3->quote($row['x']);
	$sql .= ", ";
	$sql .= "dest_y=".$conn3->quote($row['y']);
	$sql .= " WHERE id=".$row['building_id'].";";
	if ($debug == true) {
		echo '<tr>
			<td class="status">&check;</td>
			<td>'.$row["building_id"].'</td>
			<td>'.$row['building_name'].'</td>
			<td>'.$row['map_type_name'].'</td>
			<td>'.$row['mapid'].'</td>
		</tr>';
	}
	// Not actually inserting, doing an update, but we definitely don't want to delete anything!

}
echo '<tr><td colspan="20">'.$ct.' records to update.</td></tr>';
echo '</table>';
// $sql.=";"; // End SQL statement
insert($conn3, $tableName, $sql);




/**
 * stats
 */
$tableName = 'stats';
$sql2 = "SELECT * FROM stats";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "SET sql_mode='NO_AUTO_VALUE_ON_ZERO'; ";
$sql .= "INSERT INTO $tableName (id, name) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	// Construct insert statement for record into new MySQL DB.
	$sql .= "(".$row['stat_id'];
	$sql .= ", ";
	$sql .= $conn3->quote($row['stat_name']);
	$sql .= ")";
	
	if ($debug == true) {
		echo '<tr>
			<td class="status">&check;</td>
			<td>'.$row["stat_id"].'</td>
			<td>'.$row['stat_name'].'</td>
		</tr>';
	}

}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

delAndInsert($conn3, $tableName, $sql);


/**
 * flag some stats as attributes
 */
$tableName = 'stats';
$sql2 = "SELECT * FROM attribute_type";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "";
foreach($rows2 as $row) {
	$ct++;
	$sql .= "UPDATE $tableName SET is_attribute=1 WHERE name=".$conn3->quote($row['attribute_name']).";";

	if ($debug == true) {
		echo '<tr>
			<td class="status">&check;</td>
			<td>'.$row["attribute_id"].'</td>
			<td>'.$row['attribute_name'].'</td>
		</tr>';
	}

}
echo '<tr><td colspan="20">'.$ct.' records to update.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

insert($conn3, $tableName, $sql);

// Add stat descriptions into the DB, these were originally image files!
$sql = "UPDATE stats SET description='Determines physical attack power and carrying ability.' WHERE name='Strength';";
$stmt = $conn3->prepare($sql); $stmt->execute();
$sql = "UPDATE stats SET description='Increases health points, damage received when attacked, and reduces damage over time attacks (eg poisoning).' WHERE name='Constitution';";
$stmt = $conn3->prepare($sql); $stmt->execute();
$sql = "UPDATE stats SET description='Improves movement, attack speed (including casting), and chance of dodging attacks. Some spells and skills may have dexterity requirements for learning and casting.' WHERE name='Dexterity';";
$stmt = $conn3->prepare($sql); $stmt->execute();
$sql = "UPDATE stats SET description='Affects mana points for casting spells. Some spells and skills have intelligence requirements for learning and casting.' WHERE name='Intelligence';";
$stmt = $conn3->prepare($sql); $stmt->execute();
$sql = "UPDATE stats SET description='Allows more efficient attacking, blocking, casting, attack reaction, and dodging of attacks.' WHERE name='Wisdom';";
$stmt = $conn3->prepare($sql); $stmt->execute();
$sql = "UPDATE stats SET description='Determines opportunity to get help and hints from NPCs, including trading with them and getting additional quests.' WHERE name='Charisma';";
$stmt = $conn3->prepare($sql); $stmt->execute();





/**
 * stat_calculation_rules
 */
$tableName = 'stat_calculation_rules';
$sql2 = "SELECT * FROM stat_calculation_rule";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "SET sql_mode='NO_AUTO_VALUE_ON_ZERO'; ";
$sql .= "INSERT INTO $tableName (stat_id, attribute_id, attribute_coefficient) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	// Construct insert statement for record into new MySQL DB.
	$sql .= "(".$row['stat_id'];
	$sql .= ", ";
	$sql .= $conn3->quote($row['attribute_id']);
	$sql .= ", ";
	$sql .= $conn3->quote($row['attribute_coefficient']);
	$sql .= ")";
	
	if ($debug == true) {
		echo '<tr>
			<td class="status">&check;</td>
			<td>'.$row["stat_id"].'</td>
			<td>'.$row['attribute_id'].'</td>
		</tr>';
	}

}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

delAndInsert($conn3, $tableName, $sql);


/**
 * Races
 * These will now be loaded by the DB schema so this can be skipped.
 */
$tableName = 'races';
$sql2 = "SELECT * FROM race";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "SET sql_mode='NO_AUTO_VALUE_ON_ZERO'; ";
$sql .= "INSERT INTO $tableName (id, name) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	// Construct insert statement for record into new MySQL DB.
	$sql .= "(".$row['race_id'];
	$sql .= ", ";
	$sql .= $conn3->quote($row['race_name']);
	$sql .= ")";
	
	if ($debug == true) {
		echo '<tr>
			<td class="status">&check;</td>
			<td>'.$row["race_id"].'</td>
			<td>'.$row['race_name'].'</td>
		</tr>';
	}

}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

// These will now be loaded by the DB schema sql so this can be skipped.
// delAndInsert($conn3, $tableName, $sql);


// associative array of stat names to stat ids
$statIds = $conn3->query("SELECT LOWER(name) as stat_name, id FROM stats;")->fetchAll(PDO::FETCH_KEY_PAIR);


/**
 * race_attributes
 */
$tableName = 'race_attributes';
$sql2 = "SELECT * FROM race";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "SET sql_mode='NO_AUTO_VALUE_ON_ZERO'; ";
$sql .= "INSERT INTO $tableName (race_id, attribute_id, modifier) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	// Construct insert statement for record into new MySQL DB.
	$sql .= "(".$row['race_id'];
	$sql .= ", ";
	$sql .= $conn3->quote($statIds['strength']);
	$sql .= ", ";
	$sql .= $row['strength'];
	$sql .= "), ";
	$ct++;
	$sql .= "(".$row['race_id'];
	$sql .= ", ";
	$sql .= $conn3->quote($statIds['constitution']);
	$sql .= ", ";
	$sql .= $row['constitution'];
	$sql .= "), ";	
	$ct++;
	$sql .= "(".$row['race_id'];
	$sql .= ", ";
	$sql .= $conn3->quote($statIds['dexterity']);
	$sql .= ", ";
	$sql .= $row['dexterity'];
	$sql .= "), ";	
	$ct++;
	$sql .= "(".$row['race_id'];
	$sql .= ", ";
	$sql .= $conn3->quote($statIds['intelligence']);
	$sql .= ", ";
	$sql .= $row['intelligence'];
	$sql .= "), ";
	$ct++;
	$sql .= "(".$row['race_id'];
	$sql .= ", ";
	$sql .= $conn3->quote($statIds['wisdom']);
	$sql .= ", ";
	$sql .= $row['wisdom'];
	$sql .= "), ";
	$ct++;
	$sql .= "(".$row['race_id'];
	$sql .= ", ";
	$sql .= $conn3->quote($statIds['charisma']);
	$sql .= ", ";
	$sql .= $row['charisma'];
	$sql .= ") ";

	if ($debug == true) {
		echo '<tr>
			<td class="status">&check;</td>
			<td>'.$row["race_id"].'</td>
			<td>'.$row['race_name'].'</td>
		</tr>';
	}

}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement
delAndInsert($conn3, $tableName, $sql);




/**
 * birthplaces
 * originally mapname was here, but that should be referenced from map
 * One was different - placeid 18 has mapname 辨認假設, gludioname 那天，在夢裡‧‧‧
 */
$tableName = 'birthplaces';
$sql2 = "SELECT * FROM bornplaces";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "SET sql_mode='NO_AUTO_VALUE_ON_ZERO'; ";
$sql .= "INSERT INTO $tableName (id, map_id, x, y, name) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	// Construct insert statement for record into new MySQL DB.
	$sql .= "(".$row['placeid'];
	$sql .= ", ";
	// Get the new map_id based on the old one.
	$sql .= getNewMapID($row['map_type_name'], $row['mapid']);
	$sql .= ", ";
	$sql .= $conn3->quote($row['x']);
	$sql .= ", ";
	$sql .= $conn3->quote($row['y']);
	$sql .= ", ";
	$sql .= $conn3->quote($row['description']);

	$sql .= ")";
	
	if ($debug == true) {
		echo '<tr>
			<td class="status">&check;</td>
			<td>'.$row["placeid"].'</td>
			<td>'.$row['map_type_name'].'</td>
			<td>'.$row['map_id'].'</td>
			<td>'.$row['x'].'</td>
			<td>'.$row['y'].'</td>
		</tr>';
	}

}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

delAndInsert($conn3, $tableName, $sql);





/**
 * players
 * we have a small problem. There are 5 players who have a bornplace of 11 which no longer exists.
 * I could just default it to 1 (the most common and a reasonable default, I suppose).
 */
$tableName = 'players';
$sql2 = "SELECT * FROM players p JOIN users u ON u.userid=p.playerid
WHERE userid IN (SELECT userid FROM users WHERE lower(username) IN (SELECT lower(username) as username FROM users GROUP BY lower(username) HAVING COUNT(*) = 1));";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "SET sql_mode='NO_AUTO_VALUE_ON_ZERO'; ";
$sql .= "INSERT INTO $tableName (id, user_id, name, birthplace_id, visible, map_id, x, y, last_action_time,
health, health_max, money, experience, repute_radius, movement, travel, attack, defence, bonus_point, race_id, gender_id, is_super) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	// Construct insert statement for record into new MySQL DB.
	$sql .= "(".$row['playerid'];
	$sql .= ", ";
	// userid is new, on import we just use the playerid
	// as they started out the same
	$sql .= $conn3->quote($row['playerid']);
	$sql .= ", ";
	//Give the players the user's name by default
	$sql .= $conn3->quote($row['username']);
	$sql .= ", ";

	// Originally the bornplaceid was in the users table
	$sql .= ($row['bornplaceid'] == 11)?'1':$row['bornplaceid'];	
	$sql .= ", ";
	// ensure this is a valid bit
	$sql .= ($row['visible']==1)?'1':'0';
	$sql .= ", ";

	// Get the new map_id based on the old one.
	$sql .= getNewMapID($row['map_type_name'], $row['map_id']);
	$sql .= ", ";
	$sql .= $conn3->quote($row['x']);
	$sql .= ", ";
	$sql .= $conn3->quote($row['y']);
	$sql .= ", ";
	// Convert tileentertime to a datetime...
	$sql .= $conn3->quote(date("Y-m-d H:i:s", $row['tileentertime']));
	$sql .= ", ";
	$sql .= $conn3->quote($row['health']);
	$sql .= ", ";
	$sql .= $conn3->quote($row['healthmax']);
	$sql .= ", ";
	$sql .= $conn3->quote($row['money']);
	$sql .= ", ";
	$sql .= $conn3->quote($row['experience']);
	$sql .= ", ";
	$sql .= $conn3->quote($row['reputeradius']); // I'm not sure what this one does
	$sql .= ", ";
	// Give all players a minimum of 2000 movement points.
	$sql .= (int)$row['movement']<Config::INITIAL_MOVEMENT_POINTS?Config::INITIAL_MOVEMENT_POINTS:$row['movement'];
	$sql .= ", ";
	$sql .= $conn3->quote($row['travel']);
	$sql .= ", ";
	$sql .= $conn3->quote($row['attack']);
	$sql .= ", ";
	$sql .= $conn3->quote($row['defence']);
	$sql .= ", ";
	$sql .= $conn3->quote($row['bonus_point']);
	$sql .= ", ";
	$sql .= $row['race_id'];
	$sql .= ", ";
	$sql .= $conn3->quote($row['gender']);
	$sql .= ", ";
	$sql .= ($row['isadmin']==1)?"1":"0";	
	$sql .= ")";
	
	if ($debug == true) {
		echo '<tr>
			<td class="status">&check;</td>
			<td>'.$row["playerid"].'</td>
		</tr>';
	}

}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

// echo $sql;

delAndInsert($conn3, $tableName, $sql);



/**
 * player_base_attributes
 * Having some trouble here because some players have the same attribute more than once, which seems like a flaw.
 */
$tableName = 'player_base_attributes';
// This rather complex query queries for all players but only gets one attribute for each.
$sql2 = "
SELECT playerid, attribute_id, attribute_value FROM player_base_attribute WHERE playerid NOT IN
(SELECT DISTINCT playerid FROM player_base_attribute GROUP BY playerid, attribute_id HAVING count(1) > 1)
AND playerid IN (SELECT userid FROM users WHERE lower(username) IN (SELECT lower(username) as username FROM users GROUP BY lower(username) HAVING COUNT(*) = 1))
UNION
-- Add the Players with duplicate values, but only select the top one that's a reasonably sane value
SELECT DISTINCT playerid, attribute_id,
(SELECT attribute_value FROM player_base_attribute av2
 WHERE av1.playerid=av2.playerid AND av1.attribute_id=av2.attribute_id AND attribute_value < 200 ORDER BY attribute_value DESC LIMIT 1) as attribute_value
FROM player_base_attribute av1
WHERE playerid IN (SELECT DISTINCT playerid FROM player_base_attribute GROUP BY playerid, attribute_id HAVING count(1) > 1)
-- Exclude players with duplicate usernames as that now causes a unique violation
AND playerid IN (SELECT userid FROM users WHERE lower(username) IN (SELECT lower(username) as username FROM users GROUP BY lower(username) HAVING COUNT(*) = 1))
";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "SET sql_mode='NO_AUTO_VALUE_ON_ZERO'; ";
$sql .= "INSERT INTO $tableName (player_id, attribute_id, attribute_value) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	// Construct insert statement for record into new MySQL DB.
	$sql .= "(".$row['playerid'];
	$sql .= ", ";
	$sql .= $conn3->quote($row['attribute_id']);
	$sql .= ", ";
	$sql .= $conn3->quote($row['attribute_value']);
	$sql .= ")";
	
	if ($debug == true) {
		echo '<tr>
			<td class="status">&check;</td>
			<td>'.$row["playerid"].'</td>
			<td>'.$row['attribute_id'].'</td>
			<td>'.$row['attribute_value'].'</td>
		</tr>';
	}

}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

delAndInsert($conn3, $tableName, $sql);


/**
 * player_stats
 * Here there seem to be players referenced who no longer exist
 */
$tableName = 'player_stats';
// This rather complex query queries for all players but only gets one attribute for each.
$sql2 = "SELECT * FROM player_stats WHERE playerid IN (SELECT userid FROM users WHERE lower(username) IN (SELECT lower(username) as username FROM users GROUP BY lower(username) HAVING COUNT(*) = 1))";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "SET sql_mode='NO_AUTO_VALUE_ON_ZERO'; ";
$sql .= "INSERT INTO $tableName (player_id, stat_id, stat_value) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	// Construct insert statement for record into new MySQL DB.
	$sql .= "(".$row['playerid'];
	$sql .= ", ";
	$sql .= $conn3->quote($row['stat_id']);
	$sql .= ", ";
	$sql .= $conn3->quote($row['stat_value']);
	$sql .= ")";
	
	if ($debug == true) {
		echo '<tr>
			<td class="status">&check;</td>
			<td>'.$row["playerid"].'</td>
			<td>'.$row['stat_id'].'</td>
			<td>'.$row['stat_value'].'</td>
		</tr>';
	}

}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

delAndInsert($conn3, $tableName, $sql);


/**
 * player_skills
 * This table currently just has each player in it with a zero. Perhaps this is not necessary,
 * since we can assume that 0, (no skill) is an ability possessed by every player
 */

// This table just stored empty, useless data before and wasn't used to record anything meaningful
// so I'll just skip importing this one as it currently seems to cause the upgrade to fail

/*

$tableName = 'player_skills';
// This rather complex query queries for all players but only gets one attribute for each.
// skill 0 just means "no skilll" which everyone obviously automatically gets, so it's pointless to store these.
// The new version doesn't require this.
$sql2 = "SELECT * FROM playerskills WHERE skillid!=0 AND playerid IN (SELECT userid FROM users WHERE lower(username) IN (SELECT lower(username) as username FROM users GROUP BY lower(username) HAVING COUNT(*) = 1))";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "SET sql_mode='NO_AUTO_VALUE_ON_ZERO'; ";
$sql .= "INSERT INTO $tableName (player_id, skill_id) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	// Construct insert statement for record into new MySQL DB.
	$sql .= "(".$row['playerid'];
	$sql .= ", ";
	$sql .= $conn3->quote($row['skillid']);
	$sql .= ")";
	
	if ($debug == true) {
		echo '<tr>
			<td class="status">&check;</td>
			<td>'.$row["playerid"].'</td>
			<td>'.$row['skillid'].'</td>
		</tr>';
	}

}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

delAndInsert($conn3, $tableName, $sql);

*/


/**
 * player_abilities will need discussion as it's unused but requires redesign.
 * The comma separated list format for profession_limit should be refactored to multiple rows.
 */


/**
 * player_property has 9 rows of NULLs but doesn't seem to have relevant data in it.
 */


/**
 * player_professions
 */
$tableName = 'player_professions';
// This rather complex query queries for all players but only gets one of each profession.
$sql2 = "
SELECT playerid, profession_id, profession_xp, profession_level FROM player_profession_info
WHERE playerid NOT IN (SELECT playerid FROM player_profession_info GROUP BY playerid, profession_id HAVING count(1) > 1)
AND playerid IN (SELECT userid FROM users WHERE lower(username) IN (SELECT lower(username) as username FROM users GROUP BY lower(username) HAVING COUNT(*) = 1))
UNION
SELECT DISTINCT playerid, profession_id,
(SELECT profession_xp FROM player_profession_info pp2
 WHERE pp1.playerid=pp2.playerid AND pp1.profession_id=pp2.profession_id ORDER BY profession_xp DESC LIMIT 1) AS profession_xp,
(SELECT profession_level FROM player_profession_info pp3
 WHERE pp1.playerid=pp3.playerid AND pp1.profession_id=pp3.profession_id ORDER BY profession_level DESC LIMIT 1) AS profession_level
FROM player_profession_info pp1 WHERE playerid IN 
(SELECT playerid FROM player_profession_info GROUP BY playerid, profession_id HAVING count(1) > 1)
AND playerid IN (SELECT userid FROM users WHERE lower(username) IN (SELECT lower(username) as username FROM users GROUP BY lower(username) HAVING COUNT(*) = 1))
ORDER BY profession_xp DESC, profession_level DESC
";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "SET sql_mode='NO_AUTO_VALUE_ON_ZERO'; ";
$sql .= "INSERT INTO $tableName (player_id, profession_id, profession_xp, profession_level) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	// Construct insert statement for record into new MySQL DB.
	$sql .= "(".$row['playerid'];
	$sql .= ", ";
	$sql .= $conn3->quote($row['profession_id']);
	$sql .= ", ";
	$sql .= $conn3->quote($row['profession_xp']);
	$sql .= ", ";
	$sql .= $conn3->quote($row['profession_level']);		
	$sql .= ")";
	
	if ($debug == true) {
		echo '<tr>
			<td class="status">&check;</td>
			<td>'.$row["playerid"].'</td>
			<td>'.$row['profession_id'].'</td>
			<td>'.$row['profession_xp'].'</td>
			<td>'.$row['profession_level'].'</td>
		</tr>';
	}

}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

delAndInsert($conn3, $tableName, $sql);



/**
 * npcs
 * 
 */
$tableName = 'npcs';
// Only (mostly) selecting chats that are actual messages typed by humans. System generated info is being ignored.
$sql2 = "SELECT * FROM npc;";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "INSERT INTO $tableName (id, name, map_id, y_top, x_right, y_bottom, x_left, level, npc_icon_id) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	// Construct insert statement for record into new MySQL DB.
	$sql .= "(".$row['npc_id'];
	$sql .= ", ";
	$sql .= $conn3->quote(trim($row['npcname'])); // For some reason these are all 30 characters
	$sql .= ", ";
	$sql .= getNewMapID($row['map_type_name'], $row['mapid']);
	$sql .= ", ";
	$sql .= $row['npctop'];
	$sql .= ", ";
	$sql .= $row['npcright'];
	$sql .= ", ";
	$sql .= $row['npcbottom'];
	$sql .= ", ";
	$sql .= $row['npcleft'];			
	$sql .= ", ";
	$sql .= $conn3->quote($row['npclevel']);
	$sql .= ", ";
	$sql .= $conn3->quote($row['iconid']);
	$sql .= ")";
	
}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

delAndInsert($conn3, $tableName, $sql);



// Get an array with all existing profession ids.
$professionIds = $conn3->query('SELECT id FROM professions;')->fetchAll(PDO::FETCH_COLUMN);

/**
 * npc_professions
 * This table is new - it takes what was formerly a list of professions from the npc table
 */
$tableName = 'npc_professions';
$sql2 = "SELECT npc_id, profession_list FROM npc WHERE profession_list != '0';";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "INSERT INTO $tableName (npc_id, profession_id) VALUES";
foreach($rows2 as $row) {
	// Loop through the list of professions.
	$profArray = explode(',', $row['profession_list']);
	foreach($profArray as $profId) {
		// Need to check that the profession actually exists to avoid an FK violation
		if (in_array($profId, $professionIds)) {
			if ($ct++ > 0) $sql .= ", ";
			$sql .= "(".$row['npc_id'];
			$sql .= ", ";
			$sql .= $profId.")";
		}

	}
}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

delAndInsert($conn3, $tableName, $sql);


/**
 * Update some characters on the test map with new icons
 */
$sql="";
$sql.="UPDATE npcs SET npc_icon_id=522 WHERE name='Knight James';";
$sql.="UPDATE npcs SET npc_icon_id=523 WHERE name='Old Matthew';";
$sql.="UPDATE npcs SET npc_icon_id=507 WHERE name='Old Matthew Wife';";
$sql.="UPDATE npcs SET npc_icon_id=527 WHERE name='Salesman Mike';";
$sql.="UPDATE npcs SET npc_icon_id=531 WHERE name='Boy Seth';";
$sql.="UPDATE npcs SET npc_icon_id=519 WHERE name='Boy Josh';";
$sql.="UPDATE npcs SET npc_icon_id=514 WHERE name='Gluttony Peter';";
$sql.="UPDATE npcs SET npc_icon_id=525 WHERE name='Villager Pierre';";
$sql.="UPDATE npcs SET npc_icon_id=502 WHERE name='Villager Claire';";
$sql.="UPDATE npcs SET npc_icon_id=515 WHERE name='Villager Guile';";
$sql.="UPDATE npcs SET npc_icon_id=501 WHERE name='Villager Guile Sister';";
$sql.="UPDATE npcs SET npc_icon_id=530 WHERE name='Young Villager Sarah';";
$sql.="UPDATE npcs SET npc_icon_id=529 WHERE name='Young Villager Sarah Dad';";
$sql.="UPDATE npcs SET npc_icon_id=528 WHERE name='Villager Zod';";
$sql.="UPDATE npcs SET npc_icon_id=518 WHERE name='Villager Zod Brother';";
$sql.="UPDATE npcs SET npc_icon_id=520 WHERE name='Villager Crok';";
$sql.="UPDATE npcs SET npc_icon_id=503 WHERE name='Villager Crok Aunt';";
$sql.="UPDATE npcs SET npc_icon_id=504 WHERE name='Villager Anne';";
$sql.="UPDATE npcs SET npc_icon_id=529 WHERE name='Villager Anne Uncle';";
$sql.="UPDATE npcs SET npc_icon_id=525 WHERE name='Villager Leroj';";
$sql.="UPDATE npcs SET npc_icon_id=515 WHERE name='Villager Leroj Son';";
$sql.="UPDATE npcs SET npc_icon_id=526 WHERE name='Villager Creed';";
$sql.="UPDATE npcs SET npc_icon_id=524 WHERE name='Child Henry';";
$sql.="UPDATE npcs SET npc_icon_id=500 WHERE name='Villager Ben';";
$sql.="UPDATE npcs SET npc_icon_id=505 WHERE name='Villager Elle';";
$sql.="UPDATE npcs SET npc_icon_id=521 WHERE name='Villager Travis';";
$sql.="UPDATE npcs SET npc_icon_id=506 WHERE name='Villager Travis Wife';";
$sql.="UPDATE npcs SET npc_icon_id=509 WHERE name='Villager Lis';";
$sql.="UPDATE npcs SET npc_icon_id=510 WHERE name='Villager Lis Mother';";
$sql.="UPDATE npcs SET npc_icon_id=522 WHERE name='Guard Ken';";
$sql.="UPDATE npcs SET npc_icon_id=523 WHERE name='Chief Spencer';";
$sql.="UPDATE npcs SET npc_icon_id=525 WHERE name='Guide Josh';";
$sql.="UPDATE npcs SET npc_icon_id=513 WHERE name='Book Keeper John';";
$sql.="UPDATE npcs SET npc_icon_id=519 WHERE name='Master Skywalker';";
$sql.="UPDATE npcs SET npc_icon_id=502 WHERE name='Farmer Donna';";
$sql.="UPDATE npcs SET npc_icon_id=520 WHERE name='Mark Blacksmith';";
$sql.="UPDATE npcs SET npc_icon_id=527 WHERE name='Manager Bartler';";
$sql.="UPDATE npcs SET npc_icon_id=526 WHERE name='Shawn';";
$sql.="UPDATE npcs SET npc_icon_id=508 WHERE name='Elf Beauty';";
$sql.="UPDATE npcs SET npc_icon_id=500 WHERE id=99;";
insert($conn3, 'npcs', $sql);

/**
 * npc_portals
 * 
 */
$tableName = 'npc_portals';
$sql2 = "SELECT * FROM npc_portal;";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "INSERT INTO $tableName (id, npc_id, dest_map_id, dest_x, dest_y, price, name, level) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	$sql .= '(';
	$sql .= $row['portal_id'];
	$sql .= ', ';
	$sql .= $row['npc_id'];
	$sql .= ', ';
		// Get the new map_id based on the old one.
	$sql .= getNewMapID($row['map_type_name'], $row['map_id']);
	$sql .= ', ';
	$sql .= $conn3->quote($row['x']);
	$sql .= ', ';
	$sql .= $conn3->quote($row['y']);
	$sql .= ', ';
	$sql .= $conn3->quote($row['money']);
	$sql .= ', ';
	$sql .= $conn3->quote($row['portal_name']);
	$sql .= ', ';
	$sql .= $conn3->quote($row['level']);
	$sql .= ')';

}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

delAndInsert($conn3, $tableName, $sql);


// "Turn on" all the NPC portals. Not sure if this was on purpose or not...
// $sql = "UPDATE npcs SET npcs.portal_keeper=1 WHERE id IN (SELECT id FROM npc_portals);";
// $stmt = $conn3->prepare($sql); $stmt->execute();


/**
 * item_categories
 * This merges the item_category_info and itemlayerinfo tables
 *
 * These categories are currently all irrelevant. Could add some types direction into the DB creation .sql

$tableName = 'item_categories';
$sql2 = "SELECT c.itemcategoryid, c.itemcategoryname, c.nouse, l.layerindex FROM itemcategories c
LEFT OUTER JOIN itemlayerinfo l ON c.itemcategoryid=l.itemcategoryid
ORDER BY c.itemcategoryid;";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "INSERT INTO $tableName (id, name, disabled, layer_index) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	$sql .= '(';
	$sql .= $row['itemcategoryid'];
	$sql .= ', ';
	$sql .= $conn3->quote($row['itemcategoryname']);
	$sql .= ', ';
	$sql .= ($row['nouse']==1)?'1':'0';
	$sql .= ', ';
	$sql .= strlen($row['layerindex'])?$conn3->quote($row['layerindex']):'NULL';
	$sql .= ')';

}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

delAndInsert($conn3, $tableName, $sql);

$sql="";
$sql.= "UPDATE item_categories SET display_seq = 10 WHERE name='Skin';";
$sql.= "UPDATE item_categories SET display_seq = 20 WHERE name='Hair';";
$sql.= "UPDATE item_categories SET display_seq = 30 WHERE name='Eyebrows';";
$sql.= "UPDATE item_categories SET display_seq = 40 WHERE name='Eyes';";
$sql.= "UPDATE item_categories SET display_seq = 50 WHERE name='Nose';";
$sql.= "UPDATE item_categories SET display_seq = 60 WHERE name='Mustache';";
$sql.= "UPDATE item_categories SET display_seq = 70 WHERE name='Mouth';";
$sql.= "UPDATE item_categories SET display_seq = 80 WHERE name='Top';";
$sql.= "UPDATE item_categories SET display_seq = 90 WHERE name='Bottom';";
$sql.= "UPDATE item_categories SET display_seq = 100 WHERE name='Shoes';";
insert($conn3, 'item_categories', $sql);

 */


/**
 * items
 * Formerly world_item. Renamed to items for more clarity and relationship with other item tables.
 * Now the item_icon is in the same table so you don't need a join to find an icon.
 */
$tableName = 'items';
// This query will exclude a few items that are not used in the game
// as well as all the items that are avatar_images
$sql2 = "SELECT itm.item_id, itm.item_name, iconid, item_amount, item_description, item_effect,
item_weight, item_author, item_parameters, item_required_level, item_level, price, item_max_amount, nouse
FROM world_item itm 
LEFT OUTER JOIN world_item_icon icn ON itm.item_id=icn.item_id 
WHERE itm.item_id NOT IN ('101250001')
AND itm.item_id >= 300000000
ORDER BY itm.item_id;";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "INSERT INTO $tableName (old_id, name, item_category_id, item_icon_id, amount, description, item_effect_id, effect_parameters, weight, author, parameters, required_level, level, price, max_amount, disabled) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	$sql .= '(';
	$sql .= $conn3->quote($row['item_id']);
	$sql .= ', ';
	$sql .= $conn3->quote($row['item_name']);
	$itemID = $row['item_id'];
	// Item categories are not used yet
	$catID = 'NULL';
	// Gender ID and race ID are no longer needed in items
	$genderID = 'NULL';
	if (abs($itemID) < 300000000) {
		// Digits 4-5 are the category id
		$catID=substr(abs($itemID), 3, 2);
		$genderID=substr(abs($itemID), 0, 1);
	}
	$sql .= ', ';
	$sql .= $catID;
//	$sql .= ', ';
//	$sql .= $genderID;

	$sql .= ', ';
	$sql .= (is_numeric($row['iconid']))?$row['iconid']:'NULL';
	$sql .= ', ';
	// No longer using icon_extension in this table
    //	if (is_null($row['iconid'])) {
    //		if ($row['item_id'] < 0) {
    //			$sql .= '1'; // gif
    //		} else {
    //			$sql .= '0'; // png
    //		}
    //	} else $sql .= 'NULL';
	// $sql .= ', ';
	$sql .= $conn3->quote($row['item_amount']);
	$sql .= ', ';
    $sql .= (strcasecmp($row['item_description'], "NONE")==0 || $row['item_description']=='')?'NULL':$conn3->quote($row['item_description']);
	$sql .= ', ';

    $effectName = preg_replace('/(\w+).*(=\d+)?/i', '$1', $row['item_effect']);
    switch ($effectName) {
        case 'eat': $effectID=1; break;
        case 'quest_activate': $effectID=2; break;
        case 'dig': $effectID=3; break;
        default: $effectID=null;
    }
    $paramVal=null;
    if (preg_match('/\w+.*=(\d+)/i', $row['item_effect'])) $paramVal = preg_replace('/\w+.*=(\d+)/i', '$1', $row['item_effect']);

    $sql .= $effectID==null?'NULL':$effectID;
    $sql .= ', ';
    $sql .= $paramVal==null?'NULL':$paramVal;
	$sql .= ', ';
	$sql .= $conn3->quote($row['item_weight']);
	$sql .= ', ';
	$sql .= $conn3->quote($row['item_author']);
	$sql .= ', ';
    //	$sql .= $conn3->quote($row['item_parameters']); // We don't use these at all, just set NULL
    $sql .= 'NULL';
	$sql .= ', ';
	$sql .= $conn3->quote($row['item_required_level']);
	$sql .= ', ';
	$sql .= $conn3->quote($row['item_level']);
	$sql .= ', ';
	$sql .= $conn3->quote($row['price']);
	$sql .= ', ';
	$sql .= $conn3->quote($row['item_max_amount']);
	$sql .= ', ';
	$sql .= ($row['nouse']==1)?'1':'0';
	$sql .= ')';

}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement


delAndInsert($conn3, $tableName, $sql);
/* We no longer need to add all the properties to items, they aren't used here anymore

// Add the race_id for items that only apply to particular races

// Flag Human parts with correct race_id
$sql = "UPDATE items SET race_id=1 WHERE disabled=0 AND (
-- Male Human
     old_id BETWEEN 101010000 AND 101139999 OR old_id BETWEEN -101139999 AND -101010000
-- Female Human   
  OR old_id BETWEEN 201010000 AND 201139999 OR old_id BETWEEN -201139999 AND -201010000     
-- Unisex Human
  OR old_id BETWEEN 901010000 AND 901139999 OR old_id BETWEEN -901139999 AND -901010000
);";
$stmt = $conn3->prepare($sql); $stmt->execute();

// Flag Dwarf parts with correct race_id
$sql = "UPDATE items SET race_id=2 WHERE disabled=0 AND (
-- Male Dwarf
     old_id BETWEEN 102010000 AND 102139999 OR old_id BETWEEN -102139999 AND -102010000
-- Female Dwarf   
  OR old_id BETWEEN 202010000 AND 202139999 OR old_id BETWEEN -202139999 AND -202010000     
-- Unisex Dwarf
  OR old_id BETWEEN 902010000 AND 902139999 OR old_id BETWEEN -902139999 AND -902010000
);";
$stmt = $conn3->prepare($sql); $stmt->execute();

// Flag Elf parts with correct race_id
$sql = "UPDATE items SET race_id=3 WHERE disabled=0 AND (
-- Male Elf
     old_id BETWEEN 103010000 AND 103139999 OR old_id BETWEEN -103139999 AND -103010000
-- Female Elf   
  OR old_id BETWEEN 203010000 AND 203139999 OR old_id BETWEEN -203139999 AND -203010000     
-- Unisex Elf
  OR old_id BETWEEN 903010000 AND 903139999 OR old_id BETWEEN -903139999 AND -903010000
);";
$stmt = $conn3->prepare($sql); $stmt->execute();

// Flag Orc parts with correct race_id
$sql = "UPDATE items SET race_id=7 WHERE disabled=0 AND (
-- Male Orc
     old_id BETWEEN 107010000 AND 107139999 OR old_id BETWEEN -107139999 AND -107010000
-- Female Orc   
  OR old_id BETWEEN 207010000 AND 207139999 OR old_id BETWEEN -207139999 AND -207010000     
-- Unisex Orc
  OR old_id BETWEEN 907010000 AND 907139999 OR old_id BETWEEN -907139999 AND -907010000
);";
$stmt = $conn3->prepare($sql); $stmt->execute();

// Dwarf top3 had the wrong ID somehow and ended up on humans
$sql = "UPDATE items SET race_id=2 WHERE old_id=101070031;";
// Elf eyes were mixed up with skirts
$sql.="UPDATE items SET name='Skirt', item_category_id='5', disabled=1 WHERE old_id BETWEEN 203100003 AND 203100006;";
// Fix a silly spelling mistake noes->nose
$sql.="UPDATE items SET name=REPLACE(name, 'noes', 'nose') WHERE name LIKE '%noes%';";
insert($conn3, 'items', $sql);
*/

// I probably can just do this with subqueries... but alternatively I could make this structure that allows you to get the itemId
// For other tables that have item_id, we can map the old_id to the new id using this associative array
// 0 is an empty array so that the index matches the item id.
$itemIds = $conn3->query("SELECT old_id, id FROM items")->fetchAll(PDO::FETCH_KEY_PAIR);




/**
 * item_stats
 * This is barely used, but one item has "stats".
 * I will not import this
 */

/*
$tableName = 'item_stats';
$sql2 = "SELECT * FROM item_stats;";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "INSERT INTO $tableName (item_id, stat_id, stat_value) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	$sql .= '(';
	$sql .= $conn3->quote($itemIds[$row['item_id']]);
	$sql .= ', ';
	$sql .= $conn3->quote($row['stat_id']);
	$sql .= ', ';
	$sql .= $conn3->quote($row['stat_value']);
	$sql .= ')';

}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement


delAndInsert($conn3, $tableName, $sql);

*/


/**
 * player_items
 * merges former player_items, player_equipped, and player_items_repository into a single table
 */
$tableName = 'player_items';
// This joins three tables while skipping any duplicates (of where there are at least 7)
$sql2 = "SELECT DISTINCT pi.playerid, pi.item_guid, pi.item_id,
CASE WHEN e.playerid IS NOT NULL THEN 1 ELSE 0 END AS is_equipped,
r.bag_id, r.slot_id, r.item_type, r.item_amount
FROM player_items pi
LEFT OUTER JOIN player_items_repository r ON pi.item_guid=r.item_guid
LEFT OUTER JOIN player_equipped e ON pi.item_guid=e.item_guid -- 6102 items, 6080 with DISTINCT
WHERE pi.playerid IN (SELECT playerid FROM players) -- 6020 with this WHERE
AND pi.item_id IN (SELECT item_id FROM world_item WHERE item_id >= 300000000) -- 5983
AND pi.playerid IN (SELECT userid FROM users WHERE lower(username) IN (SELECT lower(username) as username FROM users GROUP BY lower(username) HAVING COUNT(*) = 1))
;";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "INSERT INTO $tableName (guid, player_id, item_id, is_equipped, bag_slot_id, bag_slot_amount) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	$sql .= '(';
	$sql .= $conn3->quote($row['item_guid']);
	$sql .= ', ';
	$sql .= $conn3->quote($row['playerid']);
	$sql .= ', ';
	$sql .= $conn3->quote($itemIds[$row['item_id']]);
	$sql .= ', ';
	$sql .= $row['is_equipped'];
	$sql .= ', ';
	$sql .= (is_numeric($row['slot_id']))?$row['slot_id']:'NULL';
    //	$sql .= ', ';
    //	$sql .= (is_numeric($row['item_type']))?$row['item_type']:0;
	$sql .= ', ';
	$sql .= (is_numeric($row['item_amount']))?$row['item_amount']:1;
	$sql .= ')';
}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

// echo $sql;

delAndInsert($conn3, $tableName, $sql);

/**
 * Given the original MWv2 quest_result_type_id, return the new type that we are converting to
 * @param $oldResultType
 * @return int
 */
function newResultType(int $oldResultType): int {
    switch ($oldResultType) {
        case 1: // Items True/False
            return 2;
        case 2: // Single Choice (items)
            return 3;
        case 3: // Choose Multiple (items)
            return 4;
        case 6: // Order (items)
            return 5;
        case 11: // Multiple calculation items
        case 12: // Multiple text answer items
            return 6;
        case 4: // Cloze
            return 7;
        case 15: // True/False
            return 8;
        case 16: // Single choice
            return 9;
        // 10 is new and doesn't exist in MWv2
        case 5: // Text answer
        case 18: // Multiple text answers
            return 11;
        case 10: // Calculation
        case 17: // Multiple calculation
            return 12;
        case 13: // Coordinates
            return 13;
        case 19: // Speaking (Conversation)
            return 14;
        default: //7 Quest Items, 8 Check In (only), 9 Check In (with Item), 14 Multiple Coordinates (Items)
            return 1;
    }
}


/**
 * Accepts two strings from the old quests table and returns the one most likely to be useful
 * @param $s1
 * @param $s2
 * @return string
 */
function bestWords($s1, $s2): string {
    $badPhrases = [
        'target npc',
        'giver',
    ];

    foreach ($badPhrases as $badPhrase) {
        if (strpos(strtolower($s1), $badPhrase)) return $s2;
        else if (strpos(strtolower($s2), $badPhrase) )return $s1;
    }
    // If we didn't find any forbidden words, just return the longest string.
    if (strlen($s1) > strlen($s2)) return $s1;
    else return $s2;
}


/******************
 Quest Tables
*******************/


/**
 * quest_types: 11 are already inserted by the db creation script.
 * quest_result_types are also already inserted by default.
 */


/**
 * quests - originally quest_general
 * quests referencing npcs that no longer exist will be removed
 * (except for when they are 0, in which case) it will be changed to NULL
 * 
 * This could be further cleaned up by converting text strings "NONE" to null.
 * That would save some space and be more "correct".
 * Perhaps blank strings would also be a good option.
 * For now I'll leave them as is, I can clean up later if necessary.
 */

$tableName = 'quests';

$sql2 = "SELECT * FROM quest_general --667
WHERE quest_giver IN (SELECT npc_id FROM npc) --658
AND (target_npc=0 OR target_npc IN (SELECT npc_id FROM npc));";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "INSERT INTO $tableName (id, name, repeatable, repeatable_cd_in_minute,
level, giver_npc_id, target_npc_id, prologue, content, target_npc_prologue, npc_has_quest_item, quest_result_type_id, old_result_type_id,
success_words, failure_words, cd_in_minute, result, base_reward_automark_percentage, reward_profession_xp, reward_xp, reward_money) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	$sql .= '(';
	$sql .= $conn3->quote($row['quest_id']);
	$sql .= ', ';
	$sql .= $conn3->quote($row['quest_name']);
	$sql .= ', ';
	// $sql .= ($row['is_quest_chain']==1 || strcasecmp($row['is_quest_chain'], 'true')==0)?'1':'0';
	// $sql .= ', ';
	$sql .= ($row['repeatable']==1 || strcasecmp($row['repeatable'], 'true')==0)?'1':'0';
	$sql .= ', ';
	$sql .= $row['repeatable_cd_in_minute'];
	$sql .= ', ';
	// $sql .= $row['quest_opportunity'];
	// $sql .= ', ';
	$sql .= $row['quest_level'];
	$sql .= ', ';
	// $sql .= ($row['pickup_limitation']==1 || strcasecmp($row['pickup_limitation'], 'true')==0)?'1':'0';
	// $sql .= ', ';
	// $sql .= ($row['general_quest']==1 || strcasecmp($row['general_quest'], 'true')==0)?'1':'0';
	// $sql .= ', ';
	$sql .= $row['quest_giver'];
	$sql .= ', ';
	$sql .= ($row['target_npc']==0)?'NULL':$row['target_npc'];
	$sql .= ', ';
	$sql .= $conn3->quote($row['prologue']);
	$sql .= ', ';
	// We'll get rid of the dollar dollar dollar prompts, they'll be problematic.
	$sql .= $conn3->quote(preg_replace('/\$\$\$[XY]=\$\$\$/', '', $row['quest_content']));
	$sql .= ', ';
    $sql .= $row['target_npc_prologue'] == 'NONE'?'NULL':$conn3->quote($row['target_npc_prologue']);
	$sql .= ', ';
	$sql .= ($row['npc_has_quest_item']==1 || strcasecmp($row['npc_has_quest_item'], 'true')==0)?'1':'0';
	// These are now moved into the quest_items table. This data is lost but it only affects a few quests
    // 1,2,27,28,33,38,46,47,48,54,56,58 -- most of these were are quests with the old, invalid result data
    //	$sql .= ', ';
    //	$sql .= $row['min_quest_item_amount'];
    //	$sql .= ', ';
    //	$sql .= $row['max_quest_item_amount'];
	$sql .= ', ';
	$sql .= newResultType((int)$row['quest_result_type_id']); // new result id
	$sql .= ', ';
    $sql .= $row['quest_result_type_id']; // Old result id
    $sql .= ', ';
    // success_words
    $best = bestWords($row['target_npc_correct_words'], $row['quest_giver_final_words']);
    $sql .= $best == 'NONE'?'NULL':$conn3->quote($best);
    $sql .= ', ';
    // failure_words
    $best = bestWords($row['target_npc_incorrect_words'], $row['quest_giver_questioning_words']);
    $sql .= $best == 'NONE'?'NULL':$conn3->quote($best);
    //$sql .= $row['target_npc_correct_words'] == 'NONE'?'NULL':$conn3->quote($row['target_npc_correct_words']);
	//$sql .= ', ';
    //$sql .= $row['target_npc_incorrect_words'] == 'NONE'?'NULL':$conn3->quote($row['target_npc_incorrect_words']);
	//$sql .= ', ';
    //$sql .= $row['quest_giver_final_words'] == 'NONE'?'NULL':$conn3->quote($row['quest_giver_final_words']);
	//$sql .= ', ';
    //$sql .= $row['quest_giver_questioning_words'] == 'NONE'?'NULL':$conn3->quote($row['quest_giver_questioning_words']);
	$sql .= ', ';
	$sql .= $conn3->quote($row['cd_in_minute']);
	// $sql .= ', ';
	// $sql .= $conn3->quote($row['quest_type_id']);
	$sql .= ', ';
    $sql .= $row['quest_result'] == 'NONE'?'NULL':$conn3->quote($row['quest_result']);
	$sql .= ', ';
	$sql .= strlen($row['base_reward_standard'])?$conn3->quote($row['base_reward_standard']):'0';
	$sql .= ', ';
	$sql .= $conn3->quote($row['reward_profession_xp']);
	$sql .= ', ';
	$sql .= ($row['reward_xp']!=null?$conn3->quote($row['reward_xp']):'0');
	$sql .= ', ';
    $sql .= ($row['reward_money']!=null?$conn3->quote($row['reward_money']):'0');
	$sql .= ')';
}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

// echo $sql;

delAndInsert($conn3, $tableName, $sql);


/**
 * quest_type_result_types
 * formerly quest_result_type
 * Seems to specify which result types each quest type has available
 *
 * This has been removed


$tableName = 'quest_type_result_types';

$sql2 = "SELECT * FROM quest_result_type;";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "INSERT INTO $tableName (quest_type_id, quest_result_type_id) VALUES";
foreach($rows2 as $row) {
    if ($ct++ > 0) $sql .= ", ";
    $sql .= '(';
    $sql .= $row['quest_type_id'];
    $sql .= ', ';
    $sql .= $row['result_type_id'];
    $sql .= ')';
}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

// echo $sql;
delAndInsert($conn3, $tableName, $sql);
 */


/**
 * quest_prerequisite_professions
 * formerly professionlimitation
 * I had intended to put profession level into this table so that 
 * each profession required could have a different level,
 * but this proved a little challenging to check given my current approach
 */

// Make an array of all the quest_ids that we use to filter the original data to ensure
// we only select values that won't have a foreign key violation.
$quests = $conn3->query('SELECT id FROM quests;')->fetchAll(PDO::FETCH_COLUMN);

$tableName = 'quest_prerequisite_professions';

$sql2 = "SELECT DISTINCT pl.profession_limitation_id, pl.quest_id FROM professionlimitation pl
-- JOIN quest_general q ON q.quest_id=pl.quest_id
WHERE profession_limitation_id IN (SELECT DISTINCT profession_id FROM profession)
AND pl.quest_id IN (".implode(',', $quests).");";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "INSERT INTO $tableName (quest_id, profession_id) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	$sql .= '(';
	$sql .= $row['quest_id'];
	$sql .= ', ';
	$sql .= $row['profession_limitation_id'];
	$sql .= ')';
}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

// echo $sql;

delAndInsert($conn3, $tableName, $sql);


/**
 * quest_prerequisite_quests
 * formerly prequest
 */

$tableName = 'quest_prerequisite_quests';

$sql2 = "SELECT * FROM prequest WHERE prequest_id IN (".implode(',', $quests).")
AND quest_id IN (".implode(',', $quests).");";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "INSERT INTO $tableName (quest_id, prerequisite_quest_id) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	$sql .= '(';
	$sql .= $row['quest_id'];
	$sql .= ', ';
	$sql .= $row['prequest_id'];
	$sql .= ')';
}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

// echo $sql;

delAndInsert($conn3, $tableName, $sql);


/**
 * quest_items
 * formerly quest_item
 */

$tableName = 'quest_items';
// Watch for FK violation on item_id, I'm not checking this at the moment.
$sql2 = "SELECT * FROM quest_item WHERE quest_id IN (".implode(',', $quests).") ORDER BY quest_id, item_id;";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "INSERT INTO $tableName (quest_id, item_id, item_amount, answer_seq) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	$sql .= '(';
	$sql .= $row['quest_id'];
	$sql .= ', ';
	$sql .= $itemIds[$row['item_id']];
	$sql .= ', ';
	$sql .= $row['item_amount'];
    $sql .= ', ';
    // Default answer_seq to 1
    $sql .= 1;
	$sql .= ')';
}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

// echo $sql;

delAndInsert($conn3, $tableName, $sql);


/**
 * quest_reward_items
 * formerly reward_item
 */

$tableName = 'quest_reward_items';
// Watch for FK violation on item_id, I'm not checking this at the moment.
$sql2 = "SELECT * FROM reward_item WHERE quest_id IN (".implode(',', $quests).");";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "INSERT INTO $tableName (quest_id, item_id, item_amount) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	$sql .= '(';
	$sql .= $row['quest_id'];
	$sql .= ', ';
	$sql .= $itemIds[$row['item_id']];
	$sql .= ', ';
	$sql .= $row['item_amount'];	
	$sql .= ')';
}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

// echo $sql;

delAndInsert($conn3, $tableName, $sql);


/**
 * quest_reward_items
 * formerly reward_item
 */

$tableName = 'quest_tools';
// Watch for FK violation on item_id, I'm not checking this at the moment.
$sql2 = "SELECT * FROM quest_tool WHERE quest_id IN (".implode(',', $quests).");";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "INSERT INTO $tableName (quest_id, item_id, item_amount) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	$sql .= '(';
	$sql .= $row['quest_id'];
	$sql .= ', ';
	$sql .= $itemIds[$row['item_id']];
	$sql .= ', ';
	$sql .= $row['item_amount'];	
	$sql .= ')';
}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement
// echo $sql;
delAndInsert($conn3, $tableName, $sql);



// Clean up quest result data before conversion to new format
$sql = "UPDATE quests SET result=NULL WHERE result LIKE '%NONE%';";
$stmt = $conn3->prepare($sql); $stmt->execute();

// Update cloze quests (old_result_type_id 4) to the new format with variables table.
$sql = "SELECT id, old_result_type_id, result FROM quests WHERE result LIKE '%A#$#%' OR result LIKE '%X#$#%';";
$rows = $conn3->query($sql)->fetchAll();
// Loop through query results and parse the var_names and var_values from each then insert into the
// quest_variables table
foreach($rows as $row) {
    // parse result which has format like
    // A#$#cousin$#$B#$#tall$#$C#$#busy$#$D#$#breakfast$#$E#$#dinner$#$F#$#fast
    // (variables could be any string, technically)
    $arr = mb_split("(#\\$#)|(\\$#\\$)", $row['result']);
    $questId = $row['id'];
    $varName = null;
    $varValue = null;
    //    echo '<span>Updating Quest '.$questId.' variables.</span>';
    foreach($arr as $el) {
        if (is_null($varName)) {
            $varName = $el;
        } else {
            $varValue = $el;
            // else we insert into quest_answers
            $sql = "INSERT INTO quest_answers (quest_id, name, answer_string) VALUES (";
            $sql.= $questId.", ".$conn3->quote($varName).", ".$conn3->quote($varValue).");";
            $stmt = $conn3->prepare($sql);
            $stmt->execute();
            $varName = null;
        }
    }
}

// Here I update all those records to NULL
$sql = "UPDATE quests SET result=NULL WHERE result LIKE '%A#$#%' OR result LIKE '%X#$#%';";
$stmt = $conn3->prepare($sql); $stmt->execute();

echo '<p class="text-green-500 dark:text-green-400">Updating quest_items</p>';
// Select all quests that use items with boolean values
$sql = "SELECT id, quest_result_type_id, old_result_type_id, result FROM quests WHERE (result LIKE '999%' OR result LIKE '3000%') AND old_result_type_id NOT IN (6,14,11)";
$rows = $conn3->query($sql)->fetchAll();
foreach($rows as $row) {
    // parse results into items and values
    $arr = preg_split("/[;,]/", $row['result']);
    $oldId = null;
    $varValue = null;
    //    echo '<span>Updating Quest '.$questId.' items: '.$row['result'].'.</span>';

    foreach($arr as $el) {
        if (is_null($oldId)) {
            $oldId = $el;
        } else {
            if (preg_match('/.*(false)|(unchecked).*/', $el)) $varValue = 0;
            else $varValue=1;
            // else we update quest_items
            if (!isset($itemIds[$oldId])) {
                echo '<p class="deleted">Item '.$oldId.' is missing, will not be added to quest items.</p>';
            }
            $sql = "UPDATE quest_items SET answer_bool=".$varValue;
            $sql.= " WHERE quest_id=".$row['id']." AND item_id=".(isset($itemIds[$oldId])?$itemIds[$oldId]:0);
            $sql.= ";";
            $stmt = $conn3->prepare($sql);
            $stmt->execute();
            $oldId = null;
            // echo '<p>'.$sql.'</p>';
        }
    }
}

// Set results for boolean items to NULL
$sql = "UPDATE quests SET result=NULL WHERE (result LIKE '999%' OR result LIKE '3000%') AND old_result_type_id NOT IN (6,14,11)";
$stmt = $conn3->prepare($sql); $stmt->execute();

// Now update items for sorts (result_type_id 6). In this case it is the order that is important.
$sql = "SELECT id, old_result_type_id, result FROM quests WHERE old_result_type_id = 6 AND result IS NOT NULL;";
$rows = $conn3->query($sql)->fetchAll();
foreach($rows as $row) {
    $arr = preg_split('/\D+/', $row['result']);
    $index = 0;
    foreach($arr as $oldId) {
        $sql = "UPDATE quest_items SET answer_seq=".++$index;
        $sql.= " WHERE quest_id=".$row['id']." AND item_id=".(isset($itemIds[$oldId])?$itemIds[$oldId]:0);
        $stmt = $conn3->prepare($sql);
        $stmt->execute();
    }
}
// Set results for boolean items to NULL
$sql = "UPDATE quests SET result=NULL WHERE old_result_type_id = 6;";
$stmt = $conn3->prepare($sql); $stmt->execute();


// Clean up calculation variables and put them into the quest_answers table.
$sql = "SELECT id, old_result_type_id, result FROM quests WHERE result IS NOT NULL AND result LIKE 'X=%';";
$rows = $conn3->query($sql)->fetchAll();
foreach($rows as $row) {
    $arr = preg_split('/,/', $row['result']);
    foreach($arr as $var) {
        $parts = preg_split('/=/', $var);
        $sql = "INSERT INTO quest_answers (quest_id, name, answer_string) VALUES (";
        // Replace placeholders with just the letter, as the math parser will handle replacing that with the valid variable.
        $sql.= $row['id'].", ".$conn3->quote($parts[0]).", ".$conn3->quote(preg_replace('/\^##([abcd])##\^/', '\1', $parts[1])).");";
        $stmt = $conn3->prepare($sql);
        $stmt->execute();
    }
}

// Remove those calculation results from the quests table
$sql = "UPDATE quests SET result = NULL WHERE result LIKE 'X=%';";
$stmt = $conn3->prepare($sql); $stmt->execute();

/* Those calculation variables should now be converted from letters (a, b, c) to ranges, and those ranges removed from the quest content field.
These values look like this: ^##a===-2,4##^ ^##b===-1,3##^

At this point I think these should just be fixed manually.
Change the format from
(^##a##^*^##b##^*^##c##^)
into something like :
(^##1,4##^*^##-1,10##^*^##3##^)

That way the ranges are contained right within the variable.
Since there are only ten, these could be done by hand.

*/

// Clean up item calculation results. Sigh, all for one lousy example quest
$sql = "SELECT id, result FROM quests WHERE old_result_type_id=11";
$rows = $conn3->query($sql)->fetchAll();
foreach($rows as $row) {
    $parts = preg_split('/#\\$#/', $row['result']);
    $oldId = $parts[0];
    $varValue = $parts[1];
    $sql = "UPDATE quest_items SET answer_string=".$conn3->quote($varValue);
    $sql.= " WHERE quest_id=".$row['id']." AND item_id=".(isset($itemIds[$oldId])?$itemIds[$oldId]:0);
    $stmt = $conn3->prepare($sql);
    $stmt->execute();
}
// Remove those calculation results from the quests table
$sql = "UPDATE quests SET result = NULL WHERE old_result_type_id=11";
$stmt = $conn3->prepare($sql); $stmt->execute();

// For any remaining results, move the result value to the quest_answers table
$sql = "SELECT id, name, old_result_type_id, result FROM quests WHERE character_length(trim(result)) >= 2";
$rows = $conn3->query($sql)->fetchAll();
foreach($rows as $row) {
    $sql = "INSERT INTO quest_answers (quest_id, name, answer_string) VALUES (";
    $sql.= $row['id'].", ".$conn3->quote('A').", ".$conn3->quote($row['result']).");";
    $stmt = $conn3->prepare($sql);
    $stmt->execute();
}

// Clean up quests that have answers that were successfully converted.
$sql = "UPDATE quests SET result = NULL WHERE old_result_type_id IN (5, 8, 9, 15, 16, 17, 18) AND id NOT IN (192)";
$stmt = $conn3->prepare($sql); $stmt->execute();

// Look for variables in the original format and move them into the quest_variables table.
$sql = "SELECT id, content FROM quests WHERE content LIKE '%^##%'";
$rows = $conn3->query($sql)->fetchAll();
foreach($rows as $row) {
    // Capture all the variables with this regex
    preg_match_all('/\^##(\w)===(-?\d+),(-?\d+)##\^/', $row['content'], $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        // I know a bulk insert would be more efficient, but there aren't very many of these (maybe 15)
        $sql = "INSERT INTO quest_variables (quest_id, var_name, min_value, max_value) VALUES (";
        $sql.= $row['id'].", ".$conn3->quote($match[1]).", ".$conn3->quote($match[2]).", ".$conn3->quote($match[3]).");";
        echo '<p>'.$sql.'</p>';
        $stmt = $conn3->prepare($sql);
        $stmt->execute();
    }
}

// Quest result type 13 could be cleaned up manually - store the coordinate equations in the quest result_x and quest result_y fields.

/* Quest result type 14 notes
These quests (in theory) require a quest tool that is used at a certain coordinate to find the specified item.
In this case, these values should be associated with the quest tool.
I probably need to create a quest_tool_items table that has the items that can be found with the quest tool
and the coordinates that these items can be found at. Those coordinate could also be equations.

This currently only applies to the following six quests, so these could be cleaned up manually.
98, 106, 107 (two items), 144, 366, 446

*/

echo '<p class="delete">Quest_result_types 13 and 14 have to be manually adjusted to the new format.</p>';

// I'll help get the quest_result_type 14 conversion started by creating the quest_tool_location records
// with the correct items, since the item IDs have changed and I want to save some hassle
// Clean up item calculation results. Sigh, all for one lousy example quest
$sql = "SELECT q.id, qt.id AS quest_tool_id, q.result FROM quests q
        JOIN quest_tools qt ON q.id=qt.quest_id
        WHERE old_result_type_id=14";
$rows = $conn3->query($sql)->fetchAll();
foreach($rows as $row) {
    $arr = mb_split("(#\\$#)|(\\$#\\$)", $row['result']);
    // var_dump($arr);
    $oldId = null;
    $locationData = null;
    foreach($arr as $el) {
        if (is_null($oldId)) {
            $oldId = $el;
        } else {
            $locationData = $el;
            // else we insert into quest_tool_locations
            if (isset($itemIds[$oldId])) {
                $sql = "INSERT INTO quest_tool_locations (quest_tool_id, item_id, success_message) VALUES (";
                $sql.= $row['quest_tool_id'].", ".$itemIds[$oldId].", ".$conn3->quote($locationData).");";
                echo '<p>'.$sql.'</p>';
                $stmt = $conn3->prepare($sql);
                $stmt->execute();
            }
            $oldId = null;
        }
    }
}
// Remove those calculation results from the quests table
//$sql = "UPDATE quests SET result = NULL WHERE old_result_type_id=14";
//$stmt = $conn3->prepare($sql); $stmt->execute();


// Take variables from item descriptions and insert them into the item_variables table.
$sql = "SELECT id, description FROM items WHERE description LIKE '%^##%'";
$rows = $conn3->query($sql)->fetchAll();
foreach($rows as $row) {
    // Capture all the variables with this regex
    preg_match_all('/\^##(\w)===(-?\d+),(-?\d+)##\^/', $row['content'], $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        // Bulk insert would be more efficient, but there aren't very many of these
        $sql = "INSERT INTO item_variables (item_id, var_name, min_value, max_value) VALUES (";
        $sql.= $row['id'].", ".$conn3->quote($match[1]).", ".$conn3->quote($match[2]).", ".$conn3->quote($match[3]).");";
        echo '<p>'.$sql.'</p>';
        $stmt = $conn3->prepare($sql);
        $stmt->execute();
    }
}



/**
 * npc_items
 * formerly npc_own_item
 */

$tableName = 'npc_items';
// Watch for FK violation on item_id, I'm not checking this at the moment.
// Convert any 0s to NULL, otherwise FK violation would occur
$sql2 = "SELECT * FROM npc_own_item WHERE (npc_id IN (SELECT npc_id FROM npc) AND npc_id IS NOT NULL) AND (quest_id IN (".implode(',', $quests).") OR quest_id=0);";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "INSERT INTO $tableName (quest_id, npc_id, item_id) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	$sql .= '(';
	$sql .= ($row['quest_id']==0)?'NULL':$row['quest_id'];
	$sql .= ', ';
	$sql .= ($row['npc_id']==0)?'NULL':$row['npc_id'];
	$sql .= ', ';
	$sql .= $itemIds[$row['item_id']];
	$sql .= ')';
}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement
// echo $sql;
delAndInsert($conn3, $tableName, $sql);



/**
 * player_quests
 * formerly quest_player
 * This is another one that uses epoch timestamps instead of datetime fields...
 * I will convert them for now, but Maiga feels that using ints offers better interoperability
 * Since you aren't typically doing a lot of date math in PHP with these, you just maybe need to know
 * a time difference or something.
 * 
 * Currently I will switch to datetime, but can switch back if I encounter problems.
 */
$tableName = 'player_quests';
$sql2 = "SELECT playerid, quest_id, max(pick_up_time) as pick_up_time, max(last_report_time) AS last_report_time FROM quest_player
WHERE playerid IN (SELECT playerid FROM players) AND quest_id IN (".implode(',', $quests).")
AND playerid IN (SELECT userid FROM users WHERE lower(username) IN (SELECT lower(username) as username FROM users GROUP BY lower(username) HAVING COUNT(*) = 1))
GROUP BY playerid, quest_id;";


$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "INSERT INTO $tableName (player_id, quest_id, pickup_time, success_time) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	$sql .= '(';
	$sql .= $row['playerid'];
	$sql .= ', ';
	$sql .= $row['quest_id'];
	$sql .= ', ';
	// Set unreported quests as NULL instead of zero
	if ($row['pick_up_time']==0) $sql .= 'NULL';
	else $sql .= $conn3->quote(date("Y-m-d H:i:s", $row['pick_up_time']));
	$sql .= ', ';
	$sql .= ($row['last_report_time'] == 0)?'NULL':$conn3->quote(date("Y-m-d H:i:s", $row['last_report_time']));
	$sql .= ')';
}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement
// echo $sql;
delAndInsert($conn3, $tableName, $sql);


/**
 * player_quest_variables
 * formerly quest_player_var
 * This table only has 31 rows
 */
$tableName = 'player_quest_variables';
$sql2 = "SELECT quest_id, playerid, varname, varvalue FROM quest_player_var
WHERE playerid IN (SELECT playerid FROM players) AND quest_id IN (".implode(',', $quests).");";

$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "INSERT INTO $tableName (player_id, quest_id, var_name, var_value) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	$sql .= '(';
	$sql .= $row['playerid'];
	$sql .= ', ';
	$sql .= $row['quest_id'];
	$sql .= ', ';
	$sql .= $conn3->quote($row['varname']);
	$sql .= ', ';
	$sql .= $conn3->quote($row['varvalue']);
	$sql .= ')';
}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement
// echo $sql;
delAndInsert($conn3, $tableName, $sql);


/**
 * quest_events is already part of the schema script, only contains four values,
 * presumably these won't change much.
 */


/**
 * player_quests_log (formerly player_quest_log) keeps track of all player's history with quests. There are no FK constraints
 * (except on the quest_events column) so we can see history for players that were deleted.
 * Nonetheless, I'll only import rows for quests and players that currently exist
 */
$tableName = 'player_quests_log';
$sql2 = "SELECT * FROM player_quest_log
WHERE playerid IN (SELECT playerid FROM players) AND quest_id IN (".implode(',', $quests).");";

$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "INSERT INTO $tableName (player_id, quest_id, event_datetime, quest_event_id, npc_id, map_id, x, y, ratio, quest_result) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	$sql .= '(';
	$sql .= $row['playerid'];
	$sql .= ', ';
	$sql .= $row['quest_id'];
	$sql .= ', ';
	$sql .= $conn3->quote(date("Y-m-d H:i:s", $row['happen_at']));
	$sql .= ', ';
	$sql .= $conn3->quote($row['event_id']);
	$sql .= ', ';
	$sql .= $conn3->quote($row['npc_id']);
	$sql .= ', ';
	$sql .= getNewMapID($row['map_type_name'], $row['map_id']);
	$sql .= ', ';
	$sql .= $row['x'];
	$sql .= ', ';
	$sql .= $row['y'];
	$sql .= ', ';
	$sql .= $conn3->quote($row['ratio']);
	$sql .= ', ';
	$sql .= $conn3->quote($row['quest_result']);
	$sql .= ')';
}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement
// echo $sql;
delAndInsert($conn3, $tableName, $sql);


/**
 * This takes those massive timestamps and makes them into precise datetime values
 * @param int $t -- timeStamp in ms
 */
function formatMicroTime($t) {
    $t = $t/1000;
    $micro = sprintf("%06d",($t - floor($t)) * 1000000);
    $d = new DateTime( date('Y-m-d H:i:s.'.$micro, $t) );
    return $d->format("Y-m-d H:i:s.u"); // note at point on "u"
}

/**
 * key_log tracks keypresses during certain kinds of quest tasks
 * Formerly keystore
 */
$tableName = 'key_log';
$sql2 = "SELECT * FROM keystore
WHERE playerid IN (SELECT playerid FROM players) AND quest_id IN (0,".implode(',', $quests).");";

$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "INSERT INTO $tableName (id, player_id, quest_id, to_player_id, map_id, x, y, keycode, down_time, up_time) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	$sql .= '(';
	$sql .= $row['key_id'];
	$sql .= ', ';
	$sql .= $row['playerid'];
	$sql .= ', ';
    $sql .= ($row['quest_id']==0)?'NULL':$row['quest_id'];
	$sql .= ', ';
    $sql .= ($row['item_id']==0)?'NULL':$row['item_id'];
	$sql .= ', ';
	$sql .= getNewMapID($row['map_type_name'], $row['map_id']);
	$sql .= ', ';
	$sql .= $row['x'];
	$sql .= ', ';
	$sql .= $row['y'];
	$sql .= ', ';
	$sql .= $row['keycode'];
	$sql .= ', ';
    $sql .= $conn3->quote(formatMicroTime($row['down_time']));
	$sql .= ', ';
    $sql .= $conn3->quote(formatMicroTime($row['up_time']));
	$sql .= ')';
}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement
// echo $sql;
delAndInsert($conn3, $tableName, $sql);


/**
 * money_flow_log keeps track of monetary transactions
 * Formerly money_flow
 */
$tableName = 'money_flow_log';
$sql2 = "SELECT * FROM money_flow
WHERE playerid IN (SELECT playerid FROM players) AND quest_id IN (0,".implode(',', $quests).");";

$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "INSERT INTO $tableName (player_id, npc_id, payout, reserve, payout_datetime, quest_id) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	$sql .= '(';
	$sql .= $row['playerid'];
	$sql .= ', ';
    $sql .= ($row['npc_id']==0)?'NULL':$row['npc_id'];
	$sql .= ', ';
	$sql .= $row['payout'];
	$sql .= ', ';
	$sql .= $row['reserve'];
	$sql .= ', ';
	$sql .= $conn3->quote(date("Y-m-d H:i:s", $row['happen_at']));
	$sql .= ', ';
	$sql .= ($row['quest_id']==0)?'NULL':$row['quest_id'];
	$sql .= ')';
}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement
// echo $sql;
delAndInsert($conn3, $tableName, $sql);



/**
 * quest_chain_type
 * This has been removed

$tableName = 'quest_chain_type';

$sql2 = "SELECT * FROM quest_chain_type";

$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "INSERT INTO $tableName (id, sequence_number, quest_type_id) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	$sql .= '(';
	$sql .= $row['quest_chain_type_id'];
	$sql .= ', ';
	$sql .= $row['sequence_number'];
	$sql .= ', ';
	$sql .= $row['quest_type_id'];
	$sql .= ')';
}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement
// echo $sql;
delAndInsert($conn3, $tableName, $sql);
 */


/**
 * protect_detail
 * There isn't much in this one, it was intended for a future quest_type.
 * This has been removed

$tableName = 'protect_detail';

$sql2 = "SELECT * FROM protect_detail WHERE quest_id IN (".implode(',', $quests).");";

$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "INSERT INTO $tableName (quest_id, map_id, building_id, y_top, x_right, y_bottom, x_left, arrival_time, protect_time, punish_xp, punish_currency) VALUES";
foreach($rows2 as $row) {
	if (getNewMapID($row['map_name'], $row['map_id']) == 'NULL') continue;
	if ($ct++ > 0) $sql .= ", ";
	$sql .= '(';
	$sql .= $row['quest_id'];
	$sql .= ', ';
	$sql .= getNewMapID($row['map_name'], $row['map_id']);
	$sql .= ', ';
	$sql .= ($row['building_id']==0)?'NULL':$row['building_id'];
	$sql .= ', ';
	$sql .= $row['protect_top'];
	$sql .= ', ';
	$sql .= $row['protect_right'];
	$sql .= ', ';
	$sql .= $row['protect_bottom'];
	$sql .= ', ';
	$sql .= $row['protect_left'];
	$sql .= ', ';
	$sql .= $row['protect_arrival_time'];
	$sql .= ', ';
	$sql .= $row['protect_time'];
	$sql .= ', ';
	$sql .= $row['punish_xp'];
	$sql .= ', ';
	$sql .= $row['punish_currency'];
	$sql .= ')';
}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement
// echo $sql;
delAndInsert($conn3, $tableName, $sql);
 */




// These can go near the end as they are unimportant for game functionality and there are many rows in these tables.

/**
 * chat_log
 * This will only import actual messages writted by people to save some space.
 */
$tableName = 'chat_log';
// Only (mostly) selecting chats that are actual messages typed by humans. System generated info is being ignored.
$sql2 = "SELECT * FROM chat WHERE message NOT SIMILAR TO 'arrives at \(\d+,\d+\)' AND message NOT SIMILAR TO '\d{1,5}';";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
$ct = 0;
$sql = "SET sql_mode='NO_AUTO_VALUE_ON_ZERO'; ";
$sql .= "INSERT IGNORE INTO $tableName (id, player_id_src, player_id_tgt, message_time, message, map_id, x, y) VALUES";
foreach($rows2 as $row) {
	if ($ct++ > 0) $sql .= ", ";
	// Construct insert statement for record into new MySQL DB.
	$sql .= "(".$row['chatid'];
	$sql .= ", ";
	$sql .= $conn3->quote($row['playeridsrc']);
	$sql .= ", ";
    $sql .= ($row['playeridtgt']==0)?'NULL':$row['playeridtgt'];
	$sql .= ", ";
	$sql .= $conn3->quote(date("Y-m-d H:i:s", $row['messagetime']));
	$sql .= ", ";
	$sql .= $conn3->quote($row['message']);
	$sql .= ", ";
	$sql .= getNewMapID($row['map_type_name'], $row['map_id']);
	$sql .= ", ";
	$sql .= $conn3->quote($row['x']);
	$sql .= ", ";
	$sql .= $conn3->quote($row['y']);
	$sql .= ")";
	
	if ($debug == true) {
		echo '<tr>
			<td class="status">&check;</td>
			<td>'.$row["chatid"].'</td>
			<td>'.$row['playeridsrc'].'</td>
			<td>'.$row['playeridtgt'].'</td>
		</tr>';
	}

}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement

delAndInsert($conn3, $tableName, $sql);


// make a map of player_action_types to their ids

// associative array mapping actions to their IDs
$actionIds = $conn3->query("SELECT name, id FROM player_action_types;")->fetchAll(PDO::FETCH_KEY_PAIR);

/**
 * player_actions_log - formerly wandering_behaviour - logs many player actions
 */
$tableName = 'player_actions_log';
// Move seems entirely redundant, move_with_energy or move_without_energy_or_skill will also be recorded for the action
$sql2 = "SELECT * FROM wandering_behaviour WHERE note!='move' ORDER BY wandering_behaviour_id";
$rows2 = $conn2->query($sql2)->fetchAll();

echo '<table><tr><td colspan="20"><h3>'.$tableName.'</h3></td></tr>';
resetTable($conn3, $tableName);
$ct = 0;
$sql = "";
foreach($rows2 as $row) {
	// This is so big that it will have to be done in several batches or the system runs out of memory.
	if ($ct++ % 2000 == 0) {
		if (strlen($sql) > 1) {
			$sql.=";";
			insert($conn3, $tableName, $sql);
		}
		$sql = "INSERT INTO $tableName (id, user_id, player_id, map_id, x, y, event_time, player_action_type_id) VALUES ";
	} else {
		$sql .= ", ";
	}

	// Construct insert statement for record into new MySQL DB.
	$sql .= "(".$row['wandering_behaviour_id'];
	$sql .= ", ";
	$sql .= $conn3->quote($row['playerid']);
	$sql .= ", ";
    $sql .= $conn3->quote($row['playerid']);
    $sql .= ", ";
	$sql .= getNewMapID($row['map_type_name'], $row['map_id']);
	$sql .= ", ";
	$sql .= $conn3->quote($row['x']);
	$sql .= ", ";
	$sql .= $conn3->quote($row['y']);
	$sql .= ", ";
	$sql .= $conn3->quote(date("Y-m-d H:i:s", $row['wandering_time']));
	$sql .= ", ";
	$sql .= $actionIds[$row['note']];
	$sql .= ")";	


}
echo '<tr><td colspan="20">'.$ct.' records to insert.</td></tr>';
echo '</table>';
$sql.=";"; // End SQL statement
insert($conn3, $tableName, $sql);

echo '<p>Creating TEST PLAYER</p>';
$sql="SET sql_mode='NO_AUTO_VALUE_ON_ZERO';";
$sql.="INSERT INTO players (id,user_id,name, birthplace_id,visible,map_id,x,y,last_action_time,map_enter_time,
     health,health_max,money,experience,repute_radius,movement,travel,attack,defence,bonus_point,race_id,gender_id,is_super)
    VALUES(0,0,'TEST PLAYER',1,0,64,9,0,NOW(),NOW(),100, 100, 100,0, 0,999999,0,10,10,0,1,1,1);
    INSERT INTO player_base_attributes (player_id, attribute_id, attribute_value) VALUES
    (0, 1, 18), (0, 2, 18), (0, 3, 18), (0, 4, 18), (0, 5, 18), (0, 6, 18);
    INSERT INTO player_stats (player_id, stat_id, stat_value) SELECT * FROM player_stats_initial_view WHERE player_id = 0;";
$stmt = $conn3->prepare($sql); $stmt->execute();

?>
</body>
</html>