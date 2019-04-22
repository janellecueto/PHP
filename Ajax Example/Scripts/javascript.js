function handleInput(field, str){
    if(!str){       //if there is no value entered 
        clearFields();
    }
    let xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function(){
        if(this.readyStat === 4 && this.status === 200){

            let jsonResponse = JSON.parse(xhttp.response); 

            document.getElementById('cname').value = jsonResponse['company'];
            document.getElementById("addr1").value = jsonResponse['address1'];
            document.getElementById("addr2").value = jsonResponse['address2'];
            document.getElementById("cityin").value = jsonResponse['city'];
            document.getElementById("statein").value = jsonResponse['state'];
            document.getElementById("zipin").value = jsonResponse['zip'];
            if(field === 1){
                document.getElementById('clientc').value = jsonResponse['code'];
            }

        }
    };

    let openStr = "fillInfo.php?";
    if(field === 1){
        //if we're filling info based on job number
        openStr += "jn="+str;
    }
    else{
        //or if we're filling info based on client code
        openStr += "cc="+str;
    }

    xhttp.open('GET', openStr, true);
    xhttp.send();
}

function clearFields(){
    document.getElementById("clientc").value = "";
    document.getElementById("cname").value = "";
    document.getElementById("addr1").value = "";
    document.getElementById("addr2").value = "";
    document.getElementById("cityin").value = "";
    document.getElementById("statein").value = "";
    document.getElementById("zipin").value = "";
    document.getElementById("clientname").innerHTML = "";
}

function checkSignature(field, init) {
    if (init.length <= 4) {

        var xhttp = new XMLHttpRequest;
        xhttp.onreadystatechange = function () {
            if (this.readyState === 4 && this.status === 200) {
                if(xhttp.responseText) field.value = xhttp.responseText;
                else field.value = init;
            }
        };
        let comp = document.getElementById("clientc").value;
        xhttp.open('GET', '../searchInitials.php?q=' + init + '&p=' + comp, true);
        xhttp.send();
    }

}

//printLbl will send information to sendToEnvelopePrinter.php or to sendToLabelPrinter.php for
//printing labels or envelopes based on the flag given (lbl or env)
function printLbl(flag){
    var attn = document.getElementById("cln").value;
    var comp = document.getElementById("cname").value;
    var addr1 = document.getElementById("addr1").value;
    var addr2 = document.getElementById("addr2").value;
    var city = document.getElementById("cityin").value;
    var st = document.getElementById("statein").value;
    var zip = document.getElementById("zipin").value;

    //add checks for missing fields
    if (!addr1 || !city || !st || !zip) {
        alert("Please fill out any missing fields before printing.");
        return;
    }

    var outArr = [attn, comp, addr1]; //, addr2, city, st, zip];
    if (addr2) outArr.push(addr2);
    outArr.push(city, st, zip);

    if (flag === 'lbl') {
        outArr.unshift(document.getElementById("jobn").value); //adds job number to array passed into sendToLabelPrinter.php
    }

    let outPrint = JSON.stringify(outArr);
    outPrint = outPrint.replace(/&/g, "%26").replace(/#/g, "%23");

    let xttp = new XMLHttpRequest();

    xttp.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {

            if (flag === 'e') {
                if (alert("Envelope sent to printer!")) {
                    clearFields();
                    document.getElementById('jobn').value = "";
                    window.parent.closeModal('printLbl');
                }
                else {
                    return;
                }
            }
            else {
                if (alert("Label sent to DYMO printer!")) {
                    clearFields();
                    document.getElementById('jobn').value = "";
                    window.parent.closeModal('printLbl');
                }
                else {
                    return;
                }
            }
        }
    }; 
    if (flag === 'env') {
        xttp.open('GET', 'sendToEnvelopePrinter.php?q=' + outPrint, true);
    }
    else {
        xttp.open('GET', 'sendToLabelPrinter.php?q=' + outPrint, true);
    }
    xttp.send();
}