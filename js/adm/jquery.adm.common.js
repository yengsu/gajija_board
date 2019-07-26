/*var servRequest = function(type, dataType, params, callback){

	var requestURL = null ;
	requestURL = baseURL+'/'+type ;
	//setTimeout(function() {
	OFUNC.Request_ajax({
					url : requestURL,
					type : 'POST',
					dataType : dataType,
					data : params,
					beforeSend : function(xhr, settings){	// 기본적으로 생성,수정 처리시 표시
						//$('.loader').show();
					},
					complete: function(e){
						//$('.loader').hide();
						if(e.readyState == 4) {}
						//console.log(e.responseJSON);
						if(callback !== undefined) callback(e.responseJSON) ;
					}
	});
	//},300);
};*/
var servRequest = function(type, dateType, params, callback){
	var qs = location.search.substr(location.search.indexOf("?"));
	var requestURL = null ;
	//requestURL = baseURL+'/'+type+qs ;
	
	if(typeof baseURL !== 'undefined' && baseURL)
		requestURL = baseURL+'/'+type+qs ;
	else
		requestURL = type+qs ;
	
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

(function($){
	$.fn.ymodal = ( function (o, callback) {
		//var opts = {attr: 'src', over:'over', out:'out', selected_class:'sfixed'};
		var opts = {show: false};
		$.extend({}, o, opts) ;
		if(o == undefined) return false;
		if (typeof(this) == 'object') {
			try {

				var btn_close = $(this).find('.close') ;
				var modal = $('.ymodal') ;
				if(o.show) {
					//-----------------------
					// close 버튼
					btn_close.bind('click', function(e){
						(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
						modal.removeClass('d-flex') ;
					}) ;
					
					$(window).bind('click', function(e) {
					    if (e.target == modal[0]) {
					    	btn_close.triggerHandler('click') ;
					    }
					});
					
					//-----------------------
					modal.addClass('d-flex') ;
				}
				else{
					// close 버튼
					btn_close.unbind('click');
					$(window).unbind('click');
					modal.removeClass('d-flex') ;
					
				}
			}catch(e){}
		}
		//if(typeof(callback) == 'function') callback('yes');
		return this;
	});

	$('.ymodal').ymodal() ;
})(jQuery);
$(function(){
	//-----------------------
	// Scroll 최상단 이동 Bar
	//-----------------------
	$('body').append(
			'<button' 
				//+' class="btn rounded-circle scroll-top"'
				+' class="rounded-circle scroll-top"'
				+' data-scroll="up"'
				+' type="button"'
				+' style="'
					+'border-radius: 50%!important;width: 55px;height: 55px;position: fixed;bottom: 80px;right: 30px;display: none;background-color: rgba(7, 9, 16, 0.35) !important;'
					+'display: inline-block; padding: 7px 9px;margin-right: 4px; border-radius: 3px; border: solid 1px #c0c0c0; background: #e9e9e9; font-size: .875em;font-weight: bold;text-decoration: none;color: #717171;vertical-align: middle;cursor: pointer;'
				+'"'
				
			+'>'
			+'<i class="fa fa-chevron-up" style="display: inline-block;color: #FFFFFF;"></i>'
			+'</button>'
		);
	$(document).scroll(function () {
	    if ($(this).scrollTop() > 100) {
	        $('.scroll-top').fadeIn();
	    } else {
	        $('.scroll-top').fadeOut();
	    }
	});
	
	$('.scroll-top').click(function () {
	    $("html, body").animate({
	        scrollTop: 0
	    }, 100);
	    return false;
	});
	//-----------------------
	
});