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

}