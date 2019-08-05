<?php
namespace Gajija\service\_traits ;
use Gajija\service\Member_service;

/**
 * Service-공용 메서드
 * 
  */
trait Service_Comm_Trait
{
	/**
	 * 권한정보
	 * 
	 * @uses Service_Comm_Trait의 authen_comm_grant 함수 참조
	 * @var array ( <br>
	 * 					group_name" => 그룹명,
					    "kind_code" => 분류코드,
					    "response" => array(
					    							"read" => array( // 응답정보
																		"code" => 0, // 응답코드
																		"msg" => null // 응답메시지
																	),
													"write" => array( // 응답정보
																		"code" => 0, // 응답코드
																		"msg" => null // 응답메시지
																	),
														....
												)
			    )
	 */
	public static $grant_content = array(
			"group_name" => null, // 그룹명 
			"kind_code" => null, // 분류코드
			"data" => null, // 권한정보
			"response" => array() // 응답정보
	) ;
	
	//#########################################
	// 공용 - 매직 메소드 (http://php.net/manual/kr/language.oop5.magic.php)
	//#########################################
	public function __call($name, $arguments)
	{
		throw new \Exception("Method ".$name." is not supported.");
	}
	public static function __callStatic($name, $arguments)
	{
		throw new \Exception("static Method ".$name." is not supported.");
	}
	//#########################################
	/**
	 * 회원형인 경우 로그인체크 및 리다이렉션
	 * @param bool $return (true:로그인 유무 리턴 / false: 로그인페이지 redirection 이동)
	 */
	public function hasMemberLogin($return=false)
	{
		//if(! class_exists('Member_service')) $this->Member_service = new Member_service();//\service\Member_service ;
		if( ! $this->Member_service instanceof Member_service ) $this->Member_service = Member_service ;
		
		$ret = $this->Member_service->hasLogin(array('return'=>$return, 'queryString'=>REQUEST_URI)) ;
		if( is_bool($ret) ) return $ret ;
	}

	/**
	 * 권한정보 가져오기 (DB데이타)
	 *
	 * @param string|integer $group_name
	 * @param string|integer $kind_code
	 * 
	 * @return array|null $data_grant
	 */
	public function get_grant($group_name, $kind_code)
	{
		$this->setTableName("grants");
	
		$conditions = array(
				"group_name" => (string)$group_name
		);
		if( !empty($kind_code) ) $conditions["kind_code"] = (string)$kind_code ;

		$data_grant = $this->dataRead(array(
				"columns"=> 'serial as grant_serial, grant_read, grant_write, grant_update, grant_delete',
				"conditions" => $conditions
		));
		
		return $data_grant ;
	}
	
	/**
	 * [권한인증] 응답결과 (읽기,쓰기,수정,삭제...)
	 *
	 * @param string $type (read | write | update | delete ....)
	 * 
	 * @return $this ($this->grant_content)
	 *
	 * @access integer $this->grant_content
	 * 		200 : 인증성공 <br>
	 *		401 : (권한 없음) 이 요청은 로그인 인증이 필요하다. 서버는 로그인이 필요한 페이지에 대해 이 요청을 제공할 수 있다. <br>
	 *		405 : (허용되지 않는 방법) 정의된 요청(read | write | update | delete)외에 방법을 사용할 수 없다. <br>
	 *		406 : (허용되지 않음) 접근권한 조건에 만족하지 않는경우
	 */
	public function grant_response($type='')
	{
		//if( preg_match('/^(read|write|update|delete)$/i', $this->grant_content['type']) )
		//{
			$msg['read'] = "읽기권한이 없습니다." ;
			$msg['write'] = "쓰기권한이 없습니다." ;
			$msg['update'] = "수정권한이 없습니다." ;
			$msg['delete'] = "삭제권한이 없습니다." ;
			
			$data_grant = $this->get_grant($this->grant_content['group_name'], $this->grant_content['kind_code']) ;
			//--------------------------------
			if( !empty($data_grant[0]) )
			{
				unset($data_grant[0]['grant_serial']) ; // 필요없어서 삭제
				
				//=================================================
				// 등급명을 가져옴
				//=================================================
				/* $this->setTableName("member_grade");
				$data_grade = $this->dataRead(array(
						"columns"=> 'grade_code, grade_name'
				));
				if( !empty($data_grade) )
				{
					$data_grade = $data_grade[0] ;
					foreach($data_grant[0] as $grant_name => $grant_value)
					{
							
							if( strpos($grant_name, "grant_") !== FALSE )
							{
								echo '<pre>';print_r($grant_name) ;
								foreach($data_grade as $grade){
									
								}
							}
							
					}
				} */
				//=================================================
				
				
				
				
				$this->grant_content['data'] = $data_grant[0] ;
				
				foreach($data_grant[0] as $typ => $grant_val) 
				{
					if( strpos($typ, "grant_") !== FALSE ) 
					{
						$grant_type = strtolower( str_replace("grant_", "", $typ) ) ;
						
						if( (int)$_SESSION['ADM'] == 1 )
						{
							$response_code =  200 ; // 권한인증 성공(OK)
						}
						else{
							if( (int)$_SESSION['MBRGRADE'] >= (int)$grant_val ){
								$response_code =  200 ; // 권한인증 성공(OK)
							}else{
								if( ! (int)$_SESSION['MBRGRADE'] && (int)$grant_val ){
									$response_code =  401 ; // 로그인 인증이 필요
								}else{
									$response_code =  406 ; // 권한인증 실패
								}
							}
						}
						//--------------------------------------
						
						if($response_code == 401){
							$response_msg = "로그인후 이용해주세요." ;
						}
						else if($response_code == 200){
							//$response_msg = "인증성공" ;
						}
						else if($response_code != 200)
						{
							$response_msg = $msg[$grant_type] ;
						}
						
						//--------------------------------------
						// 응답정보
						// $grant_type :: read . write . update . delete .... 
						$this->grant_content['response'][$grant_type] = array(
								"code" => $response_code, // 응답코드
								"msg" => $response_msg // 응답메시지
						) ;
						
						/* if( !empty($type) ){
							if($grant_type == $type) break ;
						} */
						
					}
				}
				
				//echo '<pre>';print_r($this->grant_content);
			}
			else{

				$this->grant_content['response'] = array(
						"read" => array("code"=> 200),
						"write" => array("code"=> 200),
						"update" => array("code"=> 200),
						"delete" => array("code"=> 200)
				);
			}
			
		//}
	}
	/**
	 * [권한:인증타입] 문자를 소문자로 변경 : read, write, update, delete ...
	 * 
	 * @param string &$grant_type
	 * @return void
	 */
	public function grant_getType( &$grant_type )
	{
		/* if( preg_match('/^(read|view)$/i', $grant_type) ) $grant_type = 'read'; // return 'read'
		//else if( preg_match('/^(write)$/i', $grant) ) return 'write';
		//else if( preg_match('/^(update)$/i', $grant) ) return 'update';
		//else if( preg_match('/^(delete)$/i', $grant) ) return 'delete';
		else $grant_type = strtolower($grant_type) ; // return strtolower($grant_type) ; */
		$grant_type = strtolower($grant_type) ;
	}
	/**
	 * [권한:범용] 컨텐츠 사용권한 인증 정보
	 * 
	 * @param string $grant_type (read | write | update | delete)
	 * @param string $group_name (그룹명)
	 * @param string|int $kind_code (그룹의 분류코드)
	 * @return void 
	 * 
	 * @access 응답변수 참조 : array $this->grant_content
	 */
	public function authen_comm_grant( $grant_type, $group_name, $kind_code=NULL)
	{
		$this->grant_content['group_name'] = $group_name ;
		$this->grant_content['kind_code'] = $kind_code ;

		$this->grant_getType($grant_type) ;
		$this->grant_response($grant_type) ;
	}
	/**
	 * [조회 또는 다운로드 및 컨텐츠] 사용자 ip 추가
	 * 
	 * @param array $put
	 * @return boolean
	 * 
	 * @uses db의 viewcnt 테이블 참조
	 * 
	 * @example $put = array(
				"oid" => (int) OID, // 업체코드
				"group_code" => null, // 그룹코드 ( ex: 'board' or 'page' or 'member'... ) 
				"class_code" => null, // 분류코드 ( ex: 'qna' or 'free' ...)
				"kind_name" => null, // 종류 ( ex: 'abcd.pdf' or 'okbag.xls' ...)
				"serial_code" => null, // 해당 DB Table 의 P.K 코드
				"ip" => $_SERVER['REMOTE_ADDR'],
				"regdate" => time(),
		);
	 */
	
	public function add_ip( array $put )
	{
		if( !is_array($put) || empty($put) || ctype_space($put) ) return false ;

		if( empty($put['group_code']) || empty($put['serial_code']) ) return false ;

		$conditions = array("group_code" => $put["group_code"] ) ;
		if( !empty($put["class_code"]) ){
			$conditions["class_code"] = $put["class_code"] ;
		}
		if( !empty($put["kind_name"]) ){
			$conditions["kind_name"] = $put["kind_name"] ;
		}
		$conditions["serial_code"] = $put["serial_code"] ;
		$conditions["ip"] = $_SERVER['REMOTE_ADDR'] ;
		
		$prev_tableName = self::$TABLE ;
		
		$this->setTableName("viewcnt");
		
		$exist_data = $this->count( "serial", $conditions ) ;
		if( (int)$exist_data ) return false ;

		$put_data = array_merge(array(
				"oid" => (int) OID, // 업체코드
				"group_code" => '', // 그룹코드 ( ex: 'board' or 'page' or 'member'... ) 
				"class_code" => '', // 분류코드 ( ex: 'qna' or 'free' ...)
				"kind_name" => '', // 종류 ( ex: 'abcd.pdf' or 'okbag.xls' ...)
				"serial_code" => null, // 해당 DB Table 의 P.K 코드
				"ip" => $_SERVER['REMOTE_ADDR'],
				"regdate" => time(),
		), $put) ;
		
		
		try
		{
			$insert_id = $this->dataAdd_base( $put_data ) ;
			
			$this->setTableName($prev_tableName);
			
			if( (int) $insert_id ) return true ;
		}
		/* catch (BaseException $e) {
			$e->printException('controller');
		} */
		catch (Exception $e) {
			$this->WebAppService->assign( array(
					"error" => $e->getMessage(),
					"error_code" => $e->getCode()
			));
			exit;
		}
	}
}