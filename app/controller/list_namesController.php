<?php
require_once  __DIR__ . "/../model/list_names.php";

class List_namesController
{
    public function list_names($dbh, $tree_id, $user)
    {
        $list_namesModel = new list_namesModel();
        $get_alphabet_array = $list_namesModel->getAlphabetArray($dbh, $tree_id, $user);
        $get_max_cols = $list_namesModel->getMaxCols();
        $get_max_names = $list_namesModel->getMaxNames();
        $get_item = $list_namesModel->getItem();
        $get_last_name = $list_namesModel->getLastName();
        $get_item = $list_namesModel->getItem();
        $get_all_names = $list_namesModel->getAllNames($dbh, $get_last_name, $get_item, $get_max_names);
        return array(
            "alphabet_array" => $get_alphabet_array,
            "max_cols" => $get_max_cols,
            "max_names" => $get_max_names,
            "last_name" => $get_last_name,
            "item"  => $get_item,
            "freq_last_names" => $get_all_names['freq_last_names'],
            "freq_pers_prefix" => $get_all_names['freq_pers_prefix'],
            "freq_count_last_names" => $get_all_names['freq_count_last_names'],
            "count_persons" => $get_all_names['count_persons']
       );
    }
}
