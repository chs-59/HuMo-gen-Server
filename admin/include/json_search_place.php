<?php
session_start();
if (!$_SESSION['admin_tree_id']) { exit; }
$tree_id = $_SESSION['admin_tree_id'];

// Debug
///*
error_reporting(E_ALL);
ini_set('display_errors', 1);
//*/

include_once(__DIR__ . "/../../include/db_login.php"); // *** Database login ***
include_once(__DIR__ . "/../../include/db_functions_cls.php");
if (isset($dbh)) {
    $db_functions = new db_functions($dbh);
}
include_once(__DIR__ . "/../../include/person_cls.php");
include(__DIR__ . '/editor_cls.php');
include(__DIR__ . '/../../languages/language.php');
include(__DIR__ . '/../../include/language_date.php');
include(__DIR__ . '/../../include/safe.php');

$editor_cls = new editor_cls;

$searchstring = '';
if (isset($_REQUEST['term']) && $_REQUEST['term'] != '') {
    $searchstring = '%' . safe_text_db( $_REQUEST['term'] ) . '%';
}
else {
    exit;
}


$query = "(SELECT pers_birth_place as place_order FROM humo_persons
    WHERE pers_tree_id='" . $tree_id . "' AND pers_birth_place LIKE '" . $searchstring . "' GROUP BY place_order)";

$query .= " UNION (SELECT pers_bapt_place as place_order FROM humo_persons
    WHERE pers_tree_id='" . $tree_id . "' AND pers_bapt_place LIKE '" . $searchstring . "' GROUP BY place_order)";

//$query.= " UNION (SELECT pers_place_index as place_order FROM humo_persons
//	WHERE pers_tree_id='".$tree_id."' AND pers_place_index LIKE '".$search."' GROUP BY place_order)";

$query .= " UNION (SELECT event_place as place_order FROM humo_events
    WHERE event_tree_id='" . $tree_id . "' AND event_place LIKE '" . $searchstring . "' GROUP BY place_order)";

$query .= " UNION (SELECT pers_death_place as place_order FROM humo_persons
    WHERE pers_tree_id='" . $tree_id . "' AND pers_death_place LIKE '" . $searchstring . "' GROUP BY place_order)";

$query .= " UNION (SELECT pers_buried_place as place_order FROM humo_persons
    WHERE pers_tree_id='" . $tree_id . "' AND pers_buried_place LIKE '" . $searchstring . "' GROUP BY place_order)";

$query .= ' ORDER BY place_order';
$places = $dbh->query($query);
$results = array();
while (@$resultDb = $places->fetch(PDO::FETCH_OBJ)) {
    $results[] = $resultDb->place_order;
//var_dump($resultDb);
}

echo json_encode($results);