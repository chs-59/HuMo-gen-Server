<?php
session_start();
$url = urldecode($_SERVER['QUERY_STRING']);
if ($url == 'ping') {echo 'pong'; exit;}
// skip test for logo and favicon
if (in_array($url, array('logo.png', 'logo.jpg', 'favicon.ico'))) { 
    print_mediafile(__DIR__ . '/media/' . $url) ; 
    }
// session expired / no filename
if (!$_SESSION['tree_id'] || empty($url)) { print_mediafile(__DIR__ . '/images/missing-image.jpg'); }

//error_reporting(E_ALL);
//ini_set('display_errors', 1);

include_once(__DIR__ . "/include/db_login.php"); //Inloggen database.
include_once(__DIR__ . '/include/show_tree_text.php');
include_once(__DIR__ . "/include/db_functions_cls.php");
include_once(__DIR__ . "/include/person_cls.php");

$tree_prefix   = $_SESSION['tree_prefix'];
$tree_id       = $_SESSION['tree_id'];
$user_group_id = $_SESSION['user_group_id']; // not set on guest
$user_id       = $_SESSION['user_id'];       // not set on guest
if (str_contains( $_SERVER['HTTP_REFERER'], 'admin/') 
        && isset($_SESSION['admin_tree_id']) ) {
    $tree_id = $_SESSION['admin_tree_id'];
}
$db_functions = new db_functions($dbh);
$db_functions->set_tree_id($tree_id);
$tmpuserq = '';
if (empty($user_id)) {  $tmpuserq = "SELECT * FROM humo_users WHERE user_name='guest'";         }
else {                  $tmpuserq = "SELECT * FROM humo_users WHERE user_id='" . $user_id . "'";}
$usersql = $dbh->query($tmpuserq);
$userDb = $usersql->fetch(PDO::FETCH_OBJ);
if (empty($user_group_id)){ $user_group_id = $userDb->user_group_id; }

$groupsql = $dbh->query("SELECT * FROM humo_groups WHERE group_id='" . $user_group_id . "'");
$groupDb = $groupsql->fetch(PDO::FETCH_OBJ);
$datasql = $dbh->query("SELECT * FROM humo_trees WHERE tree_id='" . $tree_id . "'");
$dataDb = $datasql->fetch(PDO::FETCH_OBJ);
$tree_pict_path = $dataDb->tree_pict_path;
if (substr($tree_pict_path, 0, 1) === '|'                   // chopstick code
        || preg_match('/^media\//', $tree_pict_path)) {     // tree is subfolder of media
    $tree_pict_path = 'media/'; 
}
// free access to admin or editor of current tree
if ( ( isset($_SESSION['group_id_admin']) && $groupDb->group_admin === 'j' )  
    || $groupDb->group_edit_trees == $tree_id
    || $userDb->user_edit_trees == $tree_id ) {
    print_mediafile(__DIR__ . '/' . $tree_pict_path . $url);  
}
// some group and user settings for privacy  and exeptions
$user['user_access_ids'] = $userDb->user_access_ids; 
$user['group_privacy'] = $groupDb->group_privacy; 
$user['group_alive'] = $groupDb->group_alive; 
$user['group_alive_date'] = $groupDb->group_alive_date; 
$user['group_alive_date_act'] = $groupDb->group_alive_date_act; 
$user['group_filter_death'] = $groupDb->group_filter_death; 
$user['group_filter_death_act'] = $groupDb->group_filter_death_act; 
$user['group_death_date'] = $groupDb->group_death_date; 
$user['group_filter_pers_show'] = $groupDb->group_filter_pers_show; 
$user['group_filter_pers_show_act'] = $groupDb->group_filter_pers_show_act; 
$user['group_filter_pers_hide'] = $groupDb->group_filter_pers_hide; 
$user['group_filter_pers_hide_act'] = $groupDb->group_filter_pers_hide_act; 
$user['group_pers_hide_totally'] = $groupDb->group_pers_hide_totally; 
$user['group_pers_hide_totally_act'] = $groupDb->group_pers_hide_totally_act; 
$db_functions->set_accessids($user);

// access to media files blocked
if ($groupDb->group_pictures != 'j')  { 
    print_mediafile(__DIR__ . '/images/missing-image.jpg'); 
}

$picture_dbname = $url; // lookup picture in db. Url modified for thumbs
$picture_dbname = preg_replace( '/thumb_(.+\.\w{3})\.jpg/', '$1', $picture_dbname ); //delete thumb extensions for lookup (new style)
$picture_dbname = str_replace('thumb_', '', $picture_dbname ); //delete thumb extension for lookup (old style)

$qry = "SELECT * FROM humo_events WHERE event_tree_id='" . $tree_id . "' "
        . "AND (event_connect_kind='person' OR event_connect_kind='family' OR event_connect_kind='source') "
        . "AND event_connect_id NOT LIKE '' AND event_event='" . $picture_dbname . "'";

$media_qry = $dbh->query($qry);
while ($media_qryDb = $media_qry->fetch(PDO::FETCH_OBJ)) {    

    if (    $media_qryDb ) {   // pic in db
        $media_filename = __DIR__ . '/' . $tree_pict_path . $url;
        // person
        if ($media_qryDb && $media_qryDb->event_connect_kind === 'person') {
            $personDb = $db_functions->get_person( $media_qryDb->event_connect_id );
            $personcls = new person_cls($personDb);
            $privacy = $personcls->set_privacy($personDb);
            if ($personDb && !$privacy) { print_mediafile($media_filename); }
        // family
        } elseif ($media_qryDb && $media_qryDb->event_connect_kind === 'family') {
            $qry2 = "SELECT * FROM humo_families WHERE fam_gedcomnumber='" . $media_qryDb->event_connect_id . "'";
            $family_qry = $dbh->query($qry2);
            $family_qryDb2 = $family_qry->fetch(PDO::FETCH_OBJ);
            @$personmnDb2 = $db_functions->get_person($family_qryDb2->fam_man);
            $man_cls2 = new person_cls($personmnDb2);
            @$personmnDb3 = $db_functions->get_person($family_qryDb2->fam_woman);
            $woman_cls = new person_cls($personmnDb3);
            // *** Only use this picture if both man and woman have disabled privacy options ***
            if ($man_cls2->privacy == '' && $woman_cls->privacy == '') { print_mediafile($media_filename);} 
        // source
        } elseif ($media_qryDb && $media_qryDb->event_connect_kind === 'source') {
            $sourceDb = $db_functions->get_source($media_qryDb->event_connect_id);
            if ( $groupDb->group_sources == 'j' && $sourceDb->source_status == 'publish' ) { print_mediafile($media_filename);  } 
            if ( $groupDb->group_show_restricted_source == 'y' ) { print_mediafile($media_filename); }
        } 
    }
}
print_mediafile(__DIR__ . '/images/missing-image.jpg');
    
function print_mediafile ($filename) {
    if (!file_exists( $filename)) {
        $filename = __DIR__ . '/images/missing-image.jpg';        
    }
    session_abort();
    $content_type_header = mime_content_type($filename);
    $filesize = filesize($filename);
    header('Content-Type: ' . $content_type_header);
    header('Content-Disposition: inline; filename="' . $url . '"');
    header('Cache-Control: private, max-age=3600');
    header('Content-Length: '. filesize($filename));
    header('Pragma:');
    header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + (3600))); // 3600s cache
    readfile($filename);
    exit;
}
