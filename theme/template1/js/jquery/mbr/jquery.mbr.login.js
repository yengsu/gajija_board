/*
 * 아직 미사용
 */
function loginOpenWin(a_str_windowURL, a_str_windowName, a_int_windowWidth, a_int_windowHeight, a_bool_scrollbars, a_bool_resizable, a_bool_menubar, a_bool_toolbar, a_bool_addressbar, a_bool_statusbar, a_bool_fullscreen){
	/*var a_int_windowWidth = 1010; //1000 ;
	var a_int_windowHeight = 640;//640 ;
	var int_windowLeft = (screen.width/ 2) - (a_int_windowWidth/ 2),
	int_windowTop = (screen.height/ 2) - (a_int_windowHeight/ 2),
	myWin = window.open('about:blank', 'n_cMp', "toolbar=1,scrollbars=1,location=0,statusbar=0,menubar=1,resizable=1,width="+a_int_windowWidth+",height="+a_int_windowHeight+",left ="+int_windowLeft+" ,top ="+int_windowTop);
	if(myWin.document)
	{
		$(myWin.document).html("<p id='msg' style='margin:200px auto;text-align:center;font-size:2.5em;font-weight:bold;color:#d24189;'>Loading...</p>");
		myWin.focus();
		
		myWin.location.href='/Member/loginGoogle';
	}*/
	var int_windowLeft = (screen.width - a_int_windowWidth) / 2;
	var int_windowTop = (screen.height - a_int_windowHeight) / 2;
	var str_windowProperties = 'height=' + a_int_windowHeight + ',width=' + a_int_windowWidth + ',top=' + int_windowTop + ',left=' + int_windowLeft + ',scrollbars=' + a_bool_scrollbars + ',resizable=' + a_bool_resizable + ',menubar=' + a_bool_menubar + ',toolbar=' + a_bool_toolbar + ',location=' + a_bool_addressbar + ',statusbar=' + a_bool_statusbar + ',fullscreen=' + a_bool_fullscreen + '';
	var obj_window = window.open('about:blank', a_str_windowName, str_windowProperties) ;
	try
	{
		if(obj_window.document)
		{
			$(obj_window.document.body).html("<p id='msg' style='margin:200px auto;text-align:center;font-size:2.5em;font-weight:bold;color:#d24189;'>Loading...</p>");
			obj_window.window.focus();
			obj_window.location.href = a_str_windowURL ;
		}
	}
	catch(e){
		obj_window.window.focus();
		obj_window.location.href = a_str_windowURL ;
	}
	
}
$(document).ready(function() {
	var login_id = $.cookie('lux_id');
	var muserpw = $.cookie('lux_pwd');
	if(login_id != undefined) {
		
		$("#muserid").val(Base64.decode(login_id));
		$("#muserpw").val(Base64.decode(muserpw));
		$("input:checkbox[name='checksaveid']").prop("checked",true);
	}
	$("#loader").hide();
	$('#mbr_submit').on('click',function(event){
		event.preventDefault();
		var keyup = jQuery.Event("keyup");
		keyup.which = 13 ;
		$("#muserpw, #muserid").trigger(keyup);
	});

	$("#muserid, #muserpw").on('keyup',function(e){
		if(e.stopPropagation) e.stopPropagation();
		if(e.preventDefault) e.preventDefault();
		if(this.value.match(/ /)) this.value = this.value.replace(' ','') ;
		if(this.value.match(/'/)) this.value = this.value.replace("'",'') ;
		if(this.value.match(/"/)) this.value = this.value.replace('"','') ;
		if(e.which == 13){
			
			if( $("input:checkbox[name='checksaveid']").is(":checked") == true ){
				var isRemember = confirm("로그인 정보를이 PC에 저장 하시겠습니까? \n\n 개인 정보가 공공 장소에서 유출 될 수 있습니다.");
				if(isRemember == true){
					$.cookie.raw = $.cookie.json = true;
					if($("#muserid").val()) $.cookie('lux_id', Base64.encode($("#muserid").val()), { expires: 30 });
					if($("#muserpw").val()) $.cookie('lux_pwd', Base64.encode($("#muserpw").val()), { expires: 30 });
				}else{
					$.removeCookie("lux_id");
					$.removeCookie("lux_pwd");
				}
			} else {
				$.removeCookie("lux_id");
				$.removeCookie("lux_pwd");
			}
			var password = $("#muserpw").val();
			var userid = $("#muserid").val();
			var qry = 'muserid='+ userid + '&muserpw=' + password;
			servRequest('login', 'json', $('form#mbrLoginFrm').serialize(), function(res){
					if(res) window.location.replace(res);
					return ;
			});
		}//if(event.which)
	});
	
	$('#btn-facebookLogin').on('click', function(e){
		(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
		if(loginApi.facebook) newWindow(loginApi.facebook, 'loginApi', 600, 500, 'yes', 'yes') ;
		//if(loginApi.facebook) loginOpenWin(loginApi.facebook, 'loginApi', 600, 500, 'yes', 'yes') ;
	});
	$('#btn-googleLogin').on('click', function(e){
		(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
		if(loginApi.google) newWindow(loginApi.google, 'loginApi', 600, 500, 'yes', 'yes') ;
		//if(loginApi.google) loginOpenWin(loginApi.google, 'loginApi', 600, 500, 'yes', 'yes') ;
	});
	$('#btn-kakaoLogin').on('click', function(e){
		(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
		if(loginApi.kakao) newWindow(loginApi.kakao, 'loginApi', 600, 550, 'yes', 'yes') ;
		//if(loginApi.kakao) loginOpenWin(loginApi.kakao, 'loginApi', 600, 550, 'yes', 'yes') ;
	});
	$('#btn-naverLogin').on('click', function(e){
		(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
		if(loginApi.naver) newWindow(loginApi.naver, 'loginApi', 600, 550, 'yes', 'yes') ;
		//if(loginApi.naver) loginOpenWin(loginApi.naver, 'loginApi', 600, 500, 'yes', 'yes') ;
	});
	$('#btn-instagramLogin').on('click', function(e){
		(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
		if(loginApi.instagram) newWindow(loginApi.instagram, 'loginApi', 600, 515, 'yes', 'yes') ;
		//if(loginApi.instagram) loginOpenWin(loginApi.instagram, 'loginApi', 600, 500, 'yes', 'yes') ;
	});
});