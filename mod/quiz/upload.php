<?php

/**
 * @author Tausif Iqbal, Vishal Rao
 * This page saves annotated pdf to database.
 * 
 * It gets the file data from JavaScript through POST request.
 * Then save it temporarily in this directory.
 * Then create new file in databse using this temporary file.
 */

require_once('../../config.php');
require_once('locallib.php');

// get the annotated data from JavaScript
if(!empty($_FILES['data'])) 
{
    $data = file_get_contents($_FILES['data']['tmp_name']);
    $fname = "temp.pdf"; // name the file
    $file = fopen("./" .$fname, 'w'); // open the file path
    fwrite($file, $data); //save data
    fclose($file);
} 
else 
{
    throw new Exception("no data");
}

$contextid = $_REQUEST['contextid'];
$attemptid = $_REQUEST['attemptid'];
$filename = $_REQUEST['filename'];
$component = 'question';
$filearea = 'response_attachments';
$filepath = '/';
$itemid = $attemptid;

$temppath = './' . $fname;

$fs = get_file_storage();
// Prepare file record object
$fileinfo = array(
    'contextid' => $contextid,
    'component' => $component,
    'filearea' => $filearea,
    'itemid' => $itemid,
    'filepath' => $filepath,
    'filename' => $filename);

// check if file already exists, then first delete it.
$doesExists = $fs->file_exists($contextid, $component, $filearea, $itemid, $filepath, $filename);
if($doesExists === true)
{
    $storedfile = $fs->get_file($contextid, $component, $filearea, $itemid, $filepath, $filename);
    $storedfile->delete();
}
// finally save the file (creating a new file)
$fs->create_file_from_pathname($fileinfo, $temppath);
?>