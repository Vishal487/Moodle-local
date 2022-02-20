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
/**
 * TODO :: customize itemid
 */

// saving to database
$fs = get_file_storage();

// Prepare file record object
$fileinfo = array(
    'contextid' => $contextID, // ID of context
    'component' => 'mod_mymodule',     // usually = table name
    'filearea' => 'myarea',     // usually = table name
    'itemid' => 5,               // usually = ID of row in table
    'filepath' => '/',           // any path beginning and ending in /
    'filename' => 'pannot.pdf'); // any filename

// Create file containing text 'hello world'
$fs->create_file_from_pathname($fileinfo, "./test4.pdf");


?>