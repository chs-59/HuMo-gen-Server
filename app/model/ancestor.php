<?php

/**
 * July 2023: refactor ancestor.php to MVC
 */

// *** This model is used by multiple ancestor reports (report/ chart/ sheet) ***

// TODO move to controller?
include_once(__DIR__ . '/family.php');

class AncestorModel extends FamilyModel
{
    //TODO check the $_GET. Normally main_person is used. ID is used for family number.
    public function getMainPerson()
    {
        // TODO this global will be removed.
        global $id;

        $main_person = 'I1'; // *** Mainperson of a family ***

        //if (isset($_GET["main_person"])) {
        //    $main_person = $_GET["main_person"];
        //}
        //if (isset($_POST["main_person"])) {
        //    $main_person = $_POST["main_person"];
        //}
        if (isset($id)) {
            // $id=last variable in link: http://127.0.0.1/humo-genealogy/ancestor_report/3/I1180
            $main_person = $id;
        }
        if (isset($_GET["id"])) {
            $main_person = $_GET["id"];
        }
        if (isset($_POST["id"])) {
            $main_person = $_POST["id"];
        }

        return $main_person;
    }

    public function getFirstnames()
    {
        $firstnames = '';
        if (isset($_GET["firstnames"])) {
            $firstnames = $_GET["firstnames"];
        }
        if (isset($_POST["firstnames"])) {
            $firstnames = $_POST["firstnames"];
        }
        return $firstnames;
    }

    // The following is used for ancestor chart, ancestor sheet and ancestor sheet PDF (ASPDF)
    public function get_ancestors($db_functions, $pers_gedcomnumber)
    {
        global $user;
        
        // person 01
        $personDb = $db_functions->get_person($pers_gedcomnumber);
        $data["firstnames"] = $this->getFirstnames();
        $data["gedcomnumber"][1] = $personDb->pers_gedcomnumber;
        $pers_famc[1] = $personDb->pers_famc;
        $data["sexe"][1] = $personDb->pers_sexe;
        $parent_array[2] = '';
        $parent_array[3] = '';
        if ($pers_famc[1]) {
            $parentDb = $db_functions->get_family($pers_famc[1]);
            $parent_array[2] = $parentDb->fam_man;
            $parent_array[3] = $parentDb->fam_woman;
            $data["marr_date"][2] = $parentDb->fam_marr_date;
            $data["marr_place"][2] = $parentDb->fam_marr_place;
        }

        // Loop to find person data
        $count_max = 64;
        // *** hourglass report ***
        if (isset($hourglass) && $hourglass === true) {
            $count_max = pow(2, $data["chosengenanc"]);
        }

        for ($counter = 2; $counter < $count_max; $counter++) {
            $data["gedcomnumber"][$counter] = '';
            $pers_famc[$counter] = '';
            $data["sexe"][$counter] = '';
            if (!empty($parent_array[$counter])) {
                $personDb = $db_functions->get_person($parent_array[$counter]);
 
                // stealth mode check and skip
                $pers_cls = new person_cls($personDb);
                if ($user['group_stealth'] === 'y' && $pers_cls->set_privacy($personDb)) {continue;}
                
                $data["gedcomnumber"][$counter] = $personDb->pers_gedcomnumber;
                $pers_famc[$counter] = $personDb->pers_famc;
                $data["sexe"][$counter] = $personDb->pers_sexe;
            }

            $Mcounter = $counter * 2;
            $Fcounter = $Mcounter + 1;
            $parent_array[$Mcounter] = '';
            $parent_array[$Fcounter] = '';
            $data["marr_date"][$Mcounter] = '';
            $data["marr_place"][$Mcounter] = '';
            if ($pers_famc[$counter]) {
                $parentDb = $db_functions->get_family($pers_famc[$counter]);
                $parent_array[$Mcounter] = $parentDb->fam_man;
                $parent_array[$Fcounter] = $parentDb->fam_woman;
                $data["marr_date"][$Mcounter] = $parentDb->fam_marr_date;
                $data["marr_place"][$Mcounter] = $parentDb->fam_marr_place;
            }
        }
        return $data;
    }

    // *** Define numbers (max. 60 generations) ***
    //TODO this is also defined in family script.
    public function getNumberRoman()
    {
        return array(
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X',
            11 => 'XI', 12 => 'XII', 13 => 'XIII', 14 => 'XIV', 15 => 'XV', 16 => 'XVI', 17 => 'XVII', 18 => 'XVIII', 19 => 'XIX', 20 => 'XX',
            21 => 'XXI', 22 => 'XXII', 23 => 'XXIII', 24 => 'XXIV', 25 => 'XXV', 26 => 'XXVII', 27 => 'XXVII', 28 => 'XXVIII', 29 => 'XXIX', 30 => 'XXX',
            31 => 'XXXI', 32 => 'XXXII', 33 => 'XXXIII', 34 => 'XXXIV', 35 => 'XXXV', 36 => 'XXXVII', 37 => 'XXXVII', 38 => 'XXXVIII', 39 => 'XXXIX', 40 => 'XL',
            41 => 'XLI', 42 => 'XLII', 43 => 'XLIII', 44 => 'XLIV', 45 => 'XLV', 46 => 'XLVII', 47 => 'XLVII', 48 => 'XLVIII', 49 => 'XLIX', 50 => 'L',
            51 => 'LI',  52 => 'LII',  53 => 'LIII',  54 => 'LIV',  55 => 'LV',  56 => 'LVII',  57 => 'LVII',  58 => 'LVIII',  59 => 'LIX',  60 => 'LX',
        );
    }

    function getAncestorHeader($name, $tree_id, $main_person)
    {
        global $humo_option, $uri_path, $link_cls;

        $data['header_active'] = array();
        $data['header_link'] = array();
        $data['header_text'] = array();
        $data["firstnames"] = $this->getFirstnames();

        $vars['id'] = $main_person;
        $link = $link_cls->get_link($uri_path, 'ancestor_report', $tree_id, true, $vars);
        $link .= 'screen_mode=ancestor_chart';
        $data['header_link'][] = $link;
        $data['header_active'][] = $name == 'Ancestor report' ? 'active' : '';
        $data['header_text'][] = __('Ancestor report');

        // TODO improve paths and variables.
        if ($humo_option["url_rewrite"] == 'j') {
            $path = 'ancestor_chart?tree_id=' . $tree_id . '&amp;id=' . $main_person;
        } else {
            $path = 'index.php?page=ancestor_chart?tree_id=' . $tree_id . '&amp;id=' . $main_person;
        }
        $data['header_link'][] = $path;
        $data['header_active'][] = $name == 'Ancestor chart' ? 'active' : '';
        $data['header_text'][] = __('Ancestor chart');

        if ($humo_option["url_rewrite"] == 'j') {
            $path = 'ancestor_sheet?tree_id=' . $tree_id . '&amp;id=' . $main_person;
        } else {
            $path = 'index.php?page=ancestor_sheet&amp;tree_id=' . $tree_id . '&amp;id=' . $main_person;
        }
        $data['header_link'][] = $path;
        $data['header_active'][] = $name == 'Ancestor sheet' ? 'active' : '';
        $data['header_text'][] = __('Ancestor sheet');

        // *** Fanchart ***
        $path = $link_cls->get_link($uri_path, 'fanchart', $tree_id, true);
        $path .= 'id=' . $main_person;
        $data['header_link'][] = $path;
        $data['header_active'][] = $name == 'Fanchart' ? 'active' : '';
        $data['header_text'][] = __('Fanchart');

        // TODO Move to view (is used multiple times)?
        // *** Tab menu ***
        $text = '
        <h1>' . __('Ancestors') . '</h1>
        <ul class="nav nav-tabs d-print-none" id="nav-tab">
            <li class="nav-item me-1">
                <a class="nav-link genealogy_nav-link ' . $data['header_active'][0] . '" href="' . $data['header_link'][0] . '">' . $data['header_text'][0] . '</a>
            </li>
            <li class="nav-item me-1">
                <a class="nav-link genealogy_nav-link ' . $data['header_active'][1] . '" href="' . $data['header_link'][1] . '">' . $data['header_text'][1] . '</a>
            </li>
            <li class="nav-item me-1">
                <a class="nav-link genealogy_nav-link ' . $data['header_active'][2] . '" href="' . $data['header_link'][2] . '">' . $data['header_text'][2] . '</a>
            </li>
            <li class="nav-item me-1">
                <a class="nav-link genealogy_nav-link ' . $data['header_active'][3] . '" href="' . $data['header_link'][3] . '">' . $data['header_text'][3] . '</a>
            </li>
        </ul>
        <!-- Align content to the left -->
        <!-- <div style="float: left; background-color:white; height:500px; padding:10px;"> -->
        <div style="float: left; background-color:white; padding:10px;">';
        if ($name != 'Ancestor chart') {
            return $text;
        }
        $text .= '        <div class="p-2 me-sm-2 genealogy_search" id="menubox">
            <!-- <div class="p-2 me-sm-2 genealogy_search d-print-none"> -->

            <div class="row">

                <div class="col-md-auto">
                    <input type="button" id="imgbutton" value="';
        $text .= __('Print');
        $text .= '" onClick="showimg();" class="btn btn-sm btn-secondary">
                </div>

                <div class="col-md-auto">
                    <input type="checkbox" id="fnames" name="firstname" onchange="window.location=this.value" value="';
        $text .= $data['header_link'][1] . '&firstnames=';
        if ($data["firstnames"] == 'a') { $text .= 's" checked>'; }
        else { $text .= 'a">'; }
        $text .= '            <label for="fnames"> ';
        $text .= __('all first names'); 
        $text .= '</label>
                </div>

            </div>
        </div>
  ';

        return $text;
    }
}
