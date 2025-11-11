<?php

/**
 * Rename places.
 */

// *** Safety line ***
if (!defined('ADMIN_PAGE')) {
    exit;
}
$loc_list = $dbh->query("SELECT * FROM humo_location ORDER BY location_location");
$known_locations = array();

while ($loc_listDb = $loc_list->fetch(PDO::FETCH_OBJ)){
//    $known_locations[] = $loc_listDb->location_location;
    $known_locations[ $loc_listDb->location_location ] = [ 'lat' => $loc_listDb->location_lat, 'lng' => $loc_listDb->location_lng ];
}
?>

<h1 class="center"><?= __('Rename places'); ?></h1>

<div class="p-3 my-md-2 genealogy_search container-md">
    <div class="row">
        <div class="col-3">
            <?= select_tree($dbh, $page, $tree_id); ?>
        </div>

        <div class="col-auto">
            <label for="count_places" class="col-form-label">
                <?= $place['result']->rowCount(); ?> <?= __('Places'); ?>.
            </label>
        </div>

        <div class="col-auto">
            <label for="location" class="col-form-label">
                <?= __('Select location'); ?>
            </label>
        </div>
        <div class="col-3">
            <form method="POST" action="index.php" style="display : inline;">
                <input type="hidden" name="page" value="<?= $page; ?>">
                <select size="1" name="place_select" class="form-select form-select-sm" onChange="this.form.submit();">
                    <?php
                    while ($person = $place['result']->fetch(PDO::FETCH_OBJ)) {
                        $has_location = '(-)';
                        if ($person->place_edit != '') {
                            if (!empty($known_locations[$person->place_edit])) {
                               $has_location = '(+)'; echo('huhu ');
                            }
                    ?>
                            <option value="<?= $person->place_edit; ?>" <?= $place['select'] == $person->place_edit ? ' selected' : ''; ?>>
                                <?= $has_location; ?> <?= $person->place_edit; ?> 
                            </option>
                    <?php
                        }
                    }
                    ?>
                </select>
            </form> 
        </div>
        <div class="col-auto">
            <?= __('(+) = Geo data set'); ?>
        </div>

        <!--
        <div class="col-auto">
            <input type="submit" name="dummy8" value="<?= __('Select'); ?>" class="btn btn-sm btn-success">
        </div>
                -->
    </div>
</div>

<!-- Change selected place -->
<?php if ($place['select']) { ?>
    <form method="POST" action="index.php" class="mt-4" name="form1" id="form1">
        <input type="hidden" name="page" value="<?= $page; ?>">
        <input type="hidden" name="place_old" value="<?= $place['select']; ?>">

        <div class="row mb-2">
            <div class="col-2"></div>

            <div class="col-2">
                <label for="change_location" class="col-form-label">
                    <?= __('Change location'); ?>:
                </label>
            </div>

            <div class="col-3">
                <input type="text" name="place_new" value="<?= $place['select']; ?>" size="60" class="form-control form-control-sm">
                <input type="hidden" name="place_new_geo" value="">
                <button type="button" onClick='window.open("index.php?page=editor_place_select&form=1&place_item=place_new&place="+encodeURI(document.form1.place_new.value),"","width=800,height=500,top=100,left=50,scrollbars=yes"); return false;'>
                    <img src="../images/search_osm.png" title="<?= __('Search with OpenStreetMap') ?>">
                </button><br>
                <span id="place_new_disp" style="display:none">
                    <img src="../images/okay.png"> <?=  __('Geolocation data added. Press "Save" to write to database'); ?>
                </span>

            </div>
            
        </div>

        <div class="row">
            <div class="col-4"></div>
            <div class="col-2">
                <input type="submit" name="place_change" value="<?= __('Save'); ?>" class="btn btn-sm btn-success">
            </div>
        </div>
    </form>
<?php
}
