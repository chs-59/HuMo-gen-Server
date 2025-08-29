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

$place_qry = "(SELECT pers_birth_place as place_order FROM humo_persons WHERE pers_tree_id='" . $tree_id . "' AND pers_birth_place LIKE '" . $searchstring . "' GROUP BY pers_birth_place)
    UNION (SELECT pers_bapt_place as place_order FROM humo_persons WHERE pers_tree_id='" . $tree_id . "' AND pers_bapt_place LIKE '" . $searchstring . "' GROUP BY pers_bapt_place)
    UNION (SELECT pers_death_place as place_order FROM humo_persons WHERE pers_tree_id='" . $tree_id . "' AND pers_death_place LIKE '" . $searchstring . "' GROUP BY pers_death_place)
    UNION (SELECT pers_buried_place as place_order FROM humo_persons WHERE pers_tree_id='" . $tree_id . "' AND pers_buried_place LIKE '" . $searchstring . "' GROUP BY pers_buried_place)";
$place_qry .= " UNION (SELECT fam_relation_place as place_order FROM humo_families WHERE fam_tree_id='" . $tree_id . "' AND fam_relation_place LIKE '" . $searchstring . "' GROUP BY fam_relation_place)
    UNION (SELECT fam_marr_notice_place as place_order FROM humo_families WHERE fam_tree_id='" . $tree_id . "' AND fam_marr_notice_place LIKE '" . $searchstring . "' GROUP BY fam_marr_notice_place)
    UNION (SELECT fam_marr_place as place_order FROM humo_families WHERE fam_tree_id='" . $tree_id . "' AND fam_marr_place LIKE '" . $searchstring . "' GROUP BY fam_marr_place)
    UNION (SELECT fam_marr_church_notice_place as place_order FROM humo_families WHERE fam_tree_id='" . $tree_id . "' AND fam_marr_church_notice_place LIKE '" . $searchstring . "' GROUP BY fam_marr_church_notice_place)
    UNION (SELECT fam_div_place as place_order FROM humo_families WHERE fam_tree_id='" . $tree_id . "' AND fam_div_place LIKE '" . $searchstring . "' GROUP BY fam_div_place)";
$place_qry .= "UNION (SELECT address_place as place_order FROM humo_addresses WHERE address_tree_id='" . $tree_id . "' AND address_place LIKE '" . $searchstring . "' GROUP BY address_place)
    UNION (SELECT event_place as place_order FROM humo_events WHERE event_tree_id='" . $tree_id . "' AND event_place LIKE '" . $searchstring . "' GROUP BY event_place)
    UNION (SELECT source_place as place_order FROM humo_sources WHERE source_tree_id='" . $tree_id . "' AND source_place LIKE '" . $searchstring . "' GROUP BY source_place)
    UNION (SELECT connect_place as place_order FROM humo_connections WHERE connect_tree_id='" . $tree_id . "' AND connect_place LIKE '" . $searchstring . "' GROUP BY connect_place)";
$place_qry .= ' ORDER BY place_order';
$places = $dbh->query($place_qry);

//$places = $dbh->query($query);
$results = array();
while (@$resultDb = $places->fetch(PDO::FETCH_OBJ)) {
    $results[] = $resultDb->place_order;
//var_dump($resultDb);
}

echo json_encode($results);