<?php

// Save msg to log
function logger($msg)
{

    $filename = $_SERVER['DOCUMENT_ROOT'].'/Utils/Logs/log.txt';
    $msg = date("[d/m] h:i:sa") . ": ". $msg . "\n";

    file_put_contents( $filename, $msg, FILE_APPEND);
}

// Get Last 10000 characters from logs
function getLogs(): string
{
    $filename = $_SERVER['DOCUMENT_ROOT'].'/Utils/Logs/log.txt';
    $filesize = filesize($filename);

    if ($filesize > 100000)
    {
        // Trim down to 10K bytes if over 100K Bytes
        $data = file_get_contents( $filename, false, null, -10000);
        file_put_contents($filename, $data);
    }

    // Get last 10K characters(bytes) or as many as possible
    if ($filesize < 10000)
        return file_get_contents( $filename, false, null, -$filesize);
    else
        return file_get_contents( $filename, false, null, -10000);

}