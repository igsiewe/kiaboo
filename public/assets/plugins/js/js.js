function getXhr(){
    var xhr = null;
    if(window.XMLHttpRequest) // Firefox et autres
        xhr = new XMLHttpRequest();
    else if(window.ActiveXObject){ // Internet Explorer
        try {
            xhr = new ActiveXObject("Msxml2.XMLHTTP");
        } catch (e) {
            xhr = new ActiveXObject("Microsoft.XMLHTTP");
        }
    }
    else { // XMLHttpRequest non supporté par le navigateur
        alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest...");
        xhr = false;
    }
    return xhr;
}

function getDetailTransaction(id){
    var xhr = getXhr();
    document.getElementById('detailTransactions').innerHTML ="";

    xhr.onreadystatechange = function(){
        if(xhr.readyState == 4 && xhr.status == 200){
            leselect = xhr.responseText;
            document.getElementById('detailTransactions').innerHTML = leselect;
        }
    }
    var url ='/transactions/transaction/edit/'+id

    xhr.open("GET",url, true );
    xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
    xhr.send();
}
