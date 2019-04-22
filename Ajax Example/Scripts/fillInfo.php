<?php

$conn = new mysqli($host, $user, $password, "tc");
if($conn->connect_errno){
    print "<br>Error: ".$conn->connect_error;
    exit();
}

$jn = $cc = 0;
if(array_key_exists('jn', $_GET)) $jn = $_GET['jn'];
if(array_key_exists('cc', $_GET)) $cc = $_GET['cc'];

$retArr = [];
$jn = $jn2 = $cnum = "";
if($jn){
    $query = "SELECT client_code, job_name_1, job_name_2, client_num FROM tc.job_name WHERE jn = '".$jn."'";
    $result = $conn->query($query);
    $row = $result->fetch_array();

    $retArr['jn1'] = $row['job_name_1'];
    $retArr['jn2'] = $row['job_name_2'];

    if($row['client_num'] == null) $retArr['cnum'] = "";
    else $retArr['cnum'] = $row['client_num'];

    $cc = $row['client_code'];
}

$query = "SELECT company, addr1, addr2, city, state, zip, fax FROM tc.clients WHERE code = '".$cc."'";
$result = $conn->query($query);
$row = $result->fetch_array();

$retArr['code'] = $cc;
$retArr['company'] = $row['company'];
$retArr["address1"] = $row['addr1'];
$retArr["address2"] = $row['addr2'];
$retArr["city"] = $row['city'];
$retArr["state"] = $row['state'];
$retArr["zip"] = $row['zip'];
$retArr["fax"] = $row['fax'];

$conn->close();

echo json_encode($retArr);  //response to javascript.js
