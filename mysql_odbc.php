<?PHP

/**
 * This script reads from tables using odbc and writes to a MySQL table using mysqli. This was used to 
 * transfer data from an older Paradox table into a MariaDB table. 
 *
 * Topics:
 *  odbc
 *      odbc_connect($dsn, $user, $password)    ->  $ODBC_connection
 *      odbc_exec($ODBC_connection, $query)     ->  $ODBC_resource
 *      odbc_fetch_row($ODBC_resource)          ->  (bool) True if there was a row, False otherwise
 *      odbc_num_fields($ODBC_resource)         ->  (int) number of fields
 *      odbc_result($ODBC_resource, $field)     ->  (<T>) extracted result from the row at $field 
 *  mysqli
 *      mysqli($host, $username, $password, $dbname)    ->  MySQL server connection
 *      mysqli->query($query)                   ->  resource or True on success, False on error
 */

$conn = new mysqli($host, $user, $password, 'tc');          
if($conn->errno){ echo "Error connecting to db: $conn->error"; exit;}       //connect to our mysql server and use 'tc' schema as db

$db = odbc_connect('Trans', '', '') or die("Cannot connect to TRANS\n".odbc_errormsg());    //odbc connect to the Trans paradox db table

/****************************** Transmittal ****************************/
$tQuery = "SELECT * FROM tc.trans ORDER BY Serialno DESC";
$tResult = $conn->query($tQuery);                                           //query for the last Serialno added to tc.trans table

$lastTrans = 0;
if($row = $tResult->fetch_array()){
    $lastTrans = intval($row['Serialno']);
}
else $lastTrans = 30000;            

echo "$lastTrans<br>";      //for debugging

$tOdbc = "SELECT * FROM Trans19 WHERE Serialno > $lastTrans";               //query for everything after the last serialno from tc.trans
$tResult = odbc_exec($db, $tOdbc);
$insert = "";                                                               //initialize our insert query to be used for mysql

while(odbc_fetch_row($tResult)){
    $insert = "INSERT INTO tc.trans VALUES(";                               //every time there is a row, we want to copy that row into tc.trans so we 
    for($i = 1; $i<=odbc_num_fields($tResult); $i++){                       //create an INSERT query
        if($i == 1 || $i == 85 || $i == 81 || $i == 77){
            if(odbc_result($tResult, $i)) $insert .= intval(odbc_result($tResult, $i)).", ";        //these fields are int, don't need to surround with quotes
            else $insert .= "null, ";
        }     
        else if(($i > 13 && $i <25) && $i != 16){                           //these are boolean
            if(odbc_result($tResult, $i)) $insert .= "1, ";
            else $insert .= "0, ";
        }
        else if(($i > 25 && $i < 57) && ($i%2 == 0)){                       //more ints
            if(odbc_result($tResult, $i)) $insert .= intval(odbc_result($tResult, $i)).", ";
            else $insert .= "null, ";
        }        
        else if(($i > 73 && $i < 92) && !($i == 77 || $i == 81 || $i == 85 || $i == 88 || $i == 90)){              
            if(odbc_result($tResult, $i)) $insert .= "1, ";                 //more boolean 
            else $insert .= "0, ";
        }
        else if($i > 57 && $i < 74){/* these fields are not included in the mariadb tables*/}
        else {$insert .= "'".preg_replace("/'/", '"', odbc_result($tResult, $i))."', ";}        //escape the single quote for the query 

    }
    $insert = substr($insert, 0, -2).")";               //remove comma and space before closing paren
    echo $insert."<br>";        

    if($conn->query($insert)) echo "Entry ".odbc_result($tResult, 1)." successful<br>";             //make insert query for each row we grab
    else{echo "Entry at ".odbc_result($tResult, 1)." failed to Insert: $conn->error"; exit;}        //if it fails, we exit and try again
}

/**************************** fax table ***************************************/

$lastFax = 18000;   //we want all records from 18000 and up

$fOdbc = "SELECT * FROM Faxtr WHERE Serialno >= $lastFax";
$fResult = odbc_exec($db, $fOdbc);

while(odbc_fetch_row($fResult)) {
   $insert = "INSERT INTO tc.faxtr VALUES(";
   for ($i = 1; $i <= odbc_num_fields($fResult); $i++) {
       if ($i == 1 || $i == 10) $insert .= odbc_result($fResult, $i) . ", ";        //these fields are ints, don't wrap with quotes
       else if ($i == 11) {
           if(odbc_result($fResult, 11)) $insert .= "1, ";                          //this field is either 1 or null
           else $insert .= "null, ";
       }
       else $insert .= "'" . preg_replace("/'/", '"', odbc_result($fResult, $i)) . "', ";   //escape the single quote for the query
   }
   $insert = substr($insert, 0, -2) . ")";                                          //remove comma and space before closing paren

   if ($conn->query($insert)) echo "Entry " . odbc_result($fResult, 1) . " successful<br>";
   else {echo "Entry at " . odbc_result($fResult, 1) . " failed to Insert: $conn->error";exit;}
}