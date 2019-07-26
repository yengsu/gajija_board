<?
use system\traits\DB_NestedSet_Trait;
use Gajija\service\CommNest_service;
use Gajija\controller\_traits\AdmController_comm;
use Gajija\service\Mail_service;

/**
 * Mail send page
 * 
 * @author youngsu lee
 * @email yengsu@hanmail.net
 */
class MailSend_controller extends CommNest_service
{
	use DB_NestedSet_Trait, AdmController_comm;
	
	/**
	 * 웹서비스용
	 * 
	 * @var object
	 */
	public $WebAppService;

	/**
	 * 라우팅 결과데이타
	 * 
	 * @var array 데이타
	 */
	public $routeResult = array();
	
	/**
	 * 회원 환경정보
	 *
	 * @filesource conf/member.conf.php
	 * @var array
	 */
	public static $mbr_conf = array();
	
	/**
	 * 메일 서비스
	 * 
	 * @var object
	 */
	public $MailService ;
	
	public function __construct($routeResult)
	{
		
		if($routeResult)
		{
			// 라우팅 결과
			$this->routeResult = $routeResult ;
		}
		// 웹서비스
		if(!$this->WebAppService  || !class_exists('WebAppService'))
		{
				// instance 생성
				$this->WebAppService = &WebApp::singleton("WebAppService:system");
				
				// Query String
				WebAppService::$queryString = Func::QueryString_filter() ;
				// base URL
				WebAppService::$baseURL = $this->routeResult["baseURL"] ;
				
				if(!self::adm_hasLogin(array('flag'=>true, 'queryString'=>REQUEST_URI)) ){
					//Logged out. Please login again.
					$this->WebAppService->assign( array("error"=>"로그아웃되었습니다. 다시 로그인해 주세요.") );
				}
				
				//self::$mbr_conf["grade"] = WebApp::getConf("member.grade");
				set_time_limit(0);
		}

	}
	
	public function __destruct()
	{
		foreach($this as $k => &$obj){
			unset($this->$k);
		}
	}

	public function main()
	{
		if($this->routeResult["code"]=="coupon"){
			$Action = 'postCoupon';
			$CODE = $_REQUEST['coupon_serial'] ;
			$this->WebAppService->assign( array('COUPON_DATA' => $this->get_Coupon_data($_REQUEST['coupon_serial'])) ) ;
		}else if($this->routeResult["code"]=="memberSingle"){
			$Action = 'postMemberSingle';
			$CODE = $_REQUEST['mailTo'] ;
			WebAppService::$queryString = Func::QueryString_removeItem('mailTo', WebAppService::$queryString);
		}else{
			$Action = 'post';
			$CODE = '' ;
		}
		$this->WebAppService->assign(array(
				'Doc' => array(
						'baseURL' => WebAppService::$baseURL,
						'Action' => $Action,
						'queryString' => WebAppService::$queryString,
						/* 'formType' => "등록", */
				        'formType' => "add",
						'CODE' => $CODE
				),
				'MBR_GRADES' => $this->get_grades(),	//self::$mbr_conf["grade"]
				'EMAIL_TEMPLATE' => WebApp::getConf_real( "global.email_template" )['basedir']
		)) ;
		
		$this->WebAppService->Output( Display::getTemplate("html/adm/member/mailSend.html"), "admin_sub");
		//$this->WebAppService->Display->define('MENU_SUB', Display::getTemplate("_layout/adm/adm.menu.member.html")) ;
		$this->WebAppService->printAll();
	}
	
	/**
	 * 회원등급 설정 리스트
	 * 
	 * @param array $queryOption
	 * @return array
	 */
	/* private function get_grades( $queryOption=null )
	{
		$this->setTableName("member_grade");
		
		try
		{
			$data_grade = $this->dataRead(array(
					"columns"=> 'serial, oid, grade_code, grade_name, c_price_more, c_price_under, c_qty_more, benefit_discount_rate, benefit_point_rate',
			));
			
			return $data_grade;
		}
		catch (Exception $e) {
			echo $e->getMessage(), "\n";
			exit;
		}
		
	} */
	
	/**
	 * @desc [Ajax 요청] 상세내용 템플릿파일 내용 가져오기
	 *
	 * @uses ajax(비동기 처리)
	 * @return string json
	 */
	public function getFile()
	{
		// attach-file Read
		if( !empty($_POST['file']) && is_file($_POST['file']) ){
			$this->WebAppService->File->file($this->attach_basedir.$_POST['file'], 'r');
			//$this->WebAppService->File->readfile();
			$file_content = $this->WebAppService->File->readfile();
			$this->WebAppService->File->close();
			
			# 쿠폰페이지에서 메일전송시 ( referrer : /adm/promotion/Coupon/couponIssue/?? )
			if($this->routeResult["code"]=="coupon")
			{
				if( (int)$_POST['serial'] ){
					$data = $this->get_Coupon_data($_POST['serial']);
					if( !empty($data) ){
						/*사용기간[시작]*/$data['c_use_sdate']= ($data['c_use_sdate']) ? date('Y-m-d', $data['c_use_sdate']) : '' ;
						/*사용기간[종료]*/$data['c_use_edate']= ($data['c_use_edate']) ? date('Y-m-d', $data['c_use_edate']) : '' ;
						/*다운로드[시작]*/$data['c_dwn_sdate']= ($data['c_dwn_sdate']) ? date('Y-m-d', $data['c_dwn_sdate']) : '' ;
						/*다운로드[종료]*/$data['c_dwn_edate']= ($data['c_dwn_edate']) ? date('Y-m-d', $data['c_dwn_edate']) : '' ;
						/*혜택금액 [할인 최대금액]*/if($data['c_dis_rate_price']) $data['c_dis_rate_price']= number_format($data['c_dis_rate_price']) ;
						/*혜택금액[할인금액]*/if($data['c_dis_price']) $data['c_dis_price']= number_format($data['c_dis_price']) ;
						/*혜택금액[할인금액 사용 가능한 결제금액]*/if($data['c_dis_price_more']) $data['c_dis_price_more']= number_format($data['c_dis_price_more']) ;
						//===============================================================
						if($data['c_type'] == 1) $data['ouput_type'] = "주문적용쿠폰" ;
						/* if($data['c_use_day']) $data['ouput_use_date'] = "쿠폰 발급일로 부터 ".$data['c_use_day']." 일 까지" ;
						else $data['ouput_use_date'] = $data['c_use_sdate'] .'~'. $data['c_use_edate'] ; */
						$data['ouput_dwn_date'] = $data['c_dwn_sdate'] .'~'. $data['c_dwn_edate'] ;
						
						if($data['c_dis_rate']) {
							$data['ouput_dis'] = $data['c_dis_rate']. "% 상품할인 " ;
							if($data['c_dis_rate_price']) $data['ouput_dis'] .= "( $ ".$data['c_dis_rate_price']."까지 할인가능 )" ;
						}
						else{
							if($data['c_dis_price']) $data['ouput_dis'] = "$ ".$data['c_dis_price']. " 상품할인" ;
							if($data['c_dis_price_more']) $data['ouput_dis'] .= "( 결제금액 $ ".$data['output_dis_price']."이상 사용가능)" ;
						}
						
						// convert
						$file_content = str_replace('{title}', $data['c_title'], $file_content);
						$file_content = str_replace('{description}', $data['c_description'], $file_content);
						$file_content = str_replace('{dwn_date}', $data['ouput_dwn_date'], $file_content);
						$file_content = str_replace('{type_name}', $data['ouput_type'], $file_content);
						$file_content = str_replace('{coupon_code}', $data['c_code'], $file_content);
						$file_content = str_replace('{dis}', $data['ouput_dis'], $file_content);
					}
					
				}
			}
			// convert
			$file_content = str_replace('{HOST}', "http://".HOST, $file_content);
			
			$this->WebAppService->assign($file_content);
			exit;
		}
		else{
			$this->WebAppService->assign('');
			exit;
		}
	
	}
	
	/**
	 * 메일전송 처리
	 * 
	 * @param array $data ('from'=>??, 'to'=>??, 'subject'=>??, 'html'=>??.....)
	 * 
	 * @example $data = array(
					'from'=> array('email'=>이메일주소, 'name'=>발신자명), // 발신자
					'to'=> array('email'=>이메일주소, 'name'=>수신자명), // 수신자
					'subject'=>$subject, // 제목
					'html'=>$html, // 내용(html)
					'text'=>$text, // 내용(텍스트)
					'attachment' => 'image/2.png' // 첨부파일
				) ;
	 * 
	 * @return bool
	 */
	private function mail_send( $data )
	{
		if( empty($data) || !is_array($data)) return false ;
		//echo '<pre>';print_r($data);
		
		/* $POST_DATA = $data;
		if( ! isset($POST_DATA["from"]["email"]) ) {
			$POST_DATA["from"] = array() ;
			$POST_DATA["from"]["email"] = $data["from"] ;
		}
		if( ! isset($POST_DATA["to"]["email"]) ) {
			$POST_DATA["from"] = array() ;
			$POST_DATA["to"]["email"] = $data["to"] ;
		}
		
		
		Func::mailSend(
				$POST_DATA["to"]["email"], 
				$POST_DATA["from"]["email"], 
				$POST_DATA["subject"],
				$POST_DATA["html"]); */
		
		if( ! $this->MailService instanceof Mail_service) $this->MailService = new Mail_service();
		$res = $this->MailService->sendMailByPHPMailer( $data ) ;
		//$res = $this->sendMailByPHPMailer( $data );

		return $res ;
	}
	
	/**
	 * 직접입력하여 메일전송
	 *
	 * @param string $From_mail (발송자 email)
	 * @param string $_POST m_mail_to (수령자 이메일)
	 * @param string $_POST m_title (제목)
	 * @param string $_POST m_memo (내용)
	 */
	private function send_action_direct( $From_mail )
	{
		if( empty($From_mail) || !filter_var($From_mail["email"], FILTER_VALIDATE_EMAIL)) {
			WebApp::moveBack("발송자 메일 주소를 입력해주세요.");
		    //WebApp::moveBack("Please enter the sender's e-mail address.");
		}
		
		if( empty($_POST['m_mail_to']) || !filter_var($_POST['m_mail_to'], FILTER_VALIDATE_EMAIL)){
			WebApp::moveBack("수신자 메일 주소를 입력해주세요.");
		    //WebApp::moveBack("Please enter your recipient email address.");
		}
		
		if( empty($_POST['m_title']) ){
			WebApp::moveBack("메일 제목을 입력해주세요.");
		    //WebApp::moveBack("Please enter the subject of the mail.");
		}
		if( empty($_POST['m_memo']) ){
			WebApp::moveBack("메일 내용을 입력해주세요.");
		    //WebApp::moveBack("Please enter the contents of the mail.");
		}
		
		$post_data = array(
				'from'=> $From_mail,
				'to'=> array("email"=>$_POST['m_mail_to']),
				'subject' => $_POST['m_title'],
				'html'=>$_POST['m_memo']
		);
		
		$res = $this->mail_send( $post_data );
		/* echo '<pre>';print_r($post_data);
		echo '<pre>';print_r($res);exit; */
		return $res ;
	}
	
	/**
	 * 회원에게 메일전송
	 * 
	 * @param string $From_mail (발송자 email)
	 * @param array $queryOption ( DB : columns, where )
	 * @param closure $callback function($data) / $data <-- db data
	 * 							(필수!! --> return $data)
	 * @example $queryOption = array(
	 * 											"columns" => "column-name, column-name",
	 * 											"conditions" => string or array(.......)
	 * @return boolean
	 */
	private function send_action_member( $From_mail, $queryOption , $callback = NULL)
	{
		if( empty($From_mail) ) return false ;
		
		if( empty($queryOption['columns']) ) return false ;
		
		set_time_limit(0); 
		
		try{
			
				$sql = "SELECT ". $queryOption['columns'] ." FROM member " ;
				
				if( isset(self::$_where['conditions']) ) $sql .= self::$_where['conditions'];
				
				//---------------------------
				$this->DBconn();
				$stmt = $this->DB->mysqli()->stmt_init();
				$stmt->prepare($sql);
				//---------------------------
				// bind_params 가공
				//---------------------------
				
				if( !empty(self::$_where['values']) && is_array(self::$_where['values']) ){
					foreach (self::$_where['values'] as $prop => $val) {
						$type = gettype($val) ;
						
						$bindParams[0] .= $type == 'NULL' ? 's' : substr($type, 0,1);
						array_push($bindParams, self::$_where['values'][$prop]);
					}
					
					//$stmt->bind_param("is", $serial, $gname);
					call_user_func_array(array($stmt, 'bind_param'), \WebApp::refValues($bindParams));
				}
				
				//---------------------------
				$stmt->execute();
				$result = $stmt->get_result();
				//---------------------------
				$count = array(
						'total' => $result->num_rows, // db-table total records
						'success' => 0, // count - send success
						'faild' => 0 // count - send faild
				);
				
				while ($data = $result->fetch_assoc()) {
					
					//$count['total']++ ;
					
					if( empty($data['userid']) || !filter_var($data['userid'], FILTER_VALIDATE_EMAIL)){
						continue ;
					}
					$post_data = array(
							'from'=> $From_mail,
							'to'=> array("email"=>$data['userid'], "name"=>$data['username']),
							'subject' => $_POST['m_title']."-".($c++),
							'html'=>$_POST['m_memo']
					);
					if( is_object($callback) ) call_user_func($callback, $post_data);
					if( !empty($post_data) ) $res = $this->mail_send( $post_data );
					
					usleep(50000); // 0.05 second delay
					
					
					//Success
					if($res)
					{
						$count['success']++ ;
					}
					// Faild
					else{
						$count['faild']++ ;
					}
				}
				
				//---------------------------
				$stmt->free_result();
				$stmt->close();

				return $count ;
							
		}catch(Exception $e){
			$this->WebAppService->assign( array(
					"error" => $e->getMessage(),
					"error_code" => $e->getCode()
			));
		}
	}
	
	/**
	 * Mail Service 호출
	 * 
	 * @return string|array
	 */
	private function get_mail_From()
	{
	    if( ! $this->MailService instanceof Mail_service) $this->MailService = new Mail_service();
	    //$mail = &WebApp::singleton('Mail_service:service');
	    $From_mail = $this->MailService->get_from_mail() ;
	    return $From_mail ;
	}
	
	public function post()
	{
		if(REQUEST_METHOD=="POST")
		{
		    $From_mail = $this->get_mail_From() ;
		    
			if( empty($From_mail) ){
			    WebApp::moveBack("발송자 메일 주소를 입력해주세요.");
			    //WebApp::moveBack("Please enter the sender's e-mail address.");
			}
			
			// 회원등급
			if($_POST['m_target'] == 'mbrGrade')
			{
					//---------------------------
					self::$_where = "";
					$condition = array();
					
					// email 수신동의 체크
					if( (int)$_POST['m_target_mbrGrade_agree'] ){
						$condition = array( "agree_news" => 1 ) ;
					}
					$queryOption = array(
							"columns" => "userid, username",
							"conditions" => array_merge(array(	'grade' => (int)$_POST['m_target_mbrGrade']), $condition)
					);
					
					$this->Conditions( $queryOption["conditions"] ) ;
					if( empty(self::$_where) ) return false ;
					//---------------------------
					$res = $this->send_action_member( $From_mail, $queryOption );
					if( (int)$res['success'] ){
						$msg = "[Total: ".number_format($res['total'])."] ".$res['success']."개를 메일 전송되었습니다." ;
					    //$msg = "[Total: ".number_format($res['total'])."] ".$res['success']." mail have been sent." ;
						WebApp::redirect(WebAppService::$baseURL."/main".WebAppService::$queryString, $msg);
						exit;
					}else{
						WebApp::moveBack("메일 전송 실패");
					    //WebApp::moveBack("Failed to send mail.");
					}
			}
			// 회원전체
			else	if($_POST['m_target'] == 'mbrAll'){
				
					//---------------------------
					self::$_where = "";
					$condition = array();
					
					// email 수신동의 체크
					if( (int)$_POST['m_target_mbrAll_agree'] ){
						$condition = array("conditions" => "agree_news=1");
					}
					$queryOption = array_merge(array(
							"columns" => "userid, username"), $condition) ;
					
					$this->Conditions( $queryOption["conditions"] ) ;
					//---------------------------
					$res = $this->send_action_member( $From_mail, $queryOption );
					
					if( $res ){
						$msg = "[Total: ".number_format($res['total'])."] ".$res['success']."개를 메일 전송되었습니다." ;
					    //$msg = "[Total: ".number_format($res['total'])."] ".$res['success']." mail have been sent." ;
						WebApp::redirect(WebAppService::$baseURL."/main".WebAppService::$queryString, $msg);
						exit;
					}else{
					    WebApp::moveBack("메일 전송 실패");
					    //WebApp::moveBack("Failed to send mail.");
					}
				
			}
			// 직접입력
			else if($_POST['m_target'] == 'direct'){
				$res = $this->send_action_direct($From_mail);
				if($res)
				{
					WebApp::redirect(WebAppService::$baseURL."/main".WebAppService::$queryString, "메일 전송되었습니다.");
				    //WebApp::redirect(WebAppService::$baseURL."/main".WebAppService::$queryString, "Mail have been sent.");
					exit;
				}
				// Faild
				else{
					WebApp::moveBack("메일 전송 실패");
				    //WebApp::moveBack("[".$res['http_code']."] Failed to send mail.");
				}
			}
			
			
		}
		
	}
	
	/**
	 * 쿠폰 등록정보 가져오기
	 * 
	 * @param integer $serial
	 * @param string $columns ( shop_coupon TB - column )
	 * 
	 * @return array
	 */
	public function get_Coupon_data( $serial, $columns='*'){
		
		// 쿠폰 코드 값이 없을경우
		if( ! (int)$serial )
		{	// exception
			WebApp::moveBack("쿠폰 코드를 찾을 수 없습니다.");
		    //WebApp::moveBack("We could not find the coupon code.");
		}
		
		if( empty($columns) ) $columns = '*';
		
		try{
			
			// DB Table 선언
			$this->setTableName("shop_coupon");
			$data= $this->dataRead( array(
					"columns"=> $columns,
					"conditions" => array("serial" => $serial)
			));
			
			if( !empty($data) )
			{
				$data = array_pop($data) ;
			}
			
		}catch(Exception $e){
			$this->WebAppService->assign( array(
					"error" => $e->getMessage(),
					"error_code" => $e->getCode()
			));
		}
		return $data ;
	}
	
	public function postCoupon()
	{
		if( ! (int) $this->routeResult["code"]){
			WebApp::moveBack("Coupon Code가 없습니다.");
		    //WebApp::moveBack("There is no Coupon Code.");
		}
		
		$From_mail = $this->get_from_mail() ;
		if( empty($From_mail) || !filter_var($From_mail["email"], FILTER_VALIDATE_EMAIL)) {
		    WebApp::moveBack("발송자 메일 주소를 입력해주세요.");
		    //WebApp::moveBack("Please enter the sender's e-mail address.");
		}
		
		$data = $this->get_Coupon_data((int)$this->routeResult["code"]);
		
		if( empty($data) ) WebApp::moveBack("Coupon Code가 없습니다.");
		//if( empty($data) ) WebApp::moveBack("There is no Coupon Code.");
		
		$search_params = array();
		// 발급 대상[회원등급 인경우]
		if( (int) $data['c_target'] == 1)
		{
			if( (int) $data['c_tgm_grade'] ){
				// 해당 등급이상인 경우
				if( (int) $data['c_tgm_grade_more'] ){
					$search_params["grade >= ".(int) $data['c_tgm_grade']] = '' ;
				}
				// 해당 등급만
				else{
					$search_params["grade=".(int) $data['c_tgm_grade']] = '' ;
				}
			}
		}
		
		$queryOption = array(
				"columns" => "userid, username",
				"conditions" => $search_params
		);
		
		$this->Conditions( $queryOption["conditions"] ) ;
		if( empty(self::$_where) ) return false ;
		
		$res = $this->send_action_member( $From_mail, $queryOption ) ;
		if( (int)$res['success'] ){
			$msg = "[Total: ".number_format($res['total'])."] ".$res['success']."개를 메일 전송되었습니다." ;
		    //$msg = "[Total: ".number_format($res['total'])."] ".$res['success']." mail have been sent." ;
			WebApp::redirect("/adm/promotion/Coupon/couponIssue/".$this->routeResult["code"].WebAppService::$queryString, $msg);
			exit;
		}else{
		    WebApp::moveBack("메일 전송 실패");
		    //WebApp::moveBack("Failed to send mail.");
		}
			
	}
	
	public function postMemberSingle()
	{
		$From_mail = $this->get_from_mail() ;
		if( empty($From_mail) ){
		    WebApp::moveBack("발송자 메일 주소를 입력해주세요.");
		    //WebApp::moveBack("Please enter the sender's e-mail address.");
		}

		$res = $this->send_action_direct( $From_mail );
		
		//Success
		if($res['http_code'] == 200)
		{
			WebApp::redirect("/adm/Member/lst".WebAppService::$queryString, "메일 전송되었습니다.");
		    //WebApp::redirect("/adm/Member/lst".WebAppService::$queryString, "Mail have been sent.");
			exit;
		}
		// Faild
		else{
			WebApp::moveBack("[".$res['http_code']."] 메일 전송 실패");
		    //WebApp::moveBack("[".$res['http_code']."] Failed to send mail.");
		}
		
	}
	
	
	
	
	
}