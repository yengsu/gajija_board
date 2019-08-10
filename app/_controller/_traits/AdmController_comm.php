<?php
namespace Gajija\controller\_traits ;
//use Gajija\service\Member_service;

/**
 * Controller용 - 공용 메서드
 *
 */

trait AdmController_comm{
	//#########################################
	// 공용 - 매직 메소드 (http://php.net/manual/kr/language.oop5.magic.php)
	//#########################################
	/* protected function __call($name, $arguments)
	{echo 999;
		throw new \Exception("Method ".$name." is not supported.");
	}
	protected static function __callStatic($name, $arguments)
	{echo 999;
		throw new \Exception("static Method ".$name." is not supported.");
	}
	protected function __set($property, $value) {
		echo 999;
		if (property_exists($this, $property)) {
			return $this->$property = $value;
		}
		//$this->{$property} = $value;
		throw new \Exception("property ".$property." is not supported.");
	} */
	//#########################################
	protected function adm_hasMemberLogin($flag=0)
	{
		//$ret = $this->Member_service->hasLogin(array('flag'=>$flag, 'queryString'=>REQUEST_URI)) ;
		$ret = $this->adm_hasLogin(array('flag'=>$flag, 'queryString'=>REQUEST_URI)) ;
		if( is_bool($ret) ) return $ret ;
	}
	
	/**
	 * 관리자 로그인 유무 체크
	 *
	 * @param boolean $param( array('flag'=> boolean, 'mcode'=>메뉴코드, 'queryString'=>'......')
	 * @return boolean or mixed
	 * flag값이 true이면 로그인 페이지이동
	 * flag값이 false 또는 null인경우 bool 리턴
	 */
	protected static function adm_hasLogin($param=NULL)
	{
		if($request_type == 'ajax' || REQUEST_WITH == 'AJAX' ) $param['flag'] = null ;
		
		if( !isset($_SESSION['ADM']) || !trim($_SESSION['ADM']) )
		{
			if( !empty($param['flag']) && $param['flag'] )
			{
				$queryStr = '';
				if( !empty($param['queryString']) )
				{
					if( preg_match("/(^[?]+)/", $param['queryString']) ) $queryStr = $param['queryString'] ;
					else $queryStr = '?redir='.$param['queryString'] ;
				}
				header("Location: /Adm/manage".$queryStr);
				exit;
				
			}else{
				return false ;
			}
		}
		return true ;
	}
	/**
	 * Ajax 요청 처리 (READ)
	 * --> 회원 상세정보 데이타 가져오기
	 */
	protected function Req_getMbrDetail()
	{
		if(REQUEST_WITH != 'AJAX') {
			//header('Location:/') ;	exit;
			exit;
		}
		$data = $this->get_memberDetail( $_POST['userid'] ) ;
		if(!empty($data)) $this->WebAppService->assign( $data );
		else echo 0;
		
	}
	/**
	 * 회원등급 설정 리스트
	 *
	 * @param array $queryOption
	 * @return array
	 */
	private function get_grades( $queryOption=array() )
	{
	    $this->setTableName("member_grade");
	    
	    try
	    {
	        $data_grade = $this->dataRead(array_merge(array(
	            //"columns"=> 'serial, oid, grade_code, grade_name, c_price_more, c_price_under, c_qty_more, benefit_discount_rate, benefit_point_rate',
	            "columns"=> 'serial, grade_code, grade_name',
	            "order" => "grade_code"
	        ),$queryOption));
	        
	        return $data_grade;
	    }
	    catch (\Exception $e) {
	        echo $e->getMessage(), "\n";
	        exit;
	    }
	    
	}
	private function get_memberDetail( $userid )
	{
		if( empty($userid) ) return false ;
		
		// DB Table 선언
		$this->setTableName("member");
		$queryOption = array(
				"columns" => "*",
				"conditions" => array(
						//"withdrawal" => 1,
						"userid" => $userid
				));
		$data = $this->dataRead( $queryOption ) ;
		if(!empty($data))
		{
			$data = array_pop($data);
			unset($data['is_admin'], $data['userpw'], $data['ip']);
			//-----------------------------
			//회원등급 명
			$this->setTableName("member_grade") ;
			$data['grade_name'] = $this->dataRead(array(
					"columns" => "grade_code, grade_name",
					"conditions" => "grade_code='".$data['grade']."'"
			));
			if(!empty($data['grade_name'])) $data['grade_name'] = array_pop(array_pop($data['grade_name'])) ;
			
			// 회원(자신) 배송지 주소
			$this->setTableName("shipping_addr") ;
			$data['shippingAddr'] = $this->dataRead(array(
					"columns" => "rec_name, rec_zip, rec_city, rec_addr, rec_phone",
					"conditions" => array(
							"userid" => $userid
					)
			)) ;
			
			/*성별*/
			if( (int)$data['sex']==1) {
				$data['sex'] = "Male" ;
			}else if( (int)$data['sex']==2) {
				$data['sex'] = "Female" ;
			}
			/*생일*/if($data['birthday']) $data['birthday'] = substr($data['birthday'], 0,4).'-'.substr($data['birthday'], 4,2).'-'.substr($data['birthday'], 6,2) ;
			/*회원탈퇴 일자*/$data['withdrawal_date'] = date('Y-m-d H:i:s', $data['withdrawal_date']) ;
			/*등록일자*/$data['regdate'] = date('Y-m-d H:i:s', $data['regdate']) ;
			/*총 주문건수*/$data['total_oea'] = number_format($data['total_oea']) ;
			/*총 주문금액*/$data['total_oprice'] = number_format($data['total_oprice']) ;
			/*총 포인트*/$data['total_point'] = number_format($data['total_point']) ;
			/*피부타입*/$data['add_skin_type'] = self::$mbr_conf['skin_type'][$data['add_skin_type']] ;
			/*피부고민*/$data['add_skin_anxiety'] = self::$mbr_conf['skin_anxiety'][$data['add_skin_anxiety']] ;
			
			// 선호 브랜드
			$this->setTableName("brand") ;
			$datas_brand = $this->dataRead(array(
					"columns" => "name",
					"conditions" => "serial in (".$data['add_pref_brand1'].",".$data['add_pref_brand2'].",".$data['add_pref_brand3'].")"
			)) ;
			if( !empty($datas_brand) ){
				foreach($datas_brand as $brand){
					$data['add_pref_brand'][] = $brand['name'] ;
				}
				$data['add_pref_brand'] = implode(', ', $data['add_pref_brand']) ;
			}
		}
		
		return $data ;
	}
	
}