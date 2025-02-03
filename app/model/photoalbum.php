<?php
class PhotoalbumModel
{
    public function get_show_pictures()
    {
        $show_pictures = 8; // *** Default value ***

        // Remark: setcookie is done in header.
        if (isset($_COOKIE["humogenphotos"]) && is_numeric($_COOKIE["humogenphotos"])) {
            $show_pictures = $_COOKIE["humogenphotos"];
        } elseif (isset($_SESSION['save_show_pictures']) && is_numeric($_SESSION['save_show_pictures'])) {
            $show_pictures = $_SESSION['save_show_pictures'];
        }
        if (isset($_POST['show_pictures']) && is_numeric($_POST['show_pictures'])) {
            $show_pictures = $_POST['show_pictures'];
            $_SESSION['save_show_pictures'] = $show_pictures;
        }
        if (isset($_GET['show_pictures']) && is_numeric($_GET['show_pictures'])) {
            $show_pictures = $_GET['show_pictures'];
            $_SESSION['save_show_pictures'] = $show_pictures;
        }
        return $show_pictures;
    }

    public function get_search_media()
    {
        // *** Photo search ***
        $search_media = '';
        if (isset($_SESSION['save_search_media'])) {
            $search_media = $_SESSION['save_search_media'];
        }
        if (isset($_POST['search_media'])) {
            $search_media = safe_text_db($_POST['search_media']);
            $_SESSION['save_search_media'] = $search_media;
        }
        if (isset($_GET['search_media'])) {
            $search_media = safe_text_db($_GET['search_media']);
            $_SESSION['save_search_media'] = $search_media;
        }
        return $search_media;
    }

    public function get_categories($dbh)
    {
        global $tree_id;
        global $selected_language;
        global $user;
        $hidden_cats_alltrees = json_decode($user['group_hide_photocat'], true);
        $hidden_cats = array(); // initialize empty array
        if (isset($hidden_cats_alltrees[$tree_id])) {
            $hidden_cats = $hidden_cats_alltrees[$tree_id];
        }
        $photoalbum['category'] = [];
        $photoalbum['category_language'] = [];
        //$photoalbum['category_enabled'] = [];
       // $photoalbum['category_id'] = [];

        // *** Check if photocat table exists ***
        $temp = $dbh->query("SHOW TABLES LIKE 'humo_mediacat'");
        if ($temp->rowCount()) {
            // *** Get array of categories ***
            $qry = $dbh->query("SELECT * FROM humo_mediacat WHERE mediacat_tree_id = '" . $tree_id . "' ORDER BY mediacat_order");
            // the table contains more than the default category (otherwise display regular photoalbum)
//            $result = $dbh->query($qry);
            $result_arr = $qry->fetchAll();
            foreach ($result_arr as $row) {
                if (!in_array($row['mediacat_name'], $hidden_cats)) {
                    $photoalbum['category'][] = $row['mediacat_name'];
                    $photoalbum['category_language'][$row['mediacat_name']] = json_decode($row['mediacat_language_names'], true)[$selected_language];
                    //$photoalbum['category_enabled'][$row['mediacat_name']] = true;
                   // $photoalbum['category_id'][$row['mediacat_name']] = $row['mediacat_id'];
                }
            }
            
        }
        return $photoalbum;
    }

    public function get_chosen_tab($category)
    {
        $chosen_tab = '';
        if (isset($category[0])) {$chosen_tab = $category[0]; }  
        if (isset($_SESSION['save_chosen_tab']) &&
                in_array($_SESSION['save_chosen_tab'], $category))
        {
            $chosen_tab = $_SESSION['save_chosen_tab'];
        }
        if (isset($_GET['select_category']) &&
                isset($category) &&
                in_array($_GET['select_category'], $category))
        {
            $chosen_tab = $_GET['select_category'];
            $_SESSION['save_chosen_tab'] = $chosen_tab;
        }
        $_SESSION['save_search_media'] = ''; // reset seach value 
        return $chosen_tab;
    }

    public function get_media_files($dbh, $tree_id, $db_functions, $chosen_tab, $search_media, $category)
    {
        global $user, $link_cls, $uri_path;
        $photoalbum['media_files'] = [];
        if (empty($chosen_tab))
        {
            return $photoalbum; // no categories set up
        }
        if (isset($_SESSION['categories'][$tree_id][$chosen_tab])) {
            return $_SESSION['categories'][$tree_id][$chosen_tab];
        }
        
        $default_cats = ['person' => 'persons', 'family' => 'families', 'source' => 'sources'];

        // *** Create an array of all pics with person_ids. Also check for OBJECT (Family Tree Maker GEDCOM file) ***
        $qry = "SELECT event_event, event_kind, event_connect_kind, event_connect_id, event_gedcomnr, event_categories, event_date, event_place, event_text FROM humo_events
            WHERE event_tree_id='" . $tree_id . "' AND (event_kind='picture' OR event_kind='object') AND (event_connect_kind='person' OR event_connect_kind='family' OR event_connect_kind='source')" . 
            " AND event_connect_id NOT LIKE '' ORDER BY event_event";
        $picqry = $dbh->query($qry);
        while ($picqryDb = $picqry->fetch(PDO::FETCH_OBJ)) {
            $temp_ckind = $default_cats[$picqryDb->event_connect_kind];
            $picname = $picqryDb->event_event;
            //echo $picname . '<br>';
            if (empty($picqryDb->event_categories)) {
                $pictcats = array();
            } else {
                $pictcats = explode(', ', $picqryDb->event_categories);
            }
            if ($chosen_tab != $temp_ckind &&
                    !in_array($chosen_tab, $pictcats))
            {
                continue; //file is not in selected category so skip current this loop
            }

            // *** Use search field (search for person) to show pictures ***
            if ($search_media) {
                $quicksearch = str_replace(" ", "%", $search_media);
                $querie = "SELECT pers_firstname, pers_prefix, pers_lastname FROM humo_persons
                    WHERE pers_tree_id='" . $tree_id . "' AND pers_gedcomnumber='" . $picqryDb->event_connect_id . "'
                    AND CONCAT(pers_firstname,REPLACE(pers_prefix,'_',' '),pers_lastname) LIKE '%$quicksearch%'";
                $persoon = $dbh->query($querie);
                $personDb = $persoon->fetch(PDO::FETCH_OBJ);
                if (!$personDb) {
                    continue;
                }
            }
            $picture_text1 = '';    // Text with link to person
            $picture_text2 = '';    // Text without link to person

            // person
            if ($picqryDb->event_connect_kind === 'person') {
                $person_cls = new person_cls;
                $personDb = $db_functions->get_person( $picqryDb->event_connect_id );
                $privacy = $person_cls->set_privacy($personDb);
                if (!$personDb || $privacy) {
                    continue;
                }
                $name = $person_cls->person_name($personDb);
                $url = $person_cls->person_url2($personDb->pers_tree_id, $personDb->pers_famc, $personDb->pers_fams, $personDb->pers_gedcomnumber);
                $picture_text1 = '<a href="' . $url . '">' . $name["standard_name"] . '</a><br>';
                $picture_text2 = $name["standard_name"];
                
            // family
            } elseif ($picqryDb->event_connect_kind === 'family') {
                $qry2 = "SELECT * FROM humo_families WHERE fam_gedcomnumber='" . $picqryDb->event_connect_id . "'";
                $family_qry = $dbh->query($qry2);
                $family_qryDb2 = $family_qry->fetch(PDO::FETCH_OBJ);
                @$personmnDb2 = $db_functions->get_person($family_qryDb2->fam_man);
                $man_cls2 = new person_cls($personmnDb2);
                @$personmnDb3 = $db_functions->get_person($family_qryDb2->fam_woman);
                $woman_cls = new person_cls($personmnDb3);
                // *** Skip this picture if man or woman have enabled privacy options ***
                if ($man_cls2->privacy || $woman_cls->privacy ) { 
                    continue;
                }
                $manname = $man_cls2->person_name($personmnDb2);
                $womname = $woman_cls->person_name($personmnDb3);
                $vars['pers_family'] = $picqryDb->event_connect_id;
                $url = $link_cls->get_link($uri_path, 'family', $tree_id, true, $vars);
                $picture_text1 = '<a href="' . $url . '">' . $manname["standard_name"] . ' & ' . $womname["standard_name"] . '</a><br>';
                $picture_text2 = $manname["standard_name"] . ' & ' . $womname["standard_name"];
                
            // source
            } elseif ($picqryDb->event_connect_kind === 'source') {
                $sourceDb = $db_functions->get_source($picqryDb->event_connect_id);
                if ( $user['group_show_restricted_source'] == 'y' ) {  } // that's fine
                elseif ( $user['group_sources'] == 'j' && $sourceDb->source_status == 'publish' ) { } // that's fine too
                else {
                    continue;  // restricted, skip the rest
                }
                $vars['source_gedcomnr'] = $picqryDb->event_connect_id;
                $url = $link_cls->get_link($uri_path, 'source', $tree_id, true, $vars );
                $picture_text1 = '<a href="' . $url . '">' . $sourceDb->source_title . '</a><br>';
                $picture_text2 = $sourceDb->source_title;
            } 

            if (!isset($photoalbum['media_files']) || !in_array($picname, $photoalbum['media_files'])) { // this pic does not appear in the array yet

                // *** Skip PDF and RTF files ***
                $check_file = strtolower($picname);
                if (substr($check_file, -4) !== '.pdf' && substr($check_file, -4) !== '.rtf') {
                    $photoalbum['media_files'][] = $picname;
                    $photoalbum['media_files_pictext'][$picname] = $picqryDb->event_text;
                    $photoalbum['media_files_linktext'][$picname] = $picture_text1;
                    $photoalbum['media_files_nolinktext'][$picname] = $picture_text2;
                    $photoalbum['media_files_date'][$picname] = $picqryDb->event_date;
                    $photoalbum['media_files_place'][$picname] = $picqryDb->event_place;
                    
                }
            }

        }
        $_SESSION['categories'][$tree_id][$chosen_tab] = $photoalbum;
        return $photoalbum;
    }
}
