/**
 * shop common library
 * 
 * @writer : youngsu lee
 * @mail : yengsu@hanmail.net
 */
// 선택 상품 편집
var btn_handler = {
	mbrLogout : function(e){
		(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
		// 로그아웃시 처리 핸들러
		//btn_handler.api_logout();
		//return ;
		window.location.href = this.getAttribute('href');//$(this).attr('href') ;
		return ;
	}	
};
var servRequest = function(type, dateType, params, callback){
	var qs = location.search.substr(location.search.indexOf("?"));
	var requestURL = null ;
	requestURL = baseURL+'/'+type+qs ;
	//setTimeout(function() {
	OFUNC.Request_ajax({
					url : requestURL,
					type : 'POST',
					dataType : dateType,
					data : params,
					beforeSend : function(xhr, settings){	// 기본적으로 생성,수정 처리시 표시
						$('.loader').show();
					},
					complete: function(e){
						$('.loader').hide();
						if(e.readyState == 4) {}
						if(callback !== undefined){
							if(e.responseJSON) callback(e.responseJSON, e) ;
							else if(e.responseText) callback(e.responseText, e) ;
						}
					},
					error: function (jqXHR, exception) {
					    if(callback !== undefined) callback(null, jqXHR) ;
					}
	});
	//},300);
};

var window_resize_evnt = function() {
	var win = $(this); //this = window
	console.log(win.width());
	if (win.width() <= 900) {
		//console.log( $('.header-top-logo', $('.header-top')) );
		var context = $('.header-top') ;
		//context.find('.col-10').removeClass('col-10').addClass('col-8');
		//context.find('.col-10').addClass('col-8');
		$('nav#TOP-MENU').prependTo($('.header-top-logo', context));
		
	}
	else{
		var context = $('.header-top') ;
		//context.find('.col-8').removeClass('col-8').addClass('col-10');
		//context.find('.col-8').addClass('col-10');
		$('#TOP-CONTAINER').prepend( $('nav#TOP-MENU') );
	}
};


$(function(){
	
	/*
	//-----------------------
	// Scroll 최상단 이동 Bar
	//-----------------------
	$('body').append(
			'<button' 
				+' class="btn rounded-circle scroll-top"'
				+' data-scroll="up"'
				+' type="button"'
				+' style="width: 55px;height: 55px;position: fixed;bottom: 80px;right: 30px;display: none;background-color: rgba(7, 9, 16, 0.35) !important;"'
			+'>'
			+'<i class="fa fa-chevron-up" style="display: inline-block;color: #FFFFFF;"></i>'
			+'</button>'
		);
	$(document).scroll(function (e) {
		//if ($(this).scrollTop() > 20 || $(this).scrollTop() > 100) {
		if ($(this).scrollTop() > 20 || $(this).scrollTop() > 100) {
			$('.header-top-logo').addClass('m-response');
	        $('.navbar-brand-logo img').addClass('m-response');
		}
		else{
			$('.header-top-logo').removeClass('m-response');
	        $('.navbar-brand-logo img').removeClass('m-response');
		}
		if ($(this).scrollTop() > 400) {	
	        $('.scroll-top').fadeIn();
	        //$('.header-top-logo').addClass('m-response');
	        //$('.navbar-brand-logo img').addClass('m-response');
	    } else {
	        $('.scroll-top').fadeOut();
	        //$('.header-top-logo').removeClass('m-response');
	        //$('.navbar-brand-logo img').removeClass('m-response');
	    }
		
	});
	
	$('.scroll-top').click(function () {
	    $("html, body").animate({
	        scrollTop: 0
	    }, 100);
	    return false;
	});
	//-----------------------
	
	
	$('#mainNavButton').bind('click', function(e){
		//(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
		if(this.checked) $(this).closest('nav').find('ul').show();
		else $(this).closest('nav').find('ul').hide();
	});
	$('body').on('click', function(e){
		//(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
		//e.stopPropagation();
		var thisObj = e.target ;
		
		// 메인 네비게이션 메뉴 자동 닫기(메뉴영역외에 클릭시)
		if( $(thisObj).attr('id') != 'mainNavButton' && $(thisObj).attr('for') != 'mainNavButton' ) {
			$('#mainNavButton').prop('checked', false);
			$('nav#TOP-MENU').find('ul').hide();
			
		}
	});
	window_resize_evnt.call($(window));
	$( window ).resize(window_resize_evnt);
	*/
	/*$(window).on('load', function() {
		window_resize_evnt.call($(window));
	});*/
	
});
/*$(document).ready(function() { 
	// When the user scrolls the page, execute myFunction 
	window.onscroll = function() {myFunction()};

	// Get the navbar
	var navbar = $('header')[0];

	// Get the offset position of the navbar
	var sticky = navbar.offsetTop;

	// Add the sticky class to the navbar when you reach its scroll position. Remove "sticky" when you leave the scroll position
	function myFunction() {
	  if (window.pageYOffset >= sticky) {
	    navbar.classList.add("sticky")
	  } else {
	    navbar.classList.remove("sticky");
	  }
	}
});*/
/*$(document).ready(function() { 
	window_resize_evnt.call($(window));
});*/
