$(function () {
	/*var delHandler = function(e){
		(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
		var id = parseInt($(this).attr('data-serial')) ;
		if(!id) return false ;
		Frm.attr('action', baseURL+'/delete/'+id+window.location.search) ;
		Frm.submit();
	}
	$('#btn-brdAdd').on('click', function(e){
		(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
		$('#btn-brd-delete').removeAttr('data-serial').unbind('click').hide();
		OFUNC.clear_form_elements({elem:Frm, clear:true}) ;
		Frm.attr('action', baseURL+'/write'+window.location.search);
	});*/
	
	// 비밀번호 찾기
	/*$('#mbrFrmReqPw').on('submit', function(e){
		var Frm = $(this) ;
		if( !validateEmail($('input[name="inputEmail"]', Frm).val() ) ){
			alert('Please enter your email address correctly.');
			$('input[name="inputEmail"]', Frm).focus();
			return false ;
		}
	}) ;*/
	
	$('#btn-pw-req', $('#mbrFrmReqPw')).on('click', function(e){
		(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
		var Frm = $('#mbrFrmReqPw') ;
		var email = $('input[name="inputEmail"]', Frm) ;
		if( !validateEmail( email.val() ) ){
			alert('이메일 주소를 입력하십시오.');
			$('input[name="inputEmail"]', Frm).focus();
			return false ;
		}
		servRequest('Req_pwdCall', 'json',{mail:email.val()}, function(res){
			if(res){
				if(res.message) alert(res.message) ;
			}
			email.val('');
			//if(res) ;
		}) ;
		
	});

	/*$('.btn-brd-edit').on('click', function(e){
		//(e.preventDefault) ? e.preventDefault() : e.returnValue = false;
		OFUNC.clear_form_elements({elem:Frm, clear:true}) ;
		var id = parseInt($(this).attr('data-serial')) ;
		if(!id) return false ;

		servRequest('Req_getQna', 'json',{serial:id}, function(res){
			if(res)
			{
				Frm.find('input[name="frm_title"]').val('').val(res.title);
				Frm.find('[name="frm_memo"]').val('').val(res.memo);
				Frm.find('input:radio[name="frm_cate"][value="'+res.cate+'"]').prop('checked', true);
				Frm.attr('action', baseURL+'/update/'+id+window.location.search) ;
				$('#btn-brd-delete').attr('data-serial', id).bind('click', delHandler).show();
			}
		}) ;
	});*/
	
});