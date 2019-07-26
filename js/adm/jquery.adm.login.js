$(document).ready(function() {
	var servRequest = function(type, params, callback){

		var requestURL = null ;
		requestURL = baseUrl+'/'+type ;
		//setTimeout(function() {
		OFUNC.Request_ajax({
						url : requestURL,
						type : 'POST',
						dataType : 'json',
						data : params,
						beforeSend : function(xhr, settings){	// 기본적으로 생성,수정 처리시 표시
							$('.loader').show();
						},
						complete: function(e){
							$('.loader').hide();
							if(e.readyState == 4) {}
							//console.log(e.responseJSON);
							if(callback !== undefined) callback(e.responseJSON) ;
						}
		});
		//},300);
	};
	$('#mbr_submit').on('click',function(e){
		(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
		e.preventDefault();
		var keyup = jQuery.Event("keyup");
		keyup.which = 13 ;
		$("#Auserpw, #Auserid").trigger(keyup);
	});

	$("#Auserid, #Auserpw").on('keyup',function(e){
		if(e.stopPropagation) e.stopPropagation();
		if(e.preventDefault) e.preventDefault();
		if(this.value.match(/ /)) this.value = this.value.replace(' ','') ;
		if(this.value.match(/'/)) this.value = this.value.replace("'",'') ;
		if(this.value.match(/"/)) this.value = this.value.replace('"','') ;
		if(e.which == 13){
			servRequest('login', $('form#admLoginFrm').serialize(), function(res){
					if(res) window.location.replace(res);
					return ;
			});
		}//if(event.which)
	});
});