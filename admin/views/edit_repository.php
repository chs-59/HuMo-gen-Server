<?php

/**
 * Edit or add a repository.
 */

// *** Safety line ***
if (!defined('ADMIN_PAGE')) {
    exit;
}



// TODO create seperate controller script.
include_once(__DIR__ . "/../include/editor_cls.php");
$editor_cls = new editor_cls;

include_once(__DIR__ . "/../include/select_tree.php");

require_once  __DIR__ . "/../models/edit_repository.php";
$editRepositoryModel = new EditorRepositoryModel($dbh);
$editRepositoryModel->set_repo_id();
$editRepositoryModel->update_repository($dbh, $tree_id, $db_functions, $editor_cls);
$editRepository['repo_id'] = $editRepositoryModel->get_repo_id();



$phpself = 'index.php';
$field_text_large = 'style="height: 100px; width:550px"';

// *** Editor icon for admin and editor: select family tree ***
//if (isset($tree_id) and $tree_id) {
//    $db_functions->set_tree_id($tree_id);
//}

$repo_qry = $dbh->query("SELECT * FROM humo_repositories WHERE repo_tree_id='" . $tree_id . "' ORDER BY repo_name, repo_place");
?>

<h1 class="center"><?= __('Repositories'); ?></h1>
<?= __('A repository can be connected to a source. Edit a source to connect a repository.'); ?>

<?php if (isset($_POST['repo_remove'])) { ?>
    <div class="alert alert-danger">
        <strong><?= __('Really remove repository with all repository links?'); ?></strong>
        <form method="post" action="<?= $phpself; ?>" style="display : inline;">
            <input type="hidden" name="page" value="<?= $page; ?>">
            <input type="hidden" name="repo_id" value="<?= $editRepository['repo_id']; ?>">
            <input type="submit" name="repo_remove2" value="<?= __('Yes'); ?>" style="color : red; font-weight: bold;">
            <input type="submit" name="dummy6" value="<?= __('No'); ?>" style="color : blue; font-weight: bold;">
        </form>
    </div>
<?php } ?>

<?php if (isset($_POST['repo_remove2'])) { ?>
    <div class="alert alert-success">
        <strong><?= __('Repository is removed!'); ?></strong>
    </div>
<?php } ?>

<div class="p-3 m-2 genealogy_search">
    <div class="row">
        <div class="col-md-auto">
            <label for="tree" class="col-form-label">
                <?= __('Family tree'); ?>:
            </label>
        </div>

        <div class="col-md-auto">
            <?= select_tree($dbh, $page, $tree_id); ?>
        </div>

        <div class="col-md-auto">
            <label for="tree" class="col-form-label">
                <?= __('Select repository'); ?>
            </label>
        </div>

        <div class="col-md-auto">
            <form method="POST" action="<?= $phpself; ?>" style="display : inline;">
                <input type="hidden" name="page" value="<?= $page; ?>">
                <select size="1" name="repo_id" class="form-select form-select-sm" onChange="this.form.submit();">
                    <option value=""><?= __('Select repository'); ?></option>
                    <?php
                    while ($repoDb = $repo_qry->fetch(PDO::FETCH_OBJ)) {
                        $selected = '';
                        if ($editRepository['repo_id'] == $repoDb->repo_id) {
                            $selected = ' selected';
                        }
                        echo '<option value="' . $repoDb->repo_id . '"' . $selected . '>' .
                            @$repoDb->repo_gedcomnr . ', ' . $repoDb->repo_name . ' ' . $repoDb->repo_place . '</option>' . "\n";
                    }
                    ?>
                </select>
            </form>
        </div>

        <div class="col-auto">
            <?= __('or'); ?>:
            <form method="POST" action="<?= $phpself; ?>" style="display : inline;">
                <input type="hidden" name="page" value="<?= $page; ?>">
                <input type="submit" name="add_repo" value="<?= __('Add repository'); ?>" class="btn btn-sm btn-secondary">
            </form>
        </div>
    </div>
</div>
<?php

// *** Show selected repository ***
if ($editRepository['repo_id'] or isset($_POST['add_repo'])) {
    if (isset($_POST['add_repo'])) {
        $repo_name = '';
        $repo_address = '';
        $repo_zip = '';
        $repo_place = '';
        $repo_phone = '';
        $repo_date = '';
        $repo_text = '';
        $repo_mail = '';
        $repo_url = '';
        $repo_new_user_id = '';
        $repo_new_datetime = '';
        $repo_changed_user_id = '';
        $repo_changed_datetime = '';
    } else {
        @$repo_qry = $dbh->query("SELECT * FROM humo_repositories WHERE repo_id='" . $editRepository['repo_id'] . "'");
        $die_message = __('No valid repository number.');
        try {
            @$repoDb = $repo_qry->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            echo $die_message;
        }
        $repo_name = $repoDb->repo_name;
        $repo_address = $repoDb->repo_address;
        $repo_zip = $repoDb->repo_zip;
        $repo_place = $repoDb->repo_place;
        $repo_phone = $repoDb->repo_phone;
        $repo_date = $repoDb->repo_date;
        $repo_text = $repoDb->repo_text;
        $repo_mail = $repoDb->repo_mail;
        $repo_url = $repoDb->repo_url;
        $repo_new_user_id = $repoDb->repo_new_user_id;
        $repo_new_datetime = $repoDb->repo_new_datetime;
        $repo_changed_user_id = $repoDb->repo_changed_user_id;
        $repo_changed_datetime = $repoDb->repo_changed_datetime;
    }
?>

    <form method="POST" action="<?= $phpself; ?>">
        <input type="hidden" name="page" value="<?= $page; ?>">
        <input type="hidden" name="repo_id" value="<?= $editRepository['repo_id']; ?>">
        <div class="p-2 me-sm-2 genealogy_search">
            <div class="row mb-2">
                <div class="col-md-1"></div>
                <div class="col-md-2">
                    <?= __('Title'); ?>
                </div>
                <div class="col-md-4">
                    <input type="text" name="repo_name" value="<?= htmlspecialchars($repo_name); ?>" size="60" class="form-control form-control-sm">
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-1"></div>
                <div class="col-md-2">
                    <?= __('Address'); ?>
                </div>
                <div class="col-md-4">
                    <input type="text" name="repo_address" value="<?= htmlspecialchars($repo_address); ?>" size="60" class="form-control form-control-sm">
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-1"></div>
                <div class="col-md-2">
                    <?= __('Zip code'); ?>
                </div>
                <div class="col-md-4">
                    <input type="text" name="repo_zip" value="<?= $repo_zip; ?>" size="60" class="form-control form-control-sm">
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-1"></div>
                <div class="col-md-2">
                    <?= ucfirst(__('date')); ?>
                </div>
                <div class="col-md-4">
                    <?= $editor_cls->date_show($repo_date, "repo_date"); ?>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-1"></div>
                <div class="col-md-2">
                    <?= ucfirst(__('place')); ?>
                </div>
                <div class="col-md-4">
                    <input type="text" name="repo_place" value="<?= htmlspecialchars($repo_place); ?>" placeholder="<?= ucfirst(__('place')); ?>" size="50" class="form-control form-control-sm">
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-1"></div>
                <div class="col-md-2">
                    <?= __('Phone'); ?>
                </div>
                <div class="col-md-4">
                    <input type="text" name="repo_phone" value="<?= $repo_phone; ?>" size="60" class="form-control form-control-sm">
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-1"></div>
                <div class="col-md-2">
                    <?= ucfirst(__('text')); ?>
                </div>
                <div class="col-md-4">
                    <textarea rows="1" name="repo_text" <?= $field_text_large; ?> class="form-control form-control-sm"><?= $editor_cls->text_show($repo_text); ?></textarea>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-1"></div>
                <div class="col-md-2">
                    <?= __('E-mail'); ?>
                </div>
                <div class="col-md-4">
                    <input type="text" name="repo_mail" value="<?= $repo_mail; ?>" size="60" class="form-control form-control-sm">
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-1"></div>
                <div class="col-md-2">
                    <?= __('URL/ Internet link'); ?>
                </div>
                <div class="col-md-4">
                    <input type="text" name="repo_url" value="<?= $repo_url; ?>" size="60" class="form-control form-control-sm">
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-1"></div>
                <?php if (isset($_POST['add_repo'])) { ?>
                    <div class="col-md-2">
                        <?= __('Add'); ?>
                    </div>
                    <div class="col-md-2">
                        <input type="submit" name="repo_add" value="<?= __('Add'); ?>" class="btn btn-sm btn-success">
                    </div>
                <?php } else { ?>
                    <div class="col-md-2">
                        <?= __('Save'); ?>
                    </div>
                    <div class="col-md-4">
                        <input type="submit" name="repo_change" value="<?= __('Save'); ?>" class="btn btn-sm btn-success">
                        <?= __('or'); ?>
                        <input type="submit" name="repo_remove" value="<?= __('Delete'); ?>" class="btn btn-sm btn-secondary">
                    </div>
                <?php } ?>
            </div>

        </div>
    </form>
<?php
}
