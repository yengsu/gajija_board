"use strict";

function str_repeat(input, multiplier) {
	  //  discuss at: http://phpjs.org/functions/str_repeat/
	  // original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	  // improved by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
	  // improved by: Ian Carter (http://euona.com/)
	  //   example 1: str_repeat('-=', 10);
	  //   returns 1: '-=-=-=-=-=-=-=-=-=-='

	  var y = '';
	  while (true) {
	    if (multiplier & 1) {
	      y += input;
	    }
	    multiplier >>= 1;
	    if (multiplier) {
	      input += input;
	    } else {
	      break;
	    }
	  }
	  return y;
	}
function str_replace(search, replace, subject, count) {
	  //  discuss at: http://phpjs.org/functions/str_replace/
	  // original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	  // improved by: Gabriel Paderni
	  // improved by: Philip Peterson
	  // improved by: Simon Willison (http://simonwillison.net)
	  // improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	  // improved by: Onno Marsman
	  // improved by: Brett Zamir (http://brett-zamir.me)
	  //  revised by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
	  // bugfixed by: Anton Ongson
	  // bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	  // bugfixed by: Oleg Eremeev
	  //    input by: Onno Marsman
	  //    input by: Brett Zamir (http://brett-zamir.me)
	  //    input by: Oleg Eremeev
	  //        note: The count parameter must be passed as a string in order
	  //        note: to find a global variable in which the result will be given
	  //   example 1: str_replace(' ', '.', 'Kevin van Zonneveld');
	  //   returns 1: 'Kevin.van.Zonneveld'
	  //   example 2: str_replace(['{name}', 'l'], ['hello', 'm'], '{name}, lars');
	  //   returns 2: 'hemmo, mars'

	  var i = 0,
	    j = 0,
	    temp = '',
	    repl = '',
	    sl = 0,
	    fl = 0,
	    f = [].concat(search),
	    r = [].concat(replace),
	    s = subject,
	    ra = Object.prototype.toString.call(r) === '[object Array]',
	    sa = Object.prototype.toString.call(s) === '[object Array]';
	  s = [].concat(s);
	  if (count) {
	    this.window[count] = 0;
	  }

	  for (i = 0, sl = s.length; i < sl; i++) {
	    if (s[i] === '') {
	      continue;
	    }
	    for (j = 0, fl = f.length; j < fl; j++) {
	      temp = s[i] + '';
	      repl = ra ? (r[j] !== undefined ? r[j] : '') : r[0];
	      s[i] = (temp)
	        .split(f[j])
	        .join(repl);
	      if (count && s[i] !== temp) {
	        this.window[count] += (temp.length - s[i].length) / f[j].length;
	      }
	    }
	  }
	  return sa ? s : s[0];
	}
"use strict";
$(function () {
//$(document).ready(function(){
	
	/*var id = $('.writeComments').find( '.wys' ).attr('id');
	var editor1 = $('.writeComments').find( '.wys' ).ckeditor(CKEDITOR_config);
	CKEDITOR.config.height = 200;
	CKEDITOR.config.width = 'auto';
	//{? Doc["Action"]=="update"}CKEDITOR.config.startupMode = 'source';{/}
	CKEDITOR.dtd.$removeEmpty['code'] = false;
	CKEDITOR.instances[id].on('instanceReady', function(event) {
		  CKEDITOR.instances[id].focus();
	});*/
});
//$(document).ready(function(){
	
	
	//###################################################################
	//var test = function(){};
	var dgx_cts = {
		reply_margin : 20,
		container : null,
		serial : null,
		act : null,
		is_mobile : false,
		pagination : {
			page : 1,
			last : 0
		},
		//전체 입력폼
		writeEachComments : "wComments",
		
		writeEle : {
				btn : $('.btnWriteComments'), // 신규 글쓰기폼 활성화 시키는 버튼
				area : $('.writeComments') // 신규 글쓰기폼 영역 
		},
		
		init : function( option ){
			
			var opt = {
				'display' : 'scroll',
				'pagination' : {
					page : 1,
					last : 0
				}
			} ;
			if(option) $.extend(opt, option ) ;
			
			if(opt.pagination.page) this.pagination.page = opt.pagination.page ;
			if(opt.pagination.last) this.pagination.last = opt.pagination.last ;
			
			this.start.action();
			//if( !kind || kind == 'scroll') pageScroll();
			
			switch(opt.display)
			{
				case "page" : 
					this.display.page.action() ;
					break ;
					
				case "scroll" :
				default :
					this.display.scroll.action() ;
			} 
		},
		/**
		* @param data ( url, params )
		* @param callback ( function )
		*/
		request : function (data, callback, act, dataType){
			if(!data) return false ;
			$.ajax({
				url : data.url,
				type : 'POST',
				dataType : dataType|| 'json',
				data : data.params || null,
				beforeSend : function(xhr, settings){	// 기본적으로 생성,수정 처리시 표시
					//$('#status-progress').html("<span style='color:red;'> 처리중...</span>") ;
				}, 
				complete: function(e){

					if(callback && callback !== undefined) {
						if(dataType == 'html') callback(act, e.status, e.responseText) ;
						else callback(act, e.status, e.responseJSON) ; 
					}
				}
			})
			/* .always(function (a, textStatus, b) {
					b.onreadystatechange = null; 
					b.abort = null; 
					b = null;
					
				})
				.done(function(r){
					//$('#loading-progress').css('display','none');
					r.onreadystatechange = null; 
					r.abort = null; 
					r = null;
				}) */
					.fail(function (jqXHR, textStatus, errorThrown) {
						//alert("Failed: " + errorThrown);
						/*
						if(!errorThrown) var msg="다시 시도해주세요.";
						else var msg = errorThrown ;
						alert("Failed: "+msg);
						*/
						if(errorThrown){
							
							try {
								errorThrown = decodeURIComponent( errorThrown );
							} catch(e) {
							}
							alert("Failed: "+errorThrown);
						}
						//$('#loading-progress').css('display','none');
						jqXHR.onreadystatechange = null; 
						jqXHR.abort = null; 
						jqXHR = null;
					});
		},
		/**
		* form데이타 추출
		*
		* @param element container ( form 영역 element )
		* @return object ( form 데이타 )
		*/
		getFormData : function(container)
		{
			var param = {};
			container.find('input, textarea').each(function() {
				switch(this.type) {
					case 'checkbox':
	            		if ( !$(this).is(":checked") ) break ;
		            case 'text':
		            case 'hidden':
		            case 'textarea':
		            	param[this.name]=$(this).val() ;
				}
			});
			return param ;
		},
		getMatches : function(string, regex, index) {
			index || (index = 1); // default to the first capturing group
			var matches = [];
			var match;
			while (match = regex.exec(string)) {
				//matches.push(match[0].replace( /^\s+|\s+/g,'')) ;	//ie7 bug :trim() //matches.push(match[0].trim()) ;
				matches.push(match[0].trim()) ;
			}
			return matches;
		},
		addStyleAttribute : function(element, styleAttribute) {
			var ele_style = '';
			if(typeof element.attr('style') !== 'undefined') var ele_style = element.attr('style');
			element.attr('style', ele_style + '; ' + styleAttribute);
			//console.log(ele_style + '; ' + styleAttribute) ;
		},
		progressbar_resume : function()
		{
			var selfBlock = dgx_cts.container.closest('[class^="wComments_block_"]') ;
			if(!selfBlock.length) selfBlock = dgx_cts.container ;
			selfBlock.css('position','relative');
			selfBlock.append(
					'<div class="status-progress text-center" style="background: rgba(206, 203, 203, 0.5);position: absolute;width: 100%;height: 100%;top: -10px;left: 0;padding: 4%;">' +
					'<i class="fa-2x fas fa-spinner fa-spin" style="text-align: center;"></i>' +
					'</div>'
					) ;
		},
		progressbar_stop : function()
		{
			var selfBlock = dgx_cts.container.closest('[class^="wComments_block_"]') ;
			if(!selfBlock.length) selfBlock = dgx_cts.container ;
			selfBlock.css('position','static');
			selfBlock.find('.status-progress').remove();
		},
		/**
		* 요청정보 가공 [ url, request 데이타 ]
		*
		* @param object param( request data )
		*           param{
		*				act : 'writeComments, updateComments, deleteComments'
		*				qty : 추가 파라미터 ( '&파라미터=값&파라미터=값.....')
		*			}
		* @retturn object { url: ??, params: ?? }
		*/
		data : function(param)
		{
			return param.act ? {
						'url' : baseURL+ '/'+param.act + baseURLadd + (param.qty||''), 
						'params' : param || null
					} : false ;
		},
		trash_init : function()
		{
			// 추가, 수정시 작성폼 영역 모두 초기화
			$('[id^="'+this.writeEachComments+'"]') 
				.empty()
					.hide() ;
			
			//put_area.show(); // 입력폼 노출 
			this.container.show(); // 입력폼 노출
			this.writeEle.btn.show();
			this.writeEle.area.hide();
		},
		/**
		* Back-end 요청 & 응답
		*
		* @param string act ( writeComments, updateComments, deleteComments)
		* @param element container ( form-area or print-area)
		* @param integer serial ( idx )
		* @return void
		*/
		put : function(container, act, serial )
		{
			//if(!serial) return false;
			this.serial = serial ;  
			this.container = container ;
			
			this.progressbar_resume();
			
			this.request(
					this.data(
							this.getFormData( this.container )
						),
					this.output_put,
					act
			) ;
			
		},
		get : function(container, serial, act)
		{
			//console.log('tttt', container, serial, act);
			if(!serial) return false; 
			
			this.serial = serial ;  
			this.container = container ;
			
			this.progressbar_resume();
			
			this.request(
					this.data(
							this.getFormData( this.container )
						),
					this.output_get,
					act
			) ;
		},
		gets : function(container, act, callback)
		{
			//if(!serial) return false; 
			
			this.container = container ;
			this.request(
					this.data(
							//this.getFormData( this.container )
							{
								'act' : act,
								'cpage' : this.pagination.page 
							}
						),
					callback,
					act
					//,'html'
			) ;
		},
		/**
		* 데이타 호출
		* @param element container (Element Block) 
		* @param int serial
		* @param string act ( action )
		* @param function callback(act, status, res)
		*/
		getData : function(container, serial, act, callback)
		{
			if(!serial) return false; 
			
			this.serial = serial ;  
			this.container = container ;
			this.request(
					this.data(
							//this.getFormData( this.container )
							{'act':act, 'serial':serial}
						),
					callback,
					act
			) ;
		},
		set_evnt : function(container)//, serial, act)
		{
	
			var thisBlock = $(this);//$($(this).html()) ;
			
			// 버튼 - 답글,수정,삭제
			thisBlock.find('a[class^="wComments"]').bind('click', dgx_cts.start.wComments_handler) ;
			
			/* $(thisBlock).find('a[class^="wComments"]').each(function(i, block) {
				$(this).bind('click', wComments_handler) ;
			}) ; */
			
			//source hightlight
			 /* if( thisBlock.find('pre code').length )
			{
				$(thisBlock).find('pre code').each(function(i, block) {
					 hljs.highlightBlock(block);
				});	
			} */
			//return thisBlock;
			//저장 버튼 클릭시 저장 & 출력 리스트에 저장된 글 삽입
			//dgx_cts.btnComments_handle(container, serial, act) ;
		},
		/**
		* 리스트 추가 : 저장된 데이타 출력
		* @param integer status (응답 처리코드)
		* @param object data ( 응답결과 데이타)
		* @return void
		*/
		output_put : function(act, status, res)
		{
			dgx_cts.progressbar_stop();
			
			if(status == 200 && res)
			{
				var container = dgx_cts.container ; // 추가,수정하려는 글의 작성폼 출력영역
				var serial = dgx_cts.serial ;
				//var act = $(container).find('[name="act"]').val() ;

				dgx_cts.trash_init();
				
				// 업데이트 후 기존글을 바뀐글로 교체
				if(act == 'updateComments')
				{
						$('.memo'+serial)
							.empty()
								.append( res.memo ) ;
						
						$('pre code').each(function(i, block) {
							hljs.highlightBlock(block);
						});
						
						var block = $(container).closest('[class^="wComments_block"]');
						
						block.attr('data-sec', res.sec);

						if(res.sec) block.find('.sec-ico').show();
						else block.find('.sec-ico').hide();
						
						block.find('.regdate').text(res.regdate) ;
						block.find('.regtime').text(res.regtime) ;
						block.find('.elapsed_days').text(res.elapsed_days) ;
						
						//.append( res.memo.replace(/\n/g, '<br>') ) ;
						//.append( str_repeat("==>", data.indent ) + data.memo ) ;
						//$('.memo'+serial).focus() ;
				}
				else if(act == 'deleteComments')
				{
						$(container).parents('[class^="wComments_block"]').eq(0).remove();
				}
				// 저장후 출력 리스트에 추가
				else if(act == 'writeComments')
				{
						$('[id^="'+dgx_cts.writeEachComments+'"]').empty().hide() ;
						
						var pasteContainer = null, 
						tpl_element =  null,
						addClass = '',
						css_style = '' ;
						
						if( parseInt(res.data.indent) > 0)
						{
							tpl_element = $('.tpl_' + container.attr('data-outputTpl') ).eq(0).clone() ; // 저장 후 출력될 자료 템플릿
						}else{
							tpl_element = $('.tpl_commentsOutput').clone() ; // 저장 후 출력될 자료 템플릿
						}
						//var tpl_element = $('.tpl_' + container.attr('data-outputTpl') ) ; // 저장 후 출력될 자료 템플릿

						//답변글일 경우 왼쪽여백조정
						/*if( parseInt(res.data.indent) == 0) css_style = "border-left:3px solid rgba(108, 167, 215, 0.42);padding-left:10px;" ;
						else if( parseInt(res.data.indent) < 5) css_style = "margin-left:" + (parseInt(res.data.indent) * dgx_cts.reply_margin) + 'px;' ;
						else 	css_style = "margin-left:" + (5 * dgx_cts.reply_margin) + 'px;' ;*/
						
						//모바일이면 indent 깊이(왼쪽여백)
						if( dgx_cts.is_mobile )
						{
							if( parseInt(res.data.indent) == 0) css_style = "border-left:3px solid rgba(108, 167, 215, 0.42);padding-left:10px;" ;
							else 	addClass = "m-l-25" ;
						}
						// 데스크톱이면
						else{
							if( parseInt(res.data.indent) == 0) css_style = "border-left:3px solid rgba(108, 167, 215, 0.42);padding-left:10px;" ;
							else if( parseInt(res.data.indent) < 5) addClass = "m-l-" + (parseInt(res.data.indent) * dgx_cts.reply_margin) ;
							else 	addClass = "m-l-" + (5 * dgx_cts.reply_margin) ;
						}
						
						
						// indent가 0인경우 왼쪽에 선표시
						//if(res.data.indent == 0) margin_indent += "border-left:3px solid #6CA7D7;" ;
						if( ! res.data.sec ) tpl_element.find('.sec-ico').hide();
						tpl_element.attr('data-sec', res.data.sec);
						
						var tpl = tpl_element.html() ;
						tpl = str_replace("[serial]", res.data.serial, tpl) ;
						tpl = str_replace("[family]", res.data.family, tpl) ;
						tpl = str_replace("[parent]", res.data.parent, tpl) ;
						tpl = str_replace("[userlev]", res.data.lev_ico, tpl) ;
						tpl = str_replace("[usernick]", res.data.usernick, tpl) ;
						
						tpl = str_replace("[profile_not_photo]", res.data.profile_not_photo, tpl) ;
						tpl = str_replace("[profile_photo]", res.data.profile_photo, tpl) ;
						
						/* if(res.data.profile_photo) tpl = str_replace("[profile_photo]", res.data.profile_photo, tpl) ;
						else{
							var profile_noimage = tpl_element.find('.profile-photo').attr('data-noimage'); //[profile_photo]
							tpl = str_replace("[profile_photo]", res.profile_noimage, tpl) ;
						} */
						
						//tpl = tpl.replace("[memo]", str_repeat("==>", data.indent ) + data.memo) ;
						tpl = str_replace("[indent]", res.data.indent, tpl) ;

						tpl = str_replace( "[addClass]", addClass, tpl) ;
						tpl = str_replace( "[css_style]", css_style, tpl) ;
						tpl = str_replace("[memo]", res.data.memo, tpl) ;
						tpl = str_replace("[regdate]", res.data.regdate, tpl) ;
						tpl = str_replace("[regtime]", res.data.regtime, tpl) ;
						tpl = str_replace("[elapsed_days]", res.data.elapsed_days, tpl) ;
						
						// 출력영역에 응답받은 데이타 출력
						//$('.memo'+serial).parent().append( tpl ) ;

						//결과 데이타 출력
						
						$(tpl).find('[data-action="readComments"],[data-action="deleteComments"]').remove();

						//$(tpl).find('a[class^="wComments"]').bind('click', wComments_handler) ;
						if( parseInt(res.data.indent) > 0)
						{
							if($('.wComments_block_'+res.data.family+'_'+res.last.serial).length > 0)
								$('.wComments_block_'+res.data.family+'_'+res.last.serial).after(tpl); // 자식노드가 있는경우
							else
								$(container).parents('[class^="wComments_block"]').eq(0).after(tpl); // 자식노드가 하나도 없는경우

						}else{
							$('.writeComments').find('[name="frm_memo"]').val('');
							
							//출력형식이 pagination형 인경우
							if($('.comments_list').find(".pagination-block").length){
								var pagination_block = $('.comments_list').find(".pagination-block").clone(true);
								$('.comments_list').find(".pagination-block").remove();
								$('.comments_list').append(tpl); // 신규등록인경우
								$('.comments_list').append(pagination_block); // 신규등록인경우
								pagination_block = undefined ;
							}
							// 출력형식이 scroll형 인경우
							else{
								$('.comments_list').append(tpl); // 신규등록인경우
							}
						}
						$('pre code').each(function(i, block) {
							hljs.highlightBlock(block);
						});
						
						//버튼 - 답글,수정,삭제
						$('.comments_list').find('a[class^="wComments"]').unbind('click').bind('click', dgx_cts.start.wComments_handler) ;
						
						/*var block = $('.comments_list').find('[class^="wComments_block"]').last();
						block.attr('data-sec', res.data.sec);
						if(res.data.sec) block.find('.sec-ico').show();
						else block.find('.sec-ico').hide();*/
						
						// 버튼 handler 추가
						//$('.memo'+serial).find('a[class^="wComments"]').bind('click', wComments_handler) ;
						//$(container).parents('.wComments_block').next().find('a[class^="wComments"]').bind('click', wComments_handler) ;
						//console.log('next', $(container).parents('.wComments_block').next());
						
						dgx_cts.writeEle.area.hide();
						dgx_cts.writeEle.btn.show();
				}
				
			}else{
				//$('[id^="'+writeEachComments+'"]').hide();
				dgx_cts.writeEle.area.hide();
				dgx_cts.writeEle.btn.show();
			}
			//writeEle.btn.hide();
			//writeEle.area.show();
			/* writeEle.area.hide();
			writeEle.btn.show(); */
			
			
		},
		
		/**
		* Form 문 : 저장된 데이타 출력
		* @param integer status (응답 처리코드)
		* @param object data ( 응답결과 데이타)
		* @return void
		*/
		output_get : function(act, status, data)
		{
			dgx_cts.progressbar_stop();

			if(status == 200)
			{
				if(!data){
					alert('해당 데이타가 삭제되었거나 존재하지 않습니다.');
					location.replace(document.URL);
					return ;
				}
				
				var container = dgx_cts.container ;
				var serial = dgx_cts.serial ;

				dgx_cts.trash_init();
				//$(container).find('[name="act"]').val("updateComments") ;
				
				// form hidden 추가
				container.append( 
						$('<input/>').attr({type : 'hidden', name : 'act', value : act}),
						$('<input/>').attr({type : 'hidden', name : 'serial', value : parseInt(data.serial) }) 
				) ;

				var tpl_element = $('.tpl_' + container.attr('data-formTpl') ) ; // 저장 후 출력될 자료 템플릿
				var tpl = tpl_element.html();
				
				container.append( tpl ) ;
				
				var block = $(container).closest('[class^="wComments_block"]');
				block.attr('data-sec', data.sec);
				if(data.sec) $(block).find('.sec-ico').show();
				else $(block).find('.sec-ico').hide();
				
				block.find('.memo'+serial).html(data.memo);
				$('pre code').each(function(i, block) {
					hljs.highlightBlock(block);
				});
				block.find('.regdate').text(data.regdate) ;
				block.find('.regtime').text(data.regtime) ;
				block.find('.elapsed_days').text(data.elapsed_days) ;

				if(data.sec==1) $(container).find('[id^="frm_sec"]').prop('checked', true) ;
				$(container).find('[id^="frm_memo"]').val(data.memo) ; // 메모 입력란
				
				if(act=="writeComments") $(container).find('.btnComments').text('답글달기'); // 버튼
				else if(act=="updateComments") $(container).find('.btnComments').text('수정하기'); // 버튼
				
				//$(container).find('.btnComments-close').unbind('click').bind('click', function(e){ $('[id^="'+writeEachComments+'"]').empty().hide() ; }); // 버튼
				dgx_cts.btnComments_handle(container, serial, 'updateComments');
				
				dgx_cts.wys(container);
				$(container).find('[id^="frm_memo"]').focus();
			}
		},
		/**
		* 위지윅 에디터 적용
		*/
		wys : function(container)
		{
			//console.log($(dgx_cts.container));
			var id = $(container).find( '.wys' ).attr('id');
			var editor1 = $(container).find( '.wys' ).ckeditor(CKEDITOR_config);
			CKEDITOR.config.height = 200;
			CKEDITOR.config.width = '100%';//'auto';
			//CKEDITOR.config.startupMode = 'source';
			//CKEDITOR.dtd.$removeEmpty['code'] = false;
			CKEDITOR.instances[id].on('instanceReady', function(event) {
				  CKEDITOR.instances[id].focus();
			});
		},
		btnComments_handle : function(container, serial, act)
		{
			if(!container || !serial) return false;

			//저장 버튼 클릭시
			$(container).find('.btnComments').unbind('click').bind('click', function(e)
			{
				(e.preventDefault) ? e.preventDefault() : e.returnValue = false; 
				
				dgx_cts.put(container, act, serial) ;
			});
			// 닫기 버튼
			$(container).find('.btnComments-close').unbind('click').bind('click', function(e){ $('[id^="'+dgx_cts.writeEachComments+'"]').empty().hide() ; }); // 버튼
		},
		/**
		 * 댓글 출력형식 (page, scroll)
		 */
		display : {
			/**
			 * Pagination으로 출력
			 */
			page : {
				
				action : function(){
					if($('.comments_list .pagination').find('a').length) $('.comments_list .pagination').find('a').bind('click', this.pagination_handler);
				},
				pagination_handler : function(e){
					(e.preventDefault) ? e.preventDefault() : e.returnValue = false; 

					// progressbar start
					$('.comments_list')
						.append(
							$('<div/>',{
								id:'comments-progress',
								'style' : 'position:relative;top:0;'
							})
								.append(
										$('<div/>',{
											//id:'comments-progress',
											'class' : 'fa-3x text-center',
											'style' : 'position:absolute;left:0;top:-120px;z-index:1000;width:100%;background: rgba(204, 204, 204, 0);'
										})
											.append('<i class="fas fa-spinner fa-spin text-dark"></i>')
								)
						);
					var container = $('.comments_list') ;
					dgx_cts.pagination.page = $(this).data('page') ;
					dgx_cts.gets(container, 'Req_getComments', function(act, status, res){
						
						$('#comments-progress').remove();
					
						dgx_cts.set_evnt.call(res.datas);//, serial, 'writeComments') ;
					
						$(dgx_cts.container).empty().append(res.datas) ;
						$('.comments_list .pagination').find('a').unbind('click').bind('click', dgx_cts.display.page.pagination_handler)
						//------------------------
						//전체적용
						//------------------------
						//source hightlight
						if( $(dgx_cts.container).find('pre code').length )
						{
							$('pre code').each(function(i, block) {
								 hljs.highlightBlock(block);
							});	
						}
						$('a[class^="wComments"]').unbind('click').bind('click', dgx_cts.start.wComments_handler) ;
						//------------------------
													
					}) ;
				}
			},
			/**
			 * 스크롤로 코멘트 출력
			 */
			scroll : {
				action : function(){
					$(window).scroll(this.comments_load);
				},
				comments_load : function(){
					
					//console.log($(window).height(), $('footer').height());
					
					var docHeight = $(document).height() - $('footer').height();
					 
					//if($(window).scrollTop() + $(window).height() >= $(document).height()){
					if($(window).scrollTop() + $(window).height() >= docHeight)
					{
						//$('.comments_list')
						var container = $('.comments_list') ;
						
						//if(!dgx_cts.pagination.last || (dgx_cts.pagination.page < dgx_cts.pagination.last))
						if( dgx_cts.pagination.last > dgx_cts.pagination.page )
						{
							$(window).off('scroll');
							
							// progressbar start
							$('.comments_list').append(
									$('<div/>',{
										id:'comments-progress',
										'class' : 'fa-3x text-center'	
									})
										.append('<i class="fas fa-spinner fa-spin text-secondary"></i>')
									);
							
							//if(!dgx_cts.pagination.last || dgx_cts.pagination.last>1) dgx_cts.pagination.page++; //$('.comments_list').attr('data-page', dgx_cts.pagination.page++) ;
							if( dgx_cts.pagination.last > 1 ) dgx_cts.pagination.page++; //$('.comments_list').attr('data-page', dgx_cts.pagination.page++) ;
										
							dgx_cts.gets(container, 'Req_getComments', function(act, status, res){
									
									$('#comments-progress').remove();
									//$('.comments_list').attr('data-page', dgx_cts.pagination.page) ;
									//dgx_cts.pagination.page = parseInt(res.paging.current)+1 ;
									dgx_cts.pagination.last = res.paging.last.num;
								
									dgx_cts.set_evnt.call(res.datas);//, serial, 'writeComments') ;
								
									//res.paging.last.num
									$(dgx_cts.container).append(res.datas) ;
									
									//------------------------
									//전체적용
									//------------------------
									//source hightlight
									if( $(dgx_cts.container).find('pre code').length )
									{
										$('pre code').each(function(i, block) {
											 hljs.highlightBlock(block);
										});	
									}
									$('a[class^="wComments"]').unbind('click').bind('click', dgx_cts.start.wComments_handler) ;
									//------------------------
									$(window).scroll(dgx_cts.display.scroll.comments_load);
							}) ;
						}
						else{
							$(window).off('scroll');
						}
						//console.log('끝');
					}
				}
			}// end scroll
			
			
		},
		start : {
				
				action : function(){
					
					//전체 입력폼
					//var writeEachComments = "wComments" ; 
					//-------------------------------------------------------------------
					
					dgx_cts.writeEle = {
						btn : $('.btnWriteComments'), // 신규 글쓰기폼 활성화 시키는 버튼
						area : $('.writeComments') // 신규 글쓰기폼 영역 
					},
					
					// 신규글쓰기 폼 - 활성화 버튼
					dgx_cts.writeEle.btn.bind('click', function(e){
						(e.preventDefault) ? e.preventDefault() : e.returnValue = false; 
						console.log('xxxx', dgx_cts.writeEle.area);
						if(dgx_cts.writeEle.area.css('display')=='none')
						{
							dgx_cts.writeEle.area.show(); // 입력폼 Block show
							
							$('[id^="'+dgx_cts.writeEachComments+'"]').empty().hide() ; // 입력폼 초기화
							
							dgx_cts.wys(dgx_cts.writeEle.area);
							
							dgx_cts.writeEle.btn.hide();
						}
						else{
							dgx_cts.writeEle.btn.show();
						}
					});
					
					dgx_cts.writeEle.area.find('.btnComments').bind('click', function(e){
						(e.preventDefault) ? e.preventDefault() : e.returnValue = false; 
						
						var act = dgx_cts.writeEle.area.find('input[name="act"]').val() ;
						
						dgx_cts.put( $(dgx_cts.writeEle.area), act) ;
					});
					
					// 버튼 Event
					$('a[class^="wComments"]').bind("click", this.wComments_handler) ;
				},
				//-------------------------------------------------------------------
				wComments_handler : function (e)
				{
						(e.preventDefault) ? e.preventDefault() : e.returnValue = false; 
						
						//CKEDITOR.instances.frm_memo.focus();
						var RegEx = /(?:^|\s)wComments-(.*?)(?:\s|$)/g;
						var thisClassName = dgx_cts.getMatches( this.className, RegEx, 1)[0];
						
						var act = this.getAttribute('data-action') ; // 
						var put_area = $('#'+thisClassName) ;//입력폼이 출력될 엘리먼트
						var put_area_isDisplay = put_area.css('display') ; // 입력폼 노출했는지
						var put_area_action = (put_area.find('input[name="act"]').length) ? put_area.find('input[name="act"]').val() : '';
						var tpl_form = $('.tpl_' + put_area.attr('data-formTpl') ) ; // 입력폼 템플릿
						var tpl_output = $('.tpl_' + put_area.attr('data-outputTpl') ) ; // 저장 후 출력될 자료 템플릿
	
						//$('[id^="'+writeEachComments+'"]').empty().hide() ;	// 입력폼 출력될 공간 초기화
						
						// 추가, 수정시 작성폼 영역 모두 초기화
						/* $('[id^="'+writeEachComments+'"]') 
							.empty()
								.hide() ; */
						var serial = thisClassName.split('-').pop() ; // idx 추출
	
						//추가
						if(act == 'addComments')
						{
							if( put_area_isDisplay != 'none' && dgx_cts.act == act)
							{
								$('[id^="'+dgx_cts.writeEachComments+'"]').empty().hide() ;	
								dgx_cts.writeEle.btn.show();
								return ;
							}
							else{
								dgx_cts.writeEle.btn.hide();
							}
							dgx_cts.act = act ;
							
							// 데이타 가져오기 & 편집폼 노출
							//dgx_cts.get(put_area, serial, 'readComments') ;
							dgx_cts.getData(put_area, serial, 'readComments',
								function(act, status, data){
										//dgx_cts.output_get.call('writeComments', status, data);
										//return ;
										
										if(status != 200) return false ;
										
										if(!data){
											alert('해당 게시글은 삭제되어 답변할 수 없습니다.');
											location.replace(document.URL);
											return false;
										}
										
										//-----------------------------------
										var block = $(dgx_cts.container).closest('[class^="wComments_block"]');
										if(block.data('sec') ==1) put_area.find('input[name="frm_sec"]').prop('checked',true);
										
										block.attr('data-sec', data.sec);
										if(data.sec) $(block).find('.sec-ico').show();
										else $(block).find('.sec-ico').hide();
										
										block.find('.memo'+serial).html(data.memo);
										block.find('.regdate').text(data.regdate) ;
										block.find('.regtime').text(data.regtime) ;
										block.find('.elapsed_days').text(data.elapsed_days) ;
										//-----------------------------------
										
										if( put_area_action == "writeComments" )
										{
											// 숨기기
											if( put_area_isDisplay != 'none' )
											{
												dgx_cts.writeEle.btn.show();
												return ;
											}
											else{
												dgx_cts.writeEle.btn.hide();
											}
										}
										else{
											dgx_cts.writeEle.btn.show();
										}
										
										dgx_cts.container = put_area;
										
										dgx_cts.trash_init();
										
										put_area.append( tpl_form.html() ) ;
										$('pre code').each(function(i, block) {
											hljs.highlightBlock(block);
										});
										dgx_cts.wys(put_area);
										/* 
										
										// form hidden 추가
										put_area.append( 
												$('<input/>').attr({type : 'hidden', name : 'act', value : "readComments"}),
												$('<input/>').attr({type : 'hidden', name : 'serial', value : parseInt(serial) }) 
										) ;
										put_area.append( tpl_form.html() ) ;
										dgx_cts.container = put_area ;
										
										// 데이타 가져오기 & 편집폼 노출
										dgx_cts.get(put_area, serial, 'writeComments') ;
										 */
										//put_area.find('.btnComments').text('답글달기'); // 버튼
										
										// form hidden 추가
										put_area.append( 
												$('<input/>').attr({type : 'hidden', name : 'act', value : "writeComments"}),
												$('<input/>').attr({type : 'hidden', name : 'serial', value : parseInt(serial) }) 
										) ;
										
										//저장 버튼 클릭시 저장 & 출력 리스트에 저장된 글 삽입
										dgx_cts.btnComments_handle(put_area, serial, 'writeComments') ;
								}) ;
						}
						//읽기
						else if(act == 'readComments')
						{
							
							var block = $(this).closest('[class^="wComments_block"]');
							if( put_area_action == "updateComments" )
							{
								if( put_area_isDisplay != 'none' && dgx_cts.act == act)
								{
									$('[id^="'+dgx_cts.writeEachComments+'"]').empty().hide() ;	
									dgx_cts.writeEle.btn.show();
									return ;
								}
								else{
									dgx_cts.writeEle.btn.hide();
								}
							}
							dgx_cts.act = act;
							
							// form hidden 추가
							put_area.append( 
									$('<input/>').attr({type : 'hidden', name : 'act', value : act}),
									$('<input/>').attr({type : 'hidden', name : 'serial', value : parseInt(serial) }) 
							) ;
							// 데이타 가져오기 & 편집폼 노출
							dgx_cts.get(put_area, serial, 'updateComments') ;
							//dgx_cts.gets(put_area, serial, 'Req_getComments') ;
						}
						//삭제
						else if(act == 'deleteComments')
						{
							if (!confirm('정말 삭제 하시겠습니까?')) return;
							// form hidden 추가
							put_area.append( 
									$('<input/>').attr({type : 'hidden', name : 'act', value : act}),
									$('<input/>').attr({type : 'hidden', name : 'serial', value : parseInt(serial) }) 
							) ;
							dgx_cts.put(put_area, act, serial) ;
						}
				}
			
			
		}
	};
//###################################################################
		
