<?php
class list_namesModel
{
    public function getAlphabetArray($dbh, $tree_id, $user)
    {
        $person_qry = "SELECT UPPER(substring(pers_lastname,1,1)) as first_character
        FROM humo_persons WHERE pers_tree_id='" . $tree_id . "' GROUP BY first_character ORDER BY first_character";

        // *** Search pers_prefix for names like: "van Mons" ***
        if ($user['group_kindindex'] == "j") {
            $person_qry = "SELECT UPPER(substring(CONCAT(pers_prefix,pers_lastname),1,1)) as first_character
            FROM humo_persons WHERE pers_tree_id='" . $tree_id . "' GROUP BY first_character ORDER BY first_character";
        }
        $person_result = $dbh->query($person_qry);
        $alphabet = [];
        while ($personDb = $person_result->fetch(PDO::FETCH_OBJ)) {
            $alphabet[] = $personDb->first_character;
        }
        return $alphabet;
    }

    public function getMaxCols()
    {
        $maxcols = 2; // number of name & nr colums in table. For example 3 means 3x name col + nr col
        if (isset($_POST['maxcols']) && is_numeric($_POST['maxcols'])) {
            $maxcols = $_POST['maxcols'];
            $_SESSION["save_maxcols"] = $maxcols;
        }
        if (isset($_SESSION["save_maxcols"])) {
            $maxcols = $_SESSION["save_maxcols"];
        }
        return $maxcols;
    }

    public function getMaxNames()
    {
        $maxnames = 100;
        if (isset($_POST['freqsurnames']) && is_numeric($_POST['freqsurnames'])) {
            $maxnames = $_POST['freqsurnames'];
            $_SESSION["save_maxnames"] = $maxnames;
        }
        if (isset($_SESSION["save_maxnames"])) {
            $maxnames = $_SESSION["save_maxnames"];
        }
        return $maxnames;
    }
    public function getItem()
    {    
        $item = 0;
        if (isset($_GET['item'])) {
            $item = $_GET['item'];
        }
        return $item;
    }

    public function getLastName()
    {    
        global $last_name;
        if (isset($_GET['last_name']) && $_GET['last_name'] && is_string($_GET['last_name'])) {
            $last_name = safe_text_db($_GET['last_name']);
        }
        if (empty($last_name)) {
            $last_name = 'all';
        }
       return $last_name;
    }

    public function getAllNames($dbh, $last_name, $item, $maxnames) 
    {
        global $user, $tree_id;
        $lastnames = array();
        $firstchars = array();
        // get all lastnames from cache
        if (!empty($_SESSION['list_names_cache'][$tree_id][$last_name])) {
            $lastnames = $_SESSION['list_names_cache'][$tree_id][$last_name];
        } else {
             // Mons, van or: van Mons
            if ($user['group_kindindex'] == "j") {
                $personqry = "SELECT pers_prefix, pers_lastname, pers_gedcomnumber
                   FROM humo_persons WHERE pers_tree_id='" . $tree_id . "'
                   ORDER BY CONCAT(pers_prefix, pers_lastname)";
             } else {
                $personqry = "SELECT pers_lastname, pers_prefix, pers_gedcomnumber
                    FROM humo_persons WHERE pers_tree_id='" . $tree_id . "'" . " ORDER BY CONCAT(pers_prefix, pers_lastname)";
            }

            $person = $dbh->query($personqry);

            while (@$personDb = $person->fetch(PDO::FETCH_OBJ)) {
                if ($user['group_stealth'] === 'y' && $this->check_list_privacy($personDb->pers_gedcomnumber)) { 
                    continue;
                }
                if ($personDb->pers_lastname == '') {
                    $personDb->pers_lastname = '...';
                }
                // just in case someone put a ; into name or prefix
                $my_name = str_replace(';', '', $personDb->pers_lastname);
                if (!empty($personDb->pers_prefix)){
                    $my_name .= ';' . str_replace(';', '', $personDb->pers_prefix);
                    }
                if (isset($lastnames[$my_name])) {
                    $lastnames[$my_name] =  1 + $lastnames[$my_name];
                } else {
                    $lastnames[$my_name] = 1;
                } 
                $my_firstchar = strtoupper( substr($my_name, 0, 1) );
                if (isset($firstchars[$my_firstchar][$my_name])) {
                    $firstchars[$my_firstchar][$my_name] =  1 + $firstchars[$my_firstchar][$my_name];
                } else {
                    $firstchars[$my_firstchar][$my_name] = 1;
                } 
            }
            $firstchars['all'] = $lastnames;
             $_SESSION['list_names_cache'][$tree_id] = $firstchars;
        //   var_dump($_SESSION['list_names_cache']);
        }
        $rangecnt = 0;
        foreach ($lastnames as $name => $freq) {
            if ($rangecnt < $item) { $rangecnt++; continue; }
            $rangecnt++;
            if ($rangecnt > ($item + $maxnames)) {        break; }

            $my_name_array = explode(';', $name);
            $freq_last_names[] = $my_name_array[0];
            $freq_count_last_names[] = $freq;
            if (!empty($my_name_array[1])){
                $freq_pers_prefix[] = $my_name_array[1];
            } else {
                $freq_pers_prefix[] = '';
            }
        }
        return array('freq_last_names' => $freq_last_names,
                     'freq_pers_prefix' => $freq_pers_prefix, 
                     'freq_count_last_names' => $freq_count_last_names,
                     'count_persons' => count($lastnames)  );
    }
    private function check_list_privacy($gcom_pers){
        global $db_functions;
        $my_persDb = $db_functions->get_person($gcom_pers);
        $pers_cls = new person_cls($my_persDb);
        $my_privacy = $pers_cls->set_privacy($my_persDb);
        return $my_privacy;
    }
    
}
