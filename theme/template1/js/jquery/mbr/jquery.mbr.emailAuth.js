/**
 * Email 인증
 * 
 * Form : formWrite
 * input name : memail
 * 
 */
var CFm = 1 ;
var mailChk = 0 ;
$(function () {
//$(document).ready(function(){
	$('#formWrite').submit(function(){
		//(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
		var Frm = $('#formWrite') ;
		if ( !$('input[name="memail"]', Frm).val().length || !validateNumber($('input[name="memail"]', Frm).val()) ){
			alert('Please enter only number(0 ~ 9) for phone number.');
			$('input[name="memail"]', Frm).focus() ;
			return false ;
		}
		if(!CFm){
			//alert('You have not authenticated your email.') ;
			alert('Email 인증하지 않았습니다.') ;
			return false ;
		}
		//return false ;
	});
	
	var emailAuthSend_handler = function(e){
		(e.preventDefault) ? e.preventDefault() : e.returnValue = false;

		var Frm = $('#formWrite') ;
		if ( !$('input[name="memail"]', Frm).val().length || !validateNumber($('input[name="memail"]', Frm).val()) ){
			//alert('Please enter only number(0 ~ 9) for number.');
			alert('숫자(0~9)를 입력해주세요.');
			$('input[name="memail"]', Frm).focus() ;
			return false;
		}
		else{

			servRequest('Req_emailAuthSend', 'json', {'email':$('input[name="memail"]', Frm).val()}, function(res){
				//console.log('Req_emailAuthSend', res) ;
				/* $('#MemailAuthCode').focus() ;
				return ; */
				if(res.error_code!='000'){
					alert("Email 인증 번호를 입력하십시오.");
					$('#memail').val('').focus() ;
				}else{
					/*
					인증폼 초기화
					//----------------------------------
					*/
					CFm = 0 ;
					$('#Lifetime-count').remove();
					$('#MemailAuthCode').val('') ; // 인증번호 입력란 초기화
					$('#btn-emailAuthConfirm').unbind('click') ; // 인증확인 버튼 unbind
					$('#emailAuth-block').addClass('hide'); // 인증폼 숨기기
					$('#btn-emailAuthSend').unbind('click').one('click', emailAuthSend_handler) ; // 인증번호 요청 버튼 bind
					//----------------------------------
					//alert(res.error_msg) ;

					$('#emailAuth-block').removeClass('hide');
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
	$('#btn-emailAuthSend').on('click', emailAuthSend_handler);
	
	var countdown = function(){
		$('#btn-emailAuthConfirm').bind('click', emailAuthConfirm_handler) ;
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
			$('#MemailAuthCode').val('') ; // 인증번호 입력란 초기화
			$('#btn-emailAuthConfirm').unbind('click') ; // 인증확인 버튼 unbind
			$('#emailAuth-block').addClass('hide'); // 인증폼 숨기기
			//----------------------------------
			$('#btn-emailAuthSend').unbind('click').one('click', emailAuthSend_handler) ; // 인증번호 요청 버튼 bind
			//alert('Authentication number input time was about 5 minute. Please request your verification number again.');
			alert('인증번호 입력시간은 5분입니다. 인증번호를 다시 요청하세요.');
		});
	};
	var emailAuthConfirm_handler = function(e){
		servRequest('Req_emailAuthConfirm', 'json', {'code': $('#MemailAuthCode').val()}, function(res){
			//console.log('Req_emailAuthConfirm', res) ;
			if(res == '000'){
				CFm = 1 ;
				/*
				인증폼 초기화
				//----------------------------------
				*/
				$('#Lifetime-count').remove();
				//$('#MemailAuthCode').val('') ; // 인증번호 입력란 초기화
				$('#btn-emailAuthConfirm').unbind('click') ; // 인증확인 버튼 unbind
				$('#emailAuth-block').addClass('hide'); // 인증폼 숨기기
				//----------------------------------
				alert('Verified.');
			}else{
				//console.log('실패');
			}

		});
	};
	
});