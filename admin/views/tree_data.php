<form method="post" action="index.php">
    <input type="hidden" name="page" value="tree">
    <input type="hidden" name="tree_id" value="<?= $trees['tree_id']; ?>">
    <input type="hidden" name="menu_admin" value="<?= $trees['menu_tab']; ?>">

    <div class="p-2 me-sm-2 genealogy_search">

        <div class="row mb-2">
            <div class="col-md-3"><?= __('E-mail address'); ?></div>
            <div class="col-md-7">
                <input type="text" name="tree_email" value="<?= $data2Db->tree_email; ?>" size="40" class="form-control form-control-sm">
                <span style="font-size: 13px;"><?= __('E-mail address will not be shown on the site: an e-mail form will be generated!'); ?></span>
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-md-3"><?= __('Owner of tree'); ?></div>
            <div class="col-md-7">
                <input type="text" name="tree_owner" value="<?= $data2Db->tree_owner; ?>" size="40" class="form-control form-control-sm">
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-md-3"><?= __('Path to the pictures'); ?></div>
            <div class="col-md-auto">
                &Gt;&Gt; <a href="index.php?page=thumbs"><?= __('Pictures/ create thumbnails'); ?></a>
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-md-3"><?= __('Tree privacy'); ?></div>
            <div class="col-md-7">
                <select size="1" name="tree_privacy" class="form-select">
                    <option value="standard"><?= __('Standard'); ?></option>
                    <option value="filter_persons" <?= $data2Db->tree_privacy == 'filter_persons' ? 'selected' : ''; ?>><?= __('FILTER ALL persons'); ?></option>
                    <option value="show_persons" <?= $data2Db->tree_privacy == 'show_persons' ? 'selected' : ''; ?>><?= __('DISPLAY ALL persons'); ?></option>
                </select>
                <span style="font-size: 13px;"><?= __('This option is valid for ALL persons in this tree!'); ?></span>
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-md-3"></div>
            <div class="col-md-7">
                <input type="submit" name="change_tree_data" value="<?= __('Change'); ?>" class="btn btn-sm btn-success">
            </div>
        </div>

    </div>
</form>