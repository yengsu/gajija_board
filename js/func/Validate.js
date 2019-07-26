function validateEmail(email) { var re = /^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i; return re.test(email); }
function validateSpecialChar(string) { 
	var stringRegx = /[~!@\#$%<>^&*\()\-=+_\’{};:.,]/gi; 
	var isValid = true; 
	if(stringRegx.test(string)) { 
		isValid = false; 
	} 
	return isValid; 
}

function validateNumber(string) { 
	res = (/^[0-9]*$/i).test(string);
	var flag = true;
	if (string != ""){
		if(!res){
			flag = false;
		}
	}else{
		flag = false;
	}
	return flag;
}