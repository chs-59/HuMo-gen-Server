<?php
// *** Check user privileges ***
if ($user['group_pictures'] != 'j' || $user['group_photobook'] != 'j') {
    echo __('You are not authorised to see this page.');
    exit();
}

// *** Show categories ***
if (!empty($photoalbum['category'])) {
?>
    <ul class="nav nav-tabs mt-1">
        <?php
        foreach ($photoalbum['category'] as $category) {
//            if ($photoalbum['category_enabled'][$category] == true) {
                // check if name for this category exists for this language
                $qry2 = "SELECT * FROM humo_mediacat WHERE mediacat_name ='" . $category . "' AND mediacat_tree_id ='" . $tree_id . "'";
                $result2 = $dbh->query($qry2);
                if ($result2->rowCount() != 0) {
                    $catnameDb = $result2->fetch(PDO::FETCH_OBJ);
                    $menutab_name = json_decode($catnameDb->mediacat_language_names, true)[$selected_language];
                    if (empty($menutab_name)) { $menutab_name = $catnameDb->mediacat_name; } 
                } else {
                    // no categories defined
                }

                $path = 'index.php?page=photoalbum?tree_id=' . $tree_id . '&amp;select_category=' . $category;
                if ($humo_option["url_rewrite"] == "j") {
                    $path = 'photoalbum/' . $tree_id . '?select_category=' . $category;
                }
        ?>
                <li class="nav-item me-1">
                    <a class="nav-link genealogy_nav-link <?php if ($category == $photoalbum['chosen_tab']) echo 'active'; ?>" href="<?= $path; ?>"><?= $menutab_name; ?></a>
                </li>
        <?php
 //           }
        }
        ?>
    </ul>

    <?php
} else {
    ?>
            <div style="float: left; background-color:white; height:auto; width:98%;padding:5px;"><br>
                <?= __('*** No category available for this tree ***'); ?><br><br>
            </div>

    <?php
}
// *** Show media/ photo's ***
if (!empty($photoalbum['media_files'])) {
        show_media_files($photoalbum['chosen_tab']);  // show only pics that match this category
} else {
    ?>
    <div style="float: left; background-color:white; height:auto; width:98%;padding:5px;"><br>
       <?= __('*** No pictures available ***'); ?><br><br>
        </div>
<?php
}
    

// *** $pref = category ***
function show_media_files($pref)
{
    global $dataDb, $dbh, $photoalbum, $uri_path, $tree_id, $db_functions, $humo_option, $link_cls;

    include_once(__DIR__ . "/../admin/include/media_inc.php");

    $tree_pict_path = $dataDb->tree_pict_path;
    if (substr($tree_pict_path, 0, 1) === '|') {
        $tree_pict_path = 'media/';
    }
    $dir = $tree_pict_path;

    // *** Calculate pages ***
    // Ordering is now done in query...
    //@usort($photoalbum['media_files'],'strnatcasecmp');   // sorts case insensitive and with digits as numbers: pic1, pic3, pic11
    $nr_pictures = count($photoalbum['media_files']);

    $albumpath = $link_cls->get_link($uri_path, 'photoalbum', $tree_id, true);

    $item = 0;
    if (isset($_GET['item'])) {
        $item = $_GET['item'];
    }
    $start = 0;
    if (isset($_GET["start"])) {
        $start = $_GET["start"];
    }

    // "<="
    $data["previous_link"] = '';
    $data["previous_status"] = '';
    if ($start > 1) {
        $start2 = $start - 20;
        $calculated = ($start - 2) * $photoalbum['show_pictures'];
        $data["previous_link"] = $albumpath . "start=" . $start2 . "&amp;item=" . $calculated;
    }
    if ($start <= 0) {
        $start = 1;
    }
    if ($start == '1') {
        $data["previous_status"] = 'disabled';
    }

    // 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19
    for ($i = $start; $i <= $start + 19; $i++) {
        $calculated = ($i - 1) * $photoalbum['show_pictures'];
        if ($calculated < $nr_pictures) {
            $data["page_nr"][] = $i;
            if ($item == $calculated) {
                $data["page_link"][$i] = '';
                $data["page_status"][$i] = 'disabled';
            } else {
                $data["page_link"][$i] = $albumpath . "start=" . $start . "&amp;item=" . $calculated;
                $data["page_status"][$i] = '';
            }
        }
    }

    // "=>"
    $data["next_link"] = '';
    $data["next_status"] = '';
    $calculated = ($i - 1) * $photoalbum['show_pictures'];
    if ($calculated < $nr_pictures) {
        $data["next_link"] = $albumpath . "start=" . $i . "&amp;item=" . $calculated;
    } else {
        $data["next_status"] = 'disabled';
    }

    //$menu_path_photoalbum = $link_cls->get_link($uri_path, 'photoalbum',$tree_id);
    $path = 'index.php?page=photoalbum?tree_id=' . $tree_id;
    //if ($photoalbum['show_categories'] === true) {
        $path .= '&amp;select_category=' . $pref;
    //}

    if ($humo_option["url_rewrite"] == "j") {
        $path = 'photoalbum/' . $tree_id;
        //if ($photoalbum['show_categories'] === true) {
            $path .= '?select_category=' . $pref;
        //}
    }
    ?>

    <div style="float: left; background-color:white; height:auto; width:98%;padding:5px;">

        <form method="post" action="<?= $path; ?>" style="display:inline">
            <div class="row mb-2">
                <div class="col-3"></div>

                <div class="col-auto">
                    <label for="show-pictures" class="col-form-label"><?= __('Photo\'s per page'); ?></label>
                </div>
                <div class="col-auto">
                    <select name="show_pictures" id="show_pictures" onChange="window.location=this.value" class="form-select form-select-sm">
                        <?php for ($i = 4; $i <= 60; $i++) { ?>
                            <option value="<?= $albumpath; ?>show_pictures=<?= $i; ?>&amp;start=0&amp;item=0&amp;select_category=<?= $photoalbum['chosen_tab']; ?>" <?= $i == $photoalbum['show_pictures'] ? ' selected' : ''; ?>>
                                <?= $i; ?>
                            </option>
                        <?php } ?>

                        <option value="<?= $albumpath; ?>show_pictures=100&amp;start=0&amp;item=0&amp;select_category=<?= $photoalbum['chosen_tab']; ?>" <?= 100 == $photoalbum['show_pictures'] ? ' selected' : ''; ?>>100</option>
                        <option value="<?= $albumpath; ?>show_pictures=200&amp;start=0&amp;item=0&amp;select_category=<?= $photoalbum['chosen_tab']; ?>" <?= 200 == $photoalbum['show_pictures'] ? ' selected' : ''; ?>>200</option>
                        <option value="<?= $albumpath; ?>show_pictures=400&amp;start=0&amp;item=0&amp;select_category=<?= $photoalbum['chosen_tab']; ?>" <?= 400 == $photoalbum['show_pictures'] ? ' selected' : ''; ?>>400</option>
                        <option value="<?= $albumpath; ?>show_pictures=800&amp;start=0&amp;item=0&amp;select_category=<?= $photoalbum['chosen_tab']; ?>" <?= 800 == $photoalbum['show_pictures'] ? ' selected' : ''; ?>>800</option>
                    </select>
                </div>

                <!-- Search by photo name -->
                <div class="col-auto">
                    <input type="text" name="search_media" value="<?= safe_text_show($photoalbum['search_media']); ?>" size="20" class="form-control form-control-sm">
                </div>
                <div class="col-auto">
                    <input type="submit" value="<?= __('Search'); ?>" class="btn btn-sm btn-success">
                </div>
            </div>
        </form>

        <div style="padding:5px" class="center">
            <?php include __DIR__ . '/partial/pagination.php'; ?>
        </div>

        <?php
        // *** Show photos ***
        for ($picture_nr = $item; $picture_nr < ($item + $photoalbum['show_pictures']); $picture_nr++) {
            if (isset($photoalbum['media_files'][$picture_nr]) && $photoalbum['media_files'][$picture_nr]) {
                $filename = $photoalbum['media_files'][$picture_nr];
                $picture_text = '';
                $picture_text2 = '';
                $picture_pictext = $photoalbum['media_files_pictext'][$filename];    // picture text
                $picture_linktext = $photoalbum['media_files_linktext'][$filename];    // Text with link to person
                $picture_nolinktext = $photoalbum['media_files_nolinktext'][$filename];    // Text without link to person
                $date_place = date_place($photoalbum['media_files_date'][$filename],$photoalbum['media_files_place'][$filename]);
                if ($picture_linktext) { 
                    $picture_text .= $picture_linktext; 
                    $picture_text2 .= $picture_nolinktext; 
                }
                if ($picture_pictext || $date_place) {
                    if ($date_place) {
                        $picture_text .= $date_place . ' ';
                    }
                    $picture_text .= $picture_pictext . '<br>';

                    $picture_text2 .= '<br>';
                    if ($date_place) {
                        $picture_text2 .= $date_place . ' ';
                    }
                    $picture_text2 .= $picture_pictext;
                }
                
                if (in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), array('jpg', 'jpeg', 'png', 'gif', 'tif', 'mp4', 'webm'))) {
                    $link_attrib = 'class="glightbox3" data-gallery="gallery1" data-glightbox="description: .custom-desc' . $picture_nr .'"';
                    $html_before = '<div class="glightbox-desc custom-desc' . $picture_nr . '">' . $picture_text2 . '</div>';
                    $picture = print_thumbnail($dir, $filename, 175, 120, '', '', true, $link_attrib, $html_before); 
                    ?>

                    <div class="photobook">
                        <!-- Show photo using the lightbox: GLightbox effect -->
                        <?= $picture; ?>
                        <div class="photobooktext"><?= $picture_text; ?></div>
                    </div>
                <?php } else { 
                    $picture = print_thumbnail($dir, $filename, 175, 120, '', '', true, 'target="_blank"'); 
                    ?>
                    <div class="photobook">
                        <?= $picture; ?>
                        <div class="photobooktext"><?= $picture_text; ?></div>
                    </div>
        <?php
                }
            }
        }
        ?>

    </div> <!-- end of white menu page -->
    <br clear="all"><br>

    <?php include __DIR__ . '/partial/pagination.php'; ?>

<?php
}
