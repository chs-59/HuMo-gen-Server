<?php
//=================================================================
// relations.php - checks relationships between person X and person Y
//
// written by Yossi Beck - August 2010 for HuMo-gen
// extended marital calculator added by Yossi Beck - February 2014
//
// contains the following functions:
// create_rel_array      - creates $rel_array with gedcom nr and generation nr of ancestors of person X and Y
// compare_rel_array     - compares the $rel_array arrays of X and Y to find common ancestor (can be the persons themselves)
// calculate_rel         - if found, determines the nature of the relation (siblings, ancestors, nephews etc.)
// calculate_ancestor    - calculates the degree of relations (2nd great-grandfather)
// calculate_descendant  - calculates the degree of relations (3rd great-grandson)
// calculate_nephews     - calculates the degree of relations (grand-niece)
// calculate_uncles      - calculates the degree of relations (4th great-grand-uncle)
// calculate_cousins     - calculates the degree of relations (2nd cousin twice removed)
// search_marital        - if no direct blood relation found, searches for relation between spouses of person X and Y
// search_bloodrel       - searches for blood relationship between X and Y
// display               - displays the result of comparison checks
// display_table         - displays simple chart showing the found relationship
// unset_var             - unsets the vital variables before searching marital relations
// get_person            - retrieves person from MySQL database by gedcom nr
// dutch_ancestor        - special algorithm to process complicated dutch terminology for distant ancestors
//
// the meaning of the value of the $table variable (for displaying table with lineage if a match is found):
// 1 = parent - child
// 2 = child - parent
// 3 = uncle - nephew
// 4 = nephew - uncle
// 5 = cousin
// 6 = siblings
// 7 = spouses or self
//
// the meaning of the value of the $spouse variable (flagging type of relationship check):
// 0 = checks relation X vs Y
// 1 = checks relation spouse of X versus person Y
// 2 = checks relation person X versus spouse of Y
// 3 = checks relation spouse of X versus spouse of Y
//
// values in the genarray:
// the genarray is an array of the ancestors of a base person (one of the two persons entered in the search or their spouses)
// genarray[][0] = gedcom number of the person
// genarray[][1] = number of generations (counted from base person)
// genarray[][2] = array number of child
//
// meaning of some other global variables:
// $doublespouse - flags situation where searched persons X and Y are both spouses of a third person
// $special_spouseX (and Y) - flags situation where the regular text "spouse of" has to be changed:
// ----- for example: "X is spouse of brother of Y" should become "X is sister-in-law of Y"
// $sexe, $sexe2 - the sexe of persons X and Y
// person, $person2 - gedcom nr of the searched persons X and Y
//============================================================================================
ini_set('max_execution_time', 600);

//global declarations for Joomla
global $foundX_nr, $foundY_nr, $foundX_gen, $foundY_gen, $foundX_match, $foundY_match, $spouse;
global $reltext, $sexe, $sexe2, $special_spouseY, $special_spouseX, $doublespouse, $table;
global $ancestortext, $dutchtext, $selected_language, $spantext;
global $famsX, $famsY, $famX, $famY, $famspouseX, $famspouseY, $rel_arrayX, $rel_arrayY, $spousenameX, $spousenameY;
global $rel_arrayspouseX, $rel_arrayspouseY;
global $person, $person2, $searchDb, $searchDb2;
global $bloodreltext, $name1, $name2, $gednr, $gednr2;
global $fampath;
// *** Only needed for dutch relation names ***
global $hoog, $opper, $aarts, $voor, $edel, $stam, $oud, $rest;

include_once("header.php"); // returns CMS_ROOTPATH constant
include_once(CMS_ROOTPATH."menu.php");
require_once(CMS_ROOTPATH."include/person_cls.php");
include_once(CMS_ROOTPATH."include/marriage_cls.php");
include_once(CMS_ROOTPATH."include/language_date.php");
include_once(CMS_ROOTPATH."include/date_place.php");

if(CMS_SPECIFIC == "Joomla") {
	$fampath = "index.php?option=com_humo-gen&amp;task=family&amp"; // path to family.php for joomla (used some 20 times in this code
}
else {
	$fampath = CMS_ROOTPATH."family.php?";
}

function create_rel_array ($gednr)  {
	// creates array of ancestors of person with gedcom nr. $gednr
	global $dbh, $db_functions, $tree_prefix_quoted;

	$family_id=$gednr;
	$ancestor_id2[] = $family_id;
	$ancestor_number2[] = 1;
	$marriage_number2[] = 0;
	$generation = 1;
	$genarray_count = 0;

	// *** Loop ancestor report ***
	while (isset($ancestor_id2[0])){
		unset($ancestor_id);
		$ancestor_id=$ancestor_id2;
		unset($ancestor_id2);

		unset($ancestor_number);
		$ancestor_number=$ancestor_number2;
		unset($ancestor_number2);

		unset($marriage_number);
		$marriage_number=$marriage_number2;
		unset($marriage_number2);

		// *** Loop per generation ***
		$kwcount=count($ancestor_id);
		for ($i=0; $i<$kwcount; $i++) {

			if ($ancestor_id[$i]!='0'){
				$person_manDb=$db_functions->get_person($ancestor_id[$i]);
				$man_cls = New person_cls;
				$man_cls->construct($person_manDb);
				$man_privacy=$man_cls->privacy;

				if (strtolower($person_manDb->pers_sexe)=='m' AND $ancestor_number[$i]>1){
					@$familyDb=$db_functions->get_family($marriage_number[$i]);

					// *** Use privacy filter of woman ***
					$person_womanDb=$db_functions->get_person($familyDb->fam_woman);
					$woman_cls = New person_cls;
					$woman_cls->construct($person_womanDb);
					$woman_privacy=$woman_cls->privacy;

					// *** Use class for marriage ***
					$marriage_cls = New marriage_cls;
					$marriage_cls->construct($familyDb, $man_privacy, $woman_privacy);
					$family_privacy=$marriage_cls->privacy;
				}

				//*** Show person data ***
				$genarray[$genarray_count][0]= $ancestor_id[$i];
				$genarray[$genarray_count][1]= $generation-1;
				$genarray_count++; // increase by one

				// *** Check for parents ***
				if ($person_manDb->pers_famc){
					@$familyDb = $db_functions->get_family($person_manDb->pers_famc);
					if ($familyDb->fam_man){
						$ancestor_id2[] = $familyDb->fam_man;
						$ancestor_number2[]=(2*$ancestor_number[$i]);
						$marriage_number2[]=$person_manDb->pers_famc;
						$genarray[][2]= $genarray_count-1;
						// save array nr of child in parent array so we can build up ancestral line later
					}

					if ($familyDb->fam_woman){
						$ancestor_id2[]= $familyDb->fam_woman;
						$ancestor_number2[]=(2*$ancestor_number[$i]+1);
						$marriage_number2[]=$person_manDb->pers_famc;
						$genarray[][2]= $genarray_count-1;
						// save array nr of child in parent array so we can build up ancestral line later
					}
				}
			}
		}	// loop per generation
		$generation++;
	}	// loop ancestors

	return @$genarray;
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

function compare_rel_array($arrX, $arrY, $spouce_flag) {

global $foundX_nr, $foundY_nr, $foundX_gen, $foundY_gen, $foundX_match, $foundY_match, $spouse;

	foreach($arrX as $keyx=>$valx) {
		foreach($arrY as $keyy=>$valy) {
			if($arrX[$keyx][0]==$arrY[$keyy][0]) {
					$foundX_match=$keyx;  // saves the array nr of common ancestor in ancestor array of X
					$foundY_match=$keyy;  // saves the array nr of common ancestor in ancestor array of Y
					if(isset($arrX[$keyx][2])) { $foundX_nr=$arrX[$keyx][2]; } // saves the array nr of the child leading to X
					if(isset($arrY[$keyy][2])) { $foundY_nr=$arrY[$keyy][2]; } // saves the array nr of the child leading to Y
					if(isset($arrX[$keyx][1])) { $foundX_gen=$arrX[$keyx][1]; }// saves the nr of generations common ancestor is removed from X
					if(isset($arrY[$keyy][1])) { $foundY_gen=$arrY[$keyy][1]; }// saves the nr of generations common ancestor is removed from Y
					$spouse=$spouce_flag; // saves global variable flagging if we're comparing X - Y or spouse combination
					return;
			}
		}
	}
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

function calculate_rel ($arr_x, $arr_y, $genX, $genY) {
// calculates the relationship found: "X is 2nd cousin once removed of Y"
global $reltext, $sexe, $spouse, $special_spouseY, $special_spouseX, $doublespouse, $table, $language;

	$doublespouse=0;
	if ( $arr_x == 0 AND $arr_y == 0 ) {  // self
		$reltext = __(' identical to ');
		if($spouse==1 OR $spouse==2) { $reltext = " "; }
		if($spouse==3) {$doublespouse=1; }
		// it's the spouse itself so text should be "X is spouse of Y", not "X is spouse of is identical to Y" !!
		$table=7;
	}
	elseif ( $arr_x == 0 AND $arr_y > 0 ) {  // x is ancestor of y
		$table=1;
		calculate_ancestor ($genY);
	}
	elseif ( $arr_y == 0 AND $arr_x > 0 ) {  // x is descendant of y
		$table=2;
		calculate_descendant ($genX);
	}
	elseif ( $genX == 1 AND $genY == 1 ) {  // x is brother of y

		$table=6;
		if($sexe=='m') {
			$reltext = __('brother of ');
			if($spouse==1) { $reltext = __('sister-in-law of '); $special_spouseX=1;}  //comparing spouse of X with Y
			if($spouse==2 OR $spouse==3) { $reltext =  __('brother-in-law of '); $special_spouseY=1;} //comparing X with spouse of Y or comparing 2 spouses
			//$special_spouseX flags not to enter "spouse of" for X in display function
			//$special_spouseY flags not to enter "spouse of" for Y in display function
		}
		else {
			$reltext = __('sister of ');
			if($spouse==1) { $reltext =  __('brother-in-law of '); $special_spouseX=1;}  //comparing spouse of X with Y
			if($spouse== 2 OR $spouse==3) { $reltext =  __('sister-in-law of '); $special_spouseY=1;}  //comparing X with spouse of Y or comparing 2 spouses
			//$special_spouseX flags not to enter "spouse of" for X in display function
			//$special_spouseY flags not to enter "spouse of" for Y in display function
		}
	}
	elseif ( $genX == 1 AND $genY > 1 ) {  // x is uncle, great-uncle etc of y
		$table=3;
		calculate_uncles ($genY);
	}
	elseif ( $genX > 1 AND $genY == 1 ) {  // x is nephew, great-nephew etc of y
		$table=4;
		calculate_nephews ($genX);
	}
	else {  // x and y are cousins of any number (2nd, 3rd etc) and any distance removed (once removed, twice removed etc)
		$table=5;
		calculate_cousins ($genX, $genY);
	}
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function spanish_degrees ($pers, $text) {
	global $spantext, $language;
	if ($pers == 2) { $spantext = $text; }
	if ($pers == 3) { $spantext = 'bis'.$text; }
	if ($pers == 4) { $spantext = 'tris'.$text; }
	if ($pers == 5) { $spantext = 'tetra'.$text; }
	if ($pers == 6) { $spantext = 'penta'.$text; }
	if ($pers == 7) { $spantext = 'hexa'.$text; }
	if ($pers == 8) { $spantext = 'hepta'.$text; }
	if ($pers == 9) { $spantext = 'octa'.$text; }
	if ($pers == 10) { $spantext = 'nona'.$text; }
	if ($pers == 11) { $spantext = 'deca'.$text; }
	if ($pers == 12) { $spantext = 'undeca'.$text; }
	if ($pers == 13) { $spantext = 'dodeca'.$text; }
	if ($pers == 14) { $spantext = 'trideca'.$text; }
	if ($pers == 15) { $spantext = 'tetradeca'.$text; }
	if ($pers == 16) { $spantext = 'pentadeca'.$text; }
	if ($pers == 17) { $spantext = 'hexadeca'.$text; }
	if ($pers == 18) { $spantext = 'heptadeca'.$text; }
	if ($pers == 19) { $spantext = 'octadeca'.$text; }
	if ($pers == 20) { $spantext = 'nonadeca'.$text; }
	if ($pers == 21) { $spantext = 'icosa'.$text; }
	if ($pers == 22) { $spantext = 'unicosa'.$text; }
	if ($pers == 23) { $spantext = 'doicosa'.$text; }
	if ($pers == 24) { $spantext = 'tricosa'.$text; }
	if ($pers == 25) { $spantext = 'tetricosa'.$text; }
	if ($pers == 26) { $spantext = 'penticosa'.$text; }
}
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

function calculate_ancestor($pers) {
global $db_functions, $reltext, $sexe, $spouse, $special_spouseY, $language, $ancestortext, $dutchtext, $selected_language, $spantext, $generY, $foundY_nr, $rel_arrayY;
global $rel_arrayspouseY;

	$anscestortext='';
	if($sexe=='m') {
		$parent = __('father');
	}
	else {
		$parent = __('mother');
	}

	if ($pers == 1) {
		if($spouse==2 OR $spouse==3) {
			$special_spouseY=1; // prevents "spouse of Y" in output
			if($parent==__('father')) { $parent=__('father-in-law'); }
			else { $parent=__('mother-in-law');}
		}
		$reltext = $parent.__(' of ');
	}
	else {
		if($selected_language=="nl") {
			dutch_ancestors($pers);
			$reltext= $ancestortext.$parent.__(' of ');
			if ($pers >4 ) {
				$gennr=$pers-2;
				$dutchtext =  "(".$ancestortext.$parent." = ".$gennr.__('th').__('great-grand').$parent.")";
			}

		}
		elseif($selected_language=="es") {
			if($parent==__('father')) { $grparent='abuelo'; $spanishnumber="o"; }
			else {$grparent='abuela'; $spanishnumber="a";}
			$gennr=$pers-1;
			$degree=$gennr.$spanishnumber." ".$grparent;
			if ($pers == 2) { $reltext = $grparent.__(' of '); }
			elseif($pers >2 AND $pers <27) {
				spanish_degrees($pers,$grparent); // sets spanish "bis", "tris" etc prefix
				$reltext = $spantext." (".$degree.")".__(' of ');
			}
			else { $reltext = $degree.__(' of '); }
		}
		elseif($selected_language=="he") {
			if($parent==__('father')) { $grparent=__('grand'); $grgrparent=__('great-grand'); }
			else {$grparent=__('grand');  $grgrparent=__('great-grand');  }
			$gennr=$pers-2;
			if ($pers == 2) { $reltext = $grparent.__(' of '); }
			elseif ($pers > 2) {
				$degree='';
				if($pers >3) {
					$degree=' דרגה ';
					$degree.=$gennr;
				}
				$reltext = $grgrparent.$degree.__(' of ');
			}
		}
		elseif($selected_language=="fi") {
			if ($pers == 2) { $reltext = __('grand').$parent.__(' of '); }
			$gennr=$pers-1;
			if ($pers >  2) { $reltext = $gennr.'. '.__('grand').$parent.__(' of '); }
		}
		elseif($selected_language=="no") {
			if ($pers == 2) { $reltext = __('grand').$parent.__(' of '); }
			if ($pers == 3) { $reltext = __('great-grand').$parent.__(' of '); }
			if ($pers == 4) { $reltext = 'tippolde'.$parent.__(' of '); }
			if ($pers == 5) { $reltext = 'tipp-tippolde'.$parent.__(' of '); }
			$gennr=$pers-3;
			if ($pers >  5) { $reltext = $gennr."x ".'tippolde'.$parent.__(' of '); }
		}

		// Swedish needs to know if grandparent is related through mother or father - different names there
		// also for great-grandparent and 2nd great-grandparent!!!
		elseif($selected_language=="sv"){

			if($spouse=="2" OR $spouse=="3") { // right person is spouse of Y, not Y
				$relarr = $rel_arrayspouseY;
			}
			else {
				$relarr = $rel_arrayY;
			}

			if ($pers > 1) {
				// grandfather
 				$arrnum=0; reset($ancsarr);
				$count=$foundY_nr;
				while($count!=0) {
					$parnumber=$count;
					$ancsarr[$arrnum]=$parnumber; $arrnum++;
					$count=$relarr[$count][2];
				}
				$persidDb=$db_functions->get_person($relarr[$parnumber][0]);
				$parsexe = $persidDb->pers_sexe;
				if($parsexe=='M') { $se_grandpar = 'far'.$parent; $direct_par = 'far';}
				else { $se_grandpar = 'mor'.$parent; $direct_par = 'mor';}
			}
 
			if ($pers > 2) {
				// great-grandfather
				$persidDb2=$db_functions->get_person($relarr[$ancsarr[$arrnum-2]][0]);
				$parsexe2 = $persidDb2->pers_sexe; 
   
				if($parsexe2=="M") {
					if($parsexe=="M") $se_gr_grandpar = 'farfars '.$parent;
					else $se_gr_grandpar = 'morfars '.$parent;
				}
				else {
					if($parsexe=="M") $se_gr_grandpar = 'farmors '.$parent;
					else $se_gr_grandpar = 'mormors '.$parent;
				}
 
 			}

			if ($pers > 3)  {
				// 2nd great-grandfather
				$persidDb3=$db_functions->get_person($relarr[$ancsarr[$arrnum-3]][0]);
				$parsexe3 = $persidDb3->pers_sexe;    
				if($parsexe3=="M") {
					if($parsexe2=="M") {
						if($parsexe=="M") $se_2ndgr_grandpar = 'farfars far'.$parent;
						else $se_2ndgr_grandpar = 'morfars far'.$parent;
					}
					else {
						if($parsexe=="M") $se_2ndgr_grandpar = 'farmors far'.$parent;
						else $se_2ndgr_grandpar = 'mormors far'.$parent;
					}
				}
				else {
					if($parsexe2=="M") {
						if($parsexe=="M") $se_2ndgr_grandpar = 'farfars mor'.$parent;
						else $se_2ndgr_grandpar = 'morfars mor'.$parent;
					}
					else {
						if($parsexe=="M") $se_2ndgr_grandpar = 'farmors mor'.$parent;
						else $se_2ndgr_grandpar = 'mormors mor'.$parent;
					}
				}
			}
 
			if ($pers == 2) { $reltext = $se_grandpar.__(' of '); }
			if ($pers == 3) { $reltext = $se_gr_grandpar.__(' of '); }
			if ($pers == 4) { $reltext = $se_2ndgr_grandpar.__(' of '); }
			$gennr=$pers;
			if ($pers >  4) { $reltext = $gennr.':e generations ana på '.$direct_par.'s sida'.__(' of '); } 
		}

		else { // *** Other languages ***
			if ($pers == 2) { $reltext = __('grand').$parent.__(' of '); }
			if ($pers == 3) { $reltext = __('great-grand').$parent.__(' of '); }
			if ($pers == 4) { $reltext = __('2nd').' '.__('great-grand').$parent.__(' of '); }
			if ($pers == 5) { $reltext = __('3rd').' '.__('great-grand').$parent.__(' of '); }
			$gennr=$pers-2;
			if ($pers >  5) { $reltext = $gennr.__('th').' '.__('great-grand').$parent.__(' of '); } 
		}
	}
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function dutch_ancestors($gennr) {
	global $ancestortext;
	global $hoog, $opper, $aarts, $voor, $edel, $stam, $oud, $rest;

	if ($gennr > 512){
		$text = " Neanthertaler ancestor of ";    //  ;-)
	}
	else {
		if ($gennr > 256) {
			$hoog="hoog-";
			$gennr-=256;
			dutch_ancestors($gennr);
		}
		elseif ($gennr > 128) {
			$opper="opper-";
			$gennr-=128;
			dutch_ancestors($gennr);
		}
		elseif ($gennr > 64) {
			$aarts="aarts-";
			$gennr-=64;
			dutch_ancestors($gennr);
		}
		elseif ($gennr > 32) {
			$voor="voor-";
			$gennr-=32;
			dutch_ancestors($gennr);
		}
		elseif ($gennr > 16) {
			$edel="edel-";
			$gennr-=16;
			dutch_ancestors($gennr);
		}
		elseif ($gennr > 8) {
			$stam="stam-";
			$gennr-=8;
			dutch_ancestors($gennr);
		}
		elseif ($gennr > 4) {
			$oud="oud";
			$gennr-=4;
			dutch_ancestors($gennr);
		}
		else {
			if ($gennr==4) { $rest='betovergroot'; }
			if ($gennr==3) { $rest='overgroot'; }
			if ($gennr==2) { $rest='groot'; }
			if ($gennr==1) { $rest=''; }
		}
	}
	$ancestortext= $hoog.$opper.$aarts.$voor.$edel.$stam.$oud.$rest;
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

function calculate_descendant($pers) {
global $db_functions, $reltext, $sexe, $spouse, $special_spouseX, $language, $selected_language, $spantext, $foundX_nr, $rel_arrayX, $rel_arrayspouseX;

	if($sexe=='m') {
		$child = __('son');
	}
	else {
		$child = __('daughter');
	}
	if ($pers == 1) {
		if($spouse==1) {
			if($child==__('son')) { $child=__('daughter-in-law'); }
			else { $child=__('son-in-law');}
			$special_spouseX=1;
		}
		$reltext = $child.__(' of ');
	}
	elseif($selected_language=="es") {
		if($child == __('son')) { $grchild='nieto'; $spanishnumber="o"; }
		else {$grchild='nieta'; $spanishnumber="a";}
		$gennr=$pers-1;
		$degree=$gennr.$spanishnumber." ".$grchild;
		if ($pers == 2) { $reltext = $grchild.__(' of '); }
		elseif($pers >2 AND $pers <27) {
			spanish_degrees($pers,$grchild); // sets spanish "bis", "tris" etc prefix
			$reltext = $spantext." (".$degree.")".__(' of ');
		}
		else { $reltext = $degree.__(' of '); }
	}
	elseif($selected_language=="he") {
		if($child==__('son')) { $grchild='נכד '; $grgrchild='נין '; }
		else {$grchild='נכדה ';  $grgrchild='נינה '; }
		$gennr=$pers-2;
		if ($pers == 2) { $reltext = $grchild.__(' of '); }
		elseif ($pers > 2) {
			$degree='';
			if($pers >3) { $degree='דרגה '.$gennr; }
			$reltext = $grgrchild.$degree.__(' of ');
		}
	}
	elseif($selected_language=="fi") {
		if ($pers == 2) { $reltext = __('grandchild').__(' of '); }
		$gennr=$pers-1;
		if ($pers >  2) { $reltext = $gennr.'. '.__('grandchild').__(' of '); }
	}
	elseif($selected_language=="no") {
		$child = 'barnet'; // barn
		if ($pers == 2) { $reltext = 'barnebarnet '.__(' of '); } // barnebarn
		if ($pers == 3) { $reltext = __('great-grand').$child.__(' of '); } // olde + barn
		if ($pers == 4) { $reltext = 'tippolde'.$child.__(' of '); } // tippolde + barn
		if ($pers == 5) { $reltext = 'tipp-tippolde'.$child.__(' of '); } // tipp-tippolde + barn
		$gennr=$pers-3;
		if ($pers >  5) { $reltext = $gennr.'x tipp-tippolde'.$child.__(' of '); }
	}
	// Swedish needs to know if grandchild is related through son or daughter - different names there
	// also for great-grandchild and 2nd great-grandchild!!!
	elseif($selected_language=="sv"){

		if($spouse=="1" OR $spouse=="3") { // right person is spouse of Y, not Y
			$relarr = $rel_arrayspouseX;
		}
		else {
			$relarr = $rel_arrayX;
		}

		if ($pers > 1) {
			// grandchild
			$arrnum=0; reset($ancsarr);
			$count=$foundX_nr;
			while($count!=0) {
				$parnumber=$count;
				$ancsarr[$arrnum]=$parnumber; $arrnum++;
				//$count=$rel_arrayX[$count][2];
				$count=$relarr[$count][2];
			}
			$persidDb=$db_functions->get_person($relarr[$foundX_nr][0]);
			$parsexe = $persidDb->pers_sexe;
			if($parsexe=='M') { $se_grandch = 'son'.$child; $direct_ch = 'son';}
			else { $se_grandch = 'dotter'.$child; $direct_ch = 'dotter';}
		}

		if ($pers > 2) {
			// great-grandchild
			$persidDb2=$db_functions->get_person($relarr[$ancsarr[1]][0]);
			$parsexe2 = $persidDb2->pers_sexe; 

			if($parsexe2=="M") {
				if($parsexe=="M") $se_gr_grandch = 'sonsons '.$child;
				else $se_gr_grandch = 'dottersons '.$child;
			}
			else {
				if($parsexe=="M") $se_gr_grandch = 'sondotters '.$child;
				else $se_gr_grandch = 'dotterdotters '.$child;
			}

		}

		if ($pers > 3)  {
			// 2nd great-grandchild
			$persidDb3=$db_functions->get_person($relarr[$ancsarr[2]][0]);
			$parsexe3 = $persidDb3->pers_sexe;    
			if($parsexe3=="M") {
				if($parsexe2=="M") {
					if($parsexe=="M") $se_2ndgr_grandch = 'sonsons son'.$child;
					else $se_2ndgr_grandch = 'dottersons son'.$child;
				}
				else {
					if($parsexe=="M") $se_2ndgr_grandch = 'sondotters son'.$child;
					else $se_2ndgr_grandch = 'dotterdotters son'.$child;
				}
			}
			else {
				if($parsexe2=="M") {
					if($parsexe=="M") $se_2ndgr_grandch = 'sonsons dotter'.$child;
					else $se_2ndgr_grandch = 'dottersons dotter'.$child;
				}
				else {
					if($parsexe=="M") $se_2ndgr_grandch = 'sondotters dotter'.$child;
					else $se_2ndgr_grandch = 'dotterdotters dotter'.$child;
				}
			}
		}

		if ($pers == 2) { $reltext = $se_grandch.__(' of '); }
		if ($pers == 3) { $reltext = $se_gr_grandch.__(' of '); }
		if ($pers == 4) { $reltext = $se_2ndgr_grandch.__(' of '); }
		$gennr=$pers;
		if ($pers >  4) { $reltext = $gennr.':e generations barn'.__(' of '); } 
	}

	else {
		if ($pers == 2) { $reltext = __('grand').$child.__(' of '); }
		if ($pers == 3) { $reltext = __('great-grand').$child.__(' of '); }
		if ($pers == 4) { $reltext = __('2nd').' '.__('great-grand').$child.__(' of '); }
		if ($pers == 5) { $reltext = __('3rd').' '.__('great-grand').$child.__(' of '); }
		$gennr=$pers-2;
		if ($pers >  5) { $reltext = $gennr.__('th').' '.__('great-grand').$child.__(' of '); }
	}
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

function calculate_nephews($generX) { // handed generations x is removed from common ancestor
global $db_functions, $reltext, $sexe, $language, $spantext, $selected_language, $reltext_nor, $reltext_nor2, $foundX_nr, $rel_arrayX, $rel_arrayspouseX, $spouse;

	if($selected_language=="es"){
		if($sexe=="m") { $neph=__('nephew'); $span_postfix="o "; $grson='nieto'; }
		else { $neph=__('niece'); $span_postfix="a "; $grson='nieta'; }
		//$gendiff = abs($generX - $generY); // FOUT
		$gendiff = abs($generX - $generY) - 1;
		$gennr=$gendiff-1;
		$degree=$grson." ".$gennr.$span_postfix;
		if($gendiff ==1) { $reltext=$neph.__(' of ');}
		elseif($gendiff > 1 AND $gendiff < 27) {
			spanish_degrees($gendiff,$grson);
			$reltext=$neph." ".$spantext.__(' of ');
		}
		else { $reltext=$neph." ".$degree; }
	}
	elseif ($selected_language=="he"){
		if($sexe=='m') { $nephniece = __('nephew'); }
		else { $nephniece = __('niece'); }
		$gendiff = abs($generX - $generY);
		$gennr=$gendiff-1;
		if($gendiff ==1) { $reltext=$nephniece.__(' of ');}
		elseif($gendiff > 1 ) {
			if($gendiff >2) { $degree='דרגה '.$gennr; }
			$reltext=$nephniece.$degree.__(' of ');
		}
	}
	elseif ($selected_language=="fi"){
		if($sexe=='m') {
			$nephniece = __('nephew');
		}
		else {
			$nephniece = __('niece');
		}
		if ($generX == 2) { $reltext = $nephniece.__(' of '); }
		if ($generX == 3) { $reltext = __('grand').$nephniece.__(' of '); }
		$gennr=$generX-2;
		if ($generX >  3) { $reltext = $gennr.'. '.__('grand').$nephniece.__(' of '); }
	}
	elseif ($selected_language=="no"){
		if($sexe=='m') { $nephniece = __('nephew'); }
		else { $nephniece = __('niece'); }
		$reltext_nor=''; $reltext_nor2='';
		if ($generX > 3) {
			$reltext_nor = "s ".substr('søskenet',0,-2);  // for: A er oldebarnet av Bs søsken

			$reltext_nor2 = 'søskenet'.
			__(' of '); // for: A er oldebarnet av søskenet av mannen til B
		}
		if ($generX == 2) { $reltext = $nephniece.__(' of '); }
		if ($generX == 3) { $reltext = 'grand'.$nephniece.__(' of '); }
		if ($generX == 4) { $reltext = __('great-grand').' barnet'.__(' of '); }
		if ($generX == 5) { $reltext = 'tippolde barnet'.__(' of '); }
		if ($generX == 6) { $reltext = 'tipp-tippolde barnet'.__(' of '); }
		$gennr=$generX-4;
		if ($generX >  6) { $reltext = $gennr.'x tippolde barnet'.__(' of '); }
	}
	elseif ($selected_language=="nl"){
		if($sexe=='m') {
			$nephniece = __('nephew');
		}
		else {
			$nephniece = __('niece');
		}
		// in Dutch we use the __('3rd [COUSIN]') variables, that work for nephews as well
		if ($generX == 2) { $reltext = $nephniece.__(' of '); }
		if ($generX == 3) { $reltext = __('2nd [COUSIN]').$nephniece.__(' of '); }
		if ($generX == 4) { $reltext = __('3rd [COUSIN]').$nephniece.__(' of '); }
		if ($generX == 5) { $reltext = __('2nd').' '.__('3rd [COUSIN]').$nephniece.__(' of '); }
		if ($generX == 6) { $reltext = __('3rd').' '.__('3rd [COUSIN]').$nephniece.__(' of '); }
		$gennr=$generX-3;
		if ($generX >  6) { $reltext = $gennr.__('th ').__('3rd [COUSIN]').$nephniece.__(' of '); }
	}	
 	elseif ($selected_language=="sv"){
		// Swedish needs to know if nephew/niece is related through brother or sister - different names there
		// also for grandnephew!!!

		if($spouse=="1" OR $spouse=="3") { // right person is spouse of Y, not Y
			$relarr = $rel_arrayspouseX;
		}
		else {
			$relarr = $rel_arrayX;
		}

		if($sexe=='m') {
			$nephniece = "son";
		}
		else {
			$nephniece = "dotter";
		}
		if ($generX >1) {
		// niece/nephew
 		$arrnum=0; reset($ancsarr);
		$count=$foundX_nr;
		while($count!=0) {
			$parnumber=$count;
			$ancsarr[$arrnum]=$parnumber; $arrnum++;
			$count=$relarr[$count][2];
		}
		$persidDb=$db_functions->get_person($relarr[$parnumber][0]);
		$parsexe = $persidDb->pers_sexe;
		if($parsexe=='M') { $se_nephniece = 'bror'.$nephniece; }
		else { $se_nephniece = 'syster'.$nephniece; }
		}
		if ($generX == 3) {
			// grandniece/nephew
			$persidDb2=$db_functions->get_person($relarr[$ancsarr[$arrnum-2]][0]);
			$parsexe2 = $persidDb2->pers_sexe;
			if($parsexe2=="M") {
				if($parsexe=="M") $se_gr_nephniece = 'brors son'.$nephniece;
				else $se_gr_nephniece = 'brors dotter'.$nephniece;
			}
			else {
				if($parsexe=="M") $se_gr_nephniece = 'systers son'.$nephniece;
				else $se_gr_nephniece = 'systers dotter'.$nephniece;
			}
 		}  
		if ($generX == 2) { $reltext = $se_nephniece.__(' of '); }
		if ($generX == 3) { $reltext = $se_gr_nephniece.__(' of '); }
		$gennr=$generX-1;
		if ($generX >  3) { 
			$persidDb=$db_functions->get_person($rel_arrayX[$foundX_nr][0]);
			$parsexe = $persidDb->pers_sexe;
			if($parsexe=='M') { $se_sib = "bror"; }
			else { $se_sib = "syster"; }
			$reltext = $se_sib.'s '.$gennr.':e generations barn'.__(' of '); 
		}
	}   
	else {
		if($sexe=='m') {
			$nephniece = __('nephew');
		}
		else {
			$nephniece = __('niece');
		}
		if ($generX == 2) { $reltext = $nephniece.__(' of '); }
		if ($generX == 3) { $reltext = __('grand').$nephniece.__(' of '); }
		if ($generX == 4) { $reltext = __('great-grand').$nephniece.__(' of '); }
		if ($generX == 5) { $reltext = __('2nd').' '.__('great-grand').$nephniece.__(' of '); }
		if ($generX == 6) { $reltext = __('3rd').' '.__('great-grand').$nephniece.__(' of '); }
		$gennr=$generX-3;
		if ($generX >  6) { $reltext = $gennr.__('th ').__('great-grand').$nephniece.__(' of '); }
	}
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

function calculate_uncles($generY) { // handed generations y is removed from common ancestor
global $db_functions, $reltext,  $sexe, $language, $ancestortext, $dutchtext, $selected_language, $spantext, $rel_arrayspouseY, $spouse;
global $foundY_nr, $rel_arrayY, $fampath;  // only for Finnish paragraph
global $reltext_nor, $reltext_nor2; // for Norwegian

	$ancestortext='';
	if($sexe=='m') {
		$uncleaunt = __('uncle');

		// Finnish needs to know if uncle is related through mother or father - different names there
		if($selected_language=="fi"){
			$count=$foundY_nr;
			while($count!=0) {
				$parnumber=$count;
				$count=$rel_arrayY[$count][2];
			}
			$persidDb=$db_functions->get_person($rel_arrayY[$parnumber][0]);
			$parsexe = $persidDb->pers_sexe;
			if($parsexe=='M') { $uncleaunt = 'setä'; }
			else { $uncleaunt = 'eno'; }
		}

		// Swedish needs to know if uncle is related through mother or father - different names there
		// also for granduncle and great-granduncle!!!
		if($selected_language=="sv"){

			if($spouse=="2" OR $spouse=="3") { // right person is spouse of Y, not Y
				$relarr = $rel_arrayspouseY;
			}
			else {
				$relarr = $rel_arrayY;
			}

			$se_sibling = "bror"; // used for gr_gr_granduncle and more "4:e gen anas bror"
			// uncle
 			$arrnum=0; reset($ancsarr);
			$count=$foundY_nr;
			while($count!=0) {
				$parnumber=$count;
				$ancsarr[$arrnum]=$parnumber; $arrnum++;
				$count=$relarr[$count][2];
			}
			$persidDb=$db_functions->get_person($relarr[$parnumber][0]);
			$parsexe = $persidDb->pers_sexe;
			if($parsexe=='M') { $uncleaunt = 'farbror'; }
			else { $uncleaunt = 'morbror'; }

			if ($generY > 2) {
				// granduncle
				$persidDb2=$db_functions->get_person($relarr[$ancsarr[$arrnum-2]][0]);
				$parsexe2 = $persidDb2->pers_sexe;    
				if($parsexe2=="M") {
					if($parsexe=="M") $se_granduncleaunt = 'fars farbror';
					else $se_granduncleaunt = 'mors farbror';
				}
				else {
					if($parsexe=="M") $se_granduncleaunt = 'fars morbror';
					else $se_granduncleaunt = 'mors morbror';
				}
			}

			if ($generY > 3)  {
				// great-granduncle
				$persidDb3=$db_functions->get_person($relarr[$ancsarr[$arrnum-3]][0]);
				$parsexe3 = $persidDb3->pers_sexe;    
				if($parsexe3=="M") {
					if($parsexe2=="M") {
						if($parsexe=="M") $se_gr_granduncleaunt = 'farfars farbror';
						else $se_gr_granduncleaunt = 'morfars farbror';
					}
					else {
						if($parsexe=="M") $se_gr_granduncleaunt = 'farmors farbror';
						else $se_gr_granduncleaunt = 'mormors farbror';
					}
				}
				else {
					if($parsexe2=="M") {
						if($parsexe=="M") $se_gr_granduncleaunt = 'farfars morbror';
						else $se_gr_granduncleaunt = 'morfars morbror';
					}
					else {
						if($parsexe=="M") $se_gr_granduncleaunt = 'farmors morbror';
						else $se_gr_granduncleaunt = 'mormors morbror';
					}
				}
			}
 
		}
	}
	else {
		$uncleaunt = __('aunt');

		// Swedish needs to know if aunt is related through mother or father - different names there
		// also for grandaunt and great-grandaunt!!!
		if($selected_language=="sv"){

			if($spouse=="2" OR $spouse=="3") { // right person is spouse of Y, not Y
				$relarr = $rel_arrayspouseY;
			}
			else {
				$relarr = $rel_arrayY;
			}

			$se_sibling = "syster"; // used for gr_gr_grandaunt and more "4:e gen anas syster"
			// aunt
 			$arrnum=0; reset($ancsarr);
			$count=$foundY_nr;
			while($count!=0) {
				$parnumber=$count;
				$ancsarr[$arrnum]=$parnumber; $arrnum++;
				$count=$relarr[$count][2];
			}
			$persidDb=$db_functions->get_person($relarr[$parnumber][0]);
			$parsexe = $persidDb->pers_sexe;
			if($parsexe=='M') { $uncleaunt = 'faster'; }
			else { $uncleaunt = 'moster'; }

			if ($generY > 2) {
				// grandaunt
				$persidDb2=$db_functions->get_person($relarr[$ancsarr[$arrnum-2]][0]);
				$parsexe2 = $persidDb2->pers_sexe;
				if($parsexe2=="M") {
					if($parsexe=="M") $se_granduncleaunt = 'fars faster';
					else $se_granduncleaunt = 'mors faster';
				}
				else {
					if($parsexe=="M") $se_granduncleaunt = 'fars moster';
					else $se_granduncleaunt = 'mors moster';
				}
 			}

			if ($generY > 3) {
				// great-grandaunt  
				$persidDb3=$db_functions->get_person($relarr[$ancsarr[$arrnum-3]][0]);
				$parsexe3 = $persidDb3->pers_sexe;    
  				if($parsexe3=="M") {  
				 	if($parsexe2=="M") {   
						if($parsexe=="M") $se_gr_granduncleaunt = 'farfars faster';
						else $se_gr_granduncleaunt = 'morfars faster';   
					}
					else {   
						if($parsexe=="M") $se_gr_granduncleaunt = 'farmors faster';
						else $se_gr_granduncleaunt = 'mormors faster';   
					}   
				}
				else {    
					if($parsexe2=="M") {   
						if($parsexe=="M") $se_gr_granduncleaunt = 'farfars moster';
						else $se_gr_granduncleaunt = 'morfars moster';   
					}
					else {   
						if($parsexe=="M") $se_gr_granduncleaunt = 'farmors moster';
						else $se_gr_granduncleaunt = 'mormors moster';  
					}    
				}   
 			}
 
		}
	}

	if($selected_language=="nl") {
		dutch_ancestors($generY-1);
		$reltext= $ancestortext.$uncleaunt.__(' of ');
		if ($generY >4 ) {
			$gennr = $generY -3;
			$dutchtext =  "(".$ancestortext.$uncleaunt." = ".$gennr.__('th').' '.__('great-grand').$uncleaunt.")";
		}

	}
	elseif($selected_language=="es"){
		if($sexe=="m") { $uncle=__('uncle'); $span_postfix="o "; $gran='abuelo'; }
		else { $uncle=__('aunt'); $span_postfix="a "; $gran='abuela'; }
		//$gendiff = abs($generX - $generY);   //FOUT
		$gendiff = abs($generX - $generY) - 1;
		$gennr=$gendiff-1;
		$degree=$gran." ".$gennr.$span_postfix;
		if($gendiff ==1) { $reltext=$uncle.__(' of ');}
		elseif($gendiff > 1 AND $gendiff < 27) {
			spanish_degrees($gendiff,$gran);
			$reltext=$uncle." ".$spantext.__(' of ');
		}
		else {$reltext=$uncle." ".$degree; }
	}
	elseif($selected_language=="he"){
		$gendiff = abs($generX - $generY);
		$gennr=$gendiff-1;
		if($gendiff ==1) { $reltext=$uncleaunt.__(' of ');}
		elseif($gendiff > 1 ) {
			if($gendiff >2) { $degree='דרגה '.$gennr; }
			$reltext=$uncleaunt.$degree.__(' of ');
		}
	}
	elseif($selected_language=="fi"){
		if ($generY == 2) { $reltext = $uncleaunt.__(' of '); }
		if ($generY == 3) { $reltext = __('grand').$uncleaunt.__(' of '); }
		$gennr=$generY-2;
		if ($generY >  3) { $reltext = $gennr.__('th').' '.__('grand').$uncleaunt.__(' of '); }
	}
	elseif($selected_language=="sv"){
		if ($generY == 2) { $reltext = $uncleaunt.__(' of '); }
		if ($generY == 3) { $reltext = $se_granduncleaunt.__(' of '); }
		if ($generY == 4) { $reltext = $se_gr_granduncleaunt.__(' of '); }
		$gennr=$generY-1;
		if ($generY >  4) { $reltext = $gennr.':e gen anas '.$se_sibling.__(' of '); }
	}
	elseif($selected_language=="no"){
		$temptext=''; $reltext_nor=''; $reltext_nor2='';
		if ($generY == 2) { $reltext = $uncleaunt.__(' of '); }
		if ($generY == 3) { $reltext = 'grand'.$uncleaunt.__(' of '); }
		if ($generY >3) {
			if($uncleaunt == __('uncle')) {
				$reltext = __('brother of ');
			}
			else {
				$reltext = __('sister of ');
			}
		}
		if ($generY == 4) { $temptext = 'oldeforelderen'; }
		if ($generY == 5) { $temptext = 'tippoldeforelderen'; }
		if ($generY == 6) { $temptext = 'tipp-tippoldeforelderen'; }
		$gennr=$generY-4;
		if ($generY >  6) { $temptext = $gennr.'x tippoldeforelderen'; }
		if($temptext != '') {
			$reltext_nor = "s ".substr($temptext,0,-2);
			$reltext_nor2 = $temptext.__(' of ');
		}
	}
	else {
		if ($generY == 2) { $reltext = $uncleaunt.__(' of '); }
		if ($generY == 3) { $reltext = __('grand').$uncleaunt.__(' of '); }
		if ($generY == 4) { $reltext = __('great-grand').$uncleaunt.__(' of '); }
		if ($generY == 5) { $reltext = __('2nd').' '.__('great-grand').$uncleaunt.__(' of '); }
		if ($generY == 6) { $reltext = __('3rd').' '.__('great-grand').$uncleaunt.__(' of '); }
		$gennr=$generY-2;
		if ($generY >  6) { $reltext = $gennr.__('th').' '.__('great-grand').$uncleaunt.__(' of '); }
	}
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

function calculate_cousins ($generX, $generY) {
global $db_functions, $reltext, $famsX, $famsY, $language, $sexe, $selected_language, $spantext, $foundY_nr, $rel_arrayY, $rel_arrayspouseY, $spouse;
global $reltext_nor, $reltext_nor2; // for Norwegian

	if($selected_language=="es") {
		$gendiff = abs($generX - $generY);

		if ($gendiff == 0) {
			if($sexe=="m") { $cousin=__('COUSIN_MALE'); $span_postfix="o "; $sibling=__('1st [COUSIN]'); }
			else { $cousin=__('COUSIN_FEMALE'); $span_postfix="a "; $sibling='hermana';}
			if($generX==2) { $reltext=$cousin." ".$sibling.__(' of ');}
			elseif($generX > 2) { $degree=$generX-1; $reltext = $cousin." ".$degree.$span_postfix.__(' of '); }
		}
		elseif($generX < $generY) {
			if($sexe=="m") { $uncle=__('uncle'); $span_postfix="o "; $gran='abuelo'; }
			else { $uncle=__('aunt'); $span_postfix="a "; $gran='abuela'; }

			if($gendiff ==1) { $relname=$uncle;}
			elseif($gendiff>1 AND $gendiff <27) {
				spanish_degrees($gendiff,$gran);
				$relname=$uncle." ".$spantext;
			}
			else { }
			$reltext= $relname." ".$generX.$span_postfix.__(' of ');
		}
		else {
			if($sexe=="m") { $nephew=__('nephew'); $span_postfix="o ";$grson='nieto'; }
			else { $nephew=__('niece'); $span_postfix="a "; $grson='nieta'; }

			if($gendiff ==1) { $relname=$nephew;}
			else {
				spanish_degrees($gendiff,$grson);
				$relname=$nephew." ".$spantext;
			}
			$reltext=$relname." ".$generY.$span_postfix.__(' of ');
		}
	}
	elseif($selected_language=="he") {
		if($sexe=='m') { $cousin=__('COUSIN_MALE'); }
		else { $cousin=__('COUSIN_FEMALE');}
		$gendiff = abs($generX - $generY);
		if ($gendiff == 0) { $removenr = ""; }
		elseif ($gendiff==1) { $removenr = 'בהפרש '.__('once removed'); }
		else {
			$removenr='בהפרש '.$gendiff." ".__('times removed');
		}
		$degree='';
		$degreediff = min($generX,$generY);
		if($degreediff > 2) {
			$degree='דרגה '.($degreediff-1)." ";
		}
		$reltext=$cousin.$degree.$removenr.__(' of ');
	}
	elseif($selected_language=="no") {
		$reltext_nor = '';
		$reltext_nor2 = '';
		$degreediff = min($generX,$generY);
		if ($degreediff == 2) {
			$nor_cousin = __('1st [COUSIN]'); // 1st cousin
		}
		elseif ($degreediff == 3) {
			$nor_cousin = __('2nd [COUSIN]'); // 2nd cousin
		}
		elseif ($degreediff == 4) {
			$nor_cousin = __('3rd [COUSIN]'); // 3rd cousin
		}
		elseif ($degreediff == 5) {
			$nor_cousin = __('4th [COUSIN]'); // 4th cousin
		}
		elseif ($degreediff > 5) {
			$gennr=$degreediff-3;
			$nor_cousin = $degreediff."-menningen";
		}

		$gendiff = abs($generX - $generY);
		if ($gendiff==0) { // A and B are cousins of same generation
			$reltext = $nor_cousin.__(' of ');
		}
		elseif ( $generX > $generY ) {  // A is the "younger" cousin  (A er barnebarnet av Bs tremenning)
			if($sexe=='m') { $child = __('son');}  // only for 1st generation
			else { $child = __('daughter');}
			if ($gendiff == 1) { $reltext = $child.__(' of '); }   // sønnen/datteren til
			if ($gendiff == 2) { $reltext = 'barnebarnet '.
				__(' of '); } // barnebarnet til
			if ($gendiff == 3) { $reltext = __('great-grand').' barnet'.__(' of '); } //olde+barnet
			if ($gendiff == 4) { $reltext = 'tippolde barnet'.__(' of '); }
			if ($gendiff == 5) { $reltext = 'tipp-tippolde barnet'.__(' of '); }
			$gennr=$gendiff-3;
			if ($gendiff >  5) { $reltext = $gennr.'x tippolde barnet'.__(' of '); }
			$reltext_nor = "s ".substr($nor_cousin,0,-2);
			$reltext_nor2 = $nor_cousin.__(' of ');
		}
		elseif ( $generX < $generY ) {  // A is the "older" cousin (A er timenning av Bs tipp-tippoldefar)
			if ($gendiff == 1) { $temptext = 'forelderen'; }
			if ($gendiff == 2) { $temptext = __('grand').'forelderen'; }
			if ($gendiff == 3) { $temptext = __('great-grand').'forelderen'; }
			if ($gendiff == 4) { $temptext = 'tippoldeforelderen'; }
			if ($gendiff == 5) { $temptext = 'tipp-tippoldeforelderen'; }
			$gennr=$gendiff-3;
			if ($gendiff >  5) { $temptext = $gennr.'x tippoldeforelderen'; }
			$reltext = $nor_cousin.__(' of ');
			$reltext_nor = "s ".substr($temptext,0,-2);
			$reltext_nor2 = $temptext.__(' of ');

			/* following is the alternative way of notation for cousins when X is the older one
			// (A er barnebarn av Bs tipp-tippolefars sosken)
			// at the moment we use the previous method that is shorter and approved by our Norwegian user
			// but we'll leave this here, just in case....
			$reltext = $nor_removed;
			if ($generX == 2) {
				$X_removed = 'barnet'."barn";
			}
			if ($generX == 3) {
				$X_removed = __('great-grand')."barn";
			}
			if ($generX == 4) {
				$X_removed = 'tippolde'."barn";
			}
			if ($generX == 5) {
				$X_removed = 'tipp-tippolde'."barn";
			}
			if ($generX >  5) {
				$gennr = $generX-3;
				$X_removed = $gennr.'x tippolde'."barn";
			}

			if ($generY == 3) {
				$Y_removed = __('great-grand')."barn";
			}
			if ($generY == 4) {
				$Y_removed = 'tippolde '."barn";
			}
			if ($generY == 5) {
				$Y_removed = 'tipp-tippolde '."barn";
			}
			if ($generY >  5) {
				$gennr = $generY-3;
				$Y_removed = $gennr.'x tippolde'."barn";
			}
			$reltext = $X_removed.__(' of ');
			$reltext_nor = "s ".$Y_removed."s ".'søskenet';
			$reltext_nor2 = $Y_removed.__(' of ');
			*/
		}
	}
  	elseif($selected_language=="sv") {
 		$degreediff = min($generX,$generY);
		if ($degreediff == 2) { $se_cousin = "kusin"; } // 1st cousin  
		elseif ($degreediff == 3) { $se_cousin = "tremänning";} // 2nd cousin  
		elseif ($degreediff == 4) { $se_cousin = "fyrmänning";} // 3rd cousin  
		elseif ($degreediff == 5) { $se_cousin = "femmänning";} // 4th cousin  
		elseif ($degreediff == 6) { $se_cousin = "sexmänning";} // 5th cousin  
		elseif ($degreediff == 7) { $se_cousin = "sjumänning";} // 6th cousin 
		elseif ($degreediff == 8) { $se_cousin = "åttamänning";} // 7th cousin  
		elseif ($degreediff == 9) { $se_cousin = "niomänning";} // 8th cousin  
		elseif ($degreediff == 10) { $se_cousin = "tiomänning";} // 9th cousin  
		elseif ($degreediff == 11) { $se_cousin = "elvammänning";} // 10th cousin  
		elseif ($degreediff == 12) { $se_cousin = "tolvmänning";} // 11nd cousin  
		elseif ($degreediff == 13) { $se_cousin = "trettonmänning";} // 12th cousin  
		elseif ($degreediff == 14) { $se_cousin = "fjortonmänning";} // 13th cousin  
		elseif ($degreediff == 15) { $se_cousin = "femtonmänning";} // 14th cousin  
		elseif ($degreediff == 16) { $se_cousin = "sextonmänning";} // 15th cousin  
		elseif ($degreediff == 17) { $se_cousin = "sjuttonmänning";} // 16th cousin  
		elseif ($degreediff == 18) { $se_cousin = "artonmänning";} // 17th cousin  
		elseif ($degreediff == 19) { $se_cousin = "nittonmänning";} // 18th cousin  
		elseif ($degreediff == 20) { $se_cousin = "tjugomänning";} // 19th cousin  
		elseif ($degreediff > 20) {
			$gennr=$degreediff-3;
			$se_cousin = $degreediff."-männing";
		}
 
		$gendiff = abs($generX - $generY); // generation gap between A and B
		if ($gendiff==0) { // A and B are cousins of same generation
			$reltext = $se_cousin.__(' of ');
		}
		elseif ( $generX > $generY ) {  // A is the "younger" cousin  (example A är tremannings barnbarn för B)
			if ($gendiff == 1)
				if($se_cousin=="kusin") { $reltext = 'kusinbarn'.__(' of '); }
				else { $reltext = $se_cousin.'s barn'.__(' of '); }    
			if ($gendiff == 2) { $reltext = $se_cousin.'s barnbarn'.__(' of '); }  
			$gennr=$gendiff;
			if ($gendiff >  2) { $reltext = $se_cousin.'s '.$gennr.':e generations barn'.__(' of '); }
		}    
 		elseif ( $generX < $generY ) {  // A is the "older" cousin (A är farfars tremanning för B)

			if($spouse=="2" OR $spouse=="3") { // right person is spouse of Y, not Y
				$relarr = $rel_arrayspouseY;
			}
			else {
				$relarr = $rel_arrayY;
			}

 			$arrnum=0; reset($ancsarr);
			$count=$foundY_nr;
			while($count!=0) {
				$parnumber=$count;
				$ancsarr[$arrnum]=$parnumber; $arrnum++;
				$count=$relarr[$count][2];
			}

			// parent
			$persidDb=$db_functions->get_person($relarr[$parnumber][0]);
			$parsexe = $persidDb->pers_sexe;
			if($parsexe == "M") { $se_par = "far"; }
			else { $se_par = "mor"; }

			//grandparent
			if ($gendiff > 1) {
				$persidDb2=$db_functions->get_person($relarr[$ancsarr[$arrnum-2]][0]);
				$parsexe2 = $persidDb2->pers_sexe;
				if($parsexe2 == "M") { $se_grpar = "fars"; }
				else { $se_grpar = "mors"; }
			}
			if ($gendiff == 1) { $reltext = $se_par.'s '.$se_cousin.__(' of '); }
			if ($gendiff == 2) { $reltext = $se_par.$se_grpar.' '.$se_cousin.__(' of '); }
			$gennr=$gendiff;
			if ($gendiff >  2) { $reltext = $gennr.':e generation anas '.$se_cousin.__(' of '); }
		}
	}
	else {
		$gendiff = abs($generX - $generY);
		if ($gendiff == 0) { $removenr = ""; }
		elseif ($gendiff == 1 ) { $removenr = ' '.__('once removed'); }
		elseif ($gendiff == 2 ) { $removenr = ' '.__('twice removed'); }
		elseif ($gendiff > 2 ) { $removenr = $gendiff.' '.__('times removed'); }

		$degreediff = min($generX,$generY);
		if($degreediff == 2) { $degree = __('1st [COUSIN]'); }  
		if($degreediff == 3) { $degree = __('2nd [COUSIN]'); }  
		if($degreediff == 4) { $degree = __('3rd [COUSIN]'); }  
		
		if($sexe=='m') {
			$cousin=__('COUSIN_MALE');
		}
		else {
			$cousin=__('COUSIN_FEMALE');
		}

		if($degreediff > 4)  {
			$degreediff-=1;
			$degree = $degreediff.__('th').' ';
			if($selected_language=="nl") { 
				$degreediff--;  // 5th cousin is in dutch "4de achterneef"
				$degree = $degreediff.__('th').' '.__('2nd [COUSIN]'); // in Dutch cousins are counted with 2nd cousin as base
			}
		}
		if(($selected_language=="fi" AND $degreediff == 3) OR ($selected_language=="nl" AND $degreediff >= 3)) {  
			// no space here (FI): pikkuserkku
			// no space here (NL): achterneef, achter-achternicht, 3de achterneef
			$reltext = $degree.$cousin.' '.$removenr.__(' of ');
		}
		else {
			$reltext = $degree.' '.$cousin.' '.$removenr.__(' of ');
		}
	}
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

function search_marital() {
global $dbh, $db_functions, $famsX, $famsY, $famspouseX, $famspouseY, $rel_arrayX, $rel_arrayY, $foundX_nr, $foundY_nr, $foundX_gen, $foundY_gen;
global $sexe, $sexe2, $spousenameX, $spousenameY, $foundX_match, $foundY_match;
global $rel_arrayspouseX, $rel_arrayspouseY, $spouse, $tree_prefix_quoted;

	$pers_cls = New person_cls;

	$marrX = explode(";",$famsX);
	$marrY = explode(";",$famsY);

	//prepared statement for use in loop
	$marr_prep=$dbh->prepare("SELECT fam_man, fam_woman FROM ".$tree_prefix_quoted."family WHERE fam_gedcomnumber=?");
	$marr_prep->bindParam(1,$marr_prep_var);

	if($famsX!='') {
		$marrcount=count($marrX);
		for($x=0; $x<$marrcount; $x++) {
			$marr_prep_var = $marrX[$x];
			$marr_prep->execute();
			@$familyDb=$marr_prep->fetch(PDO::FETCH_OBJ);
			if($sexe=='f') {
				$thespouse=$familyDb->fam_man;
			}
			else {
				$thespouse=$familyDb->fam_woman;
			}

			$rel_arrayspouseX = create_rel_array($thespouse);

			if(isset($rel_arrayspouseX)) {
				compare_rel_array ($rel_arrayspouseX, $rel_arrayY, 1); // "1" flags comparison with "spouse of X"
			}

			if($foundX_match !=='') {
				$famspouseX=$marrX[$x];

				if($sexe=='m') {$sexe="f";} else {$sexe="m";} // we have to switch sex since the spouse is the relative!
				calculate_rel ($foundX_match, $foundY_match, $foundX_gen, $foundY_gen);

				$spouseidDb=$db_functions->get_person($thespouse);
				$name=$pers_cls->person_name($spouseidDb);
				$spousenameX=$name["name"];

				break;
			}
		}
	}

	if($foundX_match==='' AND $famsY!='') {  // no match found between "spouse of X" and "Y", let's try "X" with "spouse of "Y"
		$ymarrcount=count($marrY);
		for($x=0; $x<$ymarrcount; $x++) {
			$marr_prep_var = $marrY[$x];
			$marr_prep->execute();
			@$familyDb=$marr_prep->fetch(PDO::FETCH_OBJ);
			if($sexe2=='f') {
				$thespouse2=$familyDb->fam_man;
			}
			else {
				$thespouse2=$familyDb->fam_woman;
			}

			$rel_arrayspouseY = create_rel_array($thespouse2);

			if(isset($rel_arrayspouseY)) {
				compare_rel_array ($rel_arrayX, $rel_arrayspouseY, 2); // "2" flags comparison with "spouse of Y"
			}
			if($foundX_match !=='') {
				$famspouseY=$marrY[$x];
				calculate_rel ($foundX_match, $foundY_match, $foundX_gen, $foundY_gen);
				$spouseidDb=$db_functions->get_person($thespouse2);
				$name=$pers_cls->person_name($spouseidDb);
				$spousenameY=$name["name"];
				break;
			}
		}
	}

	if($foundX_match==='' AND $famsX!='' AND $famsY!='') { // still no matches, let's try comparison of "spouse of X" with "spouse of Y"
		$xmarrcount=count($marrX);
		$ymarrcount=count($marrY);
		for($x=0; $x<$xmarrcount; $x++) {
			for($y=0; $y<$ymarrcount; $y++) {
				$marr_prep_var = $marrX[$x];
				$marr_prep->execute();
				@$familyDb=$marr_prep->fetch(PDO::FETCH_OBJ);
				if($sexe=='f') {
					$thespouse=$familyDb->fam_man;
				}
				else {
					$thespouse=$familyDb->fam_woman;
				}

				$rel_arrayspouseX = create_rel_array($thespouse);
				$marr_prep_var = $marrY[$y];
				$marr_prep->execute();
				@$familyDb=$marr_prep->fetch(PDO::FETCH_OBJ);
				if($sexe2=='f') {
					$thespouse2=$familyDb->fam_man;
				}
				else {
					$thespouse2=$familyDb->fam_woman;
				}

				$rel_arrayspouseY = create_rel_array($thespouse2);

				if(isset($rel_arrayspouseX) AND isset($rel_arrayspouseY)) {
					compare_rel_array ($rel_arrayspouseX, $rel_arrayspouseY, 3); //"3" flags comparison "spouse of X" with "spouse of Y"
				}
				if($foundX_match !=='') {

					if($sexe=='m') {$sexe="f";} else {$sexe="m";} // we have to switch sex since the spouse is the relative!
					calculate_rel ($foundX_match, $foundY_match, $foundX_gen, $foundY_gen);

					$spouseidDb=$db_functions->get_person($thespouse);
					$name=$pers_cls->person_name($spouseidDb);
					$spousenameX=$name["name"];

					$spouseidDb=$db_functions->get_person($thespouse2);
					$name=$pers_cls->person_name($spouseidDb);
					$spousenameY=$name["name"];

					$famspouseX=$marrX[$x];
					$famspouseY=$marrY[$y];

					break;
				} //end if foundmatch !=''
			} // for y
			if($foundX_match !=='') { break; }
		} // for x
	} // end if not found match

} //end function

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

function search_bloodrel() {
global $rel_arrayX, $rel_arrayY, $person, $person2, $foundX_match, $foundY_match, $reltext, $foundX_gen, $foundY_gen;
	unset_vars();
	$rel_arrayX = create_rel_array( $person ); // === gedcom nr of person X ===
	$rel_arrayY = create_rel_array( $person2 ); // === gedcom nr of person Y ===
	if(isset($rel_arrayX) AND isset($rel_arrayY)) {
		compare_rel_array ($rel_arrayX, $rel_arrayY, 0);
	}

	if ($foundX_match !=='') {
		calculate_rel ($foundX_match, $foundY_match, $foundX_gen, $foundY_gen);
	}
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

function unset_vars() {
	global $foundX_nr, $foundY_nr, $foundX_gen, $foundY_gen, $foundX_match, $foundY_match, $reltext, $spouse, $table;

	$foundX_nr='';
	$foundY_nr='';
	$foundX_gen='';
	$foundY_gen='';
	$foundX_match='';
	$foundY_match='';
	$table='';
	$reltext='';
	$spouse='';
}


function display () {
	global $db_functions, $foundX_match, $reltext, $bloodreltext, $name1, $name2, $spouse, $rel_arrayspouseX;
	global $special_spouseY, $special_spouseX, $spousenameX, $spousenameY, $table, $doublespouse, $dbh;
	global $rel_arrayX, $rel_arrayY, $famX, $famY, $language, $dutchtext, $searchDb, $searchDb2;
	global $sexe, $selected_language, $dirmark1,  $famspouseX, $famspouseY, $reltext_nor, $reltext_nor2;
	global $fampath;  // path to family.php for Joomla and regular. Defined above all functions
	global $person, $person2;
	global $tree_prefix_quoted;

	// *** Use person class ***
	$pers_cls = New person_cls;

	$language_is=' '.__('is').' ';
	if($selected_language=="he") {
		if($sexe=="m") { $language_is=' הוא '; }
		else { $language_is=' היא '; }
	}

	$bloodrel='';
	search_bloodrel();

	if($reltext) {
		//print '<table class="humo container"><tr><td>';
		print '<table class="ext"><tr><td style="padding-right:30px;vertical-align:text-top;">';
		$bloodrel=1;
		print __('BLOOD RELATIONSHIP: ');
		echo "<br><br>";
		if($selected_language=="fi") { print 'Kuka: '; }   // who
		print "&nbsp;&nbsp;<a class='relsearch' href='".$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$famX."&amp;main_person=".$rel_arrayX[0][0]."'>";
		print $name1."</a>";
		if($selected_language=="fi") { print '&nbsp;&nbsp;'.'Kenelle: '; }  // to whom
		else { print $language_is.$reltext; }
		print "<a class='relsearch' href='".$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$famY."&amp;main_person=".$rel_arrayY[0][0]."'>".$name2."</a>".$reltext_nor."<p>";
		print $dutchtext;
		if($selected_language=="fi") { echo 'Sukulaisuus tai muu suhde: <b>'.$reltext.'</b>'; }
		print '<hr style="width:100%;height:0.25em;color:darkblue;background-color:darkblue;"  >';
		$bloodreltext=$reltext;
		display_table();
	}

	if($table!=1 AND $table!=2 AND $table!=7) {
		unset_vars();
		search_marital();

		if($reltext) {

			//check if this is involves a marriage or a partnership of any kind
			$relmarriedX=0;
			if(isset($famspouseX)) {
				$kindrel=$dbh->query("SELECT fam_kind FROM ".$tree_prefix_quoted."family WHERE fam_gedcomnumber='".$famspouseX."'");
				@$kindrelDb = $kindrel->fetch(PDO::FETCH_OBJ);
				if($kindrelDb->fam_kind != 'living together' AND
					$kindrelDb->fam_kind != 'engaged' AND
					$kindrelDb->fam_kind != 'homosexual' AND
					$kindrelDb->fam_kind != 'unknown' AND
					$kindrelDb->fam_kind != 'non-marital' AND
					$kindrelDb->fam_kind != 'partners' AND
					$kindrelDb->fam_kind != 'registered') {
						$relmarriedX=1;  // use: husband or wife
				}
				else {
					$relmarriedX=0;  // use: partner
				}
			}
			$relmarriedY=0;
			if(isset($famspouseY)) {
				$kindrel2=$dbh->query("SELECT fam_kind
					FROM ".$tree_prefix_quoted."family
					WHERE fam_gedcomnumber='".$famspouseY."'");
				@$kindrel2Db = $kindrel2->fetch(PDO::FETCH_OBJ);
				if($kindrel2Db->fam_kind != 'living together' AND
					$kindrel2Db->fam_kind != 'engaged' AND
					$kindrel2Db->fam_kind != 'homosexual' AND
					$kindrel2Db->fam_kind != 'unknown' AND
					$kindrel2Db->fam_kind != 'non-marital' AND
					$kindrel2Db->fam_kind != 'partners' AND
					$kindrel2Db->fam_kind != 'registered') {
						$relmarriedY=1;  // use: husband or wife
					}
					else {
						$relmarriedY=0;  // use: partner
					}
				}

			if($bloodrel==1) {
				if(CMS_SPECIFIC=="Joomla") {
					print '</td></tr><tr><td>'; // in joomla we want blood- and marital tables one under the other for lack of space
				}
				else {
					print '</td><td style="padding-left:30px;border-left:2px solid #bbbbbb;vertical-align:text-top;">';
				}
			}
			//else { echo '<table class="humo container"<tr><td>'; }
			else { echo '<table class="ext"<tr><td>'; }

			print __('MARITAL RELATIONSHIP: ');

			echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input style="font-size:110%" type="submit" name="extended" value="'.__('Use Extended Calculator').'">';

			echo "<br><br>";
			$spousetext1=''; $spousetext2='';  $finnish_spouse1=''; $finnish_spouse2='';

			if($doublespouse==1) { // X and Y are both spouses of Z
				$spouseidDb=$db_functions->get_person($rel_arrayspouseX[$foundX_match][0]);
				$name=$pers_cls->person_name($spouseidDb);
				$spousename=$name["name"];

				print "<span>&nbsp;&nbsp;<a class='relsearch' href='".$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$famX."&amp;main_person=".$rel_arrayX[0][0]."'>";
				//print $name1."</a> and ";
				print $name1."</a> ".__('and').': ';
				print "<a class='relsearch' href='".$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$famY."&amp;main_person=".$rel_arrayY[0][0]."'>".$name2."</a>";
				if($searchDb->pers_sexe == "M") {
					echo ' '.__('are both husbands of').' ';
				}
				else {
					print ' '.__('are both wifes of').' ';
				}
				print "<a href='".$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$famY."&amp;main_person=".$rel_arrayspouseX[$foundX_match][0]."'>".$spousename."</a></span><br>";
			}
			else {

				if(($spouse==1 AND $special_spouseX!==1) OR $spouse==3) {
					if($relmarriedX==0) {
						$spousetext1 =strtolower(__('Partner')).__(' of ');
						$finnish_spouse1 = strtolower(__('Partner'));
					}
					else {
						if($searchDb->pers_sexe=='M') {
							$spousetext1 = ' '.__('husband of').' ';
							if($selected_language=="fi"){ $finnish_spouse1 = 'mies'; }
						}
						else {
							$spousetext1 = ' '.__('wife of').' ';
							if($selected_language=="fi"){ $finnish_spouse1 = 'vaimo'; }
						}
					}
				}
				if(($spouse==2 OR $spouse==3) AND $special_spouseY!==1) {
					if($relmarriedY==0) {
						$spousetext2 = strtolower(__('Partner')).__(' of ');
						$finnish_spouse2 = strtolower(__('Partner'));
					}
					else {
						if($searchDb2->pers_sexe=='M') {
							$spousetext2 = ' '.__('wife of').' ';
							if($selected_language=="fi"){ $finnish_spouse2 = 'mies'; }
							// yes - it's really husband cause the sentence goes differently
						}
						else {
							$spousetext2 = ' '.__('husband of').' ';
							if($selected_language=="fi"){ $finnish_spouse2 = 'vaimo'; }
							// yes - it's really wife cause the sentence goes differently
						}
					}
				}

				if($selected_language=="fi") {  // very different phrasing for correct grammar
					print 'Kuka: ';
					print "<span>&nbsp;&nbsp;<a class='relsearch' href='".$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$famX."&amp;main_person=".$rel_arrayX[0][0]."'>";
					print $name1."</a>";
					print '&nbsp;&nbsp;Kenelle: ';
					print "<a class='relsearch' href='".$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$famY."&amp;main_person=".$rel_arrayY[0][0]."'>".$name2."</a></span><br>";
					print 'Sukulaisuus tai muu suhde: ';
					if(!$special_spouseX AND !$special_spouseY AND $table!=7) {
						if($spousetext2 != '' AND $spousetext1 == '') { // X is relative of spouse of Y
							print '(';
							print "<a href='".$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$famX."&amp;main_person=".$rel_arrayX[0][0]."'>".$name1."</a>";
							print ' - '.$spousenameY.'):&nbsp;&nbsp;'.$reltext.'<br>';
							print $spousenameY.', '.$finnish_spouse2.' ';
							print "<a href='".$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$famY."&amp;main_person=".$rel_arrayY[0][0]."'>".$name2."</a>";
						}
						elseif ($spousetext1 != '' AND $spousetext2 == '') { // X is spouse of relative of Y
							print '('.$spousenameX.' - ';
							print "<a href='".$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$famY."&amp;main_person=".$rel_arrayY[0][0]."'>".$name2."</a>";
							print '):&nbsp;&nbsp;'.$reltext.'<br>';
							print $spousenameX.', '.$finnish_spouse1.' ';
							print "<a href='".$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$famX."&amp;main_person=".$rel_arrayX[0][0]."'>".$name1."</a>";
						}
						else {   // X is spouse of relative of spouse of Y
							print '('.$spousenameX.' - '.$spousenameY.'):&nbsp;&nbsp;'.$reltext.'<br>';
							print $spousenameX.', '.$finnish_spouse1.' ';
							print "<a href='".$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$famX."&amp;main_person=".$rel_arrayX[0][0]."'>".$name1."</a><br>";
							print $spousenameY.', '.$finnish_spouse2.' ';
							print "<a href='".$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$famY."&amp;main_person=".$rel_arrayY[0][0]."'>".$name2."</a>";
						}
					}
					elseif ($special_spouseX OR $special_spouseY) { // brother-in-law/sister-in-law/father-in-law/mother-in-law
						print '<b>'.$reltext.'</b><br>';
					}
					elseif ($table == 7) {
						if($relmarriedX==0 OR $relmarriedY==0) {
							print '<b>'.strtolower(__('Partner')).'</b><br>';
						}
						else {
							print '<b>'.$finnish_spouse1.'</b><br>';
						}
					}
				}  // end of finnish part

				else {
					if($spousetext2=='') { $reltext_nor2=''; }  // Norwegian grammar...
					else { $reltext_nor = ''; }
					if($table==6 OR $table==7) { $reltext_nor = ''; }
						print "<span>&nbsp;&nbsp;<a class='relsearch' href='".$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$famX."&amp;main_person=".$rel_arrayX[0][0]."'>";

						print $name1."</a>".$language_is.$spousetext1.$reltext.$reltext_nor2.$spousetext2;
						print "<a class='relsearch' href='".$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$famY."&amp;main_person=".$rel_arrayY[0][0]."'>".$name2."</a>".$reltext_nor."</span><br>";
				}

			}

			print '<hr style="width:100%;height:0.25em;color:darkblue;background-color:darkblue;" >';

			display_table();
/*			
			echo '<br><br><div style="margin-left:auto;margin-right:auto;padding:3px;width:400px;background-color:#eeeeee"><input type="submit" name="next_path" value="'.__('Try to find another path').'" style="font-size:115%;">';
			echo '<br>'.__('(With each consecutive search the path may get longer and computing time may increase!)').'</div>';
*/			
		}

	}

	//if($bloodreltext=='' AND $reltext=='') { 
	if($reltext=='') {  
		if($bloodreltext=='') { 
			echo '<br><table class="ext"><tr><td>'; 
			echo '<td style="text-align:left;border-left:0px;padding10px;vertical-align:text-top;width:800px">';
			echo "<div style='font-weight:bold'>".__('No blood relation or direct marital relation found')."</div>";
		}
		else { 
			echo '<td style="width:60px">&nbsp;</td>'; 
			echo '<td style="padding-left:50px;padding-right:10px;vertical-align:text-top;border-left:2px solid #bbbbbb;width:350px">';
			print __('MARITAL RELATIONSHIP: ');
			echo "<br><br><div style='font-weight:bold;margin-bottom:10px'>".__('No direct marital relation found')."</div>";
		}

		print '<hr style="width:100%;height:0.25em;color:darkblue;background-color:darkblue;" >';
 		echo  __("You may wish to try finding a connection with the <span style='font-weight:bold'>Extended Marital Calculator</span> below.<br>	
				This will find connections that span over many marital relations and generations.<br>	
				Computing time will vary depending on the size of the tree and the distance between the two persons.<br>	
				For example, in a 10,000 person tree even the most distant persons will usually be found within 1-2 seconds.<br>	
				In a 75,000 person tree the most distant persons may take up to 8 sec to find.<br><br>");
		echo '<input type="submit" name="extended" value="'.__('Perform extended marital calculation').'" style="font-size:115%;">';
		echo "</td></tr></table>";

	}
	else { 
		print '</td></tr></table>'; 
	}

	print '<br><br>';

}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

function display_table() {
	global $db_functions, $foundX_nr, $foundY_nr, $foundX_gen, $foundY_gen, $foundX_match, $foundY_match;
	global $table, $name1, $name2, $rel_arrayX, $rel_arrayY, $spouse, $rel_arrayspouseX, $rel_arrayspouseY, $famspouseX, $famspouseY;
	global $famX, $famY, $gednr, $gednr2, $dirmark1, $dirmark2;
	global $fampath;  // path to family.php for Joomla and regular

	// *** Use person class to show names ***
	$pers_cls = New person_cls;

	//$border="border:1px solid #777777;";
	$border="";

	if($table==1 OR $table==2) {  
		if($table==1 AND $foundY_gen==1 AND $spouse=='') {
			// father-son - no need for table
		}
		else if($table==2 AND $foundX_gen==1 AND $spouse=='') {
			// son-father - no need for table
		}
		else { 
			if($spouse==1) { $rel_arrayX = $rel_arrayspouseX;}
			if($spouse==2) { $rel_arrayY = $rel_arrayspouseY;}
			if($spouse==3) { $rel_arrayX = $rel_arrayspouseX; $rel_arrayY = $rel_arrayspouseY;}

			if($table==2) {
				$tempfound=$foundY_nr; $foundY_nr=$foundX_nr; $foundX_nr=$tempfound;
				$temprel=$rel_arrayY; $rel_arrayY=$rel_arrayX; $rel_arrayX=$temprel;
				$tempname=$name1;  $name1=$name2; $name2=$tempname;
				$tempfam=$famspouseX;  $famspouseX=$famspouseY; $famspouseY=$tempfam;
				$tempfamily=$famX;  $famX=$famY; $famY=$tempfamily;
				$tempged=$gednr2;  $gednr2=$gednr; $gednr=$tempged;
			}
			//print "<table id=\"reltable\" class=\"reltable\">";
			echo '<br><table class="newrel" style="border:0px;border-collapse:separate;border-spacing:3px 1px;">'; 
			print "<tr>";

			if(($spouse==1 AND $table==1) OR ($spouse==2 AND $table==2) OR $spouse==3) { 
				$persidDb=$db_functions->get_person($rel_arrayX[0][0]);
				$name=$pers_cls->person_name($persidDb);
				$personname=$name["name"];

				if($persidDb->pers_sexe == "M") $ext_cls = "extended_man ";
				else $ext_cls = "extended_woman ";
				
				print '<td class="'.$ext_cls.'"  style="width:200px;text-align:center;'.$border.'padding:2px"><a href="'.$fampath.'database='.safe_text($_SESSION['tree_prefix']).'&amp;id='.$famspouseX.'&amp;main_person='.$rel_arrayX[0][0].'">'.$personname.'</a></td>';

				$persidDb=$db_functions->get_person($gednr);
				if($persidDb->pers_sexe == "M") $ext_cls = "extended_man ";
				else $ext_cls = "extended_woman ";

				print '<td style="border:0px;">&nbsp;&nbsp;X&nbsp;&nbsp;</td>';
				
				print '<td class="'.$ext_cls.'"  style="width:200px;text-align:center;'.$border.'padding:2px"><a class="search" href="'.$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$famX."&amp;main_person=".$gednr.'">'.$name1."</a></td>";
				print '</tr><tr>';
				print '<td style="border:0px;">&#8593;</td>';
				print '<td style="border:0px;">&nbsp;</td>';
				print '<td style="border:0px;">&nbsp;</td>';
			}
			else { 
				$persidDb=$db_functions->get_person($rel_arrayX[0][0]);
				$name=$pers_cls->person_name($persidDb);
				$personname=$name["name"];

				if($persidDb->pers_sexe == "M") $ext_cls = "extended_man ";
				else $ext_cls = "extended_woman ";

				print '<td class="'.$ext_cls.'"  style="width:200px;text-align:center;'.$border.'padding:2px"><a class="search" href="'.$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$famX."&amp;main_person=".$rel_arrayX[0][0].'">'.$name1."</a></td>";
				if(($spouse==1 AND $table==2) OR ($spouse==2 AND $table==1)) {
					print '<td style="border:0px;">&nbsp;</td>';
					print '<td style="border:0px;">&nbsp;</td>';
				}
				print '</tr><tr>';
				print '<td style="border:0px;">&#8595;</td>';
			}

			print "</tr>";
			$count=$foundY_nr;
			while($count!=0) { 
				$persidDb=$db_functions->get_person($rel_arrayY[$count][0]);
				$name=$pers_cls->person_name($persidDb);
				$personname=$name["name"];

				if($persidDb->pers_sexe == "M") $ext_cls = "extended_man ";
				else $ext_cls = "extended_woman ";

				if($persidDb->pers_fams) {
					$fams=$persidDb->pers_fams;
					$tempfam=explode(";",$fams);
					$fam=$tempfam[0];
				}
				else {
					$fam=$persidDb->pers_famc;
				}
				print "<tr>";
				print '<td class="'.$ext_cls.'" style="width:200px;text-align:center;'.$border.'padding:2px"><a href="'.$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$fam."&amp;main_person=".$persidDb->pers_gedcomnumber.'">'.$personname."</a></td>";
				if($spouse==1 OR $spouse==2 OR $spouse==3) { 
					print '<td style="border:0px;">&nbsp;</td>';
				}
				print '</tr><tr><td style="border:0px;">&#8593;</td>';
				$count=$rel_arrayY[$count][2];
			}

			print "</tr><tr>";

			if(($spouse==1 AND $table==2) OR ($spouse==2 AND $table==1) OR $spouse==3) { 
				$persidDb=$db_functions->get_person($rel_arrayY[0][0]);
				$name=$pers_cls->person_name($persidDb);
				$personname=$name["name"];
/*				
				print '<td style="border:0px;">&#8593;</td>';
				print '<td style="border:0px;">&nbsp;</td>';
				print '<td style="border:0px;">&nbsp;</td>';
				print '</tr><tr>';
*/				
				if($persidDb->pers_sexe == "M") $ext_cls = "extended_man ";
				else $ext_cls = "extended_woman ";
				
				print '<td class="'.$ext_cls.'" style="width:200px;text-align:center;'.$border.'padding:2px"><a href="'.$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$famspouseY."&amp;main_person=".$rel_arrayY[0][0].'">'.$personname."</a></td>";
				
				$persidDb=$db_functions->get_person($gednr2);
				if($persidDb->pers_sexe == "M") $ext_cls = "extended_man ";
				else $ext_cls = "extended_woman ";

				print '<td style="border:0px;">&nbsp;&nbsp;X&nbsp;&nbsp;</td>';
				
				print '<td class="'.$ext_cls.'" style="width:200px;text-align:center;'.$border.'padding:2px"><a class="search" href="'.$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$famY."&amp;main_person=".$gednr2.'">'.$name2."</a></td>";
			}
			else { 
				$persidDb=$db_functions->get_person($rel_arrayY[0][0]);
				if($persidDb->pers_sexe == "M") $ext_cls = "extended_man ";
				else $ext_cls = "extended_woman ";

				print '<td class="'.$ext_cls.'" style="width:200px;text-align:center;'.$border.'padding:2px"><a class="search" href="'.$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$famY."&amp;main_person=".$rel_arrayY[0][0].'">'.$name2."</a></td>";
				if(($spouse==1 AND $table==1) OR ($spouse==2 AND $table==2)) {
					print '<td style="border:0px;">&nbsp;</td>';
					print '<td style="border:0px;">&nbsp;</td>';
				}
			}
			print "</tr></table>";
		}
	}
	if($table==3 OR $table==4 OR $table==5 OR $table==6 ) { 
		$rowcount=max($foundX_gen,$foundY_gen);
		$countX=$foundX_nr;
		$countY=$foundY_nr;
		$name1_done=0;
		$name2_done=0;

		$colspan=3;
		if($spouse==1) { $rel_arrayX = $rel_arrayspouseX;}
		if($spouse==2) { $rel_arrayY = $rel_arrayspouseY;}
		if($spouse==3) { $rel_arrayX = $rel_arrayspouseX; $rel_arrayY = $rel_arrayspouseY;}

		//print "<table id=\"reltable\" class=\"humo reltable\">";
		echo '<br><table  class="newrel" style="border-collapse:separate;border-spacing:3px 1px;">'; 

		$persidDb=$db_functions->get_person($rel_arrayX[$foundX_match][0]);
		$name=$pers_cls->person_name($persidDb);
		$personname=$name["name"];
		
		if($persidDb->pers_sexe == "M") $ext_cls = "extended_man ";
		else $ext_cls = "extended_woman ";

		print "<tr>";
		if($spouse==1 OR $spouse==3) {
			print '<td style="border:0px;">&nbsp;</td>'; 
			print '<td style="border:0px;">&nbsp;</td>'; 
		}
		if($persidDb->pers_fams) {
			$fams=$persidDb->pers_fams;
			$tempfam=explode(";",$fams);
			$fam=$tempfam[0];
		}
		else {
			$fam=$persidDb->pers_famc;
		}
		print '<td class="'.$ext_cls.'" style="width:200px;text-align:center;'.$border.'padding:2px" colspan='.$colspan.'><a href="'.$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$fam."&amp;main_person=".$persidDb->pers_gedcomnumber.'">'.$personname."</a>";

		print "</td>";
		if($spouse==2 OR $spouse==3) {
			print '<td style="border:0px;">&nbsp;</td>'; 
			print '<td style="border:0px;">&nbsp;</td>'; 
		}
		print "</tr>";
		
		print "<tr>";
		if($spouse==1 OR $spouse==3) {
			print '<td style="border:0px;">&nbsp;</td>'; 
			print '<td style="border:0px;">&nbsp;</td>'; 
		}
		print '<td style="border:0px;">&#8593;</td>'; 
		print '<td style="border:0px;">&nbsp;</td>'; 
		print '<td style="border:0px;">&#8595;</td>';
		if($spouse==2 OR $spouse==3) {
			print '<td style="border:0px;">&nbsp;</td>'; 
			print '<td style="border:0px;">&nbsp;</td>'; 
		}
		print "</tr>";
		

		for($e=1; $e <= $rowcount; $e++) {
		
			if($countX!=0) { 
				$persidDb=$db_functions->get_person($rel_arrayX[$countX][0]);
				$name=$pers_cls->person_name($persidDb);
				$personname=$name["name"];
				
				if($persidDb->pers_sexe == "M") $ext_cls = "extended_man ";
				else $ext_cls = "extended_woman ";

				print "<tr>";
				if($spouse==1 OR $spouse==3) {
					print '<td style="border:0px;">&nbsp;</td>';  
					print '<td style="border:0px;">&nbsp;</td>'; 
				}
				if($persidDb->pers_fams) {
					$fams=$persidDb->pers_fams;
					$tempfam=explode(";",$fams);
					$fam=$tempfam[0];
				}
				else {
					$fam=$persidDb->pers_famc;
				}
				print '<td class="'.$ext_cls.'" style="width:200px;text-align:center;'.$border.'padding:2px"><a href="'.$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$fam."&amp;main_person=".$persidDb->pers_gedcomnumber.'">'.$personname."</a></td>";
				$countX=$rel_arrayX[$countX][2];
			}
			elseif($name1_done==0) { 
				print "<tr>";
				if($spouse==1 OR $spouse==3) {
					$persidDb=$db_functions->get_person($rel_arrayX[0][0]);
					$name=$pers_cls->person_name($persidDb);
					$personname=$name["name"];

					if($persidDb->pers_sexe == "M") $ext_cls2 = "extended_man ";
					else $ext_cls2 = "extended_woman ";
					
					if($persidDb->pers_fams) {
						$fams=$persidDb->pers_fams;
						$tempfam=explode(";",$fams);
						$fam=$tempfam[0];
					}
					else {
						$fam=$persidDb->pers_famc;
					}
					
					$persidDb2=$db_functions->get_person($gednr);
					if($persidDb2->pers_sexe == "M") $ext_cls = "extended_man ";
					else $ext_cls = "extended_woman ";
					
					print '<td class="'.$ext_cls.'" style="width:200px;text-align:center;padding:2px"><a class="search" href="'.$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$famX."&amp;main_person=".$gednr.'">'.$name1."</a>";
					
					print '<td style="border:0px;">&nbsp;&nbsp;X&nbsp;&nbsp;</td>'; 
					
					print '<td  class="'.$ext_cls2.'" style="width:200px;text-align:center;padding:2px" class="'.$ext_cls.'" style="width:200px;text-align:center;'.$border.'padding:2px"><a href="'.$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$fam."&amp;main_person=".$persidDb->pers_gedcomnumber.'">'.$personname."</a></td>";
				}
				else {
					$persidDb2=$db_functions->get_person($gednr);
					if($persidDb2->pers_sexe == "M") $ext_cls = "extended_man ";
					else $ext_cls = "extended_woman ";
					print '<td class="'.$ext_cls.'" style="width:200px;text-align:center;padding:2px"><a class="search" href="'.$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$famX."&amp;main_person=".$gednr.'">'.$name1."</a></td>";
				}
				$name1_done=1;
			}
			else { 
				print "<tr>";
				if($spouse==1 OR $spouse==3) {
					print '<td style="border:0px;">&nbsp;</td>'; 
					print '<td style="border:0px;">&nbsp;</td>'; 
				}
				print '<td style="border:0px;">&nbsp;</td>';
			}

			if($countY!=0) { 
				$persidDb=$db_functions->get_person($rel_arrayY[$countY][0]);
				$name=$pers_cls->person_name($persidDb);
				$personname=$name["name"];

				if($persidDb->pers_sexe == "M") $ext_cls = "extended_man ";
				else $ext_cls = "extended_woman ";
				
				print '<td style="border:0px;width:70px">&nbsp;</td>';

				if($persidDb->pers_fams) {
					$fams=$persidDb->pers_fams;
					$tempfam=explode(";",$fams);
					$fam=$tempfam[0];
				}
				else {
					$fam=$persidDb->pers_famc;
				}
				print '<td class="'.$ext_cls.'" style="width:200px;text-align:center;'.$border.'padding:2px"><a href="'.$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$fam."&amp;main_person=".$persidDb->pers_gedcomnumber.'">'.$personname."</a></td>";
				if($spouse==2 OR $spouse==3) {
					print '<td style="border:0px;">&nbsp;</td>';
					print '<td style="border:0px;">&nbsp;</td>';  
				}
				print "</tr>";
				$countY=$rel_arrayY[$countY][2];
			}
			elseif($name2_done==0) { 
				if($spouse==2 OR $spouse==3) {
					$persidDb=$db_functions->get_person($rel_arrayY[0][0]);
					$name=$pers_cls->person_name($persidDb);
					$personname=$name["name"];

					if($persidDb->pers_sexe == "M") $ext_cls = "extended_man ";
					else $ext_cls = "extended_woman ";

					print '<td style="border:0px;width:70px">&nbsp;</td>';

					if($persidDb->pers_fams) {
						$fams=$persidDb->pers_fams;
						$tempfam=explode(";",$fams);
						$fam=$tempfam[0];
					}
					else {
						$fam=$persidDb->pers_famc;
					}
					print '<td class="'.$ext_cls.'" style="width:200px;text-align:center;padding:2px"><a href="'.$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$fam."&amp;main_person=".$persidDb->pers_gedcomnumber.'">'.$personname."</a></td>";
					
					print '<td style="border:0px;">&nbsp;&nbsp;X&nbsp;&nbsp;</td>';
					
					$persidDb=$db_functions->get_person($gednr2);
					if($persidDb->pers_sexe == "M") $ext_cls = "extended_man ";
					else $ext_cls = "extended_woman ";
					
					print '<td class="'.$ext_cls.'" style="width:200px;text-align:center;padding:2px"><a class="search" href="'.$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$famY."&amp;main_person=".$gednr2.'">'.$name2."</a></td>";
				}
				else {
					print '<td style="border:0px;width:70px">&nbsp;</td>';

					$persidDb=$db_functions->get_person($gednr2);
					if($persidDb->pers_sexe == "M") $ext_cls = "extended_man ";
					else $ext_cls = "extended_woman ";
					
					print '<td class="'.$ext_cls.'" style="width:200px;text-align:center;padding:2px"><a class="search" href="'.$fampath."database=".safe_text($_SESSION['tree_prefix'])."&amp;id=".$famY."&amp;main_person=".$gednr2.'">'.$name2."</a></td>";
				}
				print "</tr>";
				$name2_done=1;
			}
			else { 
				print '<td style="border:0px;width:70px>">&nbsp;</td>';
				print '<td style="border:0px;">&nbsp;</td>';
				if($spouse==2 OR $spouse==3) {
					print '<td style="border:0px;">&nbsp;</td>'; 
					print '<td style="border:0px;">&nbsp;</td>'; 
				}
				print "</tr>";
			}

				print "<tr>";
				if($spouse==1 OR $spouse==3) {
					print '<td style="border:0px;">&nbsp;</td>';  
					print '<td style="width=50px;border:0px;">&nbsp;</td>'; 
				}
				if($name1_done==0) { print '<td style="border:0px;">&#8593;</td>'; } else { print '<td style="border:0px;">&nbsp;</td>';  }
				print '<td style="border:0px;">&nbsp;</td>';
				if($name2_done==0) { print '<td style="border:0px;">&#8595;</td>'; }  else { print '<td style="border:0px;">&nbsp;</td>';  }
				if($spouse==2 OR $spouse==3) {
					print '<td style="border:0px;">&nbsp;</td>';  
					print '<td style="border:0px;">&nbsp;</td>'; 
				}
				print "</tr>";
			
		}
		print "</table>";
	}
}
//-----------------------------------------------------------------------------------------------------

/* the extended marital calculator computation */

function map_tree($pers_array, $pers_array2) {    
	// in first loop $pers_array and $pers_array2 hold persons A and B
	// in the next loop it will contain the parents, children and spouses of persons A and B, where they exist etc
	// the algorithm starts simultaneously from person A and person B in expanding circles until a common person is found (= connection found)
	// or until either person A or B runs out of persons (= no connection exists)

	global $dbh, $db_functions, $person, $person2, $globaltrack, $globaltrack2, $persqry, $persvar, $famqry, $famvar;
	global $count; $count++; if($count>400000) { echo "Database too large!!!!"; exit; }	
	global $countfunc;
	global $global_array;
	$countfunc++;
	$tree=safe_text($_SESSION['tree_prefix']);

	$work_array = array();
	$work_array2 = array(); 

	// build closest circle around person A (parents, children, spouse(s))
	foreach($pers_array as $value) {   // each array item has 4 parts, separated by "@": I124@par@I15@I54;I46;I326;I123;I15
		$params = explode("@",$value);
		$persged = $params[0]; // the gedcomnumber of this person
		$refer = $params[1];   // the referrer type: par (parent), spo (spouse), chd (child) - this means who was the previous person that called this one
		$callged = $params[2]; // the gedcomnumber of the referrer (in case referrer is child: gedcomnumber;famc gedcomnumber)
		$pathway = $params[3]; // the path from person A to this person (gedcomnumbers separated by semi-colon)

		if($refer=="chd"){
			$callarray = explode(";",$callged);	// [0] = gedcomnumber of referring child, [1] = famc gedcomnumber of referring child
		}
		else { $callarray[0] = $callged; }

		$persvar = $persged;
 		try{  $persqry->execute(); }
		catch(PDOException $e) { echo "PDO Error: ".$e->getMessage(); }
		if($persqry->rowCount()==0) { 
			echo "NO SUCH PERSON:"."ref=".$refer."persged=".$persged."callged=".$callged."$$"; return(false); 
		}  
		$persDb = $persqry->fetch();

		if($refer=="fst") { $globaltrack .= $persDb['pers_gedcomnumber']."@"; }

		if(isset($persDb['pers_famc']) AND $persDb['pers_famc']!="" AND $refer!="par") {  
			$famvar = $persDb['pers_famc'];
			try{ $famqry->execute(); }
			catch(PDOException $e) { echo "PDO Error: ".$e->getMessage(); }
			if($famqry->rowCount()==0) { 
				echo "NO SUCH FAMILY"; return; 
			}
			$famcDb = $famqry->fetch();
			if(isset($famcDb['fam_man']) AND $famcDb['fam_man']!="" AND $famcDb['fam_man']!="0" AND strpos($globaltrack,$famcDb['fam_man']."@")===false) { 
					if(strpos($_SESSION['next_path'],$famcDb['fam_man']."@")===false) {
						$work_array[] = $famcDb['fam_man']."@chd@".$persged.";".$persDb['pers_famc']."@".$pathway.";"."chd".$famcDb['fam_man']; 
						$global_array[] = $famcDb['fam_man']."@chd@".$persged.";".$persDb['pers_famc']."@".$pathway.";"."chd".$famcDb['fam_man'];
					}
					$count++;
					$globaltrack .= $famcDb['fam_man']."@";  
			}	
			if(isset($famcDb['fam_woman']) AND $famcDb['fam_woman']!="" AND $famcDb['fam_woman']!="0" AND strpos($globaltrack,$famcDb['fam_woman']."@")===false) {  
				if(strpos($_SESSION['next_path'],$famcDb['fam_woman']."@")===false) { 
					$work_array[] = $famcDb['fam_woman']."@chd@".$persged.";".$persDb['pers_famc']."@".$pathway.";"."chd".$famcDb['fam_woman'];
					$global_array[] = $famcDb['fam_woman']."@chd@".$persged.";".$persDb['pers_famc']."@".$pathway.";"."chd".$famcDb['fam_woman'];
				}
				$count++;
				$globaltrack .= $famcDb['fam_woman']."@";
			}
		}

		if(isset($persDb['pers_fams']) AND $persDb['pers_fams']!="") {  
			$famsarray = explode(";",$persDb['pers_fams']); 
			foreach($famsarray as $value) {   
				if($refer=="spo" AND $value == $callged) continue;
				if($refer=="fst" AND $_SESSION['couple']==$value) continue;
			
				$famvar = $value;
				$famqry->execute();
				$famsDb = $famqry->fetch();
				if($refer=="chd" AND $famsDb['fam_woman']==$persDb['pers_gedcomnumber'] AND isset($famsDb['fam_man']) AND $famsDb['fam_man']!="" AND $famsDb['fam_gedcomnumber']==$callarray[1]) { continue; }
	 			if(isset($famsDb['fam_children']) AND $famsDb['fam_children']!="")	{
	 				$childarray = explode(";",$famsDb['fam_children']);	
					foreach($childarray as $value) {
						if($refer=="chd" AND $callarray[0] == $value) continue;  
						if(strpos($globaltrack,$value."@")===false) {  
							if(strpos($_SESSION['next_path'],$value."@")===false) {
								$work_array[] = $value."@par@".$persged."@".$pathway.";"."par".$value;
								$global_array[] = $value."@par@".$persged."@".$pathway.";"."par".$value;
							}
							$count++;
							$globaltrack .= $value."@";
						}
					}
				}
			}  
			foreach($famsarray as $value) {  
				if($refer=="chd" AND $value == $callarray[1]) continue;
				if($refer=="spo" AND $value == $callged) continue;
				if($refer=="fst" AND $_SESSION['couple']==$value) continue;
			
				$famvar = $value;
				$famqry->execute();	
				$famsDb = $famqry->fetch();
	 			if($famsDb['fam_man'] == $persDb['pers_gedcomnumber']) { 
					if(isset($famsDb['fam_woman']) AND $famsDb['fam_woman']!="" AND $famsDb['fam_woman']!="0" AND strpos($globaltrack,$famsDb['fam_woman']."@")===false) { 
							if(strpos($_SESSION['next_path'],$famsDb['fam_woman']."@")===false) {
								$work_array[] = $famsDb['fam_woman']."@spo@".$value."@".$pathway.";"."spo".$famsDb['fam_woman'];
								$global_array[] = $famsDb['fam_woman']."@spo@".$value."@".$pathway.";"."spo".$famsDb['fam_woman'];
							}
							$count++;
							$globaltrack .= $famsDb['fam_woman']."@";
					}
				}
				else { 
					if(isset($famsDb['fam_man']) AND $famsDb['fam_man']!="" AND $famsDb['fam_man']!="0" AND strpos($globaltrack,$famsDb['fam_man']."@")===false) { 
							if(strpos($_SESSION['next_path'],$famsDb['fam_man']."@")===false) { 
								$work_array[] = $famsDb['fam_man']."@spo@".$value."@".$pathway.";"."spo".$famsDb['fam_man'];
								$global_array[] = $famsDb['fam_man']."@spo@".$value."@".$pathway.";"."spo".$famsDb['fam_man'];
							}
							$count++;
							$globaltrack .= $famsDb['fam_man']."@";
					}
				}
			}
		}
	}
	// build closest circle around person B (parents, children, spouse(s))
	foreach($pers_array2 as $value) {   
		$params = explode("@",$value);
		$persged = $params[0];
		$refer = $params[1];
		$callged = $params[2];
		$pathway = $params[3];

		if($refer=="chd"){
			$callarray = explode(";",$callged);	
		}
		else { $callarray[0] = $callged; }

		$persvar = $persged; 
 		try{  $persqry->execute(); }
		catch(PDOException $e) { echo "PDO Error: ".$e->getMessage(); }
		if($persqry->rowCount()==0) { 
			echo "NO SUCH PERSON:"."ref=".$refer."persged=".$persged."callged=".$callged."$$"; return(false); 
		}  
		$persDb = $persqry->fetch();

		if($refer=="fst") { $globaltrack2 .= $persDb['pers_gedcomnumber']."@"; }

		if(isset($persDb['pers_famc']) AND $persDb['pers_famc']!="" AND $refer!="par") {  
			$famvar = $persDb['pers_famc'];
			try{ $famqry->execute(); }
			catch(PDOException $e) { echo "PDO Error: ".$e->getMessage(); }
			if($famqry->rowCount()==0) { 
				echo "NO SUCH FAMILY"; return; 
			}
			$famcDb = $famqry->fetch();
			if(isset($famcDb['fam_man']) AND $famcDb['fam_man']!="" AND $famcDb['fam_man']!="0") { 
				$var1=strpos($_SESSION['next_path'],$famcDb['fam_man']."@");
				if(strpos($globaltrack,$famcDb['fam_man']."@")!== false  AND $var1===false) {  
					$totalpath=join_path($global_array,$pathway,$famcDb['fam_man'],"chd"); 
					$_SESSION['next_path'] .= $famcDb['fam_man']."@"; 
					display_result($totalpath);
					return($famcDb['fam_man']);  
				}
				if(strpos($globaltrack2,$famcDb['fam_man']."@")===false) { 
					if($var1===false) {
						$work_array2[] = $famcDb['fam_man']."@chd@".$persged.";".$persDb['pers_famc']."@".$pathway.";"."chd".$famcDb['fam_man']; 
					}
					$count++;
					$globaltrack2 .= $famcDb['fam_man']."@"; 
				}
			}
			if(isset($famcDb['fam_woman']) AND $famcDb['fam_woman']!="" AND $famcDb['fam_woman']!="0") {  
				$var2 = strpos($_SESSION['next_path'],$famcDb['fam_woman']."@");
				if(strpos($globaltrack,$famcDb['fam_woman']."@")!== false AND $var2===false) {  
					$totalpath=join_path($global_array,$pathway,$famcDb['fam_woman'],"chd"); 
					$_SESSION['next_path'] .= $famcDb['fam_woman']."@";  
					display_result($totalpath);
					return($famcDb['fam_woman']);  
				}
				if(strpos($globaltrack2,$famcDb['fam_woman']."@")===false) {  
					if($var2===false) {
						$work_array2[] = $famcDb['fam_woman']."@chd@".$persged.";".$persDb['pers_famc']."@".$pathway.";"."chd".$famcDb['fam_woman']; 	
					}
					$count++;
					$globaltrack2 .= $famcDb['fam_woman']."@";
				}
			}
		}

		if(isset($persDb['pers_fams']) AND $persDb['pers_fams']!="") {  
			$famsarray = explode(";",$persDb['pers_fams']); 
			foreach($famsarray as $value) {   
				if($refer=="spo" AND $value == $callged) continue;
				if($refer=="fst" AND $_SESSION['couple']==$value) continue;
				
				$famvar = $value;
				$famqry->execute();
				$famsDb = $famqry->fetch();
				if($refer=="chd" AND $famsDb['fam_woman']==$persDb['pers_gedcomnumber'] AND isset($famsDb['fam_man']) AND $famsDb['fam_man']!="" AND $famsDb['fam_gedcomnumber']==$callarray[1]) { continue; }
	 			if(isset($famsDb['fam_children']) AND $famsDb['fam_children']!="")	{
	 				$childarray = explode(";",$famsDb['fam_children']);	
					foreach($childarray as $value) {
						if($refer=="chd" AND $callarray[0] == $value) continue;  
						$var3 = strpos($_SESSION['next_path'],$value."@");
						if(strpos($globaltrack,$value."@")!== false AND $var3===false) {   
							$totalpath=join_path($global_array,$pathway,$value,"par"); 
							$_SESSION['next_path'] .= $value."@";
							display_result($totalpath);
							return($value);
						}
						if(strpos($globaltrack2,$value."@")===false) {  
							if($var3===false) {
								$work_array2[] = $value."@par@".$persged."@".$pathway.";"."par".$value;
							}
							$count++;
							$globaltrack2 .= $value."@";
						}
					}
				}
			}  
			foreach($famsarray as $value) {  
				if($refer=="chd" AND $value == $callarray[1]) continue;
				if($refer=="spo" AND $value == $callged) continue;
				if($refer=="fst" AND $_SESSION['couple']==$value) { continue; }
	
				$famvar = $value;
				$famqry->execute();	
				$famsDb = $famqry->fetch();
	 			if($famsDb['fam_man'] == $persDb['pers_gedcomnumber']) { 
					if(isset($famsDb['fam_woman']) AND $famsDb['fam_woman']!="" AND $famsDb['fam_woman']!="0") { 
						$var4 = strpos($_SESSION['next_path'],$famsDb['fam_woman']."@");
						if(strpos($globaltrack,$famsDb['fam_woman']."@")!== false AND $var4===false) {   
							$totalpath=join_path($global_array,$pathway,$famsDb['fam_woman'],"spo");
							$_SESSION['next_path'] .= $famsDb['fam_woman']."@"; 
							display_result($totalpath);
							return($famsDb['fam_woman']);
						}
						if(strpos($globaltrack2,$famsDb['fam_woman']."@")===false){ 
							if($var4===false) {
								$work_array2[] = $famsDb['fam_woman']."@spo@".$value."@".$pathway.";"."spo".$famsDb['fam_woman'];
							}
							$count++;
							$globaltrack2 .= $famsDb['fam_woman']."@";
						}
					}
				}
				elseif($famsDb['fam_woman'] == $persDb['pers_gedcomnumber']) { 
					if(isset($famsDb['fam_man']) AND $famsDb['fam_man']!="" AND $famsDb['fam_man']!="0") { 
						$var5 = strpos($_SESSION['next_path'],$famsDb['fam_man']."@");
						if(strpos($globaltrack,$famsDb['fam_man']."@")!== false AND $var5===false) {  
							$totalpath=join_path($global_array,$pathway,$famsDb['fam_man'],"spo");
							$_SESSION['next_path'] .= $famsDb['fam_man']."@"; 
							display_result($totalpath);
							return($famsDb['fam_man']);  
						}
						if(strpos($globaltrack2,$famsDb['fam_man']."@")===false) { 
							if($var5===false) {
								$work_array2[] = $famsDb['fam_man']."@spo@".$value."@".$pathway.";"."spo".$famsDb['fam_man'];;
							}
							$count++;
							$globaltrack2 .= $famsDb['fam_man']."@";
						}
					}
				}  
			}
		}
	}
	if(isset($work_array[0]) AND isset($work_array2[0])) { 
		// no common person was found but both A and B still have a wider circle to expand -> call this function again
		map_tree($work_array,$work_array2);
	}
	elseif(!isset($_SESSION['next_path'])) { 
		echo "<br><span style='font-weight:bold;font-size:120%'>&nbsp;&nbsp;".__("These persons are not related in any way.")."&nbsp;&nbsp;<br><br>";
	}
	else {
		echo "<br><span style='font-weight:bold;font-size:120%'>&nbsp;&nbsp;".__("No further paths found.")."&nbsp;&nbsp;<br><br>";
	}
}

function join_path($workarr,$path2,$pers2,$ref) {  
	// we have two trails. one from person A to the common person and one from person B to the common person (A ---> common <---- B)
	// we have to create one trail from A to B
	// since the second trail is reverse (from B to the common person) it first has to be turned around, including changing the relation to previous and next person
	
	// $workarr is the array with all trails from person A 
	// we have to find the trail that contains the common person ($pers2)
	foreach($workarr as $value) {  
		if(strpos($value.";",$pers2.";")===false) { 
			continue;
		}
		$path1 = substr($value,strrpos($value,"@")+1);  // found the right trail
	}
	$fstcommon = substr($path1,strpos($path1.";",$pers2.";")-3,3); // find the common person as appears in the trail from person A ("parI3120")
	
	// now turn around the second trail and adjust par, chd, spo values accordingly
	$secpath = explode(";",$path2);
	$new_path2 = "";
	$changepath = array();
	$commonpers = ";".$fstcommon.$pers2; 
	if($ref=="par" AND $fstcommon=="par") { 
		// the common person is a child of both sides - discard child and make right person spouse of left!
		$changepath[count($secpath)-1] = "spo".substr($secpath[count($secpath)-1],3);
		$commonpers = "";
		$_SESSION['next_path'] .= substr($secpath[count($secpath)-1],3)."@"; // add parent from side B to ignore string for next path
		$par1str = substr($path1,0,strrpos($path1,";"));  // first take off last (=common) person
		$_SESSION['next_path'] .= substr($par1str,strrpos($par1str,";")+4)."@";     // add parent from side A to ignore string for next path
	}
	elseif($ref=="par") $changepath[count($secpath)-1] = "chd".substr($secpath[count($secpath)-1],3);
	elseif($ref=="chd") $changepath[count($secpath)-1] = "par".substr($secpath[count($secpath)-1],3);
	else $changepath[count($secpath)-1] = "spo".substr($secpath[count($secpath)-1],3);
	for($w=count($secpath)-1;$w>0;$w--) {
		if(substr($secpath[$w],0,3)=="par") $changepath[$w-1] = "chd".substr($secpath[$w-1],3);
		elseif(substr($secpath[$w],0,3)=="chd") $changepath[$w-1] = "par".substr($secpath[$w-1],3);
		else $changepath[$w-1] = "spo".substr($secpath[$w-1],3);
	}
	for($w=count($changepath)-1;$w>=0;$w--) {
		$new_path2 .= ";".$changepath[$w];
	} 
	$result = substr($path1,0,strpos($path1,$pers2)-4).$commonpers.$new_path2;  // the entire trail from person A to B
	return($result);
}
/*------------------------------------------------------------------------------------------*/

/* displays result of extended marital calculator */

function display_result($result) {  
	// $result holds the entire track of persons from person A to person B
	// this string is made up of items sperated by ";"
	// each items starts with "par" (parent), "chd" (child) or "spo" (spouse), followed by the gedcomnumber of the person
	// example: parI232;parI65;chdI2304;spoI212;parI304
	// the par-chd-spo prefixes indicate if the person was called up by his parent, child or spouse so we can later create the graphical display

	global $persqry, $persvar, $person, $person2;

	echo '<div style="padding:3px;width:auto;background-color:#eeeeee"><input type="submit" name="next_path" value="'.__('Try to find another path').'" style="font-size:115%;">';
	echo '&nbsp;&nbsp;'.__('(With each consecutive search the path may get longer and computing time may increase!)').'</div>';

	$fampath = CMS_ROOTPATH."family.php?";
	$tree=safe_text($_SESSION['tree_prefix']);
	$map = array();	// array that will hold all data needed for the graphical display

	$tracks = explode(";",$result); // $tracks is array with each person in the trail

	/* initialize  */
	for($x=0;$x<count($tracks);$x++) {
		$map[$x][0]="1"; /* x value in graphical display */
		$map[$x][1]="1"; /* y value in graphical display */
		$map[$x][2]="1"; /* colspan value (usually 1, turns 2 for two column parent) */
		$map[$x][3]= substr($tracks[$x],0,3); /* call value (oar, chd, spo) */
		$map[$x][4]= substr($tracks[$x],3); /* gedcomnumber value */
	}

	$xval=1; $yval=1; $miny = 1; $maxy=1;
	$marrsign = array();

	// fill map array
	for($x=0;$x<count($tracks);$x++) {
		$ged = substr($tracks[$x],3);    // gedcomnumber
		$cal = substr($tracks[$x],0,3);  // par, chd, spo
		if($cal=="fst") { 
			continue; 
		}
		if($cal=="spo") { 
			$marrsign[$xval+1]=$yval;
			$xval +=2;
			$map[$x][0]=$xval; $map[$x][1]=$yval; 

		}
		if($cal=="chd") { 
			$yval--; 
			if($yval<$miny) $miny=$yval;
			$map[$x][0]=$xval; $map[$x][1]=$yval; 
			if(isset($map[$x+1]) AND $map[$x+1][3]=="par") $map[$x][2]=2;
		}
		if($cal=="par") {
			$yval++; 
			if($yval>$maxy) $maxy=$yval;
			if($map[$x-1][3]=="chd") $xval++;
			$map[$x][0]=$xval; $map[$x][1]=$yval; 
		}
	}
	if($miny<1) {
		for($x=0;$x<count($map);$x++) {
			$map[$x][1] += (1+abs($miny));
			if($map[$x][1] > $maxy)	$maxy=$map[$x][1];
		}	
		if(isset($marrsign)) {
			foreach($marrsign as $key => $value) {
				$marrsign[$key] += (1+abs($miny));
			}
		}
	}
	// the following code displays the graphical view of the found trail
	echo '<br><table style="border:0px;border-collapse:separate;border-spacing:30px 1px;">'; 
	for($a=1;$a<=$maxy;$a++) { 
		echo "<tr>";
		$nextline="";
		for($b=1;$b<=$xval;$b++) {
			$colsp=false; $marr=false;
			for($x=0;$x<count($map);$x++) {
				if($map[$x][0]==$b AND $map[$x][1]==$a) {
					$color = "#8ceffd"; $border="border:1px solid #777777;";
					
					$persvar = $map[$x][4];
					$persqry->execute();
					$ancDb = $persqry->fetch(PDO::FETCH_OBJ);
					if($ancDb->pers_sexe == "M") $ext_cls = "extended_man ";
					else $ext_cls = "extended_woman ";
					
					if($map[$x][4]==$person OR $map[$x][4]==$person2) {  // person A and B (first and last) get thicker border
						$color="#72fe95";
						$border = "border:2px solid #666666;"; 
					}
					if($map[$x][2]==2) {
						$b++;
						echo '<td class="'.$ext_cls.'" colspan=2 style="width:200px;text-align:center;'.$border.'padding:2px">';
						$nextline .= "&#8593;@&#8595;@";   // up and down arrows under two column parent
					}
					elseif(isset($map[$x+1][3]) AND $map[$x+1][3]=="par") { 
						$nextline .="&#8595;@";  // down arrow
						echo '<td class="'.$ext_cls.'"  style="width:200px;text-align:center;'.$border.'padding:2px">';
					}
					elseif(isset($map[$x][3]) AND $map[$x][3]=="chd") {
						$nextline .="&#8593;@";  // up arrow
						echo '<td class="'.$ext_cls.'" style="width:200px;text-align:center;'.$border.'padding:2px">';	
					}
					else {
						$nextline .= "&nbsp;@";  // empty box
						echo '<td class="'.$ext_cls.'" style="width:200px;text-align:center;'.$border.'padding:2px">';
					}

					$pers_cls = New person_cls;
					$name=$pers_cls->person_name($ancDb);
					$personname=$name["name"];
					echo "<a href='".$fampath."database=".$tree."&amp;id=".$ancDb->pers_indexnr."&amp;main_person=".$ancDb->pers_gedcomnumber."'>".$personname."</a>";
					$colsp=true;
				}

			}	
			if($colsp==false) {
				if(isset($marrsign[$b]) AND $marrsign[$b]==$a) {  // display the X sign between two married people
					echo '<td style="font-weight:bold;font-size:130%;width:10px;text-align:center;border:0px;padding:0px">X';
				}
				else {
					echo '<td style="width:10px;text-align:center;border:0px;padding:0px">';
				}
				$nextline .= "&nbsp;@";
			}
			echo "</td>";
			
		}
		echo "</tr>";
		// The following code places a row with arrows (or blanks) under a row with name boxes
		if($a != $maxy) {
			echo "<tr>";
			$nextline = substr($nextline,0,-1);
			$next = explode("@",$nextline);
			foreach($next as $value) {
				echo "<td style='padding:2px;color:black;width:10px;font-weight:bold;font-size:140%;text-align:center;'>".$value."</td>";
			}
			echo "</tr>"; 
		}
	}
	echo "</table>";
}

//-----------------------------------------------------------------------------------------------------

$foundX_nr=''; $foundY_nr='';
$foundX_gen=''; $foundY_gen='';
$foundX_match=''; $foundY_match='';
$spouse='';
$reltext='';
$special_spouseX=''; $special_spouseY='';
$table='';
$name1= ''; $name2= '';

$pers_cls = New person_cls;

//======== HELP POPUP ========================
if(CMS_SPECIFIC=="Joomla") {
	echo '<div class="fonts '.$rtlmarker.'sddm" style="z-index:400; position:absolute; top:20px; left:10px;">';
	$popwidth="width:700px;";
}
else {
	echo '<div class="fonts '.$rtlmarker.'sddm" style="margin-right:10px; float:right; display:inline">';
	$popwidth="";
}

echo '<a href="#"';
echo ' style="display:inline;" ';
if(CMS_SPECIFIC=="Joomla") {
	echo 'onmouseover="mopen(event,\'help_menu\',0,0)"';
}
else {
	echo 'onmouseover="mopen(event,\'help_menu\',10,150)"';
}
echo 'onmouseout="mclosetime()">';
echo '&nbsp;&nbsp;&nbsp;<strong>'.__('Information about the Relationship Calculator').'</strong>';
echo '</a>&nbsp;';
echo '<div class="sddm_fixed" style="'.$popwidth.' z-index:400; text-align:'.$alignmarker.'; padding:4px; direction:'.$rtlmarker.'" id="help_menu" onmouseover="mcancelclosetime()" onmouseout="mclosetime()">';
print '<br>';

echo __('This calculator will find the following relationships:<br>
<ul><li>Any blood relationship between X and Y ("X is great-grandfather of Y", "X is 3rd cousin once removed of Y" etc.)</li>
<li>Blood relationship between the spouse of X and person Y ("X is spouse of 2nd cousin of Y", "X is son-in-law of Y")</li>
<li>Blood relationship between person X and the spouse of Y ("X is 2nd cousin of spouse of Y", "X is father-in-law of Y")</li>
<li>Blood relationship between spouse of X and spouse of Y ("X spouse of sister-in-law of Y" etc.)</li>
<li>Direct marital relation ("X is spouse of Y")</li></ul>
Directions for use:<br>
<ul><li>Enter first and/or last name (or part of names) in the search boxes and press "Search". Repeat this for person 1 and 2.</li>
<li>If more than 1 person is found, select the one you want from the search result pulldown box. Repeat this for person 1 and 2.</li>
<li>Now press the "Calculate relationships" button on the right.</li>
<li><b>TIP: when you click "search" with empty first <u>and</u> last name boxes you will get a list with all persons in the database. (May take a few seconds)</b></li></ul>');

echo '</div>';
echo '</div>';

//=================================

if (isset($_SESSION['tree_prefix'])) { $tree_prefix=$_SESSION['tree_prefix'];}

if(!isset($_POST["search1"]) AND !isset($_POST["search2"]) AND !isset($_POST["calculator"]) AND !isset($_POST["switch"]) AND !isset($_POST["extended"]) AND !isset($_POST["next_path"])) {
	// no button pressed: this is a fresh entry from humogen's frontpage link: start clean search form
	$_SESSION["search1"]=''; $_SESSION["search2"]='';
	$_SESSION['rel_search_firstname']=''; $_SESSION['rel_search_lastname']='';
	$_SESSION['rel_search_firstname2']=''; $_SESSION['rel_search_lastname2']='';
}

$person=''; if (isset($_POST["person"])){	$person=$_POST['person']; }
$person2=''; if (isset($_POST["person2"])){ $person2=$_POST['person2']; }
if (isset($_POST["search1"])){ $_SESSION["search1"]=1; }
if (isset($_POST["search2"])){ $_SESSION["search2"]=1; }

if(isset($_POST["switch"])) {
	$temp=$_SESSION['rel_search_firstname']; $_SESSION['rel_search_firstname']=$_SESSION['rel_search_firstname2']; $_SESSION['rel_search_firstname2']=$temp;
	$temp=$_SESSION['rel_search_lastname'];  $_SESSION['rel_search_lastname']=$_SESSION['rel_search_lastname2'];   $_SESSION['rel_search_lastname2']=$temp;
	$temp=$person; $person=$person2; $person2=$temp;
	$temp=$_SESSION["search1"]; $_SESSION["search1"]=$_SESSION["search2"]; $_SESSION["search2"]=$temp;
}

// ===== BEGIN SEARCH BOX SYSTEM

ob_start();
print '<span class="fonts"><br><br>&nbsp;&nbsp;&nbsp;'.__('You can enter names or part of names in either search box, or leave a search box empty').'<br>';
echo '&nbsp;&nbsp;&nbsp;';
echo __('<b>TIP: when you click "search" with empty first <u>and</u> last name boxes you will get a list with all persons in the database. (May take a few seconds)</b>');
echo '</span><br>';
/* echo '<br>'; */

if(CMS_SPECIFIC == "Joomla") {
	echo '<form method="POST" action="'.'index.php?option=com_humo-gen&task=relations'.'" style="display : inline;">';
}
else {
	echo '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" style="display : inline;">';
}

echo '<br><table class="humo relmenu">';
echo '<tr class=table_headline><th>&nbsp;</th>';
	print '<th>'.__('First name').'</th>';
	print '<th>'.__('Last name').'</th>';
	echo '<th>'.__('Search').'</th>';
	echo '<th colspan=2>'.__('Pick a name from search results').'</th>';
	echo '<th>'.__('Calculate relationships').'</th></tr>';

echo '<tr><td>';
$language_person=__('Person').' ';
if(CMS_SPECIFIC == "Joomla") { $language_person=''; }  // for joomla keep it short....
echo $language_person.'1:';
echo '</td><td>';

$search_firstname='';
if (isset($_POST["search_firstname"]) AND !isset($_POST["switch"])){
	$search_firstname=safe_text($_POST['search_firstname']);
	$_SESSION['rel_search_firstname']=$search_firstname;
}
if (isset($_SESSION['rel_search_firstname'])){ $search_firstname=$_SESSION['rel_search_firstname']; }

$search_lastname='';
if (isset($_POST["search_lastname"]) AND !isset($_POST["switch"])){
	$search_lastname=safe_text($_POST['search_lastname']);
	$_SESSION['rel_search_lastname']=$search_lastname;
}
if (isset($_SESSION['rel_search_lastname'])){ $search_lastname=$_SESSION['rel_search_lastname']; }

print ' <input type="text" class="fonts relboxes" name="search_firstname" value="'.$search_firstname.'" size="15"> ';
echo '</td><td>';

print '&nbsp; <input class="fonts relboxes" type="text" name="search_lastname" value="'.$search_lastname.'" size="15">';
echo ' <input type="hidden" name="tree_prefix" value="'.$tree_prefix.'">';
echo '</td><td>';
print '&nbsp; <input class="fonts" type="submit" name="search1" value="'.__('Search').'">';
echo '</td><td>';

$len=230;  // length of name pulldown box
if(CMS_SPECIFIC == "Joomla") { $len = 180; } // for joomla keep it short....

if(isset($_SESSION["search1"]) AND $_SESSION["search1"]==1) {
	$search_qry= "SELECT * FROM ".$tree_prefix_quoted."person WHERE CONCAT(REPLACE(pers_prefix,'_',' '),pers_lastname)";
	$search_qry.= " LIKE '%".$search_lastname."%' AND pers_firstname LIKE '%".$search_firstname."%' ORDER BY pers_lastname, pers_firstname";
	$search_result = $dbh->query($search_qry);
	if ($search_result){
		if($search_result->rowCount()>0) {
			print '<select class="fonts" size="1" name="person"  style="width:'.$len.'px">';
				while($searchDb=$search_result->fetch(PDO::FETCH_OBJ)) {
					$name=$pers_cls->person_name($searchDb);
					if ($name["show_name"]){
						echo '<option';
						if(isset($person)) {
							if ($searchDb->pers_gedcomnumber==$person AND !(isset($_POST["search1"]) AND $search_lastname=='' AND $search_firstname=='')){
								echo ' SELECTED';
							}
						}

						$birth='';
						if ($searchDb->pers_bapt_date){
							$birth=' '.__('~').' '.date_place($searchDb->pers_bapt_date,'');
						}
						if ($searchDb->pers_birth_date){
							$birth=' '.__('*').' '.date_place($searchDb->pers_birth_date,'');
						}
						echo ' value="'.$searchDb->pers_gedcomnumber.'">'.$name["index_name"].$birth.' ['.$searchDb->pers_gedcomnumber.']</option>';
					}
				}
				echo '</select>';
			}
		else {print '<select size="1" name="notfound" value="1" style="width:'.$len.'px"><option>'.__('Person not found').'</option></select>'; }
	}
}
else {  print '<select size="1" name="person" style="width:'.$len.'px"><option></option></select>'; }
echo '</td><td rowspan=2>';
//echo '<input type="image" src="'.ROOTPATH.'images/turn_around.gif" alt="'.__('Switch persons').'" title="'.__('Switch persons').'" value="Submit" name="switch" >';
echo '<input type="submit" alt="'.__('Switch persons').'" title="'.__('Switch persons').'" value=" " name="switch" style="background: #fff url(\''.CMS_ROOTPATH.'images/turn_around.gif\') top no-repeat;width:25px;height:25px">';
echo '</td><td rowspan=2>';
echo '<input type="submit" name="calculator" value="'.__('Calculate relationships').'" style="font-size:115%;">';
echo '</td></tr><tr><td>';

// SECOND PERSON
echo $language_person.'2:';
echo '</td><td>';

$search_firstname2='';
if (isset($_POST["search_firstname2"]) AND !isset($_POST["switch"])){
	$search_firstname2=safe_text($_POST['search_firstname2']);
	$_SESSION['rel_search_firstname2']=$search_firstname2;
}
if (isset($_SESSION['rel_search_firstname2'])){ $search_firstname2=$_SESSION['rel_search_firstname2']; }

$search_lastname2='';
if (isset($_POST["search_lastname2"]) AND !isset($_POST["switch"])){
	$search_lastname2=safe_text($_POST['search_lastname2']);
	$_SESSION['rel_search_lastname2']=$search_lastname2;
}
if (isset($_SESSION['rel_search_lastname2'])){ $search_lastname2=$_SESSION['rel_search_lastname2']; }

print ' <input type="text" class="fonts relboxes" name="search_firstname2" value="'.$search_firstname2.'" size="15"> ';
echo '</td><td>';
print '&nbsp; <input class="fonts relboxes" type="text" name="search_lastname2" value="'.$search_lastname2.'" size="15">';
echo ' <input type="hidden" name="tree_prefix" value="'.$tree_prefix.'">';
echo '</td><td>';
print '&nbsp; <input class="fonts" type="submit" name="search2" value="'.__('Search').'">';
echo '</td><td>';

if(isset($_SESSION["search2"]) AND $_SESSION["search2"]==1) {
	$search_qry= "SELECT * FROM ".$tree_prefix_quoted."person WHERE CONCAT(REPLACE(pers_prefix,'_',' '),pers_lastname)";
	$search_qry.= " LIKE '%".$search_lastname2."%' AND pers_firstname LIKE '%".$search_firstname2."%' ORDER BY pers_lastname, pers_firstname";
	$search_result2 = $dbh->query($search_qry);
	if ($search_result2){
		if($search_result2->rowCount()>0) {
			print '<select class="fonts" size="1" name="person2" style="width:'.$len.'px">';
			while($searchDb2=$search_result2->fetch(PDO::FETCH_OBJ)) {
				$name=$pers_cls->person_name($searchDb2);
				if ($name["show_name"]){
					echo '<option';
					if(isset($person2)) {
						if ($searchDb2->pers_gedcomnumber==$person2 AND !(isset($_POST["search2"]) AND $search_lastname2=='' AND $search_firstname2=='')){
							echo ' SELECTED';
						}
					}
					$birth='';
					if ($searchDb2->pers_bapt_date){
						$birth=' '.__('~').' '.date_place($searchDb2->pers_bapt_date,'');
					}
					if ($searchDb2->pers_birth_date){
						$birth=' '.__('*').' '.date_place($searchDb2->pers_birth_date,'');
					}
					echo ' value="'.$searchDb2->pers_gedcomnumber.'">'.$name["index_name"].$birth.' ['.$searchDb2->pers_gedcomnumber.']</option>';
				}
			}
			echo '</select>';
		}
		else { print '<select size="1" name="notfound" value="1" style="width:'.$len.'px"><option>'.__('Person not found').'</option></select>'; }
	}
}
else { print '<select size="1" name="person2" style="width:'.$len.'px"><option></option></select>'; }
echo '</td></tr></table>';
/* echo '</form>'; */

// ===== END SEARCH BOX SYSTEM
ob_end_flush(); 

if(isset($_POST["extended"]) or isset($_POST["next_path"])) {
	if(!isset($_POST["next_path"])) { $_SESSION['next_path']=""; }

	ob_start();
	$count=0; $countfunc = 0;

	$globaltrack = "";
	$firstcall = array();
	$firstcall[0] = $person."@fst@fst@"."fst".$person;

	$globaltrack2 = "";
	$firstcall2 = array();
	$firstcall2[0] = $person2."@fst@fst@"."fst".$person2;

	$total_arr = array();

	$persqry = $dbh->prepare("SELECT * FROM ".safe_text($_SESSION['tree_prefix'])."person WHERE pers_gedcomnumber = ?"); 
	$persqry->bindParam(1,$persvar);
	$famqry = $dbh->prepare("SELECT * FROM ".safe_text($_SESSION['tree_prefix'])."family WHERE fam_gedcomnumber = ?");
	$famqry->bindParam(1,$famvar);

	if(isset($_POST["extended"]) AND !isset($_POST["next_path"])) {  
		$_SESSION["couple"]="";  
		// session[couple] flags that persons A & B are a couple. consequences: 
		// 1. don't display that (has already been done in regular calculator)
		// 2. in the map_tree function don't search thru the fam of the couple, since this gives errors.
		$persvar = $person; 
		$persqry->execute();
		$persDb = $persqry->fetch();

		$persvar = $person2; 
		$persqry->execute(); 
		$pers2Db = $persqry->fetch();

		if(isset($persDb['pers_fams']) AND isset($pers2Db['pers_fams'])) {
			$fam1=explode(";",$persDb['pers_fams']);
			$fam2=explode(";",$pers2Db['pers_fams']);
			foreach($fam1 as $value1) {
				foreach($fam2 as $value2) {
					if($value1==$value2) {
						$_SESSION["couple"]=$value1;  
					}
				}
			}
		}
	}
	
	echo '<br><table class="ext"><tr><td>'; 
	
	$global_array = array();
	$global_array2 = array(); 
	
	map_tree($firstcall,$firstcall2);
	echo '</td></tr></table>';
	ob_end_flush();
}


if(isset($_POST["calculator"]) OR isset($_POST["switch"])) { // calculate or switch button is pressed
	if(isset($person) AND $person!='' AND isset($person2) AND $person2!='') { // 2 persons have been selected
		$searchDb=$db_functions->get_person($person);
		$searchDb2=$db_functions->get_person($person2);
		if (isset($searchDb)){
			$gednr=$searchDb->pers_gedcomnumber;
			$name=$pers_cls->person_name($searchDb);
			$name1 = $name["name"];
			$sexe=''; if($searchDb->pers_sexe =='M') { $sexe='m'; } else { $sexe='f'; }
		}
		if($searchDb->pers_fams) {
			$famsX=$searchDb->pers_fams;
			$tempfam=explode(";",$famsX);
			$famX=$tempfam[0];
		}
		else {
			$famX=$searchDb->pers_famc;
		}
		if (isset($searchDb2)){
			$gednr2=$searchDb2->pers_gedcomnumber;
			$name=$pers_cls->person_name($searchDb2);
			$name2 = $name["name"];
			$sexe2=''; if($searchDb2->pers_sexe =='M') { $sexe2='m'; } else { $sexe2='f'; }
		}
		if($searchDb2->pers_fams) {
			$famsY=$searchDb2->pers_fams;
			$tempfam=explode(";",$famsY);
			$famY=$tempfam[0];
		}
		else {
			$famY=$searchDb2->pers_famc;
		}

		display(); // initiates all the comparison and calculation functions and writes result
	}
	else {  // "calculate" or "switch" button pressed with one or two names not selected: write warning to first choose two names
		print "<br><h3>&nbsp;&nbsp;&nbsp;".__('You have to search and than choose Person 1 and Person 2 from the search result pulldown')."</h3>";
	}
}
echo '</form>';
echo '<br><br><br><br><br><br><br><br>';
include_once(CMS_ROOTPATH."footer.php");
?>