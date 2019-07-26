/**
 * Dual-licensed under the BSD or MIT licenses
 * 
 * @author youngsu lee
 * @email yengsu@hanmail.net
 */
$(function () {
	
	$.fn.tag_locate = function(parents, filter){
		if( this.parents('.dd-item').length > 0){	
			var i = {	path : null, obj : [], };
		  	i.path = $(this).parents('[class*="'+filter+'"]').not('#nestable, ol.dd-list').map(function(){
		  			i.obj.push({
		  				tag : this.tagName,
		  				obj : this
		  			});
		  			return $(this).find('.dd-handle').eq(0).text() ;
		            //return this.tagName ;
		        }).get().reverse().join(' > ');
		  	return i ;
		}
	};
	$.fn.path_swap = function(parents,selector){
    	return this.parentsUntil(parents,selector).map(function () {
            if ($(this).children().length > 0) {
            }
            return this.tagName; // equal to : $(this).prop('nodeName')
        }).get().reverse();//.join(' ')
        
    };
    $.fn.serializeFormJSON = function () {

        var o = {};
        var a = this.serializeArray();
        $.each(a, function () {
            if (o[this.name]) {
                if (!o[this.name].push) {
                    o[this.name] = [o[this.name]];
                }
                o[this.name].push(this.value || '');
            } else {
                o[this.name] = this.value || '';
            }
        });
        return o;
    };
    
    /**
     * path 가져오기
     * 
     * @param selector (찾을려고 하는 엘리먼트의 블럭)
     * @param sep ( 구분자; ' > , / ....')
     * $(찾을 블럭 엘리먼트).path_swap(찾을 엘리먼트) ;
     * 
     * @example $('#frmGoodsSpecInfo').path_swap('.opt_name-text') ;
     */
    $.fn.Gpath_swap = function(selector, sep){
    	if(!sep) var sep = '>';
    	
    	return this.parentsUntil(selector).map(function () {
	        if ($(this).children().length > 0) {
	            //owner.push(this.tagName); //owner.push([this, this.tagName]);
	        }
	        if( $(this).closest('li.dd-item').eq(0).find('ol li').length ) {
	        	// 상품 카테고리 빼고 경로 뽑기( .rootNode 빼고 뽑기)
	        	if( !$(this).closest('li.dd-item').eq(0).find('.rootNode').length )
	        		return $(this).closest('li.dd-item').eq(0).find('.opt_name-text').eq(0).text();
	        }
	        
	    }).get().reverse().unique().join(' '+sep+' ')
    };
});
function get_nestable_opts(opts){}
$.extend(true, get_nestable_opts.prototype, {
			//thisObjs:null,
 			beforeObjs:null,
			'config' : {
					group: 1,
				  	maxDepth : 4, // The first depth is 0
				  		//rootClass : 'dd-root',
				  		threshold : 15,
				  		hideNodeClass : 'dd-hide', // root-node hide
				  		collapseAll : true
					/*,afterInit: function ( event ) { 
				         console.log( 'e', event,this );
				         
				    }*/
			},
			
			'evt' : {
					'beforeDragStart' : function(event, item, data) {
						this.beforeObjs = data;
					},
					'dragEnd' : function(event, item, source, destination, position, data, maxDepth) {
						console.log('depth', data, this.beforeObjs) ;
				   		// 추가하기-버튼 추가 :: depth가 1인경우 
				   		var btnAdd = $(item).children('.dd3-content').eq(0).find('.btn_optAdd').eq(0) ;

				   		// 이전의 부모노드와 현재의 부모노드가 다른경우
				   		if( data.parent.id != this.beforeObjs.parent.id ){
				   			
				   			//이전의 부모노드
				   			var old_parentObj = $('[data-id="'+this.beforeObjs.parent.id+'"]') ;
				   			if( ! old_parentObj.children('ol.dd-list').length){
				   				if( old_parentObj.children('.dd3-content').find('.opt-frm input').length < 2 ){
					   				var cloneObj = $('#optTmpFrm').find('.opt-form:gt(1)').clone(true) ;
					   				$('[data-id="'+this.beforeObjs.parent.id+'"]').children('.dd3-content').find('.opt-frm').eq(0).append(cloneObj);
				   				}
				   			}
				   			Menu_handle.prototype.opt_liDel_handler.call( event, $('[data-id="'+this.beforeObjs.sel.id+'"]') ) ;//$('[data-id="'+this.beforeObjs.parent.id+'"]') );
 							//--------------------------------------
 							// 현재 부모노드
				   			var current_parentObj = $('[data-id="'+data.parent.id+'"]') ;
				   			current_parentObj.children('.dd3-content').eq(0).find('.dd-content-wrap .opt-form:gt(0)').remove();
				   		}
				   		
			   			// 노드 이동위치가 변경된경우(부모노드 또는 형제노드)	
					   	if( data.parent.serial != this.beforeObjs.parent.serial || 
					   			data.sel.seq != this.beforeObjs.sel.seq )
					   	{
					   		
								   	var params = {
											'property' : 'move',
											'serial' : data.sel.serial,
											'parent' : data.parent.serial,
											'old_parent' : this.beforeObjs.parent.serial,
											'previous' : data.prev.serial
									};
								   	Menu_handle.prototype.servRequest('Req_MenuUpdate', null, params, function(){
						 				
						 				for(var prop in get_nestable_opts.prototype.beforeObjs){
											if (get_nestable_opts.prototype.beforeObjs.hasOwnProperty(prop)) delete get_nestable_opts.prototype.beforeObjs[prop] ;
										}
									}) ;
					   	}
				   			
				   		

				   		(function recurse(el, indent, maxDepth){

				   			if(!el.length) return ;
				   			for (var i = 0; i < el.length; i++) {

				   					// 해당 li 아래에 ol 인 자식노드가 더 있는경우
					   				if( $(el).eq(i).children('ol.dd-list').length ){

						   				recurse( $(el).eq(i).children('ol.dd-list').children('li.dd-item'), indent+1, maxDepth ) ;
					   				}

					   				var btnAdd = $(el).eq(i).children('.dd3-content').eq(0).find('.btn_optAdd').eq(0);

						   			if(indent < parseInt(maxDepth)-1){
							   			// +버튼이 존재하지않고 
							   			if(!btnAdd.length){
							   				$(el).eq(i).find('.btns-opt').prepend( 
							   							$('#optTmpFrm').find('i.btn_optAdd').clone()
							   								.unbind('click').bind('click', Menu_handle.prototype.opt_liAdd_handler)
							   						) ;
							   			}
							   		}else{
							   			/* 추가하기-버튼 제거  
							   				2 depth 이상부터는 row 추가버튼 나타나지 않도록 했음.
							   			*/
							   			if(indent > parseInt(maxDepth)-2){
							   				//btnAdd.unbind('click').hide();
							   				btnAdd.remove();
							   			}
							   		}
					   				
				   			}
					   			
				   		})( $(item).eq(0), data.sel.indent, parseInt(maxDepth) );
				   							   	
			   		}
			}
}) ;

 
function Menu_handle(){
	Menu_handle.prototype.init();
} ;
Menu_handle.prototype = {
	maxDepth : 4,
	res_data : {},
	select_node : null,
	//폼초기화( addType는 추가로 초기화할 type형 선언바람(예:hidden)
	clear_form_elements : function(elem, addType) {
	    $(elem).find('input, textarea').each(function() {
	        switch(this.type) {
	            case 'password':
	            case 'select-multiple':
	            case 'select-one':
	            case 'text':
	            case 'textarea':
	               $(this).val('');
	                break;
	            case 'checkbox':
	            case 'radio':
	                this.checked = false;
	                break;
	        }
	        if(addType){
	        	if(this.type == addType) $(this).val('');
	        }
	        this.setAttribute('autocomplete',"off");  
	    });
	},
	isEmpty : function(s){
		//return /^\s+$/g.test(s);
		return s.replace(/^\s+|\s+$/gm,'').length == 0;
	},
	// 정수형 상수
	isInteger : function(s){
		s = parseInt(s);
		return  Math.round(s) === s ;
	},
	// 쿼리스트링형 데이타 => object로 변환
	queryStringToJSON : function(s) {            
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
	// Back-end로 보낼 form 정보를 가공
	form_put : function(id){
		
		 var form_obj = {};
		 if( $('[data-id="'+id+'"]').children('.dd3-content').find('input, select').length )
		 {
			 $('[data-id="'+id+'"]').children('.dd3-content').find('input,select').each(function(){
				 if(this.type=="checkbox" && !this.checked){
					 return ;
				 }
				 form_obj[this.name] = this.value ;
				 //return ;
				 //event.preventDefault();
			 });
		 }
		 return form_obj ;
	},
	/*
	 * convert[array|object] : nest to flatten (add => lft,rgt)
	 * @param array|object node ( node[0] )
	 * @param integer previousLft
	 */
	flattNest : function(node, previousLft) {
		 
		 var result = [];
		 function recurse(node, previousLft){
		    /* create and store the new entry (bucket to put left, right, and id ) */
			    var indexed = {};
			    result.push(indexed);
			    //form_obj = form_put(node["id"]);
			    //indexed.id = node["id"];
			    $.extend(indexed, Menu_handle.prototype.form_put.call(this, node["id"]));
			    $.extend(indexed, node);
			    indexed.lft = parseInt(previousLft) + 1;
				delete indexed.path ;
			    var lastRgt = indexed.lft;
			    /* here we do the recursion for every child */
			    for (var x in node["children"]) {
			       lastRgt = recurse(node["children"][x], lastRgt);
			    }
			    delete indexed["children"] ;
			    /* once all children have been iterated over we can store the rigth */
			    indexed.rgt = parseInt(lastRgt) + 1;
			    
		    /* return the newly updated right for this bucket */
		    return indexed.rgt;
		 }
		 
		 recurse(node, previousLft);
		 return result;
	},
	//Another variation of answer suggested by JAR.JAR.beans
	randAlnum : function(ea){
		limit = ea ? ea : 5 ;
		return (Math.random() * Math.pow(2, 54)).toString(36).slice(0, limit);
	 },
	// Server Reqeust - Backend
	servRequest : function(type, dataType, params, callback){
		
		Request_FaildCheck = true ; // jquery.func.js 참조
		
		var requestURL = null ;
		requestURL = baseURL +'/'+type ;

		OFUNC.Request_ajax({
						url : requestURL,
						type : 'POST',
						dataType : dataType,
						data : params,
						beforeSend : function(xhr, settings){	// 기본적으로 생성,수정 처리시 표시
							
						},
						complete: function(e){
							//if(e.readyState == 4) {}
							//console.log('e', e.responseJSON);
							if(callback !== undefined){
								if(e.responseJSON) callback(e.responseJSON, e) ;
								else if(e.responseText) callback(e.responseText, e) ;
							}
						}
		});
	},

	/*
	 * convert[array|object] : nest to flatten
	 */
	flatResult : function (inArray) {
	
		var result = [];
		function recurse(inputArray, ischild) {
		    ischild = ischild || false;
		    for (var i = 0; i < inputArray.length; i++) {
		    	result.push(inputArray[i]);
		
		        if (inputArray[i].children && typeof inputArray[i].children === typeof []) {
		        	recurse(inputArray[i].children, true);
		        }
		    }
		    if(ischild === false){
		        return result;
		    }
		}
		
		return recurse(inArray);
	},
	init : function(Context, plugin){
		//opt_name-text
		// 펼치기, 접기
		this.evnt_append_handler.call(this, Context, plugin) ;

		if(plugin.options.res_data) this.res_data = plugin.options.res_data ;
	},
	
	
	evnt_append_handler : function(Context, plugin){
		
		var FrmGoodsOptsBlock = $(Context).parents('[id^=FrmGoodsOpts-]') ; // 자신이 속해있는 주문옵션 블럭
		var opt_kind = FrmGoodsOptsBlock.prop('id').replace('FrmGoodsOpts-', '') ;
		
		if(plugin.options.property)
		{
			if( opt_kind == 'base'){
				plugin.options.maxDepth = plugin.options.property.base.indent ;
			}else if( opt_kind == 'add'){
				plugin.options.maxDepth = plugin.options.property.add.indent ;
			}
		}
		
		// 펼치기,접기
		$('.nestable-menu', FrmGoodsOptsBlock).unbind('click').bind('click', this.expORcollap_handler);
		
		// 버튼 [추가, 삭제]
		this.opt_li_event(Context) ;
		
		if( $(Context).find('.opt_name-text').length ) 
			//$(Context).find('.opt_name-text').bind('focusin', this.nest_text_handler) ;
			$(Context).find('.opt_name-text:not(:eq(0))').unbind('click').bind('click', this.nest_text_handler) ;
		
		
		//$('#btnAdd', $(postFormEle)).bind('click', this.reqRegist_handler) ;
		$('#btnUpdate', $(postFormEle)).unbind('click').bind('click', this.reqUpdate_handler) ;
		$('#btnDelete', $(postFormEle)).unbind('click').bind('click', this.reqDelete_handler) ;
		
		// nestable 데이타 가져오기(refresh)
		$(".btn_Refresh", Context).unbind('click').bind('click', this.getAllData_handler); 
	},
	opt_li_event : function(Context){
		// nestable row 추가
		$(".btn_optAdd", Context).unbind('click').bind('click', this.opt_liAdd_handler); 
		
		// nestable row 삭제
		$(".btn_optDel", Context).unbind('click').bind('click', this.opt_liDel_handler);
	},
	
	opt_title_handler : function(e){
    	(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
    	Menu_handle.prototype.get_data.call(this) ;
	},
	getAllData_handler : function(e){
		$('.cate-inner').append('<div class="loading-progress"><div class="msg">로딩중 ....</div></div>');
		$('#nest-edit-block').hide();
		Menu_handle.prototype.servRequest.call(Menu_handle.prototype, 'Req_getNestDatas', 'html', null, function(res){
			$('.loading-progress').remove();
	    	if(res){
	    		var parent_block = $('.cate-inner');
	    		parent_block.empty().append(res||'');
	    		
	    		$('.nestable').unbind().removeData();
	    		$('.nestable').nestable(get_nestable_opts.prototype.config)
	    		.on('beforeDragStart', get_nestable_opts.prototype.evt.beforeDragStart)
	    		.on('dragEnd', get_nestable_opts.prototype.evt.dragEnd) ;
	    		
	    		
	    		
	    		
	    		
	    		/*li.remove() ;
	    		$('#nest-edit-block').hide();*/
	    	}else{
	    		/*if(!Response_errorDebug)
	    			alert('잠시 후 다시 시도해주세요.') ;*/
	    	}
		}) ;
	},
	get_data : function(){
		var thisObj = this ;
		var serial = $(thisObj).closest('li.dd-item').data('serial') ;
		Menu_handle.prototype.select_node = {
			serial : serial
		} ;
		if( ! Menu_handle.prototype.isInteger(serial) ) return false ;
	    var params = {
				'serial' : serial
		};
	    // 상위 그룹노드
	    if( $(thisObj).closest('li.dd-item').find('ol li').length )
	    	params['grp'] = 1;

	    Menu_handle.prototype.servRequest.call(Menu_handle.prototype, 'Req_getMenu', 'json', params, function(res){
	    	Menu_handle.prototype.editFrm(res, thisObj) ;
		}) ;
	},
	del_data : function(li){
		var thisObj = this ;
		var serial = $(thisObj).closest('li.dd-item').data('serial') ;
		if( ! Menu_handle.prototype.isInteger(serial) ) return false ;
	    var params = {
	    		'property' : 'delete',
				'serial' : serial
		};
	    Menu_handle.prototype.servRequest.call(Menu_handle.prototype, 'Req_MenuUpdate', 'json', params, function(res){
	    	if(res){
	    		li.remove() ;
	    		$('#nest-edit-block').hide();
	    	}else{
	    		/*if(!Response_errorDebug)
	    			alert('잠시 후 다시 시도해주세요.') ;*/
	    	}
		}) ;
	},
	form_validate : function(){
		var title = $('input[name="title"]', $(this).closest('.dd').find('ol > li.dd-item').eq(0)) ;
    	if( title.length ){
    		if( Menu_handle.prototype.isEmpty(title.val()) ){
    			//this.value = '할인 타이틀명' ;
    			/*alert("타이틀명을 입력해주세요.") ;*/
    			alert("Please enter a title name.") ;
    			title.focus();
    			return false ;
    		}
    	}
    	return true ;
	},
	opt_liAdd_handler : function (e) {
		   (e.preventDefault) ? e.preventDefault() : e.returnValue = false;
		   											
		    var li = $(this).closest('li.dd-item') ;

		    if(!Menu_handle.prototype.form_validate.call(this)) return false ;
		    
		    // 타이틀위의 '추가하기'버튼 클릭한경우
		    if(!li.length)
		    	li = $(this).closest('.dd').find('ol > li.dd-item').eq(0); 

		    var dd3_content = li.children('.dd3-content');
		    
		    // 깊이(depth) 체크
		    var depth = li.path_swap('.dd', 'ol.dd-list').length ; // 현재노드의 깊이값
		    
		    if( $(this).closest('.dd').data('nestable').options.maxDepth <= depth ){
		    	return false ; // 설정된 깊이값보다 크거나 같은경우 리턴
		    }
		    //펼치기
		    $(this).closest('.dd').data('nestable').expandItem(li);
		    
		    
		    // element 복사
		    var cloneObj = $('#optTmpFrm > *').clone() ;
		    
		    Menu_handle.prototype.opt_li_event.call(Menu_handle.prototype, cloneObj);
		    // 체크 : 추가하기 버튼 제거 //숨김
		    
	    	if( $(this).closest('.dd').data('nestable').options.maxDepth <= (depth+1) ){
		    	cloneObj.find('.btn_optAdd').remove();
		    }
		    var rand = Menu_handle.prototype.randAlnum(6) ;
		    if(li.attr('data-id') !== 'root') {
		    	cloneObj.attr('data-id', li.data('id')+'-'+rand );

		    	// checkbox
		    	var parent_id = cloneObj.attr('data-id') ;
		    }else{
		    	cloneObj.attr('data-id', rand );
		    }
		    
		    $('.opt_name-text', cloneObj).on('click', Menu_handle.prototype.nest_text_handler);

		    // 신규등록폼에서 입력이 완료되면
			$('.nest-form', cloneObj).on('focusout', {property:'add', parent: li.attr('data-serial')}, Menu_handle.prototype.nest_form_handler); 
				    
			
			$("input:text[name='title']", cloneObj)
				.attr('tabindex', li.index)
					//.val('할인 타이틀명')
		    			.unbind('keyup click').bind('keyup click', Menu_handle.prototype.opt_title_handler);
    
		    //-----------------------------------
			
		    if( li.children('ol').length ){
		    	li.children('ol').append( cloneObj.fadeIn('slow') ) ;
		    	$(this).closest('.dd').nestable('setParent', li); // +,- 버튼 추가(접기,펴기)
		    }else{
		    	li.append( 
		    			$('<ol />', {'class' : 'dd-list'})
		    				.append( cloneObj.fadeIn('slow') )
		    		) ;
		    	$(this).closest('.dd').nestable('setParent', li); // +,- 버튼 추가(접기,펴기)
		    }
		    //-----------------------------------
		    
		    $("input:text[name='title']", cloneObj).select();
		    
		    $('#nest-edit-block').hide();
	},

	editFrm : function(data, thisObj){
		document.getElementById( postFormEle.substr(1) ).reset(); // # <-- 제거
		$('#nest-edit-block').show();//css('display', 'inline-block');

		/*var Frm = $(postFormEle) ;
		if( Frm.length && data )
		{
			if( $('[name="serial"]', Frm).length )
				$('[name="serial"]', Frm).val( data.serial ) ;
			else
				Frm.append( $("<input />", {
							'type' : 'hidden',
							'name' : 'serial',
							'value' : data.serial
						})
				);

			$('[name="title"]', Frm).val( data.title ) ;
			if(data.orderBy) $('select[name="orderBy"]', Frm).find('option[value="'+data.orderBy+'"]').prop('selected', 'selected') ;
			$('.goods-childContain-cnt').text(data.goods_cnt) ;
			
			// 선택된 노드가 그룹노드 엘리먼트 인경우
			if( $(thisObj).closest('li.dd-item').find('ol li').length ) {
				
				var Path = $(thisObj).closest('li.dd-item').Gpath_swap('.opt_name-text') ;
				$('#cate-field-path').text(Path);
				
				return ;
				
			}else{
				var Path = $(thisObj).Gpath_swap('.opt_name-text') ;
				$('#cate-field-path').text(Path);
			}
			

		}*/
		
		if(data)
		{
			$("#formEdit").find('input[name="serial"]').val( data.serial) ;
			$("#formEdit").find('input[name="title"]').val( data.title) ;
			$("#formEdit").find('.board_qty').text( data.board_qty ) ; // 연결된 게시판갯수
			$("#formEdit").find('input:checkbox[name="imp"][value="'+data.imp+'"]').prop("checked", true);
			$("#formEdit").find('select[name="grant_read"] option[value="'+parseInt(data.grant_read)+'"]').prop('selected', 'selected') ;
		}

	},

	select_formatState : function(state) {
		  if (!state.id) { return state.text; }
		  
		  var is_branch = $(state.element).attr('data-branch') ;
		  var indent = $(state.element).attr('data-indent') ;
		  
		  var paddingLeft = 0 ;
		  if(indent > 1) paddingLeft = 18 * parseInt(indent) ;
		  else paddingLeft = 5 ;
		  
		  var $state = $(
		    	  is_branch !== undefined ? 
				    		('<span style="padding-left:'+paddingLeft+'px;font-weight:bold;"><i class="fa fa-folder text-primary"></i> <span class="text-primary">' + state.text + '</span></span>') 
				    	  : ('<span style="padding-left:'+paddingLeft+'px;"><i class="fa fa-file-text-o" style="color:#ccc;opacity:50;"></i> ' + state.text + '</span>')		    	  
		  );
		  return $state;
	},
	
	
	//txt에 포커스가 선택되면 입력박스 변환
	nest_form_handler : function(e){
		(e.preventDefault) ? e.preventDefault() : e.returnValue = false;

		var sel_li = $(this).closest('li.dd-item')//.closest('li.dd-item').eq(0) ;
	    var sel_serial = sel_li.data('serial') ;
		
		this.value = this.value.replace(/^\s+|\s+/gm,' ');
		// 공백체크
		if( Menu_handle.prototype.isEmpty(this.value) ){
			//this.value = '할인 타이틀명' ;
			
			var parent_li = $(sel_li).parents('li').eq(0) ;
			if( parent_li.find('li').length == 1 )
				$(this).closest('.dd').nestable('unsetParent', parent_li);
			
			sel_li.remove();
			$('#nest-edit-block').hide();
			return false ;
		}
		//----------------------------------------
	    
	    var params = {} ;
	    
	    if( !e.data ){
	    	params = {
					'property' : 'update',
					'serial' : sel_serial,
					'title' : this.value
			};
	    }
	    else if( e.data.property == 'add' ){
		    params = {
					'property' : 'add',
					'parent' : e.data.parent,
					'title' : this.value
			};
	    }
	    Menu_handle.prototype.servRequest.call(Menu_handle.prototype, 'Req_MenuUpdate', 'json', params, function(res){
	    	
	    	if( params.property == 'add' ){
	    		
	    		if(res.serial){
		    		$(sel_li).attr('data-serial', res.serial).data('serial', res.serial) ;
		    		// 상품대상 마크 제거
		    		$('[data-serial="'+e.data.parent+'"]').children('.dd3-content').removeClass('disc-mark').removeClass(function(index, css) {
		    		    return (css.match(/\bdisc-mark-color-\S+/g) || []).join(' ');
		    		});
	    		}
	    	}
		}) ;
	    //----------------------------------------
		
		$(this).parent().append(
			$('<span />',{
				'class' : 'opt_name-text'
				//,'tabindex' : $(this).attr('tabindex')
			}).text( $(this).val() )
				//.bind('focusin', Menu_handle.prototype.nest_text_handler)
			.bind('click', Menu_handle.prototype.nest_text_handler)
		);

		$(this).remove();
	},
	nest_text_handler : function(e){
		(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
		$('.dd3-content').removeClass('active');
		$(this).closest('.dd3-content').eq(0).addClass('active');// 선택된 메뉴 활성화
		Menu_handle.prototype.get_data.call(this) ;
	},
	nest_text_handler_org : function(e){
		 
		(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
		if( $(this).parent().hasClass('.nest-form') ) $(this).parent().find('.nest-form').remove(); 
		
		var title_ele = $('[name="title"]', document.getElementById('optTmpFrm')).clone() ;
		
		title_ele.attr({'tabindex': $(this).attr('tabindex'), 'class' : 'nest-form'})
			.val( $(this).text() )
				.bind('focusout', Menu_handle.prototype.nest_form_handler)
				.bind('focusin keyup click', Menu_handle.prototype.opt_title_handler);

		$(this).parent().append(	 title_ele ) ;
		$('.nest-form').focus();
		$(this).remove();
		
	},
	opt_liDel_handler : function (e) {
		   (e.preventDefault) ? e.preventDefault() : e.returnValue = false;

		   var ol = $(this).parents('.dd-list') ;
		   var li = $(this).closest('li.dd-item') ;
		   var activateEle = li.find('.dd3-content') ; // row안의 container블럭의 input Element들


		   if( activateEle.length ){
			   //activateEle.addClass('dd-delActive'); // 삭제할 노드의 색상 표시
	
			   if (!confirm( parseInt(activateEle.length)+'개를 정말 삭제 하시겠습니까?')){
				   activateEle.removeClass('dd-delActive');
				   return false;
			   }
		   }

			//-----------------------------------
			if( li.siblings().length < 1 && !li.parent().parent().hasClass('dd-hide') ){
				// element 복사
				var cloneObj = $('#optTmpFrm').find('.opt-form:gt(1)').clone(true) ;
			    li.parent().parent().children('.dd3-content').find('.opt-frm').eq(0).append(cloneObj);
			    
				$(this).closest('.dd').nestable('unsetParent', li.parent().parent()); // +,- 버튼 제거
			}
			//-----------------------------------
			Menu_handle.prototype.del_data.call(this, li) ;
	},

	optCheck_handler : function(e){
		(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
		
		var checkbox_ele = $(this).parents('li').parents('li').eq(0).children('.dd3-content').find('.opt-checkbox') ;
		
		if( checkbox_ele.length && !checkbox_ele.find('input').prop('checked') ) {
			this.checked = false ;
			return false ;
		}
		var s = (this.checked = !this.checked) ;
		this.checked = !this.checked ;
		var s = this.checked;

		if($(this).closest('li').find('.opt_imp').length)
		{
			$(this).closest('li').children('ol').find('.opt_imp').not(0).each(function(){
				this.checked = s;//!this.checked ;
			});
		}
	},
	expORcollap_handler : function(e){
		(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
	     var target = $(e.target),
	         action = target.data('action');
	     if (action === 'expand-all') {
	         $(this).closest('.dd').nestable('expandAll');
	     }
	     if (action === 'collapse-all') {
	    	 $(this).closest('.dd').nestable('collapseAll');
	     }
	 },
	 /*reqRegist_handler : function(e){
		 (e.preventDefault) ? e.preventDefault() : e.returnValue = false;

		 if( Menu_handle.prototype.isEmpty( $('[name="title"]', $(postFormEle)).val() ) ){
			 alert("타이틀명을 입력해주세요.") ;
			 alert("Please enter a title name.") ;
			 $('[name="title"]', $(postFormEle)).val('').focus();
			 return false ;
		 }
		 
		 var params = $(postFormEle).serializeFormJSON() ;
		 params['property'] = 'add' ;
		 Menu_handle.prototype.servRequest.call(Menu_handle.prototype, 'Req_MenuUpdate', 'json', params, function(res){
	    	if(res){
	    		//$('.dd').find('[data-serial="'+ params.serial+'"]').find('.opt_name-text').eq(0).text( decodeURIComponent(params.title) ) ;
	    		alert('저장 되었습니다.') ;
	    		//alert('Saved.') ;
	    	}
		}) ;
	 },*/
	 reqUpdate_handler : function(e){
		 (e.preventDefault) ? e.preventDefault() : e.returnValue = false;

		 if( Menu_handle.prototype.isEmpty( $('[name="title"]', $(postFormEle)).val() ) ){
			 alert("타이틀명을 입력해주세요.") ;
			 //alert("Please enter a title name.") ;
			 $('[name="title"]', $(postFormEle)).val('').focus();
			 return false ;
		 }
		 
		 /*if( Menu_handle.prototype.isEmpty( $('[name="bid"]', $(postFormEle)).val() ) ){
			 alert("게시판 ID를 선택해주세요.") ;
			 $('[name="bid"]', $(postFormEle)).val('').focus();
			 return false ;
		 }*/
		 
		 var params = $(postFormEle).serializeFormJSON() ;
		 if( $('[name="serial"]', $(postFormEle)).length ){
			 if( Menu_handle.prototype.isInteger( $('[name="serial"]', $(postFormEle)).val() ) ){
				 params['property'] = 'update' ;
				 
				 if( $('.dd').find('[data-serial="'+ params.serial+'"]').eq(0).find('ol li').length ){
					 params['grp'] = 1 ;
				 }
			 }else{
				 alert("코드값이 올바르지 않습니다.") ;
				//alert("The code value is invalid.") ;
				 return false ;
		 	}
		 }else{
			 alert("코드값을 찾을 수 없습니다.") ;
			 //alert("Code value not found.") ;
			 return false ;
		 }
		 Menu_handle.prototype.servRequest.call(Menu_handle.prototype, 'Req_MenuUpdate', 'json', params, function(res){
	    	if(res){
	    		$('.dd').find('[data-serial="'+ params.serial+'"]').find('.opt_name-text').eq(0).text( decodeURIComponent(params.title) ) ;
	    		alert('수정 되었습니다.') ;
	    		//alert('Saved.') ;
	    	}
		}) ;
	 },
	 reqDelete_handler : function(e){
		 (e.preventDefault) ? e.preventDefault() : e.returnValue = false;

		 var sel_li = $(this).closest('li.dd-item')//.closest('li.dd-item').eq(0) ;
 	    var sel_serial = sel_li.data('serial') ;
		 
		$('.dd').find('[data-serial="'+ $('[name="serial"]', $(postFormEle)).val() +'"]').find('.btn_optDel').eq(0).triggerHandler('click') ;
		//====================
		
		
	 },
	 optSubmit_handler : function(e){
			//(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
			
			$('[id^=FrmGoodsOpts-]').each( function(i, el)
			{
					var opt_kind = $(this).data('kind') ;
				    if( ! /^[0-9]+$/.test(opt_kind) ){
				    	alert('옵션 구분코드 오류입니다.');
				    	//alert('Option delimiter code error.');
				    	(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
				    	return false ;
				    }

					var opt_inputObj = $(this).find('.nestable').find('input[name=opt_name],  input[name=opt_stock_ea]').not('[type=hidden]');
					
					// 조합형 인경우
					if( $('[id^="opt_type_"]:checked', this).val() == 2){
						
						// 옵션 타이틀명 체크
						if(opt_inputObj.length){
							if(!Menu_handle.prototype.opt_default_titleFormValidate.call(this,null)) {
								(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
								return false ;
							}
						}
					}
		
					var chk = 0 ;
					opt_inputObj.each(function(){
						this.value = Menu_handle.prototype.submit_StringFiltering( this.value );
		
						if( $.inArray(this.name, ['opt_name','opt_stock_ea']) > -1 ){
		
							if(this.value == "" || this.value == " "){
								if(this.name == "opt_name"){
									alert('옵션명을 입력해주세요.');
									//alert('Please enter the option name.');
									this.focus();
									chk = 1 ;
									return false ;
								}
							}
						}
					}) ;
					if(chk){
						(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
					}
					
					var serializeObj = $(this).find('.nestable').nestable('serialize') ;
					
					//등록된 옵션이 하나도 없는경우
					if( !serializeObj[0].indent && !serializeObj[0].childs ){
						$('input[name^="opt_default_title["]', $(this).find('.goods-opt-class')).remove();
						return ;
					}
						
					resObjs = Menu_handle.prototype.flattNest.call(Menu_handle.prototype, serializeObj[0], 0) ;
					$(this).find('.opt-request').remove();
					$.each(resObjs, function(i, obj){
						var addObj ;
						for (key in this) {
							if( $.inArray(key, ['serial','parent','parentName', 'indent','lft','rgt']) > -1 || /^opt_/gi.test( key ) ) {
								addObj = $('<input/>',{
									'class' : 'opt-request',
									'type' : 'hidden',
									'name' : 'goods_opt['+opt_kind+']['+i+']['+key+']',
									'value' : this[key]
								});
								$( e.data.frm ).append( addObj ) ;
							}
							
						}
					});
			});
	},
	opt_requestAjax_handler : function(e){
		(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
		var serializeObj = $('.nestable').nestable('serialize') ;
		var resObjs = Menu_handle.prototype.flattNest.call(this, serializeObj[0], 0);
		Menu_handle.prototype.servRequest.call(Menu_handle.prototype, 'all_regist', null, {'data':resObjs}, function(data){
				
		});
	},
	submit_StringFiltering : function(str){
			str = str.trim();
			str = str.replace(/\s{2,}/g, " ");
			str = str.replace(/'/g, '');
			str = str.replace(/"/g, '');
			
			// js/jquery/jquery.func.js(StringFilter)
			str = StringFilter( str ) ;
			return str;
	}
	
}
Array.prototype.unique = function () {
    var r = [];
    o:for(var i = 0, n = this.length; i < n; i++)
    {
        for(var x = 0, y = r.length; x < y; x++)
      {
        if(r[x]===this[i])
        {
          continue o;
        }
      }
      r[r.length] = this[i];
    }
    return r;
};