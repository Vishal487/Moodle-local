<?php

/**
 * This page saves annotated pdf to database.
 */

require_once('../../config.php');
require_once('locallib.php');

if(!empty($_FILES['data'])) 
{
    // PDF is located at $_FILES['data']['tmp_name']
    // rename(...) it or send via email etc.
    $data = file_get_contents($_FILES['data']['tmp_name']);
    $fname = "test4.pdf"; // name the file
    $file = fopen("./" .$fname, 'w'); // open the file path
    fwrite($file, $data); //save data
    fclose($file);
} 
else 
{
    throw new Exception("no data");
}

$contextID = $_REQUEST['contextID'];
$attemptID = $_REQUEST['attemptID'];
$filename = $_REQUEST['filename'];

$component = 'question';
$filearea = 'response_attachments';
$filepath = '/';
$itemid = $attemptID;
$temppath = "./test4.pdf";

$fs = get_file_storage();

// Prepare file record object
$fileinfo = array(
    'contextid' => $contextID, // ID of context
    'component' => $component,     // usually = table name
    'filearea' => $filearea,     // usually = table name
    'itemid' => $itemid,  // usually = ID of row in table   
    'filepath' => $filepath,           // any path beginning and ending in /
    'filename' => $filename); // any filename   

// $hash = sha1("/$contextID/$component/$filearea/$itemid".$filepath.$filename);
// var_dump($hash);

// Create file 
// TODO :: check if exists
$doesExists = $fs->file_exists($contextID, $component, $filearea, $itemid, $filepath, $filename);
if($doesExists === true)
{
    $storedfile = $fs->get_file($contextID, $component, $filearea, $itemid, $filepath, $filename);
    $storedfile->delete();
    $fs->create_file_from_pathname($fileinfo, $temppath);

    // // $filerecord = new stdClass();
    // $fs->synchronise_stored_file_from_file($storedfile, $temppath, $fileinfo);
}
else
{
    $fs->create_file_from_pathname($fileinfo, $temppath);
}
// update_references_to_storedfile


?>