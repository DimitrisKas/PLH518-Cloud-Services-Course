<?php

// Save msg to log
function logger($msg)
{
    $msg = date("[d/m] h:i:sa") . ": ". $msg . "\n";
    file_put_contents( $_SERVER['DOCUMENT_ROOT'].'/Utils/Logs/log.txt', $msg, FILE_APPEND);
}

// Get Last 10000 characters from logs
function getLogs()
{
    return file_get_contents( $_SERVER['DOCUMENT_ROOT'].'/Utils/Logs/log.txt', false, null, -10000);

}