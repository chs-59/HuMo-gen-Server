<?php
class TreesModel
{
    private $tree_id;

    public function set_tree_id($tree_id)
    {
        $this->tree_id = $tree_id;

        if (isset($_POST['tree_id']) and is_numeric(($_POST['tree_id']))) {
            $this->tree_id = $_POST['tree_id'];
        }
    }
    public function get_tree_id()
    {
        return $this->tree_id;
    }

    public function update_tree($dbh, $db_functions)
    {
        // *** Add family tree ***
        if (isset($_POST['add_tree_data'])) {
            $sql = "INSERT INTO humo_trees SET
                tree_order='" . safe_text_db($_POST['tree_order']) . "',
                tree_prefix='" . safe_text_db($_POST['tree_prefix']) . "',
                tree_persons='0',
                tree_families='0',
                tree_email='',
                tree_privacy='',
                tree_pict_path='|../pictures/'
                ";
            $dbh->query($sql);

            $_SESSION['tree_prefix'] = safe_text_db($_POST['tree_prefix']);

            $this->tree_id = $dbh->lastInsertId();
            $_SESSION['admin_tree_id'] = $this->tree_id;
        }

        if (isset($_POST['change_tree_data'])) {
            $tree_pict_path = $_POST['tree_pict_path'];
            if (substr($_POST['tree_pict_path'], 0, 1) == '|') {
                if (isset($_POST['default_path']) and $_POST['default_path'] == 'no') $tree_pict_path = substr($tree_pict_path, 1);
            } else {
                if (isset($_POST['default_path']) and $_POST['default_path'] == 'yes') $tree_pict_path = '|' . $tree_pict_path;
            }

            $sql = "UPDATE humo_trees SET
                tree_email='" . safe_text_db($_POST['tree_email']) . "',
                tree_owner='" . safe_text_db($_POST['tree_owner']) . "',
                tree_pict_path='" . safe_text_db($tree_pict_path) . "',
                tree_privacy='" . safe_text_db($_POST['tree_privacy']) . "'
                WHERE tree_id=" . $this->tree_id;
            $dbh->query($sql);
        }

        if (isset($_POST['remove_tree2']) and is_numeric($_POST['tree_id'])) {
            $removeqry = 'SELECT * FROM humo_trees WHERE tree_id="' . safe_text_db($_POST['tree_id']) . '"';
            @$removesql = $dbh->query($removeqry);
            @$removeDb = $removesql->fetch(PDO::FETCH_OBJ);
            $remove = $removeDb->tree_prefix;

            // *** Re-order family trees ***
            $repair_order = $removeDb->tree_order;
            $item = $dbh->query("SELECT * FROM humo_trees WHERE tree_order>" . $repair_order);
            while ($itemDb = $item->fetch(PDO::FETCH_OBJ)) {
                $sql = "UPDATE humo_trees SET tree_order='" . ($itemDb->tree_order - 1) . "' WHERE tree_id=" . $itemDb->tree_id;
                $dbh->query($sql);
            }

            $sql = "DELETE FROM humo_trees WHERE tree_id='" . safe_text_db($_POST['tree_id']) . "'";
            $dbh->query($sql);

            // *** Remove items from table family_tree_text ***
            $sql = "DELETE FROM humo_tree_texts WHERE treetext_tree_id='" . safe_text_db($_POST['tree_id']) . "'";
            $dbh->query($sql);

            // *** Remove persons ***
            $sql = "DELETE FROM humo_persons WHERE pers_tree_id='" . safe_text_db($_POST['tree_id']) . "'";
            $dbh->query($sql);

            // *** Remove families ***
            $sql = "DELETE FROM humo_families WHERE fam_tree_id='" . safe_text_db($_POST['tree_id']) . "'";
            $dbh->query($sql);

            // *** Remove sources ***
            $sql = "DELETE FROM humo_sources WHERE source_tree_id='" . safe_text_db($_POST['tree_id']) . "'";
            $dbh->query($sql);

            // *** Remove texts ***
            $sql = "DELETE FROM humo_texts WHERE text_tree_id='" . safe_text_db($_POST['tree_id']) . "'";
            $dbh->query($sql);

            // *** Remove connections ***
            $sql = "DELETE FROM humo_connections WHERE connect_tree_id='" . safe_text_db($_POST['tree_id']) . "'";
            $dbh->query($sql);

            // *** Remove addresses ***
            $sql = "DELETE FROM humo_addresses WHERE address_tree_id='" . safe_text_db($_POST['tree_id']) . "'";
            $dbh->query($sql);

            // *** Remove events ***
            $sql = "DELETE FROM humo_events WHERE event_tree_id='" . safe_text_db($_POST['tree_id']) . "'";
            $dbh->query($sql);

            // *** Remove statistics ***
            $sql = "DELETE FROM humo_stat_date WHERE stat_tree_id='" . safe_text_db($_POST['tree_id']) . "'";
            $dbh->query($sql);

            // *** Remove unprocessed tags ***
            $sql = "DELETE FROM humo_unprocessed_tags WHERE tag_tree_id='" . safe_text_db($_POST['tree_id']) . "'";
            $dbh->query($sql);

            // *** Remove cache ***
            $sql = "DELETE FROM humo_settings WHERE setting_variable LIKE 'cache%' AND setting_tree_id='" . safe_text_db($_POST['tree_id']) . "'";
            $dbh->query($sql);

            // *** Remove admin favourites ***
            $sql = "DELETE FROM humo_settings WHERE setting_variable='admin_favourite' AND setting_tree_id='" . safe_text_db($_POST['tree_id']) . "'";
            $dbh->query($sql);

            // *** Remove adjusted glider settings ***
            $sql = "DELETE FROM humo_settings WHERE setting_variable='gslider_" . $remove . "'";
            $dbh->query($sql);

            // *** Remove geo_tree settings for this tree ***
            $sql = "UPDATE humo_settings SET setting_value = REPLACE(setting_value, CONCAT('@'," . safe_text_db($_POST['tree_id']) . ",';'), '')  WHERE setting_variable='geo_trees'";
            $dbh->query($sql);

            // *** Remove tree_prefix of this tree from location table (humo2_birth, humo2_death, humo2_bapt, humo2_buried)  ***
            $temp = $dbh->query("SHOW TABLES LIKE 'humo_location'");
            if ($temp->rowCount()) {
                $loc_qry = "SELECT * FROM humo_location";
                $loc_result = $dbh->query($loc_qry);
                while ($loc_resultDb = $loc_result->fetch(PDO::FETCH_OBJ)) {
                    if (strpos($loc_resultDb->location_status, $remove) !== false) {   // only do this if the prefix appears
                        $stat_qry = "UPDATE humo_location SET location_status = REPLACE(REPLACE(REPLACE(REPLACE(location_status, CONCAT('" . $remove . "','birth'),''),CONCAT('" . $remove . "','death'),''),CONCAT('" . $remove . "','bapt'),''),CONCAT('" . $remove . "','buried'),'')  WHERE location_id = '" . $loc_resultDb->location_id . "'";
                        $dbh->query($stat_qry);
                    }
                }
            }

            unset($_POST['tree_id']);

            // *** Next lines to reset session items for editor pages ***
            if (isset($_SESSION['admin_tree_prefix'])) {
                unset($_SESSION['admin_tree_prefix']);
            }
            if (isset($_SESSION['admin_tree_id'])) {
                unset($_SESSION['admin_tree_id']);
            }
            unset($_SESSION['admin_pers_gedcomnumber']);
            unset($_SESSION['admin_fam_gedcomnumber']);

            // *** Now select another family tree ***
            $check_tree_sql = $dbh->query("SELECT * FROM humo_trees WHERE tree_prefix!='EMPTY' ORDER BY tree_order LIMIT 0,1");
            @$check_treeDb = $check_tree_sql->fetch(PDO::FETCH_OBJ);
            $check_tree_id = $check_treeDb->tree_id;

            // *** Double check tree_id and save tree id in session ***
            $_SESSION['admin_tree_id'] = '';
            if ($check_tree_id and $check_tree_id != '') {
                $get_treeDb = $db_functions->get_tree($check_tree_id);

                $this->tree_id = $get_treeDb->tree_id;
                $_SESSION['admin_tree_id'] = $this->tree_id;
            }
        }

        if (isset($_GET['up']) and is_numeric($_GET['tree_order']) and is_numeric($_GET['id'])) {
            // *** Search previous family tree ***
            $item = $dbh->query("SELECT * FROM humo_trees WHERE tree_order=" . ($_GET['tree_order'] - 1));
            $itemDb = $item->fetch(PDO::FETCH_OBJ);
            // *** Raise previous family trees ***
            $sql = "UPDATE humo_trees SET tree_order='" . safe_text_db($_GET['tree_order']) . "' WHERE tree_id=" . $itemDb->tree_id;
            $dbh->query($sql);
            // *** Lower tree order ***
            $sql = "UPDATE humo_trees SET tree_order='" . safe_text_db($_GET['tree_order'] - 1) . "' WHERE tree_id=" . safe_text_db($_GET['id']);
            $dbh->query($sql);
        }

        if (isset($_GET['down']) and is_numeric($_GET['tree_order']) and is_numeric($_GET['id'])) {
            // *** Search next family tree ***
            $item = $dbh->query("SELECT * FROM humo_trees WHERE tree_order=" . ($_GET['tree_order'] + 1));
            $itemDb = $item->fetch(PDO::FETCH_OBJ);
            // *** Lower previous family tree ***
            $sql = "UPDATE humo_trees SET tree_order='" . safe_text_db($_GET['tree_order']) . "' WHERE tree_id=" . $itemDb->tree_id;
            $dbh->query($sql);
            // *** Raise tree order ***
            $sql = "UPDATE humo_trees SET tree_order='" . safe_text_db($_GET['tree_order'] + 1) . "' WHERE tree_id=" . safe_text_db($_GET['id']);
            $dbh->query($sql);
        }

        if (isset($_POST['add_tree_text'])) {
            $sql = "INSERT INTO humo_tree_texts SET
            treetext_tree_id='" . $this->tree_id . "',
            treetext_language='" . safe_text_db($_POST['language_tree']) . "',
            treetext_name='" . safe_text_db($_POST['treetext_name']) . "',
            treetext_mainmenu_text='" . safe_text_db($_POST['treetext_mainmenu_text']) . "',
            treetext_mainmenu_source='" . safe_text_db($_POST['treetext_mainmenu_source']) . "',
            treetext_family_top='" . safe_text_db($_POST['treetext_family_top']) . "',
            treetext_family_footer='" . safe_text_db($_POST['treetext_family_footer']) . "'";
            $dbh->query($sql);
        }

        if (isset($_POST['change_tree_text'])) {
            $sql = "UPDATE humo_tree_texts SET
            treetext_tree_id='" . $this->tree_id . "',
            treetext_language='" . safe_text_db($_POST['language_tree']) . "',
            treetext_name='" . safe_text_db($_POST['treetext_name']) . "',
            treetext_mainmenu_text='" . safe_text_db($_POST['treetext_mainmenu_text']) . "',
            treetext_mainmenu_source='" . safe_text_db($_POST['treetext_mainmenu_source']) . "',
            treetext_family_top='" . safe_text_db($_POST['treetext_family_top']) . "',
            treetext_family_footer='" . safe_text_db($_POST['treetext_family_footer']) . "'
            WHERE treetext_id=" . safe_text_db($_POST['treetext_id']);
            $dbh->query($sql);
        }

        // *** Add empty line ***
        if (isset($_POST['add_tree_data_empty'])) {
            $sql = "INSERT INTO humo_trees SET
                tree_order='" . safe_text_db($_POST['tree_order']) . "',
                tree_prefix='EMPTY',
                tree_persons='EMPTY',
                tree_families='EMPTY',
                tree_email='EMPTY',
                tree_privacy='EMPTY',
                tree_pict_path='EMPTY'
                ";
            $dbh->query($sql);
        }

        // *** Change collation of tree ***
        if (isset($_POST['tree_collation'])) {
            $tree_collation = safe_text_db($_POST['tree_collation']);
            $dbh->query("ALTER TABLE humo_persons CHANGE `pers_lastname` `pers_lastname` VARCHAR(50) COLLATE " . $tree_collation . ";");
            $dbh->query("ALTER TABLE humo_persons CHANGE `pers_firstname` `pers_firstname` VARCHAR(50) COLLATE " . $tree_collation . ";");
            $dbh->query("ALTER TABLE humo_persons CHANGE `pers_prefix` `pers_prefix` VARCHAR(20) COLLATE " . $tree_collation . ";");
            //$dbh->query("ALTER TABLE humo_persons CHANGE `pers_callname` `pers_callname` VARCHAR(20) COLLATE ".$tree_collation.";");
            $dbh->query("ALTER TABLE humo_events CHANGE `event_event` `event_event` TEXT COLLATE " . $tree_collation . ";");
        }
    }
}
