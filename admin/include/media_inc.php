<?php
// holds the prefixes for existent category subdirectories
global $pcat_dirs;
$pcat_dirs = get_pcat_dirs();
global $pict_options;
$pict_options = get_pict_options();
// lookup which library is available or none
function create_thumbnail($folder, $file)
{
    global $pict_options;
    if ($pict_options[2] == 'n') { return (false); } // thumbnails disabled
    $theight = 120; // default
    if (extension_loaded('imagick')) {
        return (create_thumbnail_IM($folder, $file, $theight)); // true on success
    } elseif ((extension_loaded('gd'))) {
        return (create_thumbnail_GD($folder, $file, $theight)); // true on success
    } else {
        return (false); // no thumbnails
    }
}
// lookup which library is available or none
function resize_picture($folder, $file)
{
    global $pict_options;
    if (empty($pict_options[0])) { return (false); } // resizing disabled
    if (extension_loaded('imagick')) {
        return (resize_picture_IM($folder, $file, $pict_options[0], $pict_options[1])); // true on success
    } elseif ((extension_loaded('gd'))) {
        return (resize_picture_GD($folder, $file,  $pict_options[0], $pict_options[1])); // true on success
    } else {
        return (false); // no resizing
    }
}

// Imagick library - returns true if a thumbnail has been created 
function create_thumbnail_IM($folder, $file, $theight = 120)
{
    $is_ghostscript = false;   // ghostscript has to be installed for pdf handling
    $is_ffmpeg      = false;   // ffmpeg has to be installed for video handling
    $no_windows = (strtolower(substr(PHP_OS, 0, 3)) !== 'win');
    if ($no_windows) 
    { 
        if ( trim(shell_exec('type -P gs')))       { $is_ghostscript = true; }
        if ( trim(shell_exec('type -P ffmpeg')))   { $is_ffmpeg = true; }
    }
    $add_arrow = false;
    $success = false;
    $pict_path_original = $folder . $file;
    $pict_path_thumb = $folder . 'thumb_' . $file . '.jpg';
    $imtype = strtoupper(substr($file, -3));
    if (Imagick::queryformats($imtype . '*')) {
        $fhandle = fopen($folder . '.' . $file . '.no_thumb', "w"); // create no_thumb to mark corrupt files
        fclose($fhandle);
        if ($imtype == 'PDF' && $is_ghostscript) {
            $im = new \Imagick($pict_path_original . '[0]'); //first page of PDF (default: last page)
            $im->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE); // without you only get black frames
        } elseif (($imtype == 'MP4' ||
                $imtype == 'MPG' ||
                $imtype == 'FLV' ||
                $imtype == 'MOV' ||
                $imtype == 'AVI')
            && $is_ffmpeg
        ) {
            $im = new \Imagick($pict_path_original . "[15]"); // [] should select frame 15 of video, not working, allways takes the first frame
            $add_arrow = true;
        } else {
            $im = new \Imagick($pict_path_original);
        }
        $im->setbackgroundcolor('rgb(255, 255, 255)');
        $im->thumbnailImage(0, $theight);                     // automatic proportional scaling
        // add play_button to movie thumbnails
        if ($add_arrow && is_file(__DIR__ . '/../images/play_button.png')) {
            $im2 = new \Imagick(__DIR__ . '/../images/play_button.png');
            $xpos = floor($im->getImageWidth() / 2 - $im2->getImageWidth() / 2);
            $ypos = floor($im->getImageHeight() / 2 - $im2->getImageHeight() / 2);
            $im->compositeImage($im2, $im2->getImageCompose(), $xpos, $ypos);
            $im2->clear();
            $im2->destroy();
        }
        $success = ($im->writeImage($pict_path_thumb));
        unlink($folder . '.' . $file . '.no_thumb');  // delete no_thumb
        $im->clear();
        $im->destroy();
    }
    return ($success);
}

// Imagic library - returns true on success or if picture already fits 
function resize_picture_IM($folder, $file, $maxheight = 1080, $maxwidth = 1920)
{
    $success = true;
    $pict_path_original = $folder . $file;
    $pict_path_resize = $pict_path_original;
    $imtype = strtoupper(substr($file, -3));
    if (
        $imtype == 'JPG' ||
        $imtype == 'PNG' ||
        $imtype == 'BMP' ||
        $imtype == 'TIF' ||
        $imtype == 'GIF'
    ) {
        if (Imagick::queryformats($imtype . '*'))  // format supported by Imagick?
        {
            $im = new \Imagick($pict_path_original);
            if ($im->getImageHeight() > $maxheight || $im->getImageWidth() > $maxwidth) {
                $im->resizeImage($maxwidth, $maxheight, Imagick::FILTER_CATROM, 1, true);
                $success = $im->writeImage($pict_path_resize);
                $im->clear();
                $im->destroy();
            }
        }
    }
    return ($success);
}
//search for a thumbnail or mime type placeholder and returns the image tag
function print_thumbnail($folder, $file, $maxw = 0, $maxh = 120, $css = '', $attrib = '', $link2hires=false, $link_attrib='', $html_before='')
{
    global $pcat_dirs, $pict_options, $dbh, $tree_id;

    if (!$file || !$folder) {
        return '<img src="../images/thumb_missing-image.jpg" style="width:auto; height:120px;" title="' . $folder . $file . ' missing path/filename">';
    }

    $img_style = ' style="';
    if ($maxw > 0 && $maxh > 0) {
        $img_style .= 'width:auto; height:auto; max-width:' . $maxw . 'px; max-height:' . $maxh . 'px; ' . $css . '" ' . $attrib;
    } elseif ($maxw > 0) {
        $img_style .= 'height:auto; max-width:' . $maxw . 'px; ' . $css . '" ' . $attrib;
    } elseif ($maxh > 0) {
        $img_style .= 'width:auto; max-height:' . $maxh . 'px; ' . $css . '" ' . $attrib;
    } else {
        $img_style .= 'width:auto; height:120px; ' . $css . '" ' . $attrib;
    }
    // this is a repair kit for inconsistent suffix code.
    // should be removed after a while in later versions (2024-12-17)
    // looking for media files in suffix folder and add suffix folder to Db
    if (!file_exists($folder . $file)) {
        if (array_key_exists(substr($file, 0, 3), $pcat_dirs)
            && file_exists($folder . substr($file, 0, 2) . '/' . $file) ) {
            $sql = "UPDATE humo_events SET
            event_event='" . safe_text_db(substr($file, 0, 2) . '/' . $file) . "' WHERE event_event='" . safe_text_db($file) . "'";
            $result = $dbh->query($sql);
            //echo 'DB update: ' . $result;
            $file = substr($file, 0, 2) . '/' . $file;
        }
    }
    // END repair kit
    
    if (!file_exists($folder . $file)) {
        return '<img src="../images/thumb_missing-image.jpg" style="width:auto; height:120px;" title="' . $folder . $file . ' not found">';
    }

    $rwfolder = $folder; // rwfolder will be changed due to rewrite mode
    $data2sql = $dbh->query("SELECT * FROM humo_trees WHERE tree_id=" . $tree_id);
    $data2Db = $data2sql->fetch(PDO::FETCH_OBJ);
    // for rewrite: remove tree_pict_path from folder to get subfolder
    $subfolder = str_replace( '../' .$data2Db->tree_pict_path, '', $folder);
    if ($data2Db->tree_pict_path_rewrite == 'i'){
        if (strpos( $_SERVER["PHP_SELF"], '/admin/index.php' ) ) { $rwfolder = '../media.php?' . $subfolder;}
        else { $rwfolder = 'media.php?';}
    } elseif ($data2Db->tree_pict_path_rewrite == 's') {
        if (strpos( $_SERVER["PHP_SELF"], '/admin/index.php' ) ) { $rwfolder = '../media/' . $subfolder;}
        else { $rwfolder = 'media/';}       
    }
    $link = '';
    $link_close = '';
    if ($link2hires) {
        $link = '<a href="' . $rwfolder . $file . '" ' . $link_attrib . '>' . $html_before;
        $link_close = '</a>';
    }
    $thumb_url =  thumbnail_exists($folder, $file); // array! folder, file
    if (!empty($thumb_url && $pict_options[2] == 'y')) {
        return $link . '<img src="' . $rwfolder . $thumb_url[1] . '"' . $img_style . '>' . $link_close;
    } // found thumbnail

    // no thumbnail found, create a new one
    // check for mime type and no_thumb file
    if (
        check_media_type($folder, $file) &&
        !is_file($folder . '.' . $file . '.no_thumb')
    ) {
        // script will possibily die here and hidden no_thumb file becomes persistent
        // so this code might be skiped afterwords
        if (create_thumbnail($folder, $file)) {
            $newthumb_url =  thumbnail_exists($folder, $file); // test for dir in filename
            return $link . '<img src="' . $rwfolder . $newthumb_url[1] . '"' . $img_style . '>' . $link_close;
        }
    }

    $extensions_check = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    switch ($extensions_check) {
        case 'pdf':
            return '<img src="../images/pdf.jpg" alt="PDF">';
        case 'docx':
            return '<img src="../images/msdoc.gif" alt="DOCX">';
        case 'doc':
            return '<img src="../images/msdoc.gif" alt="DOC">';
        case 'wmv':
            return '<img src="../images/video-file.png" alt="WMV">';
        case 'avi':
            return '<img src="../images/video-file.png" alt="AVI">';
        case 'mp4':
            return '<img src="../images/video-file.png" alt="MP4">';
        case 'mpg':
            return '<img src="../images/video-file.png" alt="MPG">';
        case 'mov':
            return '<img src="../images/video-file.png" alt="MOV">';
        case 'wma':
            return '<img src="../images/video-file.png" alt="WMA">';
        case 'wav':
            return '<img src="../images/audio.gif" alt="WAV">';
        case 'mp3':
            return '<img src="../images/audio.gif" alt="MP3">';
        case 'mid':
            return '<img src="../images/audio.gif" alt="MID">';
        case 'ram':
            return '<img src="../images/audio.gif" alt="RAM">';
        case 'ra':
            return '<img src="../images/audio.gif" alt="RA">';
        case 'jpg':
            return $link . '<img src="' . $rwfolder . $file . '"' . $img_style . '>' . $link_close;
        case 'jpeg':
            return $link . '<img src="' . $rwfolder . $file . '"' . $img_style . '>' . $link_close;
        case 'png':
            return $link . '<img src="' . $rwfolder . $file . '"' . $img_style . '>' . $link_close;
        case 'gif':
            return $link . '<img src="' . $rwfolder . $file . '"' . $img_style . '>' . $link_close;
        case 'tif':
            return $link . '<img src="' . $rwfolder . $file . '"' . $img_style . '>' . $link_close;
        case 'tiff':
            return $link . '<img src="' . $rwfolder . $file . '"' . $img_style . '>' . $link_close;
        case 'bmp':
            return $link . '<img src="' . $rwfolder . $file . '"' . $img_style . '>' . $link_close;
    }
    return '<img src="../images/thumb_missing-image.jpg"' . $img_style . '>';
}
// returns false if mime type of file is not listed here
function check_media_type($folder, $file)
{
    $mtypes = [
        'image/pjpeg',
        'image/jpeg',
        'image/gif',
        'image/png',
        'image/bmp',
        'image/tiff',
        'audio/mpeg',
        'audio/mpeg3',
        'audio/x-mpeg',
        'audio/x-mpeg3',
        'audio/mpg',
        'audio/mp3',
        'audio/mid',
        'audio/midi',
        'audio/x-midi',
        'audio/x-ms-wma',
        'audio/wav',
        'audio/x-wav',
        'audio/x-pn-realaudio',
        'audio/x-realaudio',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'video/quicktime',
        'video/x-flv',
        'video/avi',
        'video/x-msvideo',
        'video/msvideo',
        'video/mpeg',
        'video/mp4'
    ];
    $mtype  = mime_content_type($folder . $file);

    if (in_array($mtype, $mtypes)) {
        return ($mtype);
    }
    return (false);
}

function thumbnail_exists($folder, $file) // returns [folder, filename] or ''
{
    global $pcat_dirs;
    $pparts = pathinfo($file);
    if (!$file || !file_exists($folder . $file)) {
        return '';
    }
    if (file_exists($folder . 'thumb_' . $file . '.jpg')) {
        return [ $folder, 'thumb_' . $file . '.jpg'];
    }
    if (file_exists($folder . 'thumb_' . $file)) {
        return [$folder, 'thumb_' . $file];
    } // old naming
    if (file_exists($folder . $pparts['dirname'] . '/thumb_' . $pparts['basename'] . '.jpg')) {
        return [$folder, $pparts['dirname'] . '/thumb_' . $pparts['basename'] . '.jpg'];
    }
    if (file_exists($folder . $pparts['dirname'] . '/thumb_' . $pparts['basename'])) {
        return [$folder, $pparts['dirname'] . '/thumb_' . $pparts['basename'] ];
    } // old naming
/*    if (array_key_exists(substr($file, 0, 3), $pcat_dirs)) {
        $folder .= substr($file, 0, 2) . '/';
    } // check for cat folder
    if (file_exists($folder . 'thumb_' . $file . '.jpg')) {
        return ($folder . 'thumb_' . $file . '.jpg');
    }
    if (file_exists($folder . 'thumb_' . $file)) {
        return ($folder . 'thumb_' . $file);
    }  // old naming
*/
    return '';
}
// GD library - returns true if a thumbnail has been created
function create_thumbnail_GD($folder, $file, $theight = 120)
{
    $pict_path_original = $folder . $file;
    $pict_path_thumb = $folder . 'thumb_' . $file . '.jpg';
    $gd_info = gd_info();
    list($is_gdjpg, $is_gdgif, $is_gdpng) = array($gd_info['JPEG Support'], $gd_info['GIF Read Support'], $gd_info['PNG Support']);
    $gdmime = get_GDmime(); // a.array
    $imtype = $gdmime[ check_media_type($folder, $file) ];
    $success = false;
    list($width, $height) = getimagesize($pict_path_original);
    if ($height == 0) {
        return ($success);
    }
    $twidth = floor($width * ($theight / $height));

    if ($imtype == 'JPG' && $is_gdjpg) {
        $fhandle = fopen($folder . '.' . $file . '.no_thumb', "w"); // create no_thumb to mark corrupt files
        fclose($fhandle);
        $create_thumb = imagecreatetruecolor($twidth, $theight);
        $source = imagecreatefromjpeg($pict_path_original);
        imagecopyresized($create_thumb, $source, 0, 0, 0, 0, $twidth, $theight, $width, $height);
        $success = imagejpeg($create_thumb, $pict_path_thumb);
        imagedestroy($create_thumb);
        imagedestroy($source);
        unlink($folder . '.' . $file . '.no_thumb');  // delete no_thumb   
    } elseif ($imtype == 'PNG' && $is_gdpng) {
        $fhandle = fopen($folder . '.' . $file . '.no_thumb', "w"); // create no_thumb to mark corrupt files
        fclose($fhandle);
        $create_thumb = imagecreatetruecolor($twidth, $theight);
        $source = imagecreatefrompng($pict_path_original);
        imagecopyresized($create_thumb, $source, 0, 0, 0, 0, $twidth, $theight, $width, $height);
        $success = imagejpeg($create_thumb, $pict_path_thumb);
        imagedestroy($create_thumb);
        imagedestroy($source);
        unlink($folder . '.' . $file . '.no_thumb');  // delete no_thumb   
    } elseif ($imtype == 'GIF' && $is_gdgif) {
        $fhandle = fopen($folder . '.' . $file . '.no_thumb', "w"); // create no_thumb to mark corrupt files
        fclose($fhandle);
        $create_thumb = imagecreatetruecolor($twidth, $theight);
        $source = imagecreatefromgif($pict_path_original);
        imagecopyresized($create_thumb, $source, 0, 0, 0, 0, $twidth, $theight, $width, $height);
        $success = imagejpeg($create_thumb, $pict_path_thumb);
        imagedestroy($create_thumb);
        imagedestroy($source);
        unlink($folder . '.' . $file . '.no_thumb');  // delete no_thumb   
    }
    return ($success);
}

// GD library - returns true on success or if no resizing has to be done
function resize_picture_GD($folder, $file, $maxheight = 1080, $maxwidth = 1920)
{
    $success = false;

    $pict_path_original = $folder . $file;
    $picture_original_tmp = $folder . '0_temp' . $file . '.jpg';
    $gd_info = gd_info();
    list($is_gdjpg, $is_gdgif, $is_gdpng) = array($gd_info['JPEG Support'], $gd_info['GIF Read Support'], $gd_info['PNG Support']);
    $gdmime = get_GDmime(); // a.array
    $imtype = $gdmime[ check_media_type($folder, $file) ];
    list($width, $height) = getimagesize($pict_path_original);
    if ($width <= $maxwidth && $height <= $maxheight) {
        return (true);
    }
    if ($height == 0) {
        return (false);
    }
    if ($maxheight <= $maxwidth) {
        $rheight = $maxheight;
        $rwidth = ($rheight / $height) * $width;
    } else {
        $rwidth = $maxwidth;
        $rheight = ($rwidth / $width) * $height;
    }
    echo ('Resize: ' . $rwidth . ' - ' . $rheight);
    if ($imtype == 'JPG' && $is_gdjpg) {
        rename($pict_path_original, $picture_original_tmp);
        $create_resized = imagecreatetruecolor($rwidth, $rheight);
        $source = imagecreatefromjpeg($picture_original_tmp);
        imagecopyresized($create_resized, $source, 0, 0, 0, 0, $rwidth, $rheight, $width, $height);
        $success = imagejpeg($create_resized, $pict_path_original);
        imagedestroy($create_resized);
        imagedestroy($source);
        unlink($picture_original_tmp);
    } elseif ($imtype == 'PNG' && $is_gdpng) {
        rename($pict_path_original, $picture_original_tmp);
        $create_resized = imagecreatetruecolor($rwidth, $rheight);
        $source = imagecreatefrompng($picture_original_tmp);
        imagecopyresized($create_resized, $source, 0, 0, 0, 0, $rwidth, $rheight, $width, $height);
        $success = imagepng($create_resized, $pict_path_original);
        imagedestroy($create_resized);
        imagedestroy($source);
        unlink($picture_original_tmp);
    } elseif ($imtype == 'GIF' && $is_gdgif) {
        rename($pict_path_original, $picture_original_tmp);
        $create_resized = imagecreatetruecolor($rwidth, $rheight);
        $source = imagecreatefromgif($picture_original_tmp);
        imagecopyresized($create_resized, $source, 0, 0, 0, 0, $rwidth, $rheight, $width, $height);
        $success = imagegif($create_resized, $pict_path_original);
        imagedestroy($create_resized);
        imagedestroy($source);
        unlink($picture_original_tmp);
    }
    return ($success);
}
function get_pcat_dirs() // returns a.array with existing cat subfolders key=>dir val=>category name localized
{
    global $dbh, $tree_id, $selected_language;

    $data2sql = $dbh->query("SELECT * FROM humo_trees WHERE tree_id=" . $tree_id);
    $dataDb = $data2sql->fetch(PDO::FETCH_OBJ);
    $tree_pict_path = $dataDb->tree_pict_path;
    if (substr($tree_pict_path, 0, 1) === '|') { $tree_pict_path = 'media/'; }
    // adjust path to media dir
    $tree_pict_path = __DIR__ . '/../../' . $tree_pict_path;
    $tmp_pcat_dirs = array();
    $temp = $dbh->query("SHOW TABLES LIKE 'humo_photocat'");
    if ($temp->rowCount()) {   // there is a category table
        $catg = $dbh->query("SELECT photocat_prefix FROM humo_photocat WHERE photocat_prefix != 'none' GROUP BY photocat_prefix");
        if ($catg->rowCount()) {
            while ($catDb = $catg->fetch(PDO::FETCH_OBJ)) {
                $dirtest = $catDb->photocat_prefix;
                if (is_dir($tree_pict_path . '/' . substr($dirtest, 0, 2))) {  // there is a subfolder of this prefix
                    $name = $dbh->query("SELECT * FROM humo_photocat WHERE photocat_prefix='" . $catDb->photocat_prefix . "' AND photocat_language = '" . $selected_language . "'");
                    if ($name->rowCount()) {  // there is a name for this language
                        $nameDb = $name->fetch(PDO::FETCH_OBJ);
                        $catname = $nameDb->photocat_name;
                    } else {  // maybe a default is set
                        $name = $dbh->query("SELECT * FROM humo_photocat WHERE photocat_prefix='" . $catDb->photocat_prefix . "' AND photocat_language = 'default'");
                        if ($name->rowCount()) {  // there is a default name for this category
                            $nameDb = $name->fetch(PDO::FETCH_OBJ);
                            $catname = $nameDb->photocat_name;
                        } else {  // no name found => show directory name
                            $catname = substr($dirtest, 0, 2);
                        }
                    }
                   $tmp_pcat_dirs[$dirtest] = $catname;
                }
            }
        }
    }
    return $tmp_pcat_dirs;
}
function get_GDmime () {
    return [ 'image/pjpeg'  => 'JPG',
             'image/jpeg'   => 'JPG',
             'image/gif'    => 'GIF',
             'image/png'    => 'PNG',
             'image/bmp'    => 'BMP',
             'image/tiff'   => 'TIF',
             'audio/mpeg'   => '-',
             'audio/mpeg3'  => '-',
             'audio/x-mpeg' => '-',
             'audio/x-mpeg3'=> '-',
             'audio/mpg'    => '-',
             'audio/mp3'    => '-',
             'audio/mid'    => '-',
             'audio/midi'   => '-',
             'audio/x-midi' => '-',
             'audio/x-ms-wma' => '-',
             'audio/wav'      => '-',
             'audio/x-wav'    => '-',
             'audio/x-pn-realaudio'=> '-',
             'audio/x-realaudio'   => '-',
             'application/pdf'     => '-',
             'application/msword'  => '-',
             'application/vnd.openxmlformats-officedocument.wordprocessingml.document'  => '-',
             'video/quicktime' => '-',
             'video/x-flv'     => '-',
             'video/avi'       => '-',
             'video/x-msvideo' => '-',
             'video/msvideo'   => '-',
             'video/mpeg'      => '-',
             'video/mp4'       => '-'
            ];
}
function test_rewrite() {
   $my_path = str_replace('/admin/index.php', '', $_SERVER["PHP_SELF"]);
   $my_path = str_replace('/index.php', '', $my_path);
   $save_elevel = error_reporting(); // save error level to restore
   error_reporting(0);  // trun off errors reporting
   $link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$my_path/media.php?ping";
   $response = file_get_contents($link, true);
   if ($response !== 'pong') {
       error_reporting($save_elevel); // restore error level
       return 'nn'; // no connection, status unknown
   } 
   $link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$my_path/media/ping";
   $response = file_get_contents($link, true);
   if ($response == 'pong') {
        error_reporting($save_elevel);  // restore error level
       return 'on';  // server rewrite on 
    } 
    error_reporting($save_elevel);  // restore error level
    return 'off';  // server rewrite off 
}

function get_pict_options () {
    global $dbh, $tree_id;
    $resize_vals = array ( 0, 0, 'n');
    $data2sql = $dbh->query("SELECT * FROM humo_trees WHERE tree_id=" . $tree_id);
    $data2Db = $data2sql->fetch(PDO::FETCH_OBJ);
    if (isset($data2Db->tree_pict_resize)) {
        $tmp_res = $data2Db->tree_pict_resize;
        $resize_vals = explode('|', $tmp_res); 
   }
    if (isset($data2Db->tree_pict_thumbnail)) {
        $resize_vals[2] =$data2Db->tree_pict_thumbnail;
   }
   return $resize_vals;
}