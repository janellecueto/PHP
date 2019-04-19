<?php

/*
 * This is an example using fopen and fwrite to write a .lbl file line by 
 * line. The .lbl file that is generated gets parsed by another script and 
 * writes to our label printer.
 * 
 * It is NOT a standalone script as it requires a variable 'q' to be passed
 * via HTTP method GET.
 * 
 */ 


$q = $_GET['q']; //array passed from printLbl.php

$qarr = json_decode($q, true);

$printerFile = "Q:\\QPRIV\\Label\\d.lbl";
$handle2 = fopen($printerFile, 'w') or die('Cannot open file: '.$printerFile);

$len = sizeof($qarr);

/*
 *  must match format:
 *      label type
 *      job number
 *      attention
 *      company
 *      address line 1
 *      address line 2
 *      city
 *      stat
 *      zip
 *      "ParadoxLabelGenerator"
 * */

fwrite($handle2, "1"); //label type for address labels
fwrite($handle2, "\n");
fwrite($handle2, $qarr[0]); //job number
fwrite($handle2, "\n");
fwrite($handle2, $qarr[1]); //Attn
fwrite($handle2, "\n");
fwrite($handle2, $qarr[2]); //company
fwrite($handle2, "\n");
fwrite($handle2, $qarr[3]); //addr 1
fwrite($handle2, "\n");
if($len == 7){
    fwrite($handle2, ""); // move city state zip up one line if there is no address line 2
}
else{
    fwrite($handle2, $qarr[4]); //addr2
}
fwrite($handle2, "\n");
fwrite($handle2, $qarr[$len-3]); //city
fwrite($handle2, "\n");
fwrite($handle2, $qarr[$len-2]); // state
fwrite($handle2, "\n");
fwrite($handle2, $qarr[$len-1]); //zip
fwrite($handle2, "\n");

fwrite($handle2, "ParadoxLabelGenerator");

fclose($handle2);
//delete a.lbl after prints

$semfile = "Q:\\QPRIV\\Label\\d.sem"; //creating a .sem will trigger label file to execute then deletes after use
$handle3 = fopen($semfile, 'w') or die("Error creating sem file: ".$semfile);

fwrite($handle3, "Attempting to print Label");      //we can put anything in the .sem file
fclose($handle3);