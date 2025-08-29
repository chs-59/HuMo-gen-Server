<!-- TODO check all links in this script -->

<?php
// *** Search variables in: http://localhost/humo-gen/list/humo1_/M/ ***

// *** MAIN SETTINGS ***
$maxcols = $data["max_cols"];
$last_name = $data["last_name"];

$maxnames = $data["max_names"];
$nr_persons = $data["max_names"];
$item = $data["item"];


$start = 0;
if (isset($_GET["start"])) {
    $start = $_GET["start"];
}

function tablerow($nr, $lastcol = false)
{
    // displays one set of name & nr column items in the row
    // $nr is the array number of the name set created in function last_names
    // if $lastcol is set to true, the last right border of the number column will not be made thicker (as the other ones are to distinguish between the name&nr sets)
    global $user, $freq_last_names, $freq_pers_prefix, $freq_count_last_names, $tree_id, $link_cls, $uri_path;
    $path_tmp = $link_cls->get_link($uri_path, 'list', $tree_id, true);

?>
    <td class="namelst">
        <?php
        if (isset($freq_last_names[$nr])) {
            $top_pers_lastname = '';
            if ($freq_pers_prefix[$nr]) {
                $top_pers_lastname = str_replace("_", " ", $freq_pers_prefix[$nr]);
            }
            $top_pers_lastname .= $freq_last_names[$nr];
            if ($user['group_kindindex'] == "j") {
                echo '<a href="' . $path_tmp . 'pers_lastname=' . str_replace("_", " ", $freq_pers_prefix[$nr]) . str_replace("&", "|", $freq_last_names[$nr]);
            } else {
                $top_pers_lastname = $freq_last_names[$nr];
                if ($freq_pers_prefix[$nr]) {
                    $top_pers_lastname .= ', ' . str_replace("_", " ", $freq_pers_prefix[$nr]);
                }
                echo '<a href="' . $path_tmp . 'pers_lastname=' . str_replace("&", "|", $freq_last_names[$nr]);
                if ($freq_pers_prefix[$nr]) {
                    echo '&amp;pers_prefix=' . $freq_pers_prefix[$nr];
                } else {
                    echo '&amp;pers_prefix=EMPTY';
                }
            }
            echo '&amp;part_lastname=equals">' . $top_pers_lastname . "</a>";
        } else {
            echo '-';
        }
        ?>
    </td>

    <td class="namenr" style="text-align:center<?php if ($lastcol == false) echo 'border-right-width:3px'; ?>">
        <?php
        if (isset($freq_last_names[$nr])) {
            echo $freq_count_last_names[$nr];
        } else {
            echo '-';
        }
        ?>
    </td>
<?php
}

$number_high = 0;

// *** Get names from database ***
// all database functions moved to app/model/list_names.php 
// and app/controller/list_namesController.php
$freq_last_names = $data['freq_last_names'];
$freq_count_last_names = $data['freq_count_last_names'];
$freq_pers_prefix = $data['freq_pers_prefix'];

if (isset($freq_last_names)) {
    $row = ceil(count($freq_last_names) / $maxcols);
}

$count_persons = $data['count_persons'];

// *** If number of displayed surnames is "ALL" change value into number of surnames ***
if ($nr_persons == 'ALL') {
    $nr_persons = $count_persons;
}

if ($humo_option["url_rewrite"] == "j") {
    $url = $uri_path . 'list_names/' . $tree_id . '/' . $last_name;
} else {
    $url = 'index.php?page=list_names&amp;tree_id=' . $tree_id . '&amp;last_name=' . $last_name;
}
?>
<br>
<div style="text-align:center">
    <!-- Find first character of last name -->
    <?php
    foreach ($data["alphabet_array"] as $alphabet) {
        // test if this first char is in database
        if (empty($_SESSION['list_names_cache'][$tree_id][$alphabet])){            continue; }
        $vars['last_name'] = $alphabet;
        $link = $link_cls->get_link($uri_path, 'list_names', $tree_id, false, $vars);

        echo ' <a href="' . $link . '">' . $alphabet . '</a>';
    }

    $vars['last_name'] = 'all';
    $link = $link_cls->get_link($uri_path, 'list_names', $tree_id, false, $vars);
    echo ' <a href="' . $link . '">' . __('All names') . "</a>\n";
    ?>
</div><br>

<!-- <h1 class="standard_header"><?= __('Frequency of Surnames'); ?></h1> -->

<!-- Show options line -->
<form method="POST" action="<?= $url; ?>" style="display:inline;" id="frqnames">
    <div class="row mb-3 me-1">
        <div class="col-sm-3"></div>
        <div class="col-sm-3">
            <select size=1 name="freqsurnames" class="form-select form-select-sm" onChange="this.form.submit();">
                <option><?= __('Number of displayed surnames'); ?></option>
                <option value="25">25</option>
                <option value="51">50</option> <!-- 51 so no empty last field (if more names than this) -->
                <option value="75">75</option>
                <option value="100">100</option>
                <option value="201">200</option> <!-- 201 so no empty last field (if more names than this) -->
                <option value="300">300</option>
                <option value="999"><?= __('All'); ?></option>
            </select>
        </div>

        <div class="col-sm-3">
            <select size=1 name="maxcols" class="form-select form-select-sm" onChange="this.form.submit();">
                <option><?= __('Number of columns'); ?></option>
                <?php for ($i = 1; $i < 7; $i++) { ?>
                    <option value="<?= $i; ?>"><?= $i; ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-sm-3"></div>
    </div>
</form>

<?php
//*** Show number of persons and pages *********************

$show_line_pages = false;
// *** Check for search results ***
//if (@$person->rowCount() > 0) {

if ($count_persons > 0) {
    if ($humo_option["url_rewrite"] == "j") {
        $uri_path_string = $uri_path . 'list_names/' . $tree_id . '/' . $last_name . '?';
    } else {
        $uri_path_string = 'index.php?page=list_names&amp;last_name=' . $last_name . '&amp;';
    }

    // "<="
    $data["previous_link"] = '';
    $data["previous_status"] = '';
    if ($start > 1) {
        $show_line_pages = true;
        $start2 = $start - 20;
        $calculated = ($start - 2) * $nr_persons;
        $data["previous_link"] = $uri_path_string . "start=" . $start2 . "&amp;item=" . $calculated;
    }
    if ($start <= 0) {
        $start = 1;
    }
    if ($start == '1') {
        $data["previous_status"] = 'disabled';
    }

    // 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19
    for ($i = $start; $i <= $start + 19; $i++) {
        $calculated = ($i - 1) * $nr_persons;
        if ($calculated < $count_persons) {
            $data["page_nr"][] = $i;
            if ($item == $calculated) {
                $data["page_link"][$i] = '';
                $data["page_status"][$i] = 'active';
            } else {
                $show_line_pages = true;
                $data["page_link"][$i] = $uri_path_string . "start=" . $start . "&amp;item=" . $calculated;
                $data["page_status"][$i] = '';
            }
        }
    }

    // "=>"
    $data["next_link"] = '';
    $data["next_status"] = '';
    $calculated = ($i - 1) * $nr_persons;
    if ($calculated < $count_persons) {
        $show_line_pages = true;
        $data["next_link"] = $uri_path_string . "start=" . $i . "&amp;item=" . $calculated;
    } else {
        $data["next_status"] = 'disabled';
    }
}
if ($show_line_pages) {
?>
    <div style="text-align:center">
        <?php include __DIR__ . '/partial/pagination.php'; ?>
    </div>
<?php
}
?>

<?php $col_width = ((round(100 / $maxcols)) - 6) . "%"; ?>
<table class="table table-sm nametbl">
    <thead class="table-primary">
        <tr>
            <?php for ($x = 1; $x < $maxcols; $x++) { ?>
                <th width="<?= $col_width; ?>"><?= __('Name'); ?></th>
                <th style="text-align:center;border-right-width:3px;width:6%"><?= __('Total'); ?></th>
            <?php } ?>
            <th width="<?= $col_width; ?>"><?= __('Name'); ?></th>
            <th style="text-align:center;width:6%"><?= __('Total'); ?></th>
        </tr>
    </thead>

    <?php
    for ($i = 0; $i < $row; $i++) {
        echo '<tr>';
        for ($n = 0; $n < $maxcols; $n++) {
            if ($n == $maxcols - 1) {
                tablerow($i + ($row * $n), true); // last col
            } else {
                tablerow($i + ($row * $n)); // other cols
            }
        }
        echo '</tr>';
    }
    ?>
</table>

<!-- Show number of names with gray background bar -->
<script>
    var tbl = document.getElementsByClassName("nametbl")[0];
    var rws = tbl.rows;
    var baseperc = <?= $number_high; ?>;
    for (var i = 0; i < rws.length; i++) {
        var tbs = rws[i].getElementsByClassName("namenr");
        var nms = rws[i].getElementsByClassName("namelst");
        for (var x = 0; x < tbs.length; x++) {
            var percentage = parseInt(tbs[x].innerHTML, 10);
            percentage = (percentage * 100) / baseperc;
            if (percentage > 0.1) {
                nms[x].style.backgroundImage = "url(images/lightgray.png)";
                nms[x].style.backgroundSize = percentage + "%" + " 100%";
                nms[x].style.backgroundRepeat = "no-repeat";
                nms[x].style.color = "rgb(0, 140, 200)";
            }
        }
    }
</script><br><br>
<?php
function check_list_privacy($gcom_pers){
    global $db_functions;
    $my_persDb = $db_functions->get_person($gcom_pers);
    $pers_cls = new person_cls($my_persDb);
    $my_privacy = $pers_cls->set_privacy($my_persDb);
    return $my_privacy;
}
?>