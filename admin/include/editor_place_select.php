<?php


// *** Safety line ***
if (!defined('ADMIN_PAGE')) {
    exit;
}
include_once(__DIR__ . "/editor_cls.php");
$editor_cls = new editor_cls;

$location_lat = 0;
$location_lng = 0;
echo '<h1 class="center">' . __('Select geodata') . '</h1>';

$address_id = 0;
$place_item = '';
$form = '';
$place = '';
if (isset($_GET['form'])) {
    $check_array = array("1", "2", "3", "5", "6");
    if (in_array($_GET['form'], $check_array)) {
        $form = 'form' . $_GET['form'];
    }

    $check_array = array(
        "pers_birth_place", "pers_bapt_place", "pers_death_place", "pers_buried_place",
        "fam_relation_place", "fam_marr_notice_place", "fam_marr_place", "fam_marr_church_notice_place", "fam_marr_church_place", "fam_div_place",
        "address_place", "event_place", "birth_decl_place", "death_decl_place"
    );
    if (in_array($_GET['place_item'], $check_array)) {
        $place_item = $_GET['place_item'];
    }
    if (isset($_GET['place'])) {
        $place = $_GET['place'];
    }
}
echo '
    <script>
    function transferData(){
        /* EXAMPLE: window.opener.document.form1.pers_birth_place.value=item; */
        var place = document.geodata.add_name.value;
        window.opener.document.' . $form . '.' . $place_item . '.value = place;
        if (document.geodata.location_lat.value != 0 &&
            document.geodata.location_lng.value != 0) {
            var latlng = document.geodata.location_lat.value + ";" + document.geodata.location_lng.value;
            window.opener.document.' . $form . '.' . $place_item . '_geo.value = latlng;
            window.opener.document.getElementById("' .$place_item .'_disp").style.display = "";   
        }
        top.close();
        return false;
    }
    function cancelWindow(){
        top.close();
        return false;
    }
    </script>
';
$isindb = false;
if (strlen($place) > 0) {
    $testplace = $dbh->query("SELECT * FROM humo_location WHERE location_location = '" . $editor_cls->text_process($place) . "'");
    if ($testplaceDB = $testplace->fetch(PDO::FETCH_OBJ)) {
        $location_lat = $testplaceDB->location_lat;
        $location_lng = $testplaceDB->location_lng;
        $isindb = true;
    }
} else {
    ?>
    <b><?= __('No location specified for search!'); ?></b>
    <input type="button" name="b3" onclick="cancelWindow();" value="<?= __('Close window'); ?>" class="btn btn-sm btn-secondary">
    <?php
    exit;
}

if (empty($location_lat)) {
    $location_lat = 0;
    $location_lng = 0;
    $isindb = false; // in db but without values
    
}
?>
<link rel="stylesheet" href="../assets/leaflet/leaflet.css">
<script src="../assets/leaflet/leaflet.js"></script>
<div class="p-3 m-2 genealogy_search container-md">
    <div class="row mb-1 p-2 bg-primary-subtle">
        <?= __('Search geodata of'); ?> <?= $place; ?>
    </div>
        <div class="row mb-2">
            <div class="col-md-12">
                <div class="row mb-2">
                    <div class="col-md-6">
                    <?php
                    if ($isindb) {
                        echo '<div class="col-md-auto"><b>';
                        echo __('Location found in the database. Geodata available. Start a new search to correct the data. Click "Cancel" to keep the entries.');
                        echo '</b><br><br></div>';
                    }
                    ?>
                    <form method="POST" name="geodata" action="index.php?page=maps&amp;menu=locations">
 
                        <div class="row mb-2">
                            <div class="col-md-5">
                                <?= __('Latitude'); ?>
                            </div>
                            <div class="col-md-5">
                                <input type="text" size="20" id="latbox" name="location_lat" value="<?= $location_lat; ?>" class="form-control form-control-sm">
                            </div>
                        </div>

                        <div class="row mb-2">
                            <div class="col-md-5">
                                <?= __('Longitude'); ?>
                            </div>
                            <div class="col-md-5">
                                <input type="text" size="20" id="lngbox" name="location_lng" value="<?= $location_lng; ?>" class="form-control form-control-sm">
                            </div>
                        </div>

                        <?= __('You can also drag the marker.'); ?><br><br>
                        <?php if ($isindb) { 
                            $my_message = __('Update geodata');
                        } else {
                            $my_message = __('Import geodata');
                        }
                        ?>
                        <input type="button" name="b1" onclick="transferData();" value="<?= $my_message; ?>" class="btn btn-sm btn-success">
                        <input type="button" name="b2" onclick="cancelWindow();" value="<?= __('Cancel'); ?>" class="btn btn-sm btn-danger">
 
                        <br><br>
                        <div class="row mb-2">
                            <div class="col-md-auto">
                                <input type="text" name="add_name" id="address" value="<?= $place; ?>" size="36" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-auto">
                                <input type="button" name="loc_search" value="<?= __('Search'); ?>" onclick="codeAddress();" class="btn btn-sm btn-secondary">
                            </div>
                        </div>
                        <div id="result_places" class="row mb-2">
                    </div>
                    </form>
                    </div>
                 <div class="col-md-6">
                         <div id="map" style="height: 300px;"></div>
                </div>
                      <?php
                        echo '<script>
                            var map = L.map("map").setView([' . $location_lat . ', ' . $location_lng . '], 15);
                                
                            var myIcon = L.icon({
                                iconUrl: "../assets/leaflet/images/marker-icon.png",
                                iconRetinaUrl: "../assets/leaflet/images/marker-icon-2x.png",
                                iconSize: [25, 41],
                                iconAnchor: [9, 21],
                                popupAnchor: [0, -14]
                            });  
                            L.tileLayer(\'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png\', {
                                attribution: \'&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors\'
                            }).addTo(map);
                            var current_marker = L.marker([' . $location_lat . ', ' . $location_lng . '], {draggable:true, icon: myIcon}).addTo(map);
                               
                            function setDraggable () {
                                current_marker.on(\'dragend\', function(e) {
                                    var lat = e.target.getLatLng().lat;
                                    var lng = e.target.getLatLng().lng;
                                    //console.log("Marker drag ended at:", lat, lng);
                                    map.panTo([lat, lng], 15);
                                    document.getElementById("latbox").innerHtml = lat;
                                    document.getElementById("lngbox").innerHtml = lng;
                                    document.getElementById("latbox").value = lat;
                                    document.getElementById("lngbox").value = lng;
                                });
                            }
                            setDraggable();
                            var search_name_org = window.opener.document.' . $form . '.' . $place_item . '.value;
                            ';
                        if ($isindb === false) {
                        
                        echo '
                            document.getElementById(\'address\').value = search_name_org;
                            codeAddress(search_name_org);';
                        }
                        echo '
                            function codeAddress() {
                                var address = document.getElementById(\'address\').value;
                                var encaddr = encodeURI(address);
                                var nom_req = new XMLHttpRequest();
                                var url = "https://nominatim.openstreetmap.org/search?q=" + encaddr + "&format=json&addressdetails=1&limit=40";

                                nom_req.onreadystatechange = function() {
                                    if (this.readyState == 4 && this.status == 200) {
                                        var results = JSON.parse(this.responseText);
                                        myDisplayResult(results);
                                    }
                                };
                                nom_req.open("GET", url, true);
                                nom_req.send();

                                function myDisplayResult(res) {
                                    if (typeof (res[0]) !== "undefined") {
                                        setLanLng(res[0].lat, res[0].lon);

                                        //console.log("Found name:", res[0].display_name);

                                        var  my_list = "<ul>\n";
                                        for (var i = 0; i < res.length; i++) {
                                            my_list += \'<li><span class="hideshowlink" id="place_\' + i + \'" data-lat="\' + res[i].lat + \'" data-lng="\' + res[i].lon + \'">\' + res[i].display_name + "</span></li>\n";
                                        }
                                        document.getElementById("result_places").innerHTML = my_list + "</ul>";
                                        for (var i = 0; i < res.length; i++) {
                                            let my_id = "place_" + i;
                                            document.getElementById( my_id ).addEventListener ("click", get_places);
                                        }
                                    } else {
                                        document.getElementById("result_places").innerHTML = "<b>'; 
                        echo __('Place not found.');
                        echo '</b>";
                                    }
                                }
                                function setLanLng(lat, lng){
                                    map.removeLayer(current_marker);
                                    document.getElementById("latbox").innerHtml = lat;
                                    document.getElementById("lngbox").innerHtml = lng;
                                    document.getElementById("latbox").value = lat;
                                    document.getElementById("lngbox").value = lng;
                                    map.setView([lat, lng], 15);
                                    current_marker = L.marker([lat, lng], {draggable: true, icon: myIcon}).addTo(map);
                                    setDraggable();
                                }
                                function get_places(e) { // callback
                                    //console.log("Got LatLng:", this.dataset.lat, this.dataset.lng);
                                    setLanLng(this.dataset.lat, this.dataset.lng);
                                }
                            }
                        </script>';
?>
            </div>
        </div>
    </div>
</div>