<?php
/**
 * This script generates a pdf of a table in landscape mode. The top of the first page includes the table's title. At
 * the top of each page afterwards is the table header (like a fixed <thead>). Instead of passingeverything I want to 
 * print from the page calling this script, I reread everything from the database and dynamically create my table,
 * adding lines and new pages where I need to.
 * 
 * In this example, I display the pdf in its own tab ($pdf->Output('/tablePrintout.pdf', 'I') for inline). 
 */

//This script will allow users to print costs from the Urgent Needs tab

//NOTES:
//  - table lines are dynamically added to the document per line item (inside the addCostSummary function)
//  - 'total', 'subtotal', and 'fee' fields in the hospital.costs db table are inserted as strings and must be converted
//    to the correct format: '$XXX,XXX,XXX' for any cost rows

require_once('../../tcpdf6/tcpdf.php');
require_once('../../tcpdf6/examples/lang/eng.php');

header('Content-type: application/pdf');

$hospital = $_GET['hospital'];
$table = $_GET['table'];

$conn = new mysqli($host, $user, $password, 'hospital');
if($conn->errno){echo "Error connecting to db: $conn->error"; exit;}

$hospitalName = "";
$tableName = "";
$isObs = false;

$qName = "SELECT `name` FROM hospital.details WHERE hospital = '$hospital'";
$rName = $conn->query($qName);
if($r = $rName->fetch_row()) $hospitalName = $r[0];

switch($table){
    case 'distribution':
        $tableName = "Distribution Panels";
        break;
    case 'transfer':
        $tableName = "Transfer Switches";
        break;
    case 'observations':
        $tableName = "General Observations";
        $isObs = true;
        break;
    default:
        $tableName = ucwords($table);
}



/***************************************** pdf functions ****************************************************/
//this function adds the blue header cols to the top of the page and is called whenever a new page is added
function addHeader($y){
    global $pdf, $isObs;
    $pdf->SetFont('helvetica', 'b',11);
    $pdf->SetTextColor(255,255,255);                                //set text color to white (it is on top of blue)

    $pdf->Rect(7,$y,263,7, 'F', array(), array(30, 144, 255));      //draw the blue header box
    $pdf->SetLineWidth(0.4);                                        //draw top, left, right border lines
    $pdf->Line(7,$y,270,$y);
    $pdf->Line(7, $y, 7, $y+7);
    $pdf->Line(270, $y, 270, $y+7);

    $pdf->SetLineWidth(0.25);                                       //bottom border line   
    $pdf->Line(7, $y+7, 270, $y+7);

    if($isObs){
        //general observations have slightly different column headers
        $pdf->Text(8, $y+1, "ISSUE/VIOLATION");
        $pdf->Line(81, $y, 81, $y+7);
        $pdf->Text(83, $y+1, "RECOMMENDED ACTION");
        $pdf->Line(153, $y, 153, $y+7);
        $pdf->Text(155, $y+1, "ESTIMATED CAPITAL EXPENDITURES");
    }
    else{
        $pdf->Text(8, $y+1, "NAME");
        $pdf->Line(48, $y, 48, $y+7);
        $pdf->Text(50, $y+1, "DESCRIPTION");
        $pdf->Line(153, $y, 153, $y+7);
        $pdf->Text(155, $y+1, "ESTIMATED CAPITAL EXPENDITURES");
    }
    $pdf->SetTextColor(0,0,0);                                      //reset text color
}

//this function adds the name/issue and description/action to the table. calls addCostSummary function to add Estimated Capital Expenditures
function addItemRow($row){
    global $pdf, $conn, $globalY, $isObs;
    if($globalY > 190){                                             //add new page if we are getting close to the bottom
        $pdf->AddPage("L", "LETTER");                       
        addHeader(8);                                               //add header at top of new page
        $pdf->SetFont('helvetica', '', 9);                          //reset our y point
        $globalY = 15;
    }

    $y = $globalY;
    $hasCost = false;
    $costRow = []; 

//    query into costs table to get total and costs row
    if($row['idcosts']) {                                           //grab cost information if exists
        $costQ = "SELECT * FROM hospital.costs WHERE idcosts = " . $row['idcosts'];
        $costR = $conn->query($costQ);
        $costRow = $costR->fetch_array();
        $hasCost = true;
    }                                    

    if($isObs){                                                     //again, observations table has slightly different layout
        $pdf->MultiCell(72, 14, $row['issue_violation'], 0, '', 0, 1, 8, $y+1, 1, 0, 0, 1, 4, 'm', 1 );
        $pdf->MultiCell(72, 14, $row['recommended_action'], 0, '', 0, 1, 81, $y+1, 1, 0, 0, 1, 4, 'm', 1 );
    }
    else{
        $pdf->MultiCell(42, 14, $row['name'], 0, '', 0, 1, 8, $y+1, 1, 0, 0, 1, 4, 'm', 1 );
        $pdf->MultiCell(100, 24, $row['comments'], 0, '', 0, 1, 50, $y+1, 1, 0, 0, 1, 4, 'm', 1 );
    }
    $pdf->SetFont('helvetica', '', 9);
    if($hasCost) addCostSummary($costRow);                      //insert cost summary breakdown
    else{
        //just add lines if cost does not exist
        $pdf->SetLineWidth(0.4);
        $pdf->Line(7,$y,7,$y+15);
        $pdf->Line(270, $y, 270, $y+15);
        $pdf->SetLineWidth(0.25);

        if($isObs) $pdf->Line(81,$y,81, $y+15);
        else $pdf->Line(48,$y,48,$y+15);

        $pdf->Line(153,$y,153,$y+15);
        $pdf->Line(7,$y+15,270,$y+15);
        $globalY += 15;
    }

}

//this function adds the activity cost items and the subtotal, total, and fee to the Estimated Capital Expenditures sub-table.
//This function is also responsible for adding the table lines
function addCostSummary($costRow){      //instead of passing isObs, pass a starting x position from addItemRow function
    global $pdf, $conn, $globalY, $isObs, $runningTotal;

    $y = $globalY;

    if($isObs) $xCol = 81;
    else $xCol = 48;

    $pdf->SetFont('helvetica', 'b', 9);
    $pdf->Text(155, $y+1, "ACTIVITY");              //add sub-table headers
    $pdf->Text(246, $y+1, "GUESTIMATE");
    $pdf->SetFont('helvetica', '', 9);

    $pdf->SetLineWidth(0.4);
    $pdf->Line(7, $y, 7,$y+6);
    $pdf->Line(270, $y, 270, $y+6);
    $pdf->SetLineWidth(0.25);
    $pdf->Line($xCol, $y, $xCol, $y+6);
    $pdf->Line(153, $y, 153, $y+6);

    $activityQ = "SELECT * FROM hospital.activities WHERE idcosts = ".$costRow['idcosts'];
    $activityR = $conn->query($activityQ);

    $y += 6;
    while($aRow = $activityR->fetch_array()){       //add each activity line item
        if($y>200){
            $pdf->Line(7, $y, 270, $y);             //if we get towards the bottom, add a new page and header
            $pdf->AddPage("L", "LETTER");           //and reset local $y variable (we'll reset global once we're
            addHeader(8);                           //done adding the cost summary)
            $pdf->SetFont('helvetica', '', 9);
            $y = 15;
        }
//        $pdf->Text(155, $y, $aRow['activity']);
        $pdf->MultiCell(90, 6, $aRow['activity'], 0, 'L', 0, 1, 155, $y, 1, 0, 0, 1, 4, 'm', 1 );
//        $pdf->Text($x+85, $y, $aRow['cost']);
        $pdf->MultiCell(23, 6, "$".number_format($aRow['cost'], "0", ".", ","), 0, 'R', 0, 1, 244, $y, 1, 0, 0, 1, 4, 'm', 1 );

        $pdf->SetLineWidth(0.4);
        $pdf->Line(7, $y, 7,$y+6);                  //draw table lines 
        $pdf->Line(270, $y, 270, $y+6);
        $pdf->SetLineWidth(0.25);
        $pdf->Line($xCol, $y, $xCol, $y+6);
        $pdf->Line(153, $y, 153, $y+6);

        $y+=6;
    }

    if($y > 190){                                   //after adding the activities, we have to add 3 more rows.
        $pdf->Line(7, $y, 270, $y);                 //add a page if we're close to the bottom
        $pdf->AddPage("L", "LETTER");
        addHeader(8);
        $y = 15;
    }

    $pdf->SetFont('helvetica', 'b', 9);
    $pdf->Text(230, $y, "Sub-Total:");
    $subTotal = preg_replace('/[^0-9]/', '', $costRow['subtotal']);           //NOTE: the 'total' field in costs was inserted as a string in the format '$0XXXXX'
    $pdf->MultiCell(23, 6, "$".number_format($subTotal, "0", ".", ","), 0, 'R', 0, 1, 244, $y, 1, 0, 0, 1, 4, 'm', 1 );
    $y+=6;

    $pdf->Text(178, $y, "Engineering Design Fee(10% of project cost):");
    $fee = preg_replace('/[^0-9]/', '', $costRow['fee']);
    if($fee) $fee = "$".number_format($fee, "0", ".", ",");
    else $fee = "";
    $pdf->MultiCell(23, 6, $fee, 0, 'R', 0, 1, 244, $y, 1, 0, 0, 1, 4, 'm', 1 );
    $y+=6;

    $pdf->SetFont('helvetica', 'b', 10);
    $pdf->Text(233, $y, "TOTAL:");
    $total = preg_replace('/[^0-9]/', '', $costRow['total']);
    $runningTotal += (int)$total;
    $pdf->MultiCell(23, 6, "$".number_format($total, "0", ".", ","), 0, 'R', 0, 1, 244, $y, 1, 0, 0, 1, 4, 'm', 1 );
    $y+=6;
    $pdf->SetLineWidth(0.4);
    $pdf->Line(7, $y-18, 7,$y);
    $pdf->Line(270, $y-18, 270, $y);
    $pdf->SetLineWidth(0.25);
    $pdf->Line($xCol, $y-18, $xCol, $y);
    $pdf->Line(153, $y-18, 153, $y);


    $pdf->SetFont('helvetica', '', 9);
    $globalY =$y;                                   //reset our global y variable
    $pdf->Line(7, $globalY, 270, $globalY);         //draw bottom line for the page row 
}



/******************************************* start pdf ******************************************************/

//create pdf
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Dickerson Engineering, Inc.');
$pdf->SetTitle('DEI Portal Printouts');
$pdf->SetSubject('Dickerson Engineering Hospital Urgent Needs Report');
$pdf->SetKeywords('Dickerson, Engineering, inspection, report, hospital portal, DEI, record, table, urgent needs, cost summary');

// remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set page orientation
$pdf->SetPageOrientation('L');

//set margins
$pdf->SetMargins(0.25,0.25,0.25);

//set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 0.25);

//set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

//set some language-dependent strings
$pdf->setLanguageArray($l);

$pdf->SetFont('helvetica', '', 14); // set font

$pdf->AddPage("L","LETTER"); // add a page

//header of first page only
$pdf->Text(7, 7, $hospitalName." | ".$tableName);
$pdf->Text(228, 7, "Urgent Need Lines");

addHeader(15);
$globalY = 22;
$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(0,0,0);

$runningTotal = 0;      //this keeps track of the running total of all of the costs

if($isObs){
    $query = "SELECT * FROM hospital.observations WHERE hospital = '$hospital'";
}
else {
    if ($hospital === 'NSE') {          //this hospital specifically needs more conditions
        $query = "SELECT * FROM hospital.$table WHERE hospital = '$hospital' AND `condition` > 4 OR `condition` = 0";
    } else {
        $query = "SELECT * FROM hospital.$table WHERE hospital = '$hospital' AND `condition` = 6 OR `condition` = 0";
    }
}
$result = $conn->query($query);
while($row = $result->fetch_array()){
    addItemRow($row);          
}

//add bottom total row:
$y = $globalY;                  //when we're done adding all of our rows,
if($y>204){                     //add another page if there's not enough room for the last 'total' row
    $pdf->AddPage("L", "LETTER");
    $y=7;
}
//draw lines for last row
$pdf->SetLineWidth(0.4);
$pdf->Line(7, $y, 7,$y+8);
$pdf->Line(270, $y, 270, $y+8);
$pdf->Line(7,$y+8,270,$y+8);
$pdf->SetLineWidth(0.25);
$pdf->Line(153,$y,153,$y+8);

$pdf->SetFont('helvetica', 'b', 12);
$pdf->SetTextColor(30, 144, 255);
$pdf->Text(9,$y+1,"TOTAL:");
$pdf->MultiCell(117, 8, "$".number_format($runningTotal, "0", ".", ","), 0, 'C', 0, 1, 151, $y+1, 1, 0, 0, 1, 4, 'm', 1 );


$pdf->Output('/tablePrintout.pdf', 'I');

$conn->close();
