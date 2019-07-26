var CFm = 1 ;
var mailChk = 0 ;
var snd_count = 0 ;
var snd_max = 5 ;
$(function () {
//$(document).ready(function(){
	$('input:radio[name="auth_kind"]').on('click', function(){
		
		var Frm =$('#formWrite') ;
		$('#btn-emailAuthSend').text('인증코드 받기').css('color','#000') ;
		if(this.value == "email") {
			$('#form-join-auth-email').show();
			$('#form-join-auth-sms').hide();
			$('input[name="mphone"]', Frm).val('') ;
		}else if(this.value == "sms") {
			$('#form-join-auth-sms').show();
			$('#form-join-auth-email').hide();
			$('input[name="memail"]', Frm).val('') ;
		}
	});
	$(':reset',  $('#formWrite')).on('click', function(e){
		(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
		self.close();
	});
	$('#formWrite').submit(function(){
		//(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
		var Frm = $('#formWrite') ;
		
		// 필수입력 사항 있으면 체크
		if(inputValidate.length){
			for(var i=0, l=inputValidate.length; i<l; i++){
				if( inputValidate[i] == "name" ){
					var name = $('input[name="musername"]', Frm) ;
					name.val( OFUNC.trim(name.val()) ) ;
					if( name.val() == '' ){
						alert('이름을 입력해주세요.') ;
						name.focus();
						return false;
					}
				}
				else if( inputValidate[i] == "email" ){
					var email = $('input[name="memail"]', Frm) ;
					email.val( OFUNC.trim(email.val()) ) ;
					if( email.val() == '' ){
						alert('Email을 입력해주세요.') ;
						email.focus();
						return false;
					}
				}
				else if( inputValidate[i] == "gender" ){
					var gender = $('input:radio[name="msex"]:checked', Frm) ;
					gender.val( OFUNC.trim(gender.val()) ) ;
					if( gender.val() == '' ){
						alert('성별(gender)을 선택해주세요.') ;
						gender.focus();
						return false;
					}
				}
			}
		}
			
		var MAuthCode = $('input[name="MAuthCode"]', Frm) ;
		MAuthCode.val( OFUNC.trim(MAuthCode.val()) ) ;
		if( MAuthCode.val() == '' ){
			alert('인증코드를 입력해주세요.') ;
			MAuthCode.focus();
			return false;
		}
		if(!CFm){
			alert('인증을 하지 않았습니다.') ;//You have not authenticated your phone.
			return false ;
		}
		
		var kind = $('input:radio[name="auth_kind"]:checked').val() ;
		if( ! join_authen[kind].validate() ) return false ;
		
		//return false ;
	});
	var join_authen = {
			Frm : $('#formWrite'),
			agree : function(){
							if( !$('input:checkbox[id="magree_news"]', this.Frm).is(":checked") || !$('input:checkbox[id="magree_policy"]', this.Frm).is(":checked")){
								alert('동의를 체크하셔야 이용가능합니다.') ;
								return false ;
							}
							return true ;
					},
			authConfirm_init : function(){
				/*
				인증폼 초기화
				//----------------------------------
				*/
				$('#Lifetime-count').remove();
				$('#MAuthCode').val('') ; // 인증번호 입력란 초기화
				$('#btn-AuthConfirm').unbind('click') ; // 인증확인 버튼 unbind
				$('#Auth-block').hide(); // 인증폼 숨기기
				$('#btn-AuthSend').unbind('click') ;
				//----------------------------------
			},
			email : {
					/**
					 * 입력값 검사
					 */
					validate : function(){
							if( ! join_authen.agree() ) return false ;
							if ( !$('input[name="memail"]', join_authen.Frm).val().length || !validateEmail($('input[name="memail"]', join_authen.Frm).val()) ){
								alert('Email주소를 정확히 입력해주세요.');
								$('input[name="memail"]', join_authen.Frm).focus() ;
								return false;
							}
							return true ;
					},
					/**
					 * 인증번호 요청
					 */
					request : function(){
							var thisObj = $(this) ;
							join_authen.authConfirm_init();
							$('#btn-emailAuthSend').text('전송중...').css('color','#e02121') ;
							thisObj.unbind('click') ;
							
							servRequest('Req_AuthenSend', 'json', {'kind':'email', 'params':$('input[name="memail"]', join_authen.Frm).val()}, function(res, e){
								thisObj.bind('click',{kind:'email'}, AuthSend_handler);
								$('#btn-emailAuthSend').text('인증코드 받기').css('color','#000') ;
								if(e.status != 200){
									join_authen.authConfirm_init();
								}
								else if(res){
									snd_count ++ ;
									alert("Email 전송하였습니다.\n\nEmail 확인 후 인증 번호를 입력하십시오.");
									//$('#memail').val('').focus() ;
									/**
									인증폼 초기화
									*/
									//----------------------------------
									CFm = 0 ;
									$('#btn-AuthSend').unbind('click').one('click', AuthSend_handler) ; // 인증번호 요청 버튼 bind
									//----------------------------------
		
									$('#Auth-block').show();
									if( !$('#Lifetime-count').length){
										$('.join-time').append( 
												$('<span/>',{
													'id':'Lifetime-count'
													})
													.css({
														'color':'red',
														'font-weight':'bold'	
														})
												);
									}
									countdown();
								}
							});
					}
				
			},
			sms : {
					/**
					 * 입력값 검사
					 */
					validate : function(){
						if( ! join_authen.agree() ) return false ;
						if ( !$('input[name="mphone"]', join_authen.Frm).val().length || !validateNumber($('input[name="mphone"]', join_authen.Frm).val()) ){
							alert('폰번호는 숫자(0~9)를 입력해주세요.');
							$('input[name="mphone"]', join_authen.Frm).focus() ;
							return false;
						}
						return true ;
					},
					/**
					 * 인증번호 요청
					 */
					request : function(){
							var thisObj = $(this) ;
							join_authen.authConfirm_init();
							$('#btn-emailAuthSend').text('전송중...').css('color','#e02121') ;
							thisObj.unbind('click') ;
							
							servRequest('Req_AuthenSend', 'json', {'kind':'sms', 'params':$('input[name="mphone"]', join_authen.Frm).val()}, function(res, e){
								thisObj.bind('click',{kind:'sms'}, AuthSend_handler);
								$('#btn-emailAuthSend').text('인증코드 받기').css('color','#000') ;
								if(e.status != 200){
									join_authen.authConfirm_init();
								}
								else if(res){
									snd_count ++ ;
									//Please enter your phone's message verification number.
									alert("휴대 전화의 메시지 인증 번호를 입력하십시오.");
									//$('#mphone').val('').focus() ;
									/**
									인증폼 초기화
									*/
									//----------------------------------
									CFm = 0 ;
									$('#btn-AuthSend').unbind('click').one('click', AuthSend_handler) ; // 인증번호 요청 버튼 bind
									//----------------------------------
									//alert(res.error_msg) ;
		
									$('#Auth-block').show();
									if( !$('#Lifetime-count').length){
										$('.join-time').append( 
												$('<span/>',{
													'id':'Lifetime-count'
													})
													.css({
														'color':'red',
														'font-weight':'bold'	
														})
												);
									}
									countdown();
								}
							});
					}
			}
	} ;
	var AuthSend_handler = function(e){
		(e.preventDefault) ? e.preventDefault() : e.returnValue = false;

		if(snd_count >= snd_max){
			$('#btn-emailAuthSend').unbind('click');
			$('#btn-smsAuthSend').unbind('click');
			alert("인증코드를 입력해주세요.");
			return false ;
		}
		if(join_authen[e.data.kind].validate()){
			join_authen[e.data.kind].request.call(this);
		}
	};
	
	$('#btn-emailAuthSend').bind('click', {kind:'email'}, AuthSend_handler);
	$('#btn-smsAuthSend').bind('click', {kind:'sms'}, AuthSend_handler);
	
	var countdown = function(){
		$('#btn-AuthConfirm').bind('click', AuthConfirm_handler) ;
		CFm = 1 ;
		var fiveSeconds = new Date().getTime() + (5*60000);//5분까지 // + 500000;
		
		$('#Lifetime-count').countdown(fiveSeconds)
		.on('update.countdown', function(e) {
			$(this).text( e.strftime('%M : %S') ) ;
		})
		.on('finish.countdown', function(e) {			
			CFm = 0 ;
			$(this).text( e.strftime('%M : %S') ) ;
			
			/*
			인증폼 초기화
			//----------------------------------
			*/
			$(this).remove();
			$('#MAuthCode').val('') ; // 인증번호 입력란 초기화
			$('#btn-AuthConfirm').unbind('click') ; // 인증확인 버튼 unbind
			$('#Auth-block').hide(); // 인증폼 숨기기
			//----------------------------------
			$('#btn-AuthSend').unbind('click').one('click', AuthSend_handler) ; // 인증번호 요청 버튼 bind
			//alert('Authentication number input time was about 5 minute. Please request your verification number again.');
			alert('인증번호 입력시간이 경과되었습니다. \n\n인증번호를 다시 요청하세요.') ;
		});
	};
	var AuthConfirm_handler = function(e){
		var kind = $('input:radio[name="auth_kind"]:checked').val() ;
		servRequest('Req_AuthenConfirm', 'json', {'kind': kind, 'code': $('#MAuthCode').val()}, function(res, e){
			//console.log('Req_smsAuthConfirm', res) ;
			if(res == '000'){
				CFm = 1 ;
				/*
				인증폼 초기화
				//----------------------------------
				*/
				$('#Lifetime-count').remove();
				//$('#MAuthCode').val('') ; // 인증번호 입력란 초기화
				$('#btn-AuthConfirm').unbind('click') ; // 인증확인 버튼 unbind
				$('#Auth-block').hide(); // 인증폼 숨기기
				
				$('input:radio[name="auth_kind"]').unbind('click').on('click', function(e){
					(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
				});
				if(kind == "email")
				{
					$('#btn-emailAuthSend').unbind('click');
					if($('#memail').length) $('#memail').attr('readonly', true) ;
				}
				else if(kind == "sms")
				{
					$('#btn-smsAuthSend').unbind('click');
					if($('#mphone').length) $('#mphone').attr('readonly', true) ;
				}
				//----------------------------------
				alert('인증되었습니다.'); // Verified.
			}
			else{
				if(e.status == 400){
					/*
					인증폼 초기화
					//----------------------------------
					*/
					$('#Lifetime-count').remove();
					$('#MAuthCode').val('') ; // 인증번호 입력란 초기화
					$('#btn-AuthConfirm').unbind('click') ; // 인증확인 버튼 unbind
					$('#Auth-block').hide(); // 인증폼 숨기기
					//----------------------------------
				}
				else if(e.status == 401){
					$('#MAuthCode').val('') ;
				}
			}

		});
	};
	
});