<?php
namespace Gajija\model ;

/**
 * @author : 이영수 (youngsu lee)
 * @email : yengsu@hanmail.net
 *
 * @tutorial array관련 처리 ( 대량의 배열 처리엔 적합하지 않음 )
 */
class NestedArray_model{
	
	public static $datas =  array();
	/* 'A' => array('lft' => 1, 'rgt' => 9,'title'=>'aa'),
	 'B' => array('lft' => 2, 'rgt' => 4,'title'=>'bb'),
	 'C' => array('lft' => 5, 'rgt' => 8,'title'=>'cc'),
	 'D' => array('lft' => 6, 'rgt' => 7,'title'=>'dd'),
	 'E' => array('lft' => 10, 'rgt' => 11,'title'=>'ee')
	 ); */
	
	/**
	 * @param string $Title 타이틀
	 * @param integer $Unique 유니크 코드(auto increment)
	 */
	public function __construct($Title='HOME', $Unique=1)
	{
		self::$datas = array(
				array(
						'title' => $Title,
						'lft' => 0,
						'rgt' => 1,
						'indent' => 0,
						'serial' => $Unique //Unique 코드
				)
		);
	}
	public function _add( $put_data, $idx )
	{
		self::$datas[$idx] = $put_data ;
	}
	
	/**
	 * 신규등록 (또는 선택된 노드의 자식노드를 추가[$this->_nodeAddChildren() 동일])
	 *
	 * @param array<key,value> $put_data
	 * @param array<family, depth> $Adj
	 * @return array
	 */
	public function _adds( $put_datas, $ky='', $keyValue='' )
	{
		
		$read = array(
				/* 'lft' => 0,
				 'rgt' => 0,
				 'indent' => 0,
				 'serial' => null */
		) ;
		if($ky && $keyValue){
			$read = \Func::array_searchKeyValue(self::$datas, $ky, $keyValue);
		}else{
			$read = self::$datas[0];
		}
		
		foreach($put_datas as $data)
		{
			$put_data = array_merge($data, array(
					'lft'=>(int)$read['rgt'],
					'rgt'=> (int)$read['rgt']+1,
					'serial'=> $read['serial']+1
					,'parent'=> $read['serial']
					,'indent'=> (int)$read['indent']+1
			)) ;
			//$put_data['cnt_children'] = self::_get_nodeChildCount($put_data);
			
			$this->_add_allUpdate($read) ;
			self::$datas[] = $put_data ;
			
			$read['rgt']=$put_data['rgt']+1;
		}
		//echo '<pre>';print_r(self::$datas);
	}
	/**
	 * insert시 전체 업데이트
	 *
	 * @param array &$data ( 'lft'=>int, 'rgt'=>int .... )
	 * @return void
	 */
	private function _add_allUpdate( &$data )
	{
		foreach( self::$datas as &$item ){
			if( $item['rgt'] >= $data['rgt']) $item['rgt'] +=  2 ;
			if( $item['lft'] > $data['rgt']) $item['lft'] += 2 ;
		}
	}
	/**
	 * 현재노드의 자식노드 총 갯수
	 * @param array $data ( 'lft'=>int, 'rgt'=>int .... )
	 * @return number
	 */
	public static function _get_nodeChildCount(&$data)
	{
		return (int) ( ( (int)$data['rgt'] - (int)$data['lft']) -1 ) / 2 ;
	}
	/**
	 * 하나 레코드 배열정보 가져옴
	 * @param string|int $ky
	 * @param string $keyValue
	 * @return multitype:NULL
	 */
	public function _get_data( $ky, $keyValue=null )
	{
		return \Func::array_searchKeyValue(self::$datas, $ky, $keyValue); ;
	}
	/**
	 * [실험중] 자신 포함해서 자식노드 모두 삭제
	 *
	 * ■■문제점■■ :<br>
	 * 		childs 값이 올바르지 않음. 삭제된 연관노드들에 대해 -1씩 더 빼줘야함.<br>
	 * 		parent 값이 올바르지 않음.<br>
	 * 		indent 값이 확인이 필요함;
	 *
	 * @param integer $serial
	 * @return array
	 */
	public function _nodeDeleteContainChild($key, $keyValue)
	{
		$read = \Func::array_searchKeyValue(self::$datas, $key, $keyValue);
		if( empty($read) ) return false ;
		
		$del_cnt = 0;
		foreach( self::$datas as $key => $item ){
			
			if( self::$datas[$key]['lft'] >= $read['lft'] && self::$datas[$key]['lft'] <= $read['rgt'] ) {
				unset(self::$datas[$key]) ;
				$del_cnt ++ ;
			}
			
		}
		foreach( self::$datas as $key => $item ){
			if($del_cnt){
				if( self::$datas[$key]['lft'] > $read['rgt']) self::$datas[$key]['lft'] -= round( ($read['rgt']-$read['lft']+1) ) ;
				if( self::$datas[$key]['rgt'] > $read['rgt']) self::$datas[$key]['rgt'] -= round( ($read['rgt']-$read['lft']+1) ) ;
			}
			if(self::$datas[$key]['lft'] < 0) self::$datas[$key]['lft'] = 0 ;
		}
		
	}
	
	/**
	 * 선택한 자신의 노드만 삭제 (자식노드 삭제 안함)
	 *
	 *  --> 자식노드가 존재하면 자식노드들을 한단계 위의 indent로 올리기
	 
	 * @param integer $serial
	 * @return array
	 */
	public function _nodeDeleteSelect($ky, $keyValue)
	{
		$del_flag = 0 ;
		
		$read = \Func::array_searchKeyValue(self::$datas, $ky, $keyValue);
		if( empty($read) ) return false ;
		
		// 선택된 노드 삭제
		foreach( self::$datas as $key => $item ){
			if( !isset($item[$ky]) ) return false ;
			if( $item[$ky]==$keyValue ){
				unset(self::$datas[$key]) ;
				$del_flag = 1 ;
				break ;
			}
		}
		if($del_flag == 0) return false ;
		
		// 전체 노드 position 변경
		foreach( self::$datas as $key => $item ){
			if( self::$datas[$key]['lft'] >= $read['lft'] && self::$datas[$key]['lft'] <= $read['rgt'] ) {
				self::$datas[$key]['depth'] -= 1 ;
			}
			if( self::$datas[$key]['rgt'] >= $read['lft'] && self::$datas[$key]['rgt'] <= $read['rgt'] ) {
				self::$datas[$key]['rgt'] -= 1 ;
			}else if( self::$datas[$key]['rgt'] > $read['rgt'] ){
				self::$datas[$key]['rgt'] -= 2 ;
			}
			if( self::$datas[$key]['lft'] >= $read['lft'] && self::$datas[$key]['lft'] <= $read['rgt'] ) {
				self::$datas[$key]['lft'] -= 1 ;
			}else if( self::$datas[$key]['lft'] > $read['rgt'] ){
				self::$datas[$key]['lft'] -= 2 ;
			}
			
		}
	}
	
	/**
	 * 선택된 노드의 자식노드를 추가
	 *
	 * @param array<key,value> $put_data
	 * @param array<family, depth> $Adj
	 * @return array
	 */
	public function _nodeAddChildren( $put_data, $key, $keyValue )
	{
		$read = \Func::array_searchKeyValue(self::$datas, $key, $keyValue);
		if( empty($read) ) return false ;
		
		foreach( self::$datas as $key => $item ){
			if( self::$datas[$key]['rgt'] >= $read['rgt']) self::$datas[$key]['rgt'] += 2 ;
			if( self::$datas[$key]['lft'] > $read['rgt']) self::$datas[$key]['lft'] += 2 ;
		}
		$put_data = array_merge($put_data, array('lft'=>$read['rgt'], 'rgt'=> $read['rgt']+1, 'parent'=> $read['gcode'], 'indent'=> $read['indent']+1) ) ;
		array_push( self::$datas, $put_data );
	}
	
	/**
	 * 부모노드들 정보 가져오기(상위에서 단일노드까지)
	 *
	 * @param unknown $idx (찾을 node key)
	 * @param unknown $put_data (아직 사용안함)
	 * @return multitype (1차원배열로 저장)
	 */
	public function _nodeGetParent( $key, $keyValue, $put_data = NULL )
	{
		$read = \Func::array_searchKeyValue(self::$datas, $key, $keyValue) ;
		
		$res = array();
		$depth = $read['depth'] -- ;
		foreach( self::$datas as $key => $item ){ // && $datas[$key]['depth']==$depth
			if( self::$datas[$key]['lft'] < $read['lft'] && self::$datas[$key]['rgt'] > $read['rgt'] ){
				
				array_push($res, self::$datas[$key]) ;
				
			}
		}
		return $res ;
	}
	
	/**
	 * 
	 * @param array $read ('lft'=> int, 'rgt'=> int)
	 * @return array
	 */
	public function _get_path(array $read )
	{
	    //$read = Func::array_searchKeyValue($datas, $key, $keyValue) ;
	    
	    $res = array();
	    //$depth = $read['depth'] -- ;
	    foreach( self::$datas as &$item ){ // && $datas[$key]['depth']==$depth
	        if( $item['lft'] <= $read['lft'] && $item['rgt'] >= $read['rgt'] ){
	            
	            array_push($res, $item) ;
	            
	        }
	    }
	    
	    return $res ;
	}
}