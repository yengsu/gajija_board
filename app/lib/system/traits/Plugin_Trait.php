<?php
namespace system\traits ;
/**
 * Plugin 공용 클래스
 * 
  */
trait Plugin_Trait
{
	/**
	 * Plugin 응답 : 쇼핑상품 정보조회
	 * 
	 * @param array $args (
	 * 			baseURL : 기본 Url
	 * 			pageScale : 출력갯수
	 * 			pageBlock : 페이지 블럭
	 * 		)
	 * @param string $method (클래스 메서드명)
	 * @return boolean
	 */
	public function Plugin_put_Datas(&$args=NULL, $method)
	{
		if( empty($method) ) return false ;
		// plugin 데이타 처리
		if( !empty($args) && is_array($args)) {
			//if($args['table']) $this->setTableName( str_replace(" ", "", $args['table']));
			if($args['baseURL']) \WebAppService::$baseURL = $args['baseURL'] ;
			if($args['pageScale']) $this->pageScale = (int)$args['pageScale'] ;
			if($args['pageBlock']) $this->pageBlock = (int)$args['pageBlock'] ;
		}else{
			return false ;
		}

		// 쇼핑상품 데이타
		//$this->get_goods_lst();
		$this->$method();

		if($args['template']){
			$this->WebAppService->Output( \Display::getTemplate($args['template']));

			$content = $this->WebAppService->Display->fetch('CONTENT');
			ob_start();
			echo $content;
			ob_end_flush();
			ob_flush();
			flush();
		}
	}
	
}