<?php
function OpenCon($connect_to_db)
{
    $servername = "mysql";
    $username = "root";
    $password = "1234securePass";
    $dbname = "Project1";
    
    if ($connect_to_db)
    {
        $conn = new mysqli($servername, $username, $password, $dbname);
    } else {
        $conn = new mysqli($servername, $username, $password);
    }
    
    if ($conn->connect_error) {
        logger("Connection failed: " . $conn->connect_error);
    }
    return $conn;
 }
 
function CloseCon($conn)
{
    $conn -> close();
}

