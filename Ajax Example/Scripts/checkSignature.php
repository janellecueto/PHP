<?php

$conn = new mysqli($host, $user, $password, "tc");
if($conn->connect_errno){
    print "<br>Error: ".$conn->connect_error;
    exit();
}

$q = $_GET['q']; //initials
$p = false;
if(array_key_exists('p', $_GET)) $p = $_GET['p']; //client code

if($p){
    $query = "SELECT name FROM tc.clnames WHERE code = '".$p."' AND initials = '".$q."'";
}
else{
    $query = "SELECT name FROM tc.clnames WHERE code = 'DEI' AND initials = '".$q."'";
}

$result = $conn->query($query);
$row = $result->fetch_array();

echo $row['name']; //echo back to javascript.js

$result->free();
mysqli_close($conn);