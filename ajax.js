
// Get the HTTP Object

function getHTTPObject(){
	if (window.ActiveXObject) return new ActiveXObject("Microsoft.XMLHTTP");
	else if (window.XMLHttpRequest) return new XMLHttpRequest();
	else {
		alert("Your browser does not support AJAX.");
		return null;
	}
}



// Change the value of the outputText field

function setSponsorOutput(){
	if(httpObject.readyState == 4){
		document.getElementById('ajax_text').innerHTML = httpObject.responseText;
	}
}

function setChildOutput(){
	if(httpObject.readyState == 4){
		document.getElementById('ajax_child').innerHTML = httpObject.responseText;
	}
}


// Implement business logic

function doWork(sponsor){
	httpObject = getHTTPObject();
	if (httpObject != null) {
		httpObject.open("GET", "ajax_list_children.php?choice=" + encodeURI(sponsor) , true);
		httpObject.send(null);
		httpObject.onreadystatechange = setSponsorOutput;
	}
}

function doChild(child){
	httpObject = getHTTPObject();
	if (httpObject != null) {
		httpObject.open("GET", "ajax_list_sponsors.php?choice=" + encodeURI(child) , true);
		httpObject.send(null);
		httpObject.onreadystatechange = setChildOutput;
	}
}

function addPayment() {
	  var x = document.getElementById("add_payment");
	  if (x.style.display === "none") {
	    x.style.display = "block";
	  } else {
	    x.style.display = "none";
	  }
	
}

function ReplaceDiv(id,content) {
	var container = document.getElementById(id);
	container.innerHTML = content;
}





var httpObject = null;

