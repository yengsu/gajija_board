var CFm = 0 ;
var mailChk = 0 ;
var snd_count = 0 ;
var snd_max = 5 ;

$(function () {
//$(document).ready(function(){
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
				$('#Auth-block').addClass('hide'); // 인증폼 숨기기
				//$('#btn-AuthSend').unbind('click') ; // 인증코드 보내기
				//----------------------------------
			},
			
			email : {
					/**
					 * 입력값 검사
					 */
					validate : function(){
						
							if( ! join_authen.agree() ) return false ;
							if ( !$('input[name="muserid"]', join_authen.Frm).val().length || !validateEmail($('input[name="muserid"]', join_authen.Frm).val()) ){
								alert('Email주소를 정확히 입력해주세요.');
								$('input[name="muserid"]', join_authen.Frm).focus() ;
								return false;
							}
							return true ;
					},
					/**
					 * 인증번호 요청
					 */
					request : function(evt){
							var thisObj = $(this) ;
							join_authen.authConfirm_init();
							$('#btn-museridChk').text('전송중...').css('color','#e02121') ;
							thisObj.unbind('click') ;
							
							servRequest('Req_AuthenSend', 'json', {'kind':'email', 'params':$('input[name="muserid"]', join_authen.Frm).val()}, function(res, e){
								
								thisObj.bind('click',{kind:'email'}, join_authen.authSend_handler);
								
								$('#btn-museridChk').text('인증코드 받기').css('color','#000') ;
								
								CFm = 0 ;
								
								if(e.status == 200)
								{
									if(res){
										snd_count ++ ;
										//$('#btn-museridChk').text('인증코드 받기').css('color','#000') ;
										alert("Email 전송하였습니다.\n\nEmail 확인 후 인증 번호를 입력하십시오.");
										//$('#muserid').val('').focus() ;
										/**
										인증폼 초기화
										*/
										//----------------------------------
										//CFm = 0 ;
										//----------------------------------
			
										$('#Auth-block').removeClass('hide');
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
										join_authen.countdown.call(thisObj, evt);
									}
									else{
										join_authen.authConfirm_init();
									}
								}
								else if(e.status == 501){
									join_authen.authConfirm_init();
									//$('#btn-AuthSend').unbind('click').one('click', join_authen.authSend_handler) ; // 인증번호 요청 버튼 bind
								}
								else{
									join_authen.authConfirm_init();
								}
							});
					}
				
			},
			countdown : function(evt){
				$('#btn-AuthConfirm').bind('click', join_authen.authConfirm_handler) ;
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
					$('#Auth-block').addClass('hide'); // 인증폼 숨기기
					//----------------------------------
					$('#btn-AuthSend').unbind('click').one('click', {'kind':evt.data.kind}, join_authen.authSend_handler) ; // 인증번호 요청 버튼 bind
					alert('인증번호 입력시간이 경과되었습니다. \n\n인증번호를 다시 요청하세요.') ;
					//alert('Authentication number input time was about 5 minute. Please request your verification number again.');
				});
			},
			useridExist_handler : function(e){
				(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
				
				if( $('input[name="muserid"]', join_authen.Frm).val() == ""){
					alert('Email주소를 입력해주세요.');//Please enter mail
					$('input[name="muserid"]', join_authen.Frm).focus();
					return false ;
				}
				if( !validateEmail($('input[name="muserid"]', join_authen.Frm).val() ) ){
					alert('Email주소를 정확히 입력해주세요.'); // Please enter mail
					$('input[name="muserid"]', join_authen.Frm).focus();
					return false ;
				}
				servRequest('Req_useridExist', 'json', {'muserid':$('input[name="muserid"]', join_authen.Frm).val()}, function(res){
					if(res == 1){
						mailChk = 1 ;
						alert('이용가능한 Email입니다.'); //Available mail.
						$(e.target).text('인증코드 받기').unbind('click').bind('click', {'kind':'email'}, join_authen.authSend_handler) ;
					}else{
						mailChk = 0 ;
						alert('이미 존재하는 ID(Email) 입니다.'); // The ID(email) already exists.
						$('input[name="muserid"]', join_authen.Frm).val('').focus() ;
					}
				});
			},
			
			authSend_handler : function(e){
				//(e.preventDefault) ? e.preventDefault() : e.returnValue = false;

				if(snd_count >= snd_max){
					$('#btn-museridChk').unbind('click');
					alert("인증코드를 입력해주세요.");
					return false ;
				}
				if(join_authen[e.data.kind].validate()){
					join_authen[e.data.kind].request.call(this, e);
				}
			},
			
			authConfirm_handler : function(e){
				
				servRequest('Req_AuthenConfirm', 'json', {'kind': 'email', 'code': $('#MAuthCode').val()}, function(res, e){
					//console.log('Req_AuthenConfirm', res) ;
					if(res == '000'){
						CFm = 1 ;
						/*
						인증폼 초기화
						//----------------------------------
						*/
						$('#Lifetime-count').remove();
						//$('#MAuthCode').val('') ; // 인증번호 입력란 초기화
						$('#btn-AuthConfirm').unbind('click') ; // 인증확인 버튼 unbind
						$('#Auth-block').addClass('hide'); // 인증폼 숨기기
						//----------------------------------
						$('#btn-museridChk').unbind('click');
						if($('#muserid').length) $('#muserid').attr('readonly', true) ;
						
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
							$('#Auth-block').addClass('hide'); // 인증폼 숨기기
							//----------------------------------
						}
						else if(e.status == 401){
							$('#MAuthCode').val('') ;
						}
					}

				});
			},
			submit_handler : function(e){
				(e.preventDefault) ? e.preventDefault() : e.returnValue = false;

				//var Frm = $('#formWrite') ;
				if( $('input[name="muserid"]', join_authen.Frm).val() == '' ){
					alert('이메일 주소를 입력해주세요.'); //Please enter your email address correctly.
					$('input[name="muserid"]', join_authen.Frm).focus();
					return false ;
				}
				else if( !validateEmail($('input[name="muserid"]', join_authen.Frm).val() ) ){
					alert('이메일 주소를 정확하게 입력해주세요.'); //Please enter your email address correctly.
					$('input[name="muserid"]', join_authen.Frm).focus();
					return false ;
				}
				else if(!mailChk){
					alert('이메일을 사용할 수 있는지 중복체크 하세요.'); //Please check if Email is available.
					return false ;
				}
				else if(!CFm){
					alert('인증하지 않았습니다.') ; //You have not authenticated your phone.
					return false ;
				}
				//regex = /^(?=.*[a-zA-Z])((?=.*\d)|(?=.*\W)).{4,16}$/;
				else if(!/^(?=.*[a-zA-Z])((?=.*\d)|(?=.*\W)).{6,15}$/.test( $('input[name="muserpw"]', join_authen.Frm).val()) ){
					alert('비밀번호는 숫자 또는 특수 문자를 포함하여 6 ~ 15 자 여야합니다.') ; //Password must be 6~15 characters, including numbers or special characters.
					$('input[name="muserpw"]', join_authen.Frm).focus();
					return false ;
				}
				else if( $('input[name="muserpw"]', join_authen.Frm).val()!=$('input[name="muserpw_confirm"]', join_authen.Frm).val() ){
					alert('비밀번호를 올바르게 입력해주세요.'); //Please enter your password correctly.
					$('input[name="muserpw_confirm"]', join_authen.Frm).focus();
					return false ;
				}
				
				else if (!$('input[name="musername"]', join_authen.Frm).val().length || !validateSpecialChar( $('input[name="musername"]', join_authen.Frm).val() )  ){
					//$('input[name="musername"]', Frm).val().indexOf(" ") >= 0) {
					alert('이름을 정확하게 입력해주세요.'); //Please enter your name correctly.
					$('input[name="musername"]', join_authen.Frm).focus() ;
					return false ;
				}
				else if (!$('input[name="musernick"]', join_authen.Frm).val().length || !validateSpecialChar( $('input[name="musernick"]', join_authen.Frm).val() )  ){
					//$('input[name="musername"]', Frm).val().indexOf(" ") >= 0) {
					alert('닉네임(별명)을 입력해주세요.'); //Please enter your name correctly.
					$('input[name="musernick"]', join_authen.Frm).focus() ;
					return false ;
				}
				else if ( !$('input[name="mhp"]', join_authen.Frm).val().length || !validateNumber($('input[name="mhp"]', join_authen.Frm).val()) ){
					alert('전화 번호는 숫자(0 ~ 9)만 입력해주세요.'); //Please enter only number(0 ~ 9) for phone number.
					$('input[name="mhp"]', join_authen.Frm).focus() ;
					return false ;
				}
				
				var MAuthCode = $('input[name="MAuthCode"]', join_authen.Frm) ;
				MAuthCode.val( MAuthCode.val() ) ;
				if( MAuthCode.val() == '' ){
					alert('인증코드를 입력해주세요.') ;
					MAuthCode.focus();
					return false;
				}
				
				if( ! join_authen.agree() ) return false ;
				
				join_authen.Frm.submit();
				/*servRequest('Req_CaptchaConfirm', 'json', {captcha: grecaptcha.getResponse()}, function(res, e){
					if(e.status==200) 	join_authen.Frm.submit();
				});*/
			}
	}
	$('#btn-museridChk').bind( 'click', join_authen.useridExist_handler ) ;
	$('#btn-AuthSend').bind('click', {kind:'email'}, join_authen.authSend_handler);
	$('#btn-submit').bind('click', join_authen.submit_handler );
	//$('#formWrite').submit(join_authen.submit_handler );
	
	
	
});