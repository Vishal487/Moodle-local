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

// var_dump(filesize("./temp.pdf"));

// get the annotated data from JavaScript
if(!empty($_FILES['data'])) 
{
    $data = file_get_contents($_FILES['data']['tmp_name']);
    $fname = "temp.pdf"; // name the file
    $file = fopen("./" .$fname, 'w'); // open the file path
    fwrite($file, $data); //save data
    fclose($file);
    
    // max file size allowed in the particular course
    // $maxbytes = (int)($_REQUEST['maxbytes']);    // in bytes
    $max_upload = (int)(ini_get('upload_max_filesize'));
    $max_post = (int)(ini_get('post_max_size'));
    $memory_limit = (int)(ini_get('memory_limit'));
    $max_mb = min($max_upload, $max_post, $memory_limit); // in mb
    $maxbytes = $max_mb*1024*1024; // in bytes

    $mdl_maxbytes = $CFG->maxbytes;
    if($mdl_maxbytes > 0)
    {
        $maxbytes = min($maxbytes, $mdl_maxbytes);
    }

    // curr file size
    $fsize = strlen($data);   // in bytes

    $file = fopen("./test.txt", "w");
    fwrite($file, $max_upload);
    fwrite($file, "\n");
    fwrite($file, $max_post);
    fwrite($file, "\n");
    fwrite($file, $memory_limit);
    fwrite($file, "\n");
    fwrite($file, $max_mb);
    fwrite($file, "\n");
    fwrite($file, $maxbytes);
    fwrite($file, "\n");
    fwrite($file, $fsize);
    // fclose($file);

    if(($fsize > 0) && ($maxbytes > 0) && ($fsize < $maxbytes))
    {
        $contextid = $_REQUEST['contextid'];
        $attemptid = $_REQUEST['attemptid'];
        $filename = $_REQUEST['filename'];
        $component = 'question';
        $filearea = 'response_attachments';
        $filepath = '/';
        $itemid = $attemptid;

        $temppath = './' . $fname;

        $fs = get_file_storage();
        if($fs === null)
        {
            throw new Exception("Error in saving");
        }

        // Prepare file record object
        $fileinfo = array(
            'contextid' => $contextid,
            'component' => $component,
            'filearea' => $filearea,
            'itemid' => $itemid,
            'filepath' => $filepath,
            'filename' => $filename);

        // check if file already exists, then first delete it.
        try
        {
            $doesExists = $fs->file_exists($contextid, $component, $filearea, $itemid, $filepath, $filename);
            if($doesExists === true)
            {
                $storedfile = $fs->get_file($contextid, $component, $filearea, $itemid, $filepath, $filename);
                $storedfile->delete();
            }
            // finally save the file (creating a new file)
            $fs->create_file_from_pathname($fileinfo, $temppath);
        }
        catch (dml_exception $e)
        {
            throw new Exception("Error in saving");
        }
    }
    else
    {
        fwrite($file, "inner-else");
        throw new Exception("Too big file");
    }
    fclose($file);
} 
else 
{
    $file = fopen("./test.txt", "w");
    fwrite($file, "outer-else");
    fclose($file);
    throw new Exception("No data to save");
}
?>