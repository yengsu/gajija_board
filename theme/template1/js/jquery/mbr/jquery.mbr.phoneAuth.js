var CFm = 1 ;
var mailChk = 0 ;
$(function () {
//$(document).ready(function(){
	$('#formWrite').submit(function(){
		//(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
		var Frm = $('#formWrite') ;
		if ( !$('input[name="mhp"]', Frm).val().length || !validateNumber($('input[name="mhp"]', Frm).val()) ){
			alert('Please enter only number(0 ~ 9) for phone number.');
			$('input[name="mhp"]', Frm).focus() ;
			return false ;
		}
		if(!CFm){
			alert('You have not authenticated your phone.') ;
			return false ;
		}
		//return false ;
	});
	
	var smsAuthSend_handler = function(e){
		(e.preventDefault) ? e.preventDefault() : e.returnValue = false;

		var Frm = $('#formWrite') ;
		if ( !$('input[name="mhp"]', Frm).val().length || !validateNumber($('input[name="mhp"]', Frm).val()) ){
			alert('Please enter only number(0 ~ 9) for phone number.');
			$('input[name="mhp"]', Frm).focus() ;
			return false;
		}
		else{

			servRequest('Req_smsAuthSend', 'json', {'phone':$('input[name="mhp"]', Frm).val()}, function(res){
				//console.log('Req_smsAuthSend', res) ;
				/* $('#MsmsAuthCode').focus() ;
				return ; */
				if(res.error_code!='000'){
					//휴대 전화의 메시지 확인 번호를 입력하십시오.
					alert("Please enter your phone's message verification number.");
					$('#mhp').val('').focus() ;
				}else{
					/*
					인증폼 초기화
					//----------------------------------
					*/
					CFm = 0 ;
					$('#Lifetime-count').remove();
					$('#MsmsAuthCode').val('') ; // 인증번호 입력란 초기화
					$('#btn-smsAuthConfirm').unbind('click') ; // 인증확인 버튼 unbind
					$('#smsAuth-block').addClass('hide'); // 인증폼 숨기기
					$('#btn-smsAuthSend').unbind('click').one('click', smsAuthSend_handler) ; // 인증번호 요청 버튼 bind
					//----------------------------------
					//alert(res.error_msg) ;

					$('#smsAuth-block').removeClass('hide');
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
	
	};
	$('#btn-smsAuthSend').on('click', smsAuthSend_handler);
	
	var countdown = function(){
		$('#btn-smsAuthConfirm').bind('click', smsAuthConfirm_handler) ;
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
			$('#MsmsAuthCode').val('') ; // 인증번호 입력란 초기화
			$('#btn-smsAuthConfirm').unbind('click') ; // 인증확인 버튼 unbind
			$('#smsAuth-block').addClass('hide'); // 인증폼 숨기기
			//----------------------------------
			$('#btn-smsAuthSend').unbind('click').one('click', smsAuthSend_handler) ; // 인증번호 요청 버튼 bind
			alert('Authentication number input time was about 5 minute. Please request your verification number again.');
		});
	};
	var smsAuthConfirm_handler = function(e){
		servRequest('Req_smsAuthConfirm', 'json', {'code': $('#MsmsAuthCode').val()}, function(res){
			//console.log('Req_smsAuthConfirm', res) ;
			if(res == '000'){
				CFm = 1 ;
				/*
				인증폼 초기화
				//----------------------------------
				*/
				$('#Lifetime-count').remove();
				//$('#MsmsAuthCode').val('') ; // 인증번호 입력란 초기화
				$('#btn-smsAuthConfirm').unbind('click') ; // 인증확인 버튼 unbind
				$('#smsAuth-block').addClass('hide'); // 인증폼 숨기기
				//----------------------------------
				alert('Verified.');
			}else{
				//console.log('실패');
			}

		});
	};
	
});