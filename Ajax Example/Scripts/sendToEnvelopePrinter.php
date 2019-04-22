<?php

/**
 * This script is kind of cool because it writes PCL directly to the printer on the server to 
 * bust out an envelope. I had to use a giant book on PCL to figure this out.
 * 
 * NOTE: this is similar to PHP/fwrite.php so it uses the same topics
 */

$q = $_GET['q']; //supposedly an array from printLbl

$qarr = json_decode($q, true);

$printerFile = $host."\ReceptionPrinter"; 
$handle2 = fopen($printerFile, 'w') or die('Cannot open file: '.$printerFile);

$esc = chr(27); //escape key ascii

/*
 * Below code only inserts new lines after each addr element
 * */
$len = sizeof($qarr);
/*
 *  PCL escape code commands for envelope printing
 * */
fwrite($handle2, $esc);
fwrite($handle2, "E"); //printer reset cmd
fwrite($handle2, $esc);
fwrite($handle2, "%1A");
fwrite($handle2, $esc);
fwrite($handle2, "&l0s6h81a1o1X"); //sets primary font 

fwrite($handle2, $esc);
fwrite($handle2, "&a8r40C"); //cursor position
fwrite($handle2, $esc);
fwrite($handle2, "&a40l");  //set left margin

fwrite($handle2, $esc);
fwrite($handle2, "(8U");
fwrite($handle2, $esc);
fwrite($handle2, "(s1p12v0s0b4148T"); //style code for addr

//insert address
for($i = 0; $i<$len; $i++){
    if ($i >= $len - 3){
        //format the last 3 elements differently
        fwrite($handle2, $qarr[$i]);
        fwrite($handle2, ", ");
        break;
    }
    //adds newline char after each element of array
    fwrite($handle2, (string)$qarr[$i]);
    fwrite($handle2, "\r\n");
}
//after break; in for loop above:
fwrite($handle2, $qarr[$len - 2]);
fwrite($handle2, "  ");
fwrite($handle2, $qarr[$len - 1]);
//end address

fwrite($handle2, $esc);
fwrite($handle2, "(10U");
fwrite($handle2, $esc);
fwrite($handle2, "(s0p10h12v0s0b3T");
fwrite($handle2, $esc);
fwrite($handle2, "&a23r50C");
fwrite($handle2, $esc);
fwrite($handle2, "(15Y");
fwrite($handle2, $esc);
fwrite($handle2, "(s1p12v0s0b0T");
fwrite($handle2, "*");
fwrite($handle2, $qarr[$len-1]);
fwrite($handle2, "*");
fwrite($handle2, $esc);
fwrite($handle2, $esc);     //not sure what all of this does
fwrite($handle2, "E"); //reset printer

fclose($handle2);