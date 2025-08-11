<?php
// *** Safety line ***
if (!defined('ADMIN_PAGE')) {
    exit;
}

// *** Update tree settings ***
if (isset($_POST['user_change']) && isset($_POST["id"]) && is_numeric($_POST["id"])) {
    $user_hide_trees = '';
    $user_edit_trees = '';
    $acids = array();
    $data3sql = $dbh->query("SELECT * FROM humo_trees WHERE tree_prefix!='EMPTY'");
    while ($data3Db = $data3sql->fetch(PDO::FETCH_OBJ)) {
        // *** Show/ hide trees ***
        $check = 'show_tree_' . $data3Db->tree_id;
        if (isset($_POST["$check"]) && $_POST["$check"] == 'no') {
            if ($user_hide_trees !== '') {
                $user_hide_trees .= ';';
            }
            $user_hide_trees .= $data3Db->tree_id;
        }
        if (isset($_POST["$check"]) && $_POST["$check"] == 'yes') {
            if ($user_hide_trees !== '') {
                $user_hide_trees .= ';';
            }
            $user_hide_trees .= 'y' . $data3Db->tree_id;
        }

        // *** Edit trees (NOT USED FOR ADMINISTRATOR) ***
        $check = 'edit_tree_' . $data3Db->tree_id;
        if (isset($_POST["$check"])) {
            if ($user_edit_trees !== '') {
                $user_edit_trees .= ';';
            }
            $user_edit_trees .= $data3Db->tree_id;
        }
        // *** Skip privacy reglementation for these IDs
        $p_acids = 'access_ids_' . $data3Db->tree_id;
        $p_gen = 'access_gen_' . $data3Db->tree_id;
        if (isset($_POST["$p_acids"]) && $_POST["$p_acids"] != '') {
            $my_acids = explode(';',$_POST["$p_acids"], -1 );
            $my_acids_checked = array();
            foreach ($my_acids as $my_acid) {
                //check for valid gedcom nr in this tree
                if (preg_match('/^I\d+$/', $my_acid) === 1) {
                    try {
                        $checkDbtmp = $dbh->query("SELECT pers_id FROM humo_persons WHERE pers_tree_id='" . $data3Db->tree_id . "' AND pers_gedcomnumber='" . $my_acid . "'");
                        $checkDb = $checkDbtmp->fetch(PDO::FETCH_OBJ);
                       
                    } catch (PDOException $e) {
                        echo $e->getMessage() . "<br/>";
                    }
//                     echo(' -  Search ' . $my_acid) . ' - ';
//                    var_dump($checkDb);
                   if ($checkDb != false) { $my_acids_checked[] =  $my_acid;
                    }
                }
            }
            if (is_numeric($_POST["$p_gen"])) {
                $acids[$data3Db->tree_id] = ['acids' => implode(';', $my_acids_checked) . ';', 
                                             'gen'   => $_POST["$p_gen"] ];
            }
            else {
               $acids[$data3Db->tree_id] = ['acids' => implode(';', $my_acids_checked) . ';', 
                                             'gen'   => 0 ];
            }
        }
    }
    $sql = "UPDATE humo_users SET user_hide_trees='" . $user_hide_trees . "',  user_edit_trees='" . $user_edit_trees . "', user_access_ids='" . json_encode($acids) . "'  WHERE user_id=" . $_POST["id"];
    $result = $dbh->query($sql);
}
?>

<h1 align=center><?= __('Extra settings'); ?></h1>



<?php
if (isset($_GET['user'])) {
    $user = $_GET['user'];
}
if (isset($_POST['id'])) {
    $user = $_POST['id'];
}
if (is_numeric($user)) {
    $usersql = "SELECT * FROM humo_users WHERE user_id='" . $user . "'";
    $result = $dbh->query($usersql);
    
// *** Read user from database ***
    /*$user_props = array();
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $user_props[$row[1]] = $row[2];
    }*/
    
    $userDb = $result->fetch(PDO::FETCH_OBJ);

    $hide_tree_array = explode(";", $userDb->user_hide_trees);
    $edit_tree_array = explode(";", $userDb->user_edit_trees);
    if ( isset($userDb->user_access_ids) && $userDb->user_access_ids !='' ) {
        $accessids = json_decode($userDb->user_access_ids, true);
    }
    elseif (!isset($userDb->user_access_ids)) {
        $updatesql = "ALTER TABLE humo_users ADD user_access_ids TEXT CHARACTER SET utf8 NOT NULL DEFAULT ''";
        $dbh->query($updatesql);
    }
    ?>

<h2 align="center">    <?= __('for '); ?><b><?= $userDb->user_name; ?></b></h2>
<h3 align="left"><?= __('Hide or show family trees'); ?></h3>


    <form method="POST" action="index.php?page=editor_user_settings">
        <input type="hidden" name="page" value="editor_user_settings">
        <input type="hidden" name="id" value="<?= $userDb->user_id; ?>">
        <table class="table">
            <thead class="table-primary">
                <tr>
                    <th><?= __('Family tree'); ?></th>
                    <th><?= __('Show tree?'); ?></th>
                    <th><input type="submit" name="user_change" value="<?= __('Change'); ?>" class="btn btn-sm btn-success"></br><?= __('Edit tree?'); ?> </th>
                </tr>
            </thead>
            <?php
            $treecnt=1;
            $data3sql = $dbh->query("SELECT * FROM humo_trees WHERE tree_prefix!='EMPTY' ORDER BY tree_order");
            while ($data3Db = $data3sql->fetch(PDO::FETCH_OBJ)) {
                $treetext = show_tree_text($data3Db->tree_id, $selected_language);
                $treetext_name = $treetext['name'];
            ?>
                <tr>
                    <td><?= $treetext_name; ?></td>

                    <!-- Show/ hide tree for user -->
                    <td>
                        <select size="1" name="show_tree_<?= $data3Db->tree_id; ?>">
                            <option value="user-group"><?= __('Use user-group setting'); ?></option>
                            <option value="yes" <?= in_array('y' . $data3Db->tree_id, $hide_tree_array) ? 'selected' : ''; ?>><?= __('Yes'); ?></option>
                            <option value="no" <?= in_array($data3Db->tree_id, $hide_tree_array) ? 'selected' : ''; ?>><?= __('No'); ?></option>
                        </select>
                    </td>

                    <td>
                        <input type="checkbox" name="edit_tree_<?= $data3Db->tree_id; ?>" <?= in_array($data3Db->tree_id, $edit_tree_array) || $userDb->user_id == '1' ? 'checked' : ''; ?> <?= $userDb->user_id == '1' ? 'disabled' : ''; ?>>
                    </td>
                </tr>
            <?php } ?>
        </table>
        <h3 align="left"><?= __('Skip privacy check for persons and descendants'); ?></h3>
        
        <table class="table">
            <thead class="table-primary">
                <tr>
                    <th><?= __('Family tree'); ?></th>
                    <th><?= __('Type name to search for GEDCOM numbers'); ?></th>
                    <th> <input type="submit" name="user_change" value="<?= __('Change'); ?>" class="btn btn-sm btn-success"></br><?= __('Generations'); ?></th>
                </tr>
            </thead>
            <?php
            $data3sql = $dbh->query("SELECT * FROM humo_trees WHERE tree_prefix!='EMPTY' ORDER BY tree_order");
            while ($data3Db = $data3sql->fetch(PDO::FETCH_OBJ)) {
                $treetext = show_tree_text($data3Db->tree_id, $selected_language);
                $treetext_name = $treetext['name'];
                $my_accessids = '';
                $my_accessgen = '';
                if (isset($accessids["$data3Db->tree_id"])) {
                    $my_accessids = $accessids[$data3Db->tree_id]['acids'];
                    $my_accessgen = $accessids[$data3Db->tree_id]['gen'];
                }
            ?>
                <tr>
                    <td><?= $treetext_name; ?></td>

                    <!-- Show/ hide tree for user -->
                    <td>
                        <input type="text" id="accessids_<?= $data3Db->tree_id; ?>" data-tree="<?= $data3Db->tree_id; ?>" name="access_ids_<?= $data3Db->tree_id; ?>" class="search_gnr" style="width: 100%;" value="<?= $my_accessids; ?>" size="17" placeholder="<?= __('GEDCOM numbers (IDs)'); ?>" >
                    </td>

                    <td>
                        <select size="1" name="access_gen_<?= $data3Db->tree_id; ?>">
                            <option value="0" <?= $my_accessgen == 0  ? 'selected' : ''; ?>><?= __('Only these persons'); ?></option>
                            <option value="1" <?= $my_accessgen == 1  ? 'selected' : ''; ?>>1</option>
                            <option value="2" <?= $my_accessgen == 2  ? 'selected' : ''; ?>>2</option>
                            <option value="3" <?= $my_accessgen == 3  ? 'selected' : ''; ?>>3</option>
                            <option value="4" <?= $my_accessgen == 4  ? 'selected' : ''; ?>>4</option>
                        </select>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </form>
<?php } ?>