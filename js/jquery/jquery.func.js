// ajax 처리 실패했는지 체크 (성공 : true / 실패 :false)
var Response_errorDebug = true ;	

var OFUNC = {
			Request_ajax: function(opts) {
				if("object" !== typeof opts) opts = {};
				return $.ajax( $.extend(true, {
						url : this.HOST_NAME,
						type : 'GET',
						//crossOrigin: true,
						//dataType : 'json',
						//jsonp : 'callback',
						//jsonpCallback: "",
						cache : false,
						aysnc : false, // true(동기), false(비동기)
						data : '',
						beforeSend : function(xhr, settings){	// 기본적으로 생성,수정 처리시 표시
							//$('#status-progress').html("<span style='color:red;'> 처리중...</span>") ;
						}, 
						complete: function(e){
							if(e.status === 200){
								e.onreadystatechange = null; 
								e.abort = null; 
								e = null;
							}else{
								alert(HTTP_STATUS_CODE[e.status]) ;
								e.onreadystatechange = null; 
								e.abort = null; 
								e = null;
								return ;
							}
						}
				},opts))
				.always(function (a, textStatus, b) {
					//b.onreadystatechange = null; 
					//b.abort = null; 
					//b = null;
					
				})
				.done(function(r){
					$('#loading-progress').css('display','none');
					/*r.onreadystatechange = null; 
					r.abort = null; 
					r = null;*/
				})
				.fail(function (jqXHR, textStatus, errorThrown) {
					//alert("Failed: " + errorThrown);
					/*
					if(!errorThrown) var msg="다시 시도해주세요.";
					else var msg = errorThrown ;
					alert("Failed: "+msg);
					*/
					//console.log(jqXHR, textStatus, errorThrown);
					if(errorThrown){
						
						// Debug mode - check
						if( !Response_errorDebug ) return ;
						
						try {
							errorThrown = decodeURIComponent( errorThrown );
						} catch(e) {
						}
						//alert("Failed: "+errorThrown);
						alert(errorThrown);
					}
					$('#loading-progress').css('display','none');
					jqXHR.onreadystatechange = null; 
					jqXHR.abort = null; 
					jqXHR = null;
				});
		},
		callFunction : function(func, fileUrl){
			OFUNC[func](fileUrl);
			//eval("OFUNC."+func+"('"+fileUrl+"')");
		},
		set_attach_main : function(file){
			$('#form_tpl').val(file) ;
		},
		add_fncParam : function(url, param){
			if(typeof param === "string"){
				if(typeof url === "string" ){
					if( url.indexOf('?') > -1 )
						param = param.substr(0,1).indexOf('&') > -1 ? param : '&'+param ;
					else
						param = '?'+param ;
				}
			}
			return param ;
		},
		ued_encode : function(obj){
			return Object.keys(obj).map(function(key){ 
				return encodeURIComponent(key) + '=' + encodeURIComponent(obj[key]); 
			}).join('&');
		},
		getParam : function( sname )
		{
		  var params = location.search.substr(location.search.indexOf("?")+1);
		  console.log(params);
		  var sval = "";
		  params = params.split("&");
		    for (var i=0; i<params.length; i++)
		       {
		         temp = params[i].split("=");
		         if ( [temp[0]] == sname ) { sval = temp[1]; }
		       }
		  return sval;
		},
		// 쿼리스트링형 데이타 => object로 변환
		queryStringToJSON : function(s) {     
			if(s) s = s.substr(s.indexOf("?")+1);
		    if(s) var pairs = s.split('&');
		    else var pairs = location.search.slice(1).split('&');
		    
		    var result = {};
		    pairs.forEach(function(pair) {
		        pair = pair.split('=');
		        result[pair[0]] = decodeURIComponent(pair[1] || '');
		        //result[pair[0]] = pair[1] || '';
		    });
		    return JSON.parse(JSON.stringify(result));
		},
		/**
		* form Elements init & enable|disable 
		* @param element elem ( groupping Element )
		* @param boolean use
		* @param string addType (form type)
		* @param object opt { 
		* 							elem : Element (parent or self)
		* 							disabled : bool, 
		* 							clear : bool, 
		* 							callback : function
		* 						}
		*/
		clear_form_elements : function (opt){
			var default_opt = {
					'elem' : null,
					'disabled' : false,
					'clear' : false,
					'callback' : null,
					'kind' : 'input, select, textarea'
			};
			if(typeof opt !== 'object') return false ;
			if( !$(opt.elem).length ) return false ;
			var Opt = $.extend(default_opt, opt);
			default_opt = opt = null ;
		    $(Opt.elem).find(Opt.kind).each(function() {
		        switch(this.type) {
		            case 'select-multiple':
		            case 'select-one':
		            	if(Opt.clear) $(this).find('option').prop('selected' , false);
		            	$(this).prop('disabled', Opt.disabled);
		            	break ;
		            case 'password':
		            case 'text':
		            case 'textarea':
		            	if(Opt.clear) $(this).val('') ;
		               $(this).prop('disabled', Opt.disabled);
		                break;
		            case 'checkbox':
		            case 'radio':
		            	if(Opt.clear) $(this).prop('checked', false);
		            	$(this).prop('disabled', Opt.disabled);
		                break;
		        }
		        
		        if(typeof Opt.callback === 'function') callback() ;
		        
		        this.setAttribute('autocomplete',"off");  
		    });
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
		evnt_target : function(e){
			if (!e) var e = window.event;
			if (e.target) var targ = e.target;
			else if (e.srcElement) var targ = e.srcElement;
			if (targ.nodeType == 3) // defeat Safari bug
				targ = targ.parentNode;
			
			return targ ;
		},
		skin_imgLoad : function(obj, img){ // 이미지 교체시 호출
			var attributes = null ;
			if(typeof img === 'string') attributes = img ;
			else if(typeof img === 'object') attributes = img ;
			obj.attr(img).load(function() {
				$('<span />').text('loading...').fadeOut(); 
				//$('<span />').text('loading...').fadeOut(); 
			});
		},
		tag_swap : function(obj, parents)
		{
			$(obj).parents(parents).map(function() {   //$(obj).parents('*').map(function() {   
		        //s.push(this.tagName) ;
		      return this.tagName;
		    }).get().join(' ') ;
	  	},
	  	shuffleArray : function(array) {
			for (var i = array.length - 1; i > 0; i--) {
				var j = Math.floor(Math.random() * (i + 1));
				var temp = array[i];
				array[i] = array[j];
				array[j] = temp;
			}
			return array;
		},
		// object find key,value to remove
		removeFunction : function(myObjects,prop,valu)
		{
			return myObjects.filter(function (val) {
				return val[prop] !== valu;
			});
		},
		convertToArrayOfObjects : function(data) {
			var keys = data.shift(),
			i = 0, k = 0,
			obj = null,
			output = [];
		
			for (i = 0; i < data.length; i++) {
				obj = {};
		
				for (k = 0; k < keys.length; k++) {
					obj[keys[k]] = data[i][k];
				}
		
				output.push(obj);
			}
		
			return output;
		},
		convertFormToObject : function(frm){
			return $(frm).serializeArray().reduce(function(result, item, index, array) {
				  result[item.name] = item.value;
				  return result;
			}, {}) ;
		},
		rgb2hex : function (rgb) {
				rgb = rgb.match(/^rgba?[\s+]?\([\s+]?(\d+)[\s+]?,[\s+]?(\d+)[\s+]?,[\s+]?(\d+)[\s+]?/i);
				//return (rgb && rgb.length === 4) ? "0x" +
				return (rgb && rgb.length === 4) ? "#" +
							("0" + parseInt(rgb[1],10).toString(16)).slice(-2) +
							("0" + parseInt(rgb[2],10).toString(16)).slice(-2) +
							("0" + parseInt(rgb[3],10).toString(16)).slice(-2) : '';
		},
		/**
		* input 배열변수 키값추출
		* 
		* ex : fild[13][0] / fildNum[2][0] ....
		* result : [ 'fild', '13', '' ] or  [ 'fildNum', '2', '' ] ...
		* return 13 or 2
		*/
		extractTypeFromInputName : function (name) {
				var keys = name.split('['); // split string into array
				keys = $.map(keys, function (key) { return key.replace(/]/g, ''); }); // remove closing brackets
				if (keys[0] === '') { keys.shift(); }
				return keys[1] ;
		},
		addStyleAttribute : function(element, styleAttribute) {
			var ele_style = '';
			if(typeof element.attr('style') !== 'undefined') var ele_style = element.attr('style');
			element.attr('style', ele_style + '; ' + styleAttribute);
			//console.log(ele_style + '; ' + styleAttribute) ;
		},
		addStringProtocol : function(Str) {
			Str = Str.trim() ;
			if(Str){
				//if (!Str.search(/^http[s]?:\/\//)){
				if (Str.indexOf('http://') === -1 && Str.indexOf('https://') === -1) {
		    		Str = 'http://' + Str;
				}
			}
			//console.log(Str);
			return Str ;
		},
		
		/**
		 * 금액의 콤마추가
		 * @param str
		 * @returns
		 */
		comma : function(str) {
		    //str = String(str);
		    str = str.toString();
		    return str.replace(/(\d)(?=(?:\d{3})+(?!\d))/g, '$1,');
		},
		/**
		 * 금액의 콤마제거
		 * @param str
		 * @returns
		 */
		uncomma : function(str) {
		    str = String(str);
		    return str.replace(/[^\d]+/g, '');
		},
		numComma : function(num) {
			num = num.toString().replace( /^\s+|\s+/g,'') ;
			var parts = num.toString().split(".");
		    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
		    return parts.join(".");
		},
		/**
		 * integer float check
		 * @uses 정확히 숫자를 입력해야함. 문자열은 false 반환
		 * @deprecated (사용 금지)
		 */
		isNumber : function(str){
			//return /^[0-9]+(\.)?[0-9]$/.test(str) ;
			//return /^[0-9]+$/.test(str) ;
			return typeof value === 'number' && isFinite(value);
		},
		/**
		 * is Numeric check
		 * 
		 * @return bool
		 * 
		 * @example true : "-10", "0", 0xFF, "0xFF", "8e5", "3.1415", +10, 0144
		 * @example false : "-0x42", "7.2acdgs","",{},NaN, null, true, Infinity, undefined
		 */
		isNumeric : function(n) {
			 return !isNaN(parseFloat(n)) && isFinite(n);
		},
		/**
		 * Array check
		 * 
		 * @return bool
		 */
		isArray : function(arr) {
			return Object.prototype.toString.call( arr ) === '[object Array]' ;
		},
		//is_numeric_event.call(this,e);
		/**
		 * only number - input event
		 * 
		 * @param event e
		 */
		is_numeric_event : function(e){
			var keyCode = e.keyCode ? e.keyCode : e.which;
		    -1!==$.inArray(keyCode,[46,8,9,27,13,110,190])||/65|67|86|88/.test(keyCode)&&(!0===e.ctrlKey||!0===e.metaKey)||35<=keyCode&&40>=keyCode||(e.shiftKey||48>keyCode||57<keyCode)&&(96>keyCode||105<keyCode)&&e.preventDefault();
		},
		/**
		 * 터치이벤트 가능한 디바이스인지
		 * 
		 * @return bool
		 */
		isTouchDevice : function() {
			return 'ontouchstart' in document.documentElement;
		}
	};

	if (!String.prototype.trim) {
	  String.prototype.trim = function () {
	    return this.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, '');
	  };
	}
	/*if(typeof String.prototype.trim !== 'function') {
	  String.prototype.trim = function() {
	    return this.replace(/^\s+|\s+$/g, '');
	  }
	}*/
	
	/**
	* 모두체크 or 모두해제
	*/
	$.fn.checkd = function(callback, ele){
		
	    var flag = null;
	    if($(this).find(':checkbox:eq(0)').is(':checked')) flag = true;
	    else flag = false ;

	    var checkEle = null ;
	    if( typeof ele !== 'undefined' ) checkEle = ele ;
	    else checkEle = ':checkbox[name^="toggle["]' ;
	    //else checkEle = ':checkbox:gt(0)' ; // checkbox All
	    
	    $(this).find(checkEle).each(function(){
	        this.checked = flag;
	        if(typeof callback=== 'function') callback(this, flag);
	    });
	}
	 /*==========================
	 태그제거
	 ==========================*/
	var StringFilter = function(text)
	{
			text = text.replace(/<br>/ig, "\n"); // <br>을 엔터로 변경
			text = text.replace(/&nbsp;/ig, " "); // 공백      
			// HTML 태그제거
			text = text.replace(/<(\/)?([a-zA-Z]*)(\s[a-zA-Z]*=[^>]*)?(\s)*(\/)?>/ig, "");
			
			text = text.replace(/<(no)?script[^>]*>.*?<\/(no)?script>/ig, "");
			text = text.replace(/<style[^>]*>.*<\/style>/ig, "");
			text = text.replace(/<(\"[^\"]*\"|\'[^\']*\'|[^\'\">])*>/ig, "");
			text = text.replace(/<\\w+\\s+[^<]*\\s*>/ig, "");
			text = text.replace(/&[^;]+;/ig, "");
			text = text.replace(/\\s\\s+/ig, "");
			text = text.replace(/\\n+/ig, "");
			text = text.replace(/\\r+/ig, "");
			text = text.replace(/\\rn+/ig, "");
			//text = text.replace(/\s{2,}/g, " "); // space 2개이상을 한개로
			//text = text.trim();
			return text;
	};
	var newWindow = function(a_str_windowURL, a_str_windowName, a_int_windowWidth, a_int_windowHeight, a_bool_scrollbars, a_bool_resizable, a_bool_menubar, a_bool_toolbar, a_bool_addressbar, a_bool_statusbar, a_bool_fullscreen) {
	  var int_windowLeft = (screen.width - a_int_windowWidth) / 2;
	  var int_windowTop = (screen.height - a_int_windowHeight) / 2;
	  var str_windowProperties = 'height=' + a_int_windowHeight + ',width=' + a_int_windowWidth + ',top=' + int_windowTop + ',left=' + int_windowLeft + ',scrollbars=' + a_bool_scrollbars + ',resizable=' + a_bool_resizable + ',menubar=' + a_bool_menubar + ',toolbar=' + a_bool_toolbar + ',location=' + a_bool_addressbar + ',statusbar=' + a_bool_statusbar + ',fullscreen=' + a_bool_fullscreen + '';
	  var obj_window = window.open(a_str_windowURL, a_str_windowName, str_windowProperties)
	    if (parseInt(navigator.appVersion) >= 4) {
	      obj_window.window.focus();
	    }
	}
	/**
	 * 날짜 검색 관련
	 * 
	 * @param opt { 
	 * 					ele : { 
	 * 						'start' : element(시작일자 form name명)
	 * 					 	'end' : element(종료일자 form name명)
	 * 					},
	 * 					pos : 'start' (기간범위 시작일자 변경) / 'end' (기간범위 종료일자 변경) 
	 * 				}
	 * @returns
	 */
	function ydateRange(opt){
		ydateRange.prototype.init(opt);
		
	};
	ydateRange.prototype = {
			
			opts : null,
			default_opt : {
				ele : {
					start : null,
					end : null
				},
				pos : 'start'
			},
			init : function(opt){
				this.opts = $.extend({}, this.default_opt, opt);
				this.resume();
			},
			resume : function(){
				for(var i=1; i<=8; i++ ){
			 		$('.btn-date-'+i).on('click', $.proxy(this.date_condition_handler, this, i)) ;
			 	}
			},
			dateToYYYYMMDD : function(date){
				function pad(num) {
					num = num + '';
					return num.length < 2 ? '0' + num : num;
				}
				return date.getFullYear() + '-' + pad(date.getMonth()+1) + '-' + pad(date.getDate());
			},
			date_condition_handler : function(kind, e){
				
				var flag = (this.opts.pos != 'start') ? 2 : 0;  
				
				var currDate = new Date(); // 현재 날짜
				var start = '';
				var end = this.dateToYYYYMMDD(currDate);

				 // 오늘
				 if( kind == 1 ){
					 start = end ;
				}
				// 7일전
				else if( kind == 2 ){
					start = new Date(currDate.setDate(currDate.getDate()-7 +(7*flag) ));
					start = this.dateToYYYYMMDD(start);
				}
				// 15일전
				else if( kind == 3 ){
					start = new Date(currDate.setDate(currDate.getDate()-15 +(15*flag) ));
					start = this.dateToYYYYMMDD(start);
				}
				// 1개월전
				else if( kind == 4 ){
					start = new Date(currDate.setMonth(currDate.getMonth()-1 +(1*flag) ));
					start = this.dateToYYYYMMDD(start);
				}
				// 3개월전
				else if( kind == 5 ){
					start = new Date(currDate.setMonth(currDate.getMonth()-3 +(3*flag) ));
					start = this.dateToYYYYMMDD(start);
				}
				// 6개월전
				else if( kind == 6 ){
					start = new Date(currDate.setMonth(currDate.getMonth()-6 +(6*flag) ));
					start = this.dateToYYYYMMDD(start);
				}
				// 1년전
				else if( kind == 7 ){
					start = new Date(currDate.setMonth(currDate.getMonth()-12 +(12*flag) ));
					start = this.dateToYYYYMMDD(start);
				}
				else{
					start = end = '';
				}
				if( !flag )
				{
					$('[name="'+this.opts.ele.start+'"]', '#date-range').val(start) ;
					$('[name="'+this.opts.ele.end+'"]', '#date-range').val(end) ;	
				}
				else{
					$('[name="'+this.opts.ele.start+'"]', '#date-range').val(end) ;
					$('[name="'+this.opts.ele.end+'"]', '#date-range').val(start) ;
				}
				 
			}
			
	};
	/**
	 * 팝업관련
	 * 
	 * ypopup({
			width : 300,
			height: 300,
			output : 'base',
			wname : 'ok',
			content : '<div class="pop-cont" style="width:400px;height:400px;background-color:red;margin:0px auto;">okokokok<br><br><br><br>okokoko</div>',
			visible_date : true,
			kind : 'always',
			Activate : function(obj){
												
						this.css('top', parseInt(this.css('top'))-850 );
						this.animate({
							top: parseInt(this.css('top'))+850 },{
								duration: 'fast',
								//easing: 'easeInOutQuint',
								complete: function(){
									
								}
						});
						this.clearQueue();
				}
		});
	 * 
	 * 
	 */
	var ypopup = function(opt){
		ypopup.prototype.init(opt);
	};
	ypopup.prototype = {
			
			opts : null,
			default_opt : {
				width : 0,
				height: 0,
				output : 'base', // 'base' or 'win' or 'layer'
				wname : '', // 팝업 고유이름
				content : '', // 본문내용
				attr : null, // attribute 정의 
				style : null, // css속성 정의
				visible_date : false, // (오늘그만,7일간 보지않기 노출) 하는 액션 사용할지 유무
				url : '' // window팝업창 용 (layer에서는 사용안함)
			},
			init : function(opt){
				this.opts = $.extend({}, this.default_opt, opt);
				this.opts.wname = 'ypopup-' + this.opts.wname ;
				this.resume();
			},
			resume : function(){
				
				//if(typeof this.opts.onLoad === 'function') this.opts.onLoad.call($Ele, this) ;
				this.style(this.opts.output) ;
				
			},
			style : function(style)
			{
				var _ua = navigator.userAgent.toLowerCase(); // User Agent정보
				var is_msie = /msie/.test(_ua); // ms ie버전
				
				var $Ele = $(this.opts.content) ;
				var pop_name = this.opts.wname ;
				
				
				
				if(style == 'base')
				{
					var div = $('<div />').addClass(pop_name).css({
						display : 'block',
						position : 'absolute',
						top : 0, left : 0,
						width :'100%', height :'100%',
						zIndex :100
					});
					$('<div />').addClass('bg').css({
						position  :'fixed',
						top : 0, left : 0,
						width :'100%', height :'100%',
						backgroundColor : '#000',
						opacity: 0.7,
						'filter' : 'alpha(opacity='+(0.7*100)+')'
					}).appendTo(div);
					$Ele.appendTo(div).css('display','block');
					div.appendTo(document.body) ;
	
					if(is_msie) $Ele.css('position','absolute');
					else $Ele.css('position','relative');
					
					var kind = this.opts.kind ;
					
					if(kind === undefined || kind == 'static')
					{
						var pageYOffset = (window.pageYOffset !== undefined) ? window.pageYOffset : (document.documentElement || document.body.parentNode || document.body).scrollTop;
						if ($Ele.outerHeight() < $(document).height() ) 
				 			//$Ele.css('top',pageYOffset+(document.documentElement.clientHeight/2)-$('.'+pop_name).outerHeight()/2+'px');
							$Ele.css('top',pageYOffset+(document.documentElement.clientHeight/2)-$Ele.outerHeight()/2+'px');
						else $Ele.css('top','0px');
				 		//$Ele.css('top',(pageYOffset+(document.documentElement.clientHeight/2))-($('.'+pop_name).outerHeight()/2)+'px');
					}
					else if(kind == 'always')
					{
						/*$('.'+pop_name).css('position','fixed') ;
						$('.bg').css('position','absolute') ;
						if ($Ele.outerWidth() < $(document).width() ) $Ele.css('margin-left', '-'+$Ele.outerWidth()/2+'px');
			 			else $Ele.css('left', '0px');*/
						$('.'+pop_name).css({
								'position' : 'fixed',
								'display' : 'flex',
								'align-items' : 'center',
								'flex' : 1
							}) ;
						$Ele.css('top', 'inherit');
						
			 		}
					this.handler.call( this, $('.'+pop_name) ) ;
					
					if(typeof this.opts.Activate === 'function') this.opts.Activate.call($Ele, this) ;
					//if ( $.isFunction(callback) ) return callback(api) ;
				}
				//팝업창 형식
				else if(style == 'win')
				{
					if(this.opts.url) newWindow(this.opts.url, pop_name, this.opts.width, this.opts.height) ;
				}
				// 레이어 형식
				else if(style == 'layer'){
					
					if( $.cookie(pop_name) ) return false ;
					
					$('.'+pop_name).remove();
					$('body').append(
							'<div class="ypopup '+pop_name+'" style="'
								+ 'position: absolute;'
								+ 'margin: auto;'
								+ 'top: 0;'
								+ 'right: 0;'
								+ 'bottom: 0;'
								+ 'left: 0;'
								+ 'width:' + this.opts.width + 'px;'
								+ 'height:' + ((this.opts.height)? this.opts.height + 'px;' : 'auto;')
								//+ 'background-color: #ccc;'
								//+ 'border: 1px solid #ccc;'
								//+ 'border-radius: 3px;'
								+ 'z-index: 80;'
								
								+ (this.opts.style? this.opts.style : '') // add stylesheet( css )
								
							+ '"'
							+ (this.opts.attr? this.opts.attr : '') // add attribute 
							+'>'
								+'<div class="ypopup-container" style="display:block;height:inherit;overflow:auto;border: 1px solid #ccc;">'+this.opts.content+'</div>'

								+'<div class="ypopup-checkbox" style="display:flex;background-color:#000;color:#fff;padding: 2px 10px;">'
								
								+
								(this.opts.visible_date? 
								'		<div class="yclose-today" style="display:inline-block;cursor:pointer;">오늘그만 보기</div>'
								+'		<div class="yclose-week" style="display:inline-block;cursor:pointer;margin-left:1.5vh;">7일간 않보기</div>'
								:'')
								+'		<div class="yclose" style="display:flex;flex: 1;justify-content: flex-end;cursor:pointer;">닫기</div>'
								+'</div>'

							+'</div>'
					);
					this.handler.call( this, $('.'+pop_name) ) ;
					
					if(typeof this.opts.Activate === 'function') this.opts.Activate.call($(this.opts.content), this) ;
				}
				
			},
			handler : function(container)
			{
				var Api = this ;
				if( $('.yclose-today', container).length )
				{
					$('.yclose-today', container).bind('click', function(e){
						$.cookie(Api.opts.wname, 1, { expires: 1 });
						container.remove();
					});
				}
				if( $('.yclose-week', container).length )
				{
					$('.yclose-week', container).bind('click', function(e){
						$.cookie(Api.opts.wname, 1, { expires: 7 });
						container.remove();
					});
				}
				if( $('.yclose', container).length )
				{
					$('.yclose', container).bind('click', function(e){
						container.remove();
					});
				}
			},
			
			view_Condition : function()
			{
				if( $(".pop-check").is(":checked") == true ){
				//if( $("input:checkbox[name='checksaveid']").is(":checked") == true ){
					$.cookie(this.opts.wname, 1, { expires: 1 });
				}
			}
			
	};
	
