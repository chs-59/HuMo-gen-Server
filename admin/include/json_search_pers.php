<?php
session_start();
if (!$_SESSION['admin_tree_id']) { exit; }
$tree_id = $_SESSION['admin_tree_id'];

// Debug
/*
error_reporting(E_ALL);
ini_set('display_errors', 1);
*/

include_once(__DIR__ . "/../../include/db_login.php"); // *** Database login ***
include_once(__DIR__ . "/../../include/db_functions_cls.php");
if (isset($dbh)) {
    $db_functions = new db_functions($dbh);
}
include_once(__DIR__ . "/../../include/person_cls.php");
include(__DIR__ . '/editor_cls.php');
include(__DIR__ . '/../../languages/language.php');
include(__DIR__ . '/../../include/language_date.php');

$editor_cls = new editor_cls;

// *** Read settings from database ***
$humo_option = array();
@$result_setting = $dbh->query("SELECT * FROM humo_settings");
while (@$row = $result_setting->fetch(PDO::FETCH_NUM)) {
    $humo_option[$row[1]] = $row[2];
}
if(!isset($humo_option["date_display"])) {
    $humo_option["date_display"] = 'eu';
}
$searchstring = '';
if (isset($_REQUEST['term']) && $_REQUEST['term'] != '') {
    $searchstring = $_REQUEST['term'];
}
else {
    exit;
}
$searcharr = explode(':::', $searchstring);
$searchstring = $searcharr[0];
if (isset($searcharr[1]) && $searcharr[1] != '') {
    $tree_id = $searcharr[1];
}
//
//echo json_encode(['label' => $searcharr[1]]);
//exit;
/*$testarr = array('Rosen', 'Tulplen', 'Nelken', 'Rosinen','Maschinen','Gardinen','Geranien');
$strg = '/' . $searchstring . '/i';
$results = preg_grep($strg, $testarr);
foreach ($results as $key => $value) {
    if ($value != false) { $result[] = $value; } 
}
*/
$result = array();

if ($searchstring != '') {
     // *** Replace space by % to find first AND lastname in one search "Huub Mons" ***
    $searchstring = str_replace(' ', '%', $searchstring);
    // *** In case someone entered "Mons, Huub" using a comma ***
    $searchstring = str_replace(',', '', $searchstring);
    //$person_qry= "SELECT *, CONCAT(pers_firstname,pers_prefix,pers_lastname) as concat_name
    $person_qry = "SELECT *
    FROM humo_persons
    WHERE pers_tree_id='" . $tree_id . "'
        AND (CONCAT(pers_firstname,REPLACE(pers_prefix,'_',' '),pers_lastname) LIKE '%" . $searchstring . "%'
        OR CONCAT(pers_lastname,REPLACE(pers_prefix,'_',' '),pers_firstname) LIKE '%" . $searchstring . "%'
        OR CONCAT(pers_lastname,pers_firstname,REPLACE(pers_prefix,'_',' ')) LIKE '%" . $searchstring . "%'
        OR CONCAT(REPLACE(pers_prefix,'_',' '), pers_lastname,pers_firstname) LIKE '%" . $searchstring . "%')
        ORDER BY pers_lastname, pers_firstname";
    $person_result = $dbh->query($person_qry);

    
}
$search = array("'", '"');
$replace = array("&#39;", '\"');
while ($person = $person_result->fetch(PDO::FETCH_OBJ)) {
    $bdate_arr = explode(" ", $person->pers_birth_date);
    //if(is_numeric(substr($bdate_arr[0],0,1))===false){ $dateprefix = $bdate_arr[0]." "; $dateself = substr($person->pers_birth_date,strpos($person->pers_birth_date," ")+1);}
    if (substr($bdate_arr[0], 0, 3) === "BEF" || substr($bdate_arr[0], 0, 3) === "AFT" || substr($bdate_arr[0], 0, 3) === "ABT" || substr($bdate_arr[0], 0, 3) === "BET") {
        $dateprefix = $bdate_arr[0] . " ";
        $dateself = substr($person->pers_birth_date, strpos($person->pers_birth_date, " ") + 1);
    } else {
        $dateself = $person->pers_birth_date;
        $dateprefix = "";
    }

    $ddate_arr = explode(" ", $person->pers_death_date);
    if (substr($ddate_arr[0], 0, 3) === "BEF" || substr($ddate_arr[0], 0, 3) === "AFT" || substr($ddate_arr[0], 0, 3) === "ABT" || substr($ddate_arr[0], 0, 3) === "BET") {
        $dateprefix2 = $ddate_arr[0] . " ";
        $dateself2 = substr($person->pers_death_date, strpos($person->pers_death_date, " ") + 1);
    } else {
        $dateself2 = $person->pers_death_date;
        $dateprefix2 = "";
    }

    $pgn = $person->pers_gedcomnumber;
    $ppf = str_replace($search, $replace, $person->pers_prefix);
    $pln = str_replace($search, $replace, $person->pers_lastname);
    $pfn = str_replace($search, $replace, $person->pers_firstname);
    $pbdp = $dateprefix;
    $pbd = $dateself;
    $pbp = str_replace($search, $replace, $person->pers_birth_place);
    $pddp = $dateprefix2;
    $pdd = $dateself2;
    $pdp = str_replace($search, $replace, $person->pers_death_place);
    $psx = $person->pers_sexe;
    $result[] = array('value' => $pgn,
                'label' => $editor_cls->show_selected_person($person) );
        //'[' . $pgn . '], ' . $ppf . ', ' . $pln . ', ' . $pfn . ', ' . $pbdp . ', ' . $pbd . ', ' . $pbp . ', ' . $pddp . ', ' . $pdd . ', ' . $pdp . ', ' . $psx);
//    $result[] = $editor_cls->show_selected_person($person);
            // '<a href="" onClick=\'return select_item2("' . $pgn . '","' . $ppf . '","' . $pln . '","' . $pfn . '","' . $pbdp . '","' . $pbd . '","' . $pbp . '","' . $pddp . '","' . $pdd . '","' . $pdp . '","' . $psx . '")\'>' . $editor_cls->show_selected_person($person) . '</a><br>';
}

echo json_encode($result);