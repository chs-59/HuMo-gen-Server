<?php
// *** Safety line ***
if (!defined('ADMIN_PAGE')) {
    exit;
}

include_once(__DIR__ . "/../include/select_tree.php");
include_once(__DIR__ . "/../include/media_inc.php");

$prefx = '../'; // to get out of the admin map

$data2sql = $dbh->query("SELECT * FROM humo_trees WHERE tree_id=" . $tree_id);
$data2Db = $data2sql->fetch(PDO::FETCH_OBJ);
?>

<ul class="nav nav-tabs mt-1">
    <li class="nav-item me-1">
        <a class="nav-link genealogy_nav-link <?php if ($thumbs['menu_tab'] == 'picture_settings') echo 'active'; ?>" href="index.php?page=<?= $page; ?>"><?= __('Picture settings'); ?></a>
    </li>
    <li class="nav-item me-1">
        <a class="nav-link genealogy_nav-link <?php if ($thumbs['menu_tab'] == 'picture_thumbnails') echo 'active'; ?>" href="index.php?page=<?= $page; ?>&amp;menu_tab=picture_thumbnails"><?= __('Create thumbnails'); ?></a>
    </li>
    <li class="nav-item me-1">
        <a class="nav-link genealogy_nav-link <?php if ($thumbs['menu_tab'] == 'picture_show') echo 'active'; ?>" href="index.php?page=<?= $page; ?>&amp;menu_tab=picture_show"><?= __('Show thumbnails'); ?></a>
    </li>
    <li class="nav-item me-1">
        <a class="nav-link genealogy_nav-link <?php if ($thumbs['menu_tab'] == 'picture_categories') echo 'active'; ?>" href="index.php?page=<?= $page; ?>&amp;menu_tab=picture_categories"><?= __('Photo album categories'); ?></a>
    </li>
</ul>

<!-- Align content to the left -->
<div style="background-color:white; height:500px; padding:10px;">
    <?php if ($thumbs['menu_tab'] == 'picture_settings') { ?>
        <div class="p-3 m-2 genealogy_search">
            <div class="row mb-2">
                <div class="col-md-4">
                    <label for="tree" class="col-form-label"><?= __('Choose family tree'); ?></label>
                </div>

                <div class="col-md-7">
                    <?= select_tree($dbh, $page, $tree_id, $thumbs['menu_tab']); ?>
                </div>
            </div>
        <form method="POST" action="index.php">
                <input type="hidden" name="page" value="thumbs">
                <input type="hidden" name="menu_tab" value="<?= $thumbs['menu_tab']; ?>">
                <input type="hidden" name="tree_id" value="<?= $tree_id; ?>">


            <div class="row mb-2">
                <div class="col-md-4">
                    <label for="picture_path" class="col-form-label"><?= __('Path to the pictures'); ?></label>
                </div>

                <!-- Set path to pictures -->
                <div class="col-md-8">

                        <div class="form-check">
                            <input class="form-check-input" type="radio" value="yes" name="default_path" id="default_path" <?= $thumbs['default_path'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="default_path">
                                <?= __('Use default picture path:'); ?> <b>media/</b>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" value="no" name="default_path" id="default_path" <?= !$thumbs['default_path'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="default_path">
                                <input type="text" name="tree_pict_path" value="<?= $thumbs['own_pict_path']; ?>" size="40" placeholder="../pictures/" class="form-control form-control-sm">
                            </label>
                        </div>
                </div>
                <div class="col-md-4"><?= __('Status of picture path'); ?></div>

                <div class="col-md-7">
                        <?php 
                        $rewrite_status = test_rewrite();
                        echo '<input type="hidden" name="server_rewrite_status" value="' . $rewrite_status . '">';
                        $display_rewrite = '';
                        
                        echo ('Server rewrite engine: ') . '<span class=';
                        if ($rewrite_status == 'on') { echo '"bg-success-subtle">' . __('On'); }
                        elseif ($rewrite_status == 'off') { echo '"bg-warning-subtle">' . __('Off'); }
                        else { echo '"bg-warning-subtle">' . __('Unknown'); }
                        echo '</span><br><br>';
                        // this code only for display, calculation is done in 
                        // function save_picture_path_rewrite in admin/models/thumbs.php
                        echo ('DocumentRoot = <br>' . $_SERVER['DOCUMENT_ROOT'] . '<br><br>');
                        if ( file_exists($prefx . $thumbs['tree_pict_path']) ) { 
                            echo (__('Media directory') . ' =<br>' . realpath($prefx . $thumbs['tree_pict_path']) . '/<br>');
                            if ( preg_match('/^media\//', $thumbs['tree_pict_path'])
                                    && $rewrite_status == 'on' )  {
                                    echo '<span class="bg-success-subtle">' . __('Safe. Path protected by rewrite engine.') . '</span><br>';
                                    $display_rewrite = __('No');
                            } elseif (str_contains( realpath($prefx . $thumbs['tree_pict_path']), $_SERVER['DOCUMENT_ROOT'] )  ) {
                                echo '<span class="bg-danger-subtle">' . __('Unsafe. Path inside DocRoot.') . '</span><br>';
                                $display_rewrite = __('No');
                            } else {
                                echo '<span class="bg-success-subtle">' . __('Safe. Path outside DocRoot.') . '</span><br>';
                                $display_rewrite = ($rewrite_status == 'on' ? __('Yes - Use server rewrite') : __('Use HuMo rewrite'));
                            }
                        } else {
                            echo '<span class="bg-warning-subtle"><b>' . __('Picture path doesn\'t exist!') . '</b></span>';
                            $display_rewrite = __('No');
                        }
                    ?><br><br>
                </div>
            </div>


            <div class="row mb-2">
                <div class="col-md-4"><?= __('Rewrite media path'); ?></div>
                            
                        <div class="col-md-7">
                            <?= $display_rewrite; ?>
                            <br>
                        </div>
                <div class="col-md-4">&nbsp;</div>
                        <div class="col-md-7">
                            <input type="submit" name="change_tree_data" value="<?= __('Change'); ?>" class="btn btn-sm btn-success"><br>
                        </div>
                        - <?= __('To show pictures, also check the user-group settings: '); ?>
            <a href="index.php?page=groups"><?= __('User groups'); ?></a>

            </div>
            </form>
       </div>

    <?php } 

            // -- enable thumbnails and resize -->
            if ($thumbs['menu_tab'] == 'picture_thumbnails') {
            // TODO refactor
                $is_thumblib = false;
                $no_windows = (strtolower(substr(PHP_OS, 0, 3)) !== 'win');
                if ($no_windows || extension_loaded('gd')) {
                    $is_thumblib = true;
                }

            // Auto create thumbnails
//            if (isset($_POST["thumbnail_auto_create"]) && ($_POST["thumbnail_auto_create"] == 'y' || $_POST["thumbnail_auto_create"] == 'n')) {
//                $db_functions->update_settings('thumbnail_auto_create', $_POST["thumbnail_auto_create"]);
//                $humo_option["thumbnail_auto_create"] = $_POST["thumbnail_auto_create"];
//            }            
            ?>

            <div class="p-3 m-2 genealogy_search">
                <div class="row mb-2">
                    <div class="col-md-4">
                        <label for="tree" class="col-form-label"><?= __('Choose family tree'); ?></label>
                    </div>

                    <div class="col-md-7">
                        <?= select_tree($dbh, $page, $tree_id, $thumbs['menu_tab']); ?>
                    </div>
                </div>

                <h4><?= _('Test') ?></h4>
                <div class="row mb-2">
                    <div class="col-md-7">
                        <?= __('Imagick (images)'); ?>
                    </div>
                    <div class="col-md-auto">
                        <b><?= extension_loaded('imagick') ? strtolower(__('Yes')) : strtolower(__('No')); ?></b>
                    </div>
                </div>

                <?php if (extension_loaded('imagick') && $no_windows) { ?>
                    <div class="row mb-2">
                        <div class="col-md-7">
                            <?= __('Ghostscript (PDF support)'); ?>
                        </div>
                        <div class="col-md-auto">
                            <b><?= (trim(shell_exec('type -P gs'))) ? strtolower(__('Yes')) . '<br>' : strtolower(__('No')); ?></b>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-7">
                            <?= __('ffmpeg (movie support)'); ?>
                        </div>
                        <div class="col-md-auto">
                            <b><?= (trim(shell_exec('type -P ffmpeg'))) ? strtolower(__('Yes')) . '<br>' : strtolower(__('No')); ?></b>
                        </div>
                    </div>
                <?php } ?>

                <div class="row mb-2">
                    <div class="col-md-7">
                        <?= __('GD (images)'); ?>
                    </div>
                    <div class="col-md-auto">
                        <b><?= extension_loaded('gd') ? strtolower(__('Yes')) : strtolower(__('No')); ?></b>
                    </div>
                </div>

                <?php if ( $is_thumblib) { ?>
                <h4><?= __('Settings') ?></h4>

                <!-- Automatically create thumbnails -->
                <form method="POST" action="index.php">
                    <input type="hidden" name="page" value="thumbs">
                    <input type="hidden" name="menu_tab" value="<?= $thumbs['menu_tab']; ?>">
                    <input type="hidden" name="tree_id" value="<?= $tree_id; ?>">
                    <input type="hidden" name="change_thumbnail_status" value="yes">
                    <div class="row mb-2">
                        <div class="col-md-7">
                            <?= __('Use thumbnails [create and display]?'); ?>
                        </div>
                        <div class="col-md-auto">
                            <select size="1" name="thumbnail_status" onChange="this.form.submit();" class="form-select form-select-sm">
                                <option value="n"><?= __('No'); ?></option>
                                <option value="y" <?= $thumbs["tree_pict_thumbnail"] == 'y' ? 'selected' : ''; ?>><?= __('Yes'); ?></option>
                            </select>
                        </div>
                    </div>
                </form>


                <form method="POST" action="index.php">
                    <input type="hidden" name="page" value="thumbs">
                    <input type="hidden" name="menu_tab" value="<?= $thumbs['menu_tab']; ?>">
                    <input type="hidden" name="tree_id" value="<?= $tree_id; ?>">
                    <input type="hidden" name="change_resize_status" value="yes">
                    <div class="row mb-2">
                        <div class="col-md-7">
                            <?= __('Resize pictures on upload [set maximum width and height]?'); ?>
                        </div>
                        <div class="col-md-auto">
                            <select size="1" name="resize_status" onChange="this.form.submit();" class="form-select form-select-sm">
                                <option value="0|0"><?= __('No'); ?></option>
                                <option value="720|480" <?= $thumbs["tree_pict_resize"] == '720|480' ? 'selected' : ''; ?>>720×480</option>
                                <option value="1280|720" <?= $thumbs["tree_pict_resize"] == '1280|720' ? 'selected' : ''; ?>>1280×720</option>
                                <option value="1920|1080" <?= $thumbs["tree_pict_resize"] == '1920|1080' ? 'selected' : ''; ?>>1920x1080</option>
                                <option value="3840|2160" <?= $thumbs["tree_pict_resize"] == '3840|2160' ? 'selected' : ''; ?>>3840x2160</option>
                           </select>
                        </div>
                    </div>
                </form>
                <?php } else { ?>
                    <?= __('No Thumbnail library available'); ?><br>
                <?php } ?>
        </div>

        <?php } ?>

        <!-- Show thumbnails -->
        <?php if ($thumbs['menu_tab'] == 'picture_show') { ?>
        <div class="p-3 m-2 genealogy_search">
               <div class="row mb-2">
                    <div class="col-md-4">
                        <label for="tree" class="col-form-label"><?= __('Choose family tree'); ?></label>
                    </div>

                    <div class="col-md-7">
                        <?= select_tree($dbh, $page, $tree_id, $thumbs['menu_tab']); ?>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-4"><?= __('You can change filenames here.'); ?></div>

                    <div class="col-md-7">
                        <form method="POST" action="index.php">
                            <input type="hidden" name="page" value="thumbs">
                            <input type="hidden" name="menu_tab" value="picture_show">
                            <input type="hidden" name="tree_id" value="<?= $tree_id; ?>">
                            <input type="submit" name="change_filename" value="<?= __('Show thumbnails'); ?>" class="btn btn-sm btn-success">
                        </form>
                    </div>
                </div>
        </div>
            <?php } ?>

        <?php 



    // *** Picture categories ***
    if ($thumbs['menu_tab'] == 'picture_categories') {

        $temp = $dbh->query("SHOW TABLES LIKE 'humo_mediacat'");
        if (!$temp->rowCount()) {
            // no category database table exists - so create it

            $albumtbl = "CREATE TABLE humo_mediacat (
              mediacat_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                mediacat_order MEDIUMINT(6),
                mediacat_tree_id smallint(5), 
                mediacat_name VARCHAR(30) CHARACTER SET utf8,
                mediacat_language_names TEXT CHARACTER SET utf8
            ) DEFAULT CHARSET=utf8";
            $dbh->query($albumtbl);
            // also create table item event_categories in humo_events
            $d2sql = $dbh->query("SELECT * FROM humo_events");
            $d2Db = $d2sql->fetch(PDO::FETCH_OBJ);
            if (!property_exists($d2Db, 'event_categories')) {
                $sql = "ALTER TABLE humo_events ADD event_categories VARCHAR(1023) CHARACTER SET utf8 AFTER event_event;";
                $dbh->query($sql);
            }
        
       }
        $cat_setup = $dbh->query("SELECT * FROM humo_mediacat WHERE mediacat_tree_id='" . ($tree_id * -1) . "'");
        if (!$cat_setup->rowCount()) {
            // categories never been setup - create default
//            $treesql = $dbh->query("SELECT * FROM humo_mediacat WHERE mediacat_tree_id='" . $tree_id . "'");
//            $dataDb = $datasql->fetch(PDO::FETCH_OBJ);
//            if (!$treesql->rowCount()) {
                // no categories for this tree - create default
                $languages = array();
                foreach ($language_file as $lang){ $languages[$lang] = ''; }
                // Enter the default categories with default name that can be changed by admin afterwards
                $dbh->query("INSERT INTO humo_mediacat (mediacat_order,mediacat_tree_id,mediacat_name,mediacat_language_names) VALUES ('1','" . $tree_id . "','persons','" . json_encode($languages) . "')");
                $dbh->query("INSERT INTO humo_mediacat (mediacat_order,mediacat_tree_id,mediacat_name,mediacat_language_names) VALUES ('2','" . $tree_id . "','families','" . json_encode($languages) . "')");
                $dbh->query("INSERT INTO humo_mediacat (mediacat_order,mediacat_tree_id,mediacat_name,mediacat_language_names) VALUES ('3','" . $tree_id . "','sources','" . json_encode($languages) . "')");
                // negative tree_id is to indicate directory was allready setup and all categories were deleted by user. In this case default cats should not be restored
                $dbh->query("INSERT INTO humo_mediacat (mediacat_order,mediacat_tree_id,mediacat_name) VALUES ('1','" . ($tree_id * -1) . "','__none__')");
//            }
            //echo '<h1 align=center>'.__('Photo album categories').'</h1>';
        }
        $language_tree = $selected_language; // Default language
        if (isset($_GET['language_tree'])) {
            $language_tree = $_GET['language_tree'];
        }
        if (isset($_POST['language_tree'])) {
            $language_tree = $_POST['language_tree'];
        }
        if (isset($_GET['cat_drop2']) && $_GET['cat_drop2'] == 1 && !isset($_POST['save_cat'])) {
            // delete category and make sure that the order sequence is restored
            $dbh->query("UPDATE humo_mediacat SET mediacat_order = (mediacat_order-1) WHERE mediacat_order > '" . safe_text_db($_GET['cat_order']) . "' AND mediacat_tree_id = '" . $tree_id . "'");
            $dbh->query("DELETE FROM humo_mediacat WHERE mediacat_name = '" . safe_text_db($_GET['cat_prefix']) . "'");
        }
        if (isset($_GET['cat_up']) && !isset($_POST['save_cat'])) {
            // move category up
            $dbh->query("UPDATE humo_mediacat SET mediacat_order = '999' WHERE mediacat_order ='" . safe_text_db($_GET['cat_up']) . "' AND mediacat_tree_id = '" . $tree_id . "'");  // set present one to temp
            $dbh->query("UPDATE humo_mediacat SET mediacat_order = '" . $_GET['cat_up'] . "' WHERE mediacat_order ='" . (safe_text_db($_GET['cat_up']) - 1) . "' AND mediacat_tree_id = '" . $tree_id . "'");  // move the one above down
            $dbh->query("UPDATE humo_mediacat SET mediacat_order = '" . (safe_text_db($_GET['cat_up']) - 1) . "' WHERE mediacat_order = '999' AND mediacat_tree_id = '" . $tree_id . "'");  // move this one up
        }
        if (isset($_GET['cat_down']) && !isset($_POST['save_cat'])) {
            // move category down
            $dbh->query("UPDATE humo_mediacat SET mediacat_order = '999' WHERE mediacat_order ='" . safe_text_db($_GET['cat_down']) . "' AND mediacat_tree_id = '" . $tree_id . "'");  // set present one to temp
            $dbh->query("UPDATE humo_mediacat SET mediacat_order = '" . safe_text_db($_GET['cat_down']) . "' WHERE mediacat_order ='" . (safe_text_db($_GET['cat_down']) + 1) . "' AND mediacat_tree_id = '" . $tree_id . "'");  // move the one under it up
            $dbh->query("UPDATE humo_mediacat SET mediacat_order = '" . (safe_text_db($_GET['cat_down']) + 1) . "' WHERE mediacat_order = '999' AND mediacat_tree_id = '" . $tree_id . "'");  // move this one down
        }

        if (isset($_POST['save_cat'])) {  // the user decided to add a new category and/or save changes to names
            // save names of existing categories in case some were altered. There is at least always one name (for default category)

            //$qry = "SELECT * FROM humo_photocat GROUP BY photocat_prefix";
            // *** Renewed query because of ONLY_FULL_GROUP_BY setting in MySQL 5.7 (otherwise query will stop) ***
            $qry = "SELECT * FROM humo_mediacat WHERE mediacat_tree_id = '" . $tree_id . "'";
            $result = $dbh->query($qry);

            while ($resultDb = $result->fetch(PDO::FETCH_OBJ)) {
                if (isset($_POST[$resultDb->mediacat_name])) {
                    $cat_translations = json_decode($resultDb->mediacat_language_names, true);
                   $cat_translations[$language_tree] = str_replace('"', '', $_POST[$resultDb->mediacat_name]);
                    $dbh->query("UPDATE humo_mediacat SET mediacat_language_names = '" . json_encode($cat_translations, JSON_UNESCAPED_UNICODE) . "'
                                WHERE mediacat_name='" . $resultDb->mediacat_name . "' AND mediacat_tree_id = '" . $tree_id . "'"); 
                }
            }

            // save new category
            if (isset($_POST['new_cat_prefix']) && isset($_POST['new_cat_name'])
                    && str_replace('"', '', $_POST['new_cat_prefix']) != "") {
                $new_cat_name = str_replace('"', '', $_POST['new_cat_prefix']);
                $check_exist = $dbh->query("SELECT * FROM humo_mediacat WHERE mediacat_name='" . safe_text_db($new_cat_name) . "' AND mediacat_tree_id = '" . $tree_id . "'");
                if ($check_exist->rowCount() == 0 
                        && strlen($new_cat_name) < 30
                        && preg_match('/^[A-Za-z0-9_-]+$/', $new_cat_name)) {
                    $languages = array();
                    foreach ($language_file as $lang){ $languages[$lang] = ''; }
                    $languages[$language_tree] = str_replace('"', '', $_POST['new_cat_name']);
                    $highest_order = $dbh->query("SELECT MAX(mediacat_order) AS maxorder FROM humo_mediacat");
                    $orderDb = $highest_order->fetch(PDO::FETCH_ASSOC);
                    $order = $orderDb['maxorder'];
                    $order++;
                    $qry = "INSERT INTO humo_mediacat (mediacat_order,mediacat_tree_id,mediacat_name,mediacat_language_names) VALUES ('" . 
                            safe_text_db($order) . "', '" . safe_text_db($tree_id) . "', '" . safe_text_db($new_cat_name) . "', '" . 
                            json_encode($languages, JSON_UNESCAPED_UNICODE)  . "')";
                    $dbh->query($qry);
                } else {   // this category prefix already exists!
                    $warning_exist_prefix = __('Category exists or name invalid!');
                    $warning_prefix = $_POST['new_cat_prefix'];
                }
            }
        }
        
       
        ?>

        <form method="post" action="index.php" style="display : inline;">
            <input type="hidden" name="page" value="thumbs">
            <input type="hidden" name="menu_tab" value="picture_categories">
            <input type="hidden" name="language_tree" value="<?= $language_tree; ?>">

            <div class="p-3 m-2 genealogy_search">
                <div class="row mb-2">
                    <div class="col-md-4">
                        <label for="tree" class="col-form-label"><?= __('Choose family tree'); ?></label>
                    </div>

                    <div class="col-md-7">
                        <?= select_tree($dbh, $page, $tree_id, $thumbs['menu_tab']); ?>
                    </div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-11">
                        <h3><?= __('Create categories for your photo albums'); ?></h3>
                    </div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-11">
                        <li><?= __('Here you can create categories for all media files in the "Photobook" section.</li><li><b>Without any category no pictures will be displayed there!</b></li><li>On first use of this page default categories are created: "persons", "families" and "sources" will display all media files of the corresponding data sheet sections.</li><li>Feel free to add, remove, restore or reorder any category. Put translations for your prefered languages into the right input fields</li>'); ?></li>
                        <li><?= __('Category names are limited to a maximum of 30 characters. Only A-Z, a-z, 0-9, - and _ are accepted!'); ?></li>
                        <li><?= __('Consult <a href="../README.md" target="_new">README</a> for more information'); ?></li>
                    </div>
                </div>

                <table class="humo" cellspacing="0" style="margin-left:0px; text-align:center; width:80%">
                    <tr>
                        <td style="border-bottom:0px;"></td>
                        <td style="font-size:120%;border-bottom:0px;width:25%" white-space:nowrap;"><b><?= __('Category name'); ?></b></td>
                        <td style="font-size:120%;border-bottom:0px;width:60%"><b><?= __('Category translation'); ?></b></td>
                    </tr>

                    <?php
                    $add = "";
                    if (isset($_POST['add_new_cat'])) {
                        $add = "&amp;add_new_cat=1";
                    }

                    // *** Language choice ***
                    $language_tree2 = $language_tree;
                    if ($language_tree == 'default') {
                        $language_tree2 = $selected_language;
                    }
                    include(__DIR__ . '/../../languages/' . $language_tree2 . '/language_data.php');
                    $select_top = '';
                    ?>

                    <tr>
                        <td style="border-top:0px"></td>
                        <td style="border-top:0px"></td>
                        <td style="border-top:0px;text-align:center">
                            <div class="row mb-2">
                                <div class="col-md-auto">
                                    <?= __('Language'); ?>:
                                </div>

                                <div class="col-md-auto">
                                    <?php include_once(__DIR__ . "/../../views/partial/select_language.php"); ?>
                                    <?php $language_path = 'index.php?page=thumbs&amp;menu_tab=picture_categories&amp;'; ?>
                                    <?= show_country_flags($language_tree2, '../', 'language_tree', $language_path); ?>
                                </div>

                                <div class="col-md-auto">
                                </div>
                            </div>
                        </td>
                    </tr>

                    <?php

                    $qry = "SELECT * FROM humo_mediacat WHERE mediacat_tree_id = '". $tree_id ."' ORDER BY mediacat_order";
                    $cat_result = $dbh->query($qry);
                    $number = 1;  // number on list

                    while ($catDb = $cat_result->fetch(PDO::FETCH_OBJ)) {
                        //var_dump($catDb); exit;
                        $catname = $catDb->mediacat_name;
                        $langs = json_decode($catDb->mediacat_language_names, true);
                        $catlang = $langs[$language_tree2];
                        if (empty($catlang)) { $catlang = $catname;}
                        // arrows
                        $order_sequence = $dbh->query("SELECT MAX(mediacat_order) AS maxorder, MIN(mediacat_order) AS minorder FROM humo_mediacat WHERE mediacat_tree_id = '". $tree_id ."'");
                        $orderDb = $order_sequence->fetch(PDO::FETCH_ASSOC);
                        $maxorder = $orderDb['maxorder'];
                        $minorder = $orderDb['minorder'];


                        ?>
                        <tr>
                            <td>
                                <div style="width:25px;" class="d-inline-block">
                                    <?php
                                    echo '<a href="index.php?page=thumbs&amp;menu_tab=picture_categories&amp;cat_order=' . $catDb->mediacat_order . '&amp;cat_prefix=' . $catDb->mediacat_name . '&amp;cat_drop=1"><img src="images/button_drop.png"></a>';

/*                                    if ($catDb->photocat_prefix != 'none') {
                                        echo '<a href="index.php?page=thumbs&amp;menu_tab=picture_categories&amp;cat_order=' . $catDb->photocat_order . '&amp;cat_prefix=' . $catDb->photocat_prefix . '&amp;cat_drop=1"><img src="images/button_drop.png"></a>';
                                    }
*/
                                    ?>
                                </div>

                                <div style="width:20px;" class="d-inline-block">
                                    <?php
                                    if ($catDb->mediacat_order != $minorder) {
//                                    if ($catDb->photocat_order != $minorder) {
//                                        echo '<a href="index.php?page=thumbs&amp;menu_tab=picture_categories&amp;cat_prefix=' . $catDb->photocat_prefix . '&amp;cat_up=' . $catDb->photocat_order . '"><img src="images/arrow_up.gif"></a>';
                                        echo '<a href="index.php?page=thumbs&amp;menu_tab=picture_categories&amp;cat_prefix=' . $catDb->mediacat_name . '&amp;cat_up=' . $catDb->mediacat_order . '"><img src="images/arrow_up.gif"></a>';
                                    }
                                    ?>
                                </div>

                                <div style="width:20px;" class="d-inline-block">
                                    <?php
                                    if ($catDb->mediacat_order != $maxorder) {
//                                    if ($catDb->photocat_order != $maxorder) {
//                                        echo '<a href="index.php?page=thumbs&amp;menu_tab=picture_categories&amp;cat_prefix=' . $catDb->photocat_prefix . '&amp;cat_down=' . $catDb->photocat_order . '"><img src="images/arrow_down.gif"></a>';
                                        echo '<a href="index.php?page=thumbs&amp;menu_tab=picture_categories&amp;cat_prefix=' . $catDb->mediacat_name . '&amp;cat_down=' . $catDb->mediacat_order . '"><img src="images/arrow_down.gif"></a>';
                                    }
                                    ?>
                                </div>
                            </td>

                            <td style="white-space:nowrap;"><?= $catname; ?></td>

                            <td><input type="text" name="<?= $catDb->mediacat_name; ?>" value="<?= $catlang; ?>" size="30" class="form-control form-control-sm"></td>
                        </tr>
                    <?php
                    }

                    $content = "";
                    if (isset($warning_prefix)) {
                        $content = $warning_prefix;
                    }
                    ?>
                    <tr>
                        <td></td>
                        <td style="white-space:nowrap;"><input type="text" name="new_cat_prefix" value="<?= $content; ?>" size="6" class="form-control form-control-sm">
                            <?php if (isset($warning_invalid_prefix)) { ?>
                                <br><span style="color:red"><?= $warning_invalid_prefix; ?></span>
                            <?php
                            }
                            if (isset($warning_exist_prefix)) {
                            ?>
                                <br><span style="color:red"><?= $warning_exist_prefix; ?></span>
                            <?php } ?>
                        </td>
                        <td>
                            <input type="text" name="new_cat_name" value="" size="30" class="form-control form-control-sm">
                            <?php if (isset($warning_noname)) { ?>
                                <br><span style="color:red"><?= $warning_noname; ?></span>
                            <?php } ?>
                        </td>
                    </tr>

                    <?php if (isset($_GET['cat_drop']) && $_GET['cat_drop'] == 1) { ?>
                        <tr>
                            <td colspan="3" style="color:red;font-weight:bold;font-size:120%">
                                <?= __('Do you really want to delete category:'); ?>&nbsp;<?= $_GET['cat_prefix']; ?>&nbsp;?
                                &nbsp;&nbsp;&nbsp;<input type="button" style="color:red;font-weight:bold" onclick="location.href='index.php?page=thumbs&amp;menu_tab=picture_categories&amp;cat_order=<?= $_GET['cat_order']; ?>&amp;cat_prefix=<?= $_GET['cat_prefix']; ?>&amp;cat_drop2=1';" value="<?= __('Yes'); ?>">
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" style="color:green;font-weight:bold" onclick="location.href='index.php?page=thumbs&amp;menu_tab=picture_categories';" value="<?= __('No'); ?>">
                            </td>
                        </tr>
                    <?php } ?>
                </table><br>
                <div style="margin-left:auto; margin-right:auto; text-align:center;"><input type="submit" name="save_cat" value="<?= __('Save changes'); ?>" class="btn btn-sm btn-success"></div>
            </div>

        </form>
        <?php
    }
    $pict_path = $data2Db->tree_pict_path;
    if (substr($pict_path, 0, 1) === '|') {
        $pict_path = 'media/';
    }
// Check for trees with identical tree_pict_path to inherit delete button
// if same media file is used in different trees    
$same_picpath = array();
$tree_checksql = $dbh->query("SELECT * FROM humo_trees");
while ($treeCheck = $tree_checksql->fetch(PDO::FETCH_OBJ)) {
    if ($treeCheck->tree_id != $tree_id
            && $treeCheck->tree_pict_path == $pict_path) {
        $same_picpath[] = $treeCheck->tree_id;
    }    
}

    // *** Change filename ***
    if (isset($_POST['filename'])) {
        $db_path = $_POST['picture_path'];
        $picture_path = $prefx . $pict_path . $db_path;
        $db_filename = $db_path . $_POST['filename'];
        $db_filename_old = $db_path . $_POST['filename_old'];
//        echo('pp: ' . $picture_path . 'File: ' . $db_filename);
/*   $picture_path_old = $_POST['picture_path'];
        $picture_path_new = $_POST['picture_path'];
        // *** If filename has a category AND a sub category directory exists, use it ***
        if (substr($_POST['filename'], 0, 2) !== substr($_POST['filename_old'], 0, 2) && ($_POST['filename'][2] == '_' || $_POST['filename_old'][2] == '_')) { // we only have to do this if something changed in a prefix
            if ($_POST['filename'][2] == '_') {
                if (preg_match('!.+/[a-z][a-z]/$!', $picture_path_new) == 1) {   // original path had subfolder
                    if (is_dir(substr($picture_path_new, 0, -3) . substr($_POST['filename'], 0, 2))) {   // subtract subfolder and add new subfolder
                        $picture_path_new = substr($picture_path_new, 0, -3) . substr($_POST['filename'], 0, 2) . "/"; // move from subfolder to other subfolder
                    } else {
                        $picture_path_new = substr($picture_path_new, 0, -3); // move file with prefix that has no folder to main folder
                    }
                } elseif (is_dir($_POST['picture_path'] . substr($_POST['filename'], 0, 2))) {
                    $picture_path_new .= substr($_POST['filename'], 0, 2) . '/';   // move from main folder to subfolder
                }
            } elseif (preg_match('!.+/[a-z][a-z]/$!', $picture_path_new) == 1) {    // regular file, just check if original path had subfolder
                $picture_path_new = substr($picture_path_new, 0, -3);  // move from subfolder to main folder
            }
        }
*/
        // remove thumb old naming system
        if (file_exists($picture_path . 'thumb_' . $_POST['filename_old'])) {
            unlink($picture_path . 'thumb_' . $_POST['filename_old']);
        }
        // remove old thumb new system
        if (file_exists($picture_path . 'thumb_' . $_POST['filename_old'] . '.jpg')) {
            unlink($picture_path . 'thumb_' . $_POST['filename_old'] . '.jpg');
        }
        // delete file or rename and create new thumbnail       
        if (file_exists($picture_path . $_POST['filename_old'])) {
            if (isset($_POST['delete_file'])) {
                unlink($picture_path . $_POST['filename_old']); 
                echo '<b>' . __('Deleted file:') . ' </b>' . $picture_path .  $_POST['filename_old'] . '<br>';
            }
            else {
                rename($picture_path . $_POST['filename_old'], $picture_path . $_POST['filename']);
                echo '<b>' . __('Changed filename:') . ' </b>' . $picture_path .  $_POST['filename_old'] . ' <b>' . __('into filename:') . '</b> ' . $picture_path .  $_POST['filename'] . '<br>';
                if (check_media_type($picture_path, $_POST['filename']) && create_thumbnail($picture_path, $_POST['filename'])) {
                    echo '<b>' . __('Changed filename:') . ' ' . __('into filename:') . '</b> ' . $picture_path . 'thumb_' . $_POST['filename'] . '.jpg<br>';
                }
                $sql = "UPDATE humo_events SET
                event_event='" . safe_text_db($db_filename) . "' WHERE event_event='" . safe_text_db($db_filename_old) . "'";
                $result = $dbh->query($sql);
            }
        }

     }


    // *** Show thumbnails to rename ***
    $counter = 0;
//    if (isset($_POST["thumbnail"]) || isset($_POST['change_filename'])) {
    if (isset($_POST['change_filename'])) {

        //$selected_picture_folder=$prefx.$pict_path;
        $array_picture_folder[] = $prefx . $pict_path;

        // *** Extra safety check if folder exists ***
        //if (file_exists($selected_picture_folder)){
        if (file_exists($array_picture_folder[0])) {
            // *** Get all subdirectories ***
            function get_dirs($prefx, $path)
            {
                global $array_picture_folder;
                $ignore = array('cms', 'slideshow', 'thumbs', '.', '..');
                $dh = opendir($prefx . $path);
                while (false !== ($filename = readdir($dh))) {
                    if (!in_array($filename, $ignore) && is_dir($prefx . $path . $filename)) {
                        $array_picture_folder[] = $prefx . $path . $filename . '/';
                        get_dirs($prefx, $path . $filename . '/');
                    }
                }
                closedir($dh);
            }

            get_dirs($prefx, $pict_path);

            foreach ($array_picture_folder as $selected_picture_folder) {
                echo '<br style="clear: both">';
                echo '<h3>' . $selected_picture_folder . '</h3>';

                $files = preg_grep('/^([^.])/', scandir($selected_picture_folder));
                foreach ($files as $filename) {

/*                    if (
                        substr($filename, 0, 5) !== 'thumb' &&
                        isset($_POST["thumbnail"]) &&
                        !is_dir($selected_picture_folder . $filename)  &&
                        check_media_type($selected_picture_folder, $filename)
                    ) {

                        if (
                            !is_file($selected_picture_folder . '.' . $filename . '.no_thumb') && // don't create thumb on corrupt file
                            empty(thumbnail_exists($selected_picture_folder, $filename))
                        ) {    // don't create thumb if one exists
                            create_thumbnail($selected_picture_folder, $filename); // in media_inc.php script 
                        }
                    }
*/
                    // *** Show thumbnails ***
                    if (
                        substr($filename, 0, 5) !== 'thumb' &&
                        check_media_type($selected_picture_folder, $filename) &&
                        !is_dir($selected_picture_folder . $filename)
                    ) {
        ?>
                        <div class="photobook_select">
                            <?php
                            echo print_thumbnail($selected_picture_folder, $filename);
                            // *** Show name of connected persons ***
                            include_once(__DIR__ . '/../../include/person_cls.php');
                            $db_dir = str_replace($array_picture_folder[0], '', $selected_picture_folder);
                            $picture_text = '<br>';
//                            $sql = "SELECT * FROM humo_events WHERE event_tree_id='" . safe_text_db($tree_id) . "' "
//                                . "AND (event_connect_kind='person' OR event_connect_kind='family' OR event_connect_kind='source') "
                            $sql = "SELECT * FROM humo_events WHERE (event_connect_kind='person' OR event_connect_kind='family' OR event_connect_kind='source') "
                                . "AND event_connect_id NOT LIKE '' AND event_event='" . $db_dir . $filename . "'";
/*                            $sql = "SELECT * FROM humo_events WHERE event_tree_id='" . safe_text_db($tree_id) . "'
                                AND event_connect_kind='person' AND event_kind='picture'
                                AND LOWER(event_event)='" . safe_text_db(strtolower($db_dir . $filename)) . "'";
*/

                            $picture_privacy = false;
                            $is_connected = false;
                            $db_functions->set_tree_id($tree_id);
                            
                            $afbqry = $dbh->query($sql);
                            while ($afbDb = $afbqry->fetch(PDO::FETCH_OBJ)) {
                                if ($afbDb->event_tree_id != $tree_id) {
                                    // do nothing unless:
                                    if (in_array($afbDb->event_tree_id, $same_picpath)) {
                                        $picture_text .= __('Used in tree') . ' ' . $afbDb->event_tree_id . '<br>';
                                        $is_connected = true;
                                    }
                                } 
                                elseif ($afbDb && $afbDb->event_connect_kind === 'person') {
                                    $person_cls = new person_cls;
    //                                $db_functions->set_tree_id($tree_id);
                                    $personDb = $db_functions->get_person($afbDb->event_connect_id);
                        //            var_dump($personDb);
                                    $name = $person_cls->person_name($personDb);
                                    $picture_text .= '<a href="' . $prefx . 'admin/index.php?page=editor&menu_tab=person&tree_id=' 
                                            . $tree_id . '&person=' . $personDb->pers_gedcomnumber . '" target="_blank"><b>'. __('person') . ':</b> ' . $name["standard_name"] . '</a><br>';
                                    $is_connected = true;
                                } elseif ($afbDb && $afbDb->event_connect_kind === 'family') {
                                    $fqry = "SELECT * FROM humo_families WHERE fam_tree_id='" . safe_text_db($tree_id) . "' AND fam_gedcomnumber='" . $afbDb->event_connect_id . "'";
                                    $family_qry = $dbh->query($fqry);
                                    $family_Db = $family_qry->fetch(PDO::FETCH_OBJ);
//                                    var_dump($family_Db);
                                    @$personman = $db_functions->get_person($family_Db->fam_man);
//                                    var_dump($personman);
                                    @$personwoman = $db_functions->get_person($family_Db->fam_woman);
                                    $picture_text .= '<a href="' . $prefx . 'admin/index.php?page=editor&menu_tab=marriage&tree_id=' 
                                            . $tree_id . '&person=' . $family_Db->fam_man . '&family=' . $afbDb->event_connect_id . '" target="_blank"><b>' 
                                            . __('relation') . ':</b> ' . $personman->pers_lastname . ' &amp; ' . $personwoman->pers_lastname . '</a><br>';
                                    $is_connected = true;

                                } elseif ($afbDb && $afbDb->event_connect_kind === 'source') {
    //                                $db_functions->set_tree_id($tree_id);
                                    $sourceDb = $db_functions->get_source($afbDb->event_connect_id);
                                    //var_dump($db_functions);
                                    $title = $sourceDb->source_title;// || $sourceDb->source_gedcomnr;
                                    $picture_text .= '<a href="' . $prefx . 'admin/index.php?page=edit_sources&source_id=' . $sourceDb->source_gedcomnr 
                                            . '&tree_id=' . $tree_id .  '" target="_blank"><b>' 
                                            . __('source') . ':</b> ' . $title . '</a><br>';
                                    $is_connected = true;

                                }
                                
                            }

                            ?>
                                <form method="POST" action="index.php">
                                    <input type="hidden" name="page" value="thumbs">
                                    <input type="hidden" name="menu_tab" value="picture_show">
                                    <input type="hidden" name="tree_id" value="<?= $tree_id; ?>">
                                    <input type="hidden" name="picture_path" value="<?= $db_dir; ?>">
                                    <input type="hidden" name="filename_old" value="<?= $filename; ?>">
                                    <input type="text" name="filename" value="<?= $filename; ?>" size="20">
                                    <input type="submit" name="change_filename" value="<?= __('Change filename'); ?>">
                               
    <?php
                        if ($is_connected) {
                            echo '</form>';
                            echo $picture_text;
                        }
                        else  {
                           echo '<br><br>' . __('Not in use') . '<br>';
                           echo '<input type="submit" onclick="return confirm(\'' . __('Delete file:') . ' ' . $filename . '?\');" name="delete_file" value="' . __('Delete file') . '">';
                           echo '</form>';
                        }

                        echo '</div>';
                        
                    }
                }
            }
        }
    }
    ?>
</div>