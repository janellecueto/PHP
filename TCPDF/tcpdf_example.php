<?php
/**
 * This script uses the tcpdf library to generate a pdf on the server and send to 
 * server printers to print. This is also a protocol for saving this information 
 * in a table in our database. If a 'save' variable is passed via GET, we save the 
 * pdf on the server and exit, otherwise continue and sent the pdf to a printer.
 * 
 * Topics:
 *  tcpdf
 *      basic pdf set up 
 *      tcpdf->Text($x, $y, $text)                      : $x - horizontal point, from left margin as 0
 *      tcpdf->SetFont($font, $decoration, $size)       : $y - vertical point, from top margin as 0
 *      tcpdf->SetTextColor($R, $G, $B)
 *      tcpdf->SetLineWidth($width)
 *      tcpdf->Line($startX, $startY, $endX, $endY)
 *      tcpdf->SetLineStyle($width, $cap, $join, $dash)
 *      tcpdf->GetStringWidth($string) - returns length of string in points (page units)
 *      tcpdf->Rect($x, $y, $width, $height, $style, $borderstyle, $fillcolor)     : $height is going down from $y starting point 
 *      tcpdf->MultiCell($width, $height, $text, $border, $align, $fill, $ln=1, $x, $y, $resetH=true)
 *      tcpdf->AddPage($orientation, $size)
 */

require_once('../tcpdf6/tcpdf.php');                //include tcpdf library
require_once('../tcpdf6/examples/lang/eng.php');

// header('Content-type: application/pdf');            //uncomment when testing in browser
// header('Content-disposition: inline');

$isJanelle = false;
if(strpos('.0.22', $station)){
    $isJanelle = true;
}

$conn = new mysqli($host, $user, $password, 'tc');
if($conn->errno){echo "Error: $conn->error"; exit;}

$p = 0;
if(array_key_exists('p', $_GET)) $p = json_decode($_GET['p'], true);
$saveOnly = false;
if(array_key_exists('save', $_GET)) $saveOnly = $_GET['save'];


/******** TEST VALUES ****************************************************************/
//$projectName = "Project Name";
//$projectNumber = '18106.02';
//$buildingType = 3;
//$constructionType = [20,80];
//$squareFootage = "40,000";
//$numberFloors = "5";
//$projectCost = 30000000;
//$constructionCost = 2000000;
//$addedServices = [["numMeetings", 50], ["mtgHrs", 6],["emp", "sb"],["calcEmpRate", 64500],["voiceData",5000],["security",2000],["DAS",1000],["Something",1200]];
//$standardFees = [["CDB","9.39%","11.39%"],["ADVOCATE","8.9%","10.9%"],["HDR","11.68%","13.18%"],["AVERAGE","9.99%","11.82%"]];
//$electricalPercentage = 15;
//$electricalCost = 300000;
//$aeSplit = 80;
//$estimatedFees = [["CDB", "$14,909"],["Advocate","$15,818"],["HDR","$16,098"],["Average","$15,611"]];
//$notes = "nothing";
/******** TEST VALUES ****************************************************************/

$projectName = $p['projectName'];
$projectNumber = $p['projectNumber'];
$buildingType = $p['buildingType'];
$constructionType = $p['constructionType'];
$squareFootage = $p['squareFootage'];
$numberFloors = $p['numberFloors'];
$projectCost = $p['projectCost'];
$constructionCost = $p['constructionCost'];
$addedServices = $p['addedServices'];
$reimbursables = $p['reimbursables'];
$standardFees = $p['standardFees'];
$electricalPercentage = $p['electricalPercentage'];
$electricalCost = $p['electricalCost'];
$aeSplit = $p['aeSplit'];
$estimatedFees = $p['estimatedFees'];
$notes = $p['notes'];


$serviceDict = array(
    "voiceData" => "Voice/Data",
    "security" => "Security/Access Control",
    "sound" => "Sound System",
    "nurse" => "Nurse Call",
    "soundMask" => "Sound Masking",
    "leed" => "LEED/HSRM",
    "audio" => "Audio/Visual",
    "DAS" => "DAS System",
    "area" => "Area of Rescue",
    "commissioning" => "Commissioning",
    "reports" => "Narratives/Reports"
);
$ignoreService = ["mtgHrs", "numMeetings", "emp", "calcEmpRate"];

//compute added total of services before everything because it's used in a few places :/
$addedTotal = 0;
foreach($addedServices as $as){
    if(!in_array($as[0], $ignoreService))
        $addedTotal += $as[1];
    if($as[0] == 'calcEmpRate')
        $addedTotal += $as[1];
}

$id = 0;
$idservices = 0;

$q = "SELECT * FROM tc.feeform WHERE projectNumber = '$projectNumber'";
$r = $conn->query($q);
if($row = $r->fetch_assoc()){
    $id = $row['idfeeform'];
}

if($id){        //if fee calc for current job exists, delete current db entry and replace it (instead of using an update query)
    //update
    $deleteQ = "DELETE FROM tc.feeform WHERE idfeeform = $id";
    if(!$conn->query($deleteQ)){ echo "Error deleting previous calculation entry: $conn->error"; exit;}
    $deleteServices = "DELETE FROM tc.services WHERE idfeeform = $id";
    if(!$conn->query($deleteServices)){echo "Error deleting services: $conn->error"; exit;}

}
else{
    //find new id
    $q = "SELECT idfeeform FROM tc.feeform ORDER BY idfeeform DESC";
    $r = $conn->query($q);
    if($row = $r->fetch_row()){
        $id = $row[0];
    }
    $id++;      //increment $id to get next
}

//find next services id 
$q = "SELECT idservices FROM tc.services ORDER BY idservices DESC";
$r = $conn->query($q);
if($row = $r->fetch_row()){
    $idservices = $row[0];
}
$idservices++;      

//insert into the tc.feeform table which holds all basic info 
$qInsert = "INSERT INTO tc.feeform VALUES(";
$qInsert .= "$id, '$projectNumber',";
$qInsert .= "'".str_replace("'", '"', $projectName)."', ";
$qInsert .= "$buildingType, $constructionType[0], $constructionType[1], '$squareFootage', '$numberFloors', ";

if(!$projectCost) $qInsert .= "null, ";
else $qInsert .= "$projectCost, ";

$qInsert .= "$constructionCost, $addedTotal, $electricalPercentage, $electricalCost, $aeSplit,";

if(!$reimbursables) $qInsert .= "null)";
else $qInsert .= "$reimbursables)";

if(!$conn->query($qInsert)){echo "Error inserting into feeform: $conn->error"; exit;}

//insert services with $id from tc.feeform
foreach($addedServices as $as){
    $qService = "INSERT INTO tc.services VALUES($idservices, ";
    $qService .= "'".str_replace("'", '"', $as[0])."', ";
    if(is_int($as[1])) $qService .= "$as[1], ";
    else $qService .= "'$as[1]', "; 

    $qService .= "$id)";
    if(!$conn->query($qService)) {echo "Error inserting into services: $conn->error"; exit;}
    $idservices++;
}

echo "Form data saved in db \n";        //if we get to this point with now error, let the user know that info has been savedd
$conn->close();
/**************************** end table insert ****************************/


/**************************** start pdf creation ****************************/
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Dickerson Engineering, Inc.');
$pdf->SetTitle('DEI Fee Calculation Form');
$pdf->SetSubject('Dickerson Engineering Fee Calculation Form');
$pdf->SetKeywords('Dickerson, Engineering, fee, calculator, calculation, form');

// remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set page orientation
$pdf->SetPageOrientation('P');

//set margins
$pdf->SetMargins(0.25,0.25,0.25);

//set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 0.25);

//set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

//set some language-dependent strings
$pdf->setLanguageArray($l);

$pdf->SetFont('helvetica', 'B', 16); // set font

$pdf->AddPage("P","LETTER"); // add a page

$pdf->Text(65, 10, "FEE CALCULATION SHEET");

//draw header box
$pdf->SetLineWidth(0.25); // set line width to 0.25mm
$pdf->Line(15,21,196,21);
$pdf->Line(15,28,196,28);
$pdf->Line(15,35,196,35);
$pdf->Line(15,21,15,35);
$pdf->Line(196,21,196,35);
$pdf->Line(60,21,60,35);
$pdf->Line(20, 65, 190,65);
$pdf->SetFont('helvetica', 'b', 11);
$pdf->Text(16, 22, "Project Name");
$pdf->Text(16, 29, "Project Number");
$pdf->SetFont('helvetica', 'b', 10);
$pdf->Text(18, 38, "Building Type:");

$pdf->Text(18, 47, "Construction Type:");

$pdf->Text(18, 56, "Square footage:");
$pdf->Text(100, 56, "# of floors:");
$pdf->Text(18, 70, "TOTAL PROJECT COST/BUDGET:");
$pdf->Text(18, 77, "TOTAL CONSTRUCTION COST/BUDGET:");
$pdf->Text(18, 84, "Standard Fee based on the following matrices:");
$pdf->Text(18, 125, "Estimated Electrical Percentage of Construction:");
$pdf->Text(18, 135, "Estimated Electrical Cost of Construction:");
$pdf->Text(18, 145, "Architect/Engineering Split:");
$pdf->Text(18, 155, "Added Services:");
$pdf->Text(18, 209, "Reimbursables:");
$pdf->Text(35, 202, "TOTAL:");


$pdf->SetFont('helvetica', '', 10);
$pdf->Text(95, 38, "GROUP I");
$pdf->Text(120, 38, "GROUP II");
$pdf->Text(145, 38, "GROUP III");

$pdf->Text(92, 47, "NEW CONSTRUCTION");
$pdf->Text(145, 47, "RENOVATION");
$pdf->Text(67, 91, "NEW CONSTRUCTION");
$pdf->Text(120, 91, "RENOVATION");

$pdf->Text(35, 97, "CDB");
$pdf->Text(35, 103, "ADVOCATE");
$pdf->Text(35, 109, "HDR");
$pdf->Text(35, 115, "AVERAGE");


$pdf->SetFont('helvetica', 'b', 12);
$pdf->Text(67, 216, "ESTIMATED ENGINEERING FEE:");

$pdf->SetFont('helvetica', '', 10);
//$pdf->SetTextColor(0,0,204);

$pdf->Text(65,22,$projectName);
$pdf->Text(65, 29, $projectNumber);

$pdf->SetLineWidth(0.25);
switch($buildingType){
    case 1:
        $pdf->Line(94,43,114,43);
        break;
    case 2:
        $pdf->Line(119, 43, 139, 43);
        break;
    case 3:
        $pdf->Line(144, 43, 165, 43);
}

$pdf->Text(82, 47, $constructionType[0]."%");
$pdf->Text(135, 47, $constructionType[1]."%");

$pdf->Text(55, 56, number_format($squareFootage, 0 , '.', ','));
$pdf->Text(125, 56, $numberFloors);
if(!$projectCost) $projectCost = "N/A";
else $projectCost = "$".number_format($projectCost, 0, '.', ',');
$pdf->Text(165, 70, $projectCost);
$constructionCost = "$".number_format($constructionCost, 0, '.', ',');
$pdf->Text(165, 77, $constructionCost);
$pdf->Text(165, 125, $electricalPercentage."%");
$electricalCost = "$".number_format($electricalCost, 0, '.', ',');
$pdf->Text(165, 135, $electricalCost);
$split = "$aeSplit% / ".(100-$aeSplit)."%";

$pdf->Text(165,145,$split);


$y = 97;
foreach($standardFees as $sf){
    if($constructionType[0] == 100) $pdf->Text(82, $y, $sf[1]);
    else if($constructionType[1] == 100) $pdf->Text(126, $y, $sf[1]);
    else {
        $pdf->Text(82, $y, $sf[1]);
        $pdf->Text(126, $y, $sf[2]);
    }
    $y += 6;
}

$lStyle1 = array('width'=>0.25, 'cap'=>'butt', 'join'=>'miter', 'dash'=>'2,8');     //draws dotted line 
$y = 162;
foreach ($addedServices as $as){
    if(!in_array($as[0], $ignoreService)) {
        $service = $as[0];
        if (array_key_exists($as[0], $serviceDict)) $service = $serviceDict[$as[0]];
        $pdf->Text(35, $y, $service);
        $s1 = $pdf->GetStringWidth($service);
        $asCost = "$" . number_format($as[1], 0, '.', ',');
        $pdf->Text(150, $y, $asCost);
        $pdf->Line(38+$s1, $y+3, 150, $y+3, $lStyle1);
        //we need to add the break down:
        $y += 6;
    }
    else{
        if($as[0] == "calcEmpRate"){
            //NOTE: in the tc.services table, the order is always numMeetings, mtgHrs, emp, calcEmpRate 
            $pdf->Text(35, $y, "Meetings Beyond Standard Rate");
            $s1 = $pdf->GetStringWidth("Meetings Beyond Standard Rate");
            $asCost = "$" . number_format($as[1], 0, '.', ',');
            $pdf->Text(150, $y, $asCost);
            $pdf->Line(38+$s1, $y+3, 150, $y+3, $lStyle1);
            $y+=6;
            $pdf->Text(42, $y, "Number of Meetings Beyond Standard");
            $pdf->Text(125, $y, $addedServices[0][1]);              //this is kind of cheating, i really should be parsing for "numMeetings"
            $s1 = $pdf->GetStringWidth("Number of Meetings Beyond Standard");
//            $pdf->Line(45+$s1, $y+3, 125, $y+3, $lStyle1);
            $y+=6;                                                  //and "mtgHrs" in the $addedServices array and grabbing values that way :/
            $pdf->Text(42, $y, "Hours per Meeting");
            $pdf->Text(125, $y, $addedServices[1][1]);
            $s1 = $pdf->GetStringWidth("Hours per Meeting");
//            $pdf->Line(45+$s1, $y+3, 125, $y+3, $lStyle1);
            $y+=6;
        }
    }
}

$pdf->SetLineStyle(array('width' => 0.25, 'dash'=>0));  //set line style back


$addedTotal = "$".number_format($addedTotal, 0, ".", ",");
$reimbursables = "$".number_format($reimbursables, 0, ".", ",");

$pdf->Text(165, 202, $addedTotal);
$pdf->Text(165, 209, $reimbursables);

if(in_array(100, $constructionType)) {
    $formula = "($constructionCost x $electricalPercentage% x $aeSplit% x Standard Fee) + $addedTotal =";
    $pdf->Text(46,222, $formula);
}
else{
    $formula = "(($constructionCost x $constructionType[0]% x New Construction Fee) + ($constructionCost x $constructionType[1]% x Renovation Fee))";
    $formula .= " x 0.$electricalPercentage x 0.$aeSplit + $addedTotal =";
    $pdf->Text(16,222, $formula);
}

$pdf->SetFont('helvetica', 'b', 10);

$y = 230;
foreach($estimatedFees as $ef){
    $pdf->Text(68, $y, $ef[0]);
    $pdf->Text(120, $y, $ef[1]);
    $y += 6;
}

$pdf->Rect(13,257,187, 8, 'F', array(), array(30,144,255));
$pdf->Line(70, 257, 70, 265);
$pdf->Line(140, 257, 140, 265);


$pdf->SetFont('helvetica', 'b', 10);
$pdf->SetTextColor(255, 255, 255);

$pdf->Text(15, 259, 'AVG Fee:');
if($constructionType[0] == 100) $pdf->Text(45,259, $standardFees[3][1]);
else if($constructionType[1] == 100) $pdf->Text(45,259, $standardFees[3][2]);
else {
    $pdf->Text(37,259, $standardFees[3][1]." / ".$standardFees[3][2]);
}

$pdf->Text(72, 259, 'Added Services:');
$pdf->Text(115, 259, $addedTotal);

$pdf->Text(142, 259, 'Reimbursables');
$pdf->Text(180, 259, $reimbursables);

//Add new page for the notes section
$pdf->AddPage('P', "LETTER");
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 12);
$pdf->MultiCell(186,55, "Notes: \n$notes", 1, '', 0, 1, 15, 15, true);

$pdf->Output('/fee.pdf', 'F');              //Since I am sending this pdf directly to a printer when 
                                            //the user clicks 'print', I output the pdf to File. This 
                                            //means that the user won't see the pdf before it is printed.
                                            //This was specifically requested for my workplace because 
                                            //they were tired of having to click "print" twice :/ (once in 
                                            //the web form, once in Acrobat/browser print dialogue)

//$pdf->Output('/fee.pdf', 'I');            //if you want to view the pdf on your browser, use this

if (intval(substr($projectNumber, 0, 2))) {
    $year = "20" . substr($projectNumber, 0, 2);
}//THIS WILL FAIL IN 2100 (JC 12.11.2018)
else
    $year = date('Y');

if (strpos($projectNumber, "P") !== false) { //if this ain't a P number, it might have a subfolder for contracts
    $baseJN = substr($projectNumber, 0, strpos($projectNumber, '.'));
    $savePath .= "$baseJN\\";
}
//all P numbers and other names go into the contract/<year> base folder

//save created pdf in S:/OFFICE.
//if number is over 40, save in base, otherwise find sub folder
$numJN = intval(substr($projectNumber, 2, 4));       //assuming that format is YYxxx.xx, does not account for p numbers
$savePath = "S:\\OFFICE\\contract\\$year\\";


$savePath .= "$projectNumber-eFee-calc-" . date('ymdhis') . ".pdf";

if (copy("/fee.pdf", $savePath)) echo "File saved in $savePath";
else echo "FAILED to save PDF in S://OFFICE//contract";

//stop here if we're only saving. Otherwise continue to create pdf
if ($saveOnly) {
    exit;
}

if ($isJanelle) copy("/fee.pdf", $adminPrinter);
else copy("/fee.pdf", $defaultPrinter);

echo "Form sent to printer!\n";