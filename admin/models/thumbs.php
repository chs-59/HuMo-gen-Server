<?php
class ThumbsModel
{
    public function get_menu_tab()
    {
        $menu_tab = 'picture_settings';
        if (isset($_POST['menu_tab'])) {
            $menu_tab = $_POST['menu_tab'];
        }
        if (isset($_GET['menu_tab'])) {
            $menu_tab = $_GET['menu_tab'];
        }

        return $menu_tab;
    }

    // *** Save new/ changed picture path ***
    public function save_picture_path($dbh, $tree_id)
    {
        if (isset($_POST['change_tree_data'])) {
            $tree_pict_path = $_POST['tree_pict_path'];
            if (!str_ends_with($tree_pict_path, '/')) { $tree_pict_path .= '/';}
            if (substr($_POST['tree_pict_path'], 0, 1) === '|') {
                if (isset($_POST['default_path']) && $_POST['default_path'] == 'no') {
                    $tree_pict_path = substr($tree_pict_path, 1);
                }
            } elseif (isset($_POST['default_path']) && $_POST['default_path'] == 'yes') {
                $tree_pict_path = '|' . $tree_pict_path;
            }
            $dbh->query("UPDATE humo_trees SET tree_pict_path='" . safe_text_db($tree_pict_path) . "' WHERE tree_id=" . safe_text_db($tree_id));
        }
    }
// *** Save new/ changed picture path rewrite mode***
// *** This flag is used by print_thumbnail function (media_inc.php)    
    public function save_picture_path_rewrite($dbh, $tree_id)
    {
        if (isset($_POST['change_tree_data'])) {
            $rewrite_flag_db = 'n';
            $server_rewrite_status = $_POST['server_rewrite_status'];
            $tree_pict_path = $_POST['tree_pict_path'];
            if (!str_contains( realpath( '../' .  $tree_pict_path), $_SERVER['DOCUMENT_ROOT']) // outside DocRoot
                && is_dir( '../' .  $tree_pict_path)                                           // existing path
                && $_POST['default_path'] == 'no') {                                           // chopstick code :(
                if ($server_rewrite_status == 'on' ) { $rewrite_flag_db = 's'; }                // rewrite by server (mod_rewrite)
                else { $rewrite_flag_db = 'i'; }                                                // rewrite by HuMo internal
            }
            $dbh->query("UPDATE humo_trees SET tree_pict_path_rewrite='" . safe_text_db($rewrite_flag_db) . "' WHERE tree_id=" . safe_text_db($tree_id));
       }
    }
    public function save_picture_thumbnail($dbh, $tree_id)
    {
        if (isset($_POST['change_thumbnail_status'])) {
            $picture_thumbnail = $_POST['thumbnail_status'];
            $dbh->query("UPDATE humo_trees SET tree_pict_thumbnail='" . safe_text_db($picture_thumbnail) . "' WHERE tree_id=" . safe_text_db($tree_id));
       }
    }
    public function save_picture_resize($dbh, $tree_id)
    {
        if (isset($_POST['change_resize_status'])) {
            $picture_resize= $_POST['resize_status'];
            $dbh->query("UPDATE humo_trees SET tree_pict_resize='" . safe_text_db($picture_resize) . "' WHERE tree_id=" . safe_text_db($tree_id));
       }
    }

    public function get_tree_pict_path($dbh, $tree_id)
    {
        $data2sql = $dbh->query("SELECT tree_pict_path FROM humo_trees WHERE tree_id=" . $tree_id);
        $data2Db = $data2sql->fetch(PDO::FETCH_OBJ);
        return $data2Db->tree_pict_path;
    }
    public function get_tree_pict_path_rewrite($dbh, $tree_id)
    {
        $data2sql = $dbh->query("SELECT * FROM humo_trees WHERE tree_id=" . $tree_id);
        $data2Db = $data2sql->fetch(PDO::FETCH_OBJ);
        if (!property_exists($data2Db, 'tree_pict_path_rewrite')) {
            $sql = "ALTER TABLE humo_trees ADD tree_pict_path_rewrite VARCHAR(100) CHARACTER SET utf8 AFTER tree_pict_path;";
            $dbh->query($sql);
            return '';
        }
        return $data2Db->tree_pict_path_rewrite;
    }
    public function get_tree_pict_thumbnail($dbh, $tree_id)
    {
        $data2sql = $dbh->query("SELECT * FROM humo_trees WHERE tree_id=" . $tree_id);
        $data2Db = $data2sql->fetch(PDO::FETCH_OBJ);
        if (!property_exists($data2Db, 'tree_pict_thumbnail')) {
            $sql = "ALTER TABLE humo_trees ADD tree_pict_thumbnail VARCHAR(100) CHARACTER SET utf8 AFTER tree_pict_path;";
            $dbh->query($sql);
            return 'n';
        }
        return $data2Db->tree_pict_thumbnail;
    }
    public function get_tree_pict_resize($dbh, $tree_id)
    {
        $data2sql = $dbh->query("SELECT * FROM humo_trees WHERE tree_id=" . $tree_id);
        $data2Db = $data2sql->fetch(PDO::FETCH_OBJ);
        if (!property_exists($data2Db, 'tree_pict_resize')) {
            $sql = "ALTER TABLE humo_trees ADD tree_pict_resize VARCHAR(100) CHARACTER SET utf8 AFTER tree_pict_path;";
            $dbh->query($sql);
            return '0x0';
        }
        return $data2Db->tree_pict_resize;
    }
    
    public function get_default_path($tree_pict_path)
    {
        // *** Picture path. A | character is used for a default path (the old path will remain in the field) ***
        if (substr($tree_pict_path, 0, 1) === '|') {
            return true;
        } else {
            return false;
        }
    }
}
