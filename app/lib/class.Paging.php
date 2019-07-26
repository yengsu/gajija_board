<?php
class Paging
{
	public $config = array(
    			'pageVariable'   => 'page',
    			//'numberFormat'   => '[%n]',
    			'showFirstLast'  => true,   // 맨처음, 맨 마지막으로 가는 링크를 만들것인가.
    			'itemPerPage'    => 10, // 리스트 목록수
    			'pagePerView'    => 10 // 페이지당 네비게이션 항목수
    	);
	public $totalItem;
    public $DATA = array();
	
	/**
	 * URL Query String
	 * 
	 * @var string
	 */
    public static $queryString ;
    
    public function __construct()
    {
    	if( !self::$queryString ) self::$queryString = $_SERVER["QUERY_STRING"] ;
    }

	public function index($total=1,$qs='', $currentPage)
	{
		$this->totalItem = $total ;
		$this->currentPage = (int)$currentPage ? (int)$currentPage : 1 ;

		if( !empty($qs)) self::QueryString_filter($qs);
	}
	/**
	 * URL Query String(Parameters) 처리
	 * 
	 * [usage] Paging::QueryString_filter( array<key, value> )
	 * 
	 * @param array $add_qs <key, value>
	 * @return string self::$queryString (result => ?a1=10&b2=20....)
	 */
	public static function QueryString_filter($add_qs)
	{
		if(!self::$queryString) self::$queryString = $_SERVER["QUERY_STRING"] ;
		self::$queryString = preg_replace("/(^[?]+)/", '', self::$queryString) ;
		parse_str( self::$queryString, $queryString) ;

		if( is_array($add_qs) ) 
			$queryString = array_merge($queryString, $add_qs);

		self::$queryString = http_build_query($queryString);
		if(self::$queryString) $res = '?'. self::$queryString;
		
		return $res ;
	}
	
    public function setConf($key,$val='')
    {
        if (is_array($key)) $this->config = array_merge($this->config,$key);
        else $this->config[$key] = $val;
    }
    public function setTotal($total)
    {
        $this->totalItem = $total;
    }
	/*
	return : 
		(array)$DATA

			- Values : 
					first(처음), prev(이전), current(현재페이지), no(페이지번호), next(다음), end(마지막)

			- Structure : 
					first = array( 
							num => ???,
							url => ???
					);
	*/
    public function output()
    {
    	$this->DATA = array(); // 초기화
		
    	$this->DATA['pageVariable'] = $this->config['pageVariable'] ;
    	$this->DATA['current'] = $this->currentPage ;
    	
		if((!$AllPage = ceil( $this->totalItem / $this->config['itemPerPage'] )) || $AllPage < $this->currentPage || !is_numeric( $this->currentPage) || strstr($this->currentPage, ' ') ) $this->currentPage = 1 ;
		$perpage = ($this->currentPage-1) * $this->config['itemPerPage'] ;

		if ($this->config['showFirstLast']) {
        	  	$this->DATA['first'] = array( 
        	  		'num' => 1, 
        	  		'url' => self::QueryString_filter( array($this->config['pageVariable']=>1) )//$this->qs->setVar($this->config['pageVariable'],1) 
        	  	) ;
        	  	$this->DATA['last'] = array( 
        	  		'num' => $AllPage,
        	  		'url' => self::QueryString_filter( array($this->config['pageVariable']=>$AllPage) ) //$this->qs->setVar($this->config['pageVariable'],$AllPage) 
        	  	) ;
		}

    	$lysi = ( $this->config['pagePerView']*intval(($this->currentPage-1)/$this->config['pagePerView']) ) + 1 ;
		$prevPage = $lysi - 1 ;
		$next = $lysi + $this->config['pagePerView'] ;
    	if($this->totalItem){

    		if ($lysi > $this->config['pagePerView']) {
    			$this->DATA['prev'] = array(
				 	'num' => $prevPage, 
				 	'url' => self::QueryString_filter( array($this->config['pageVariable']=>$prevPage) )//$this->qs->setVar($this->config['pageVariable'],$prevPage)
				 );
    		}

    		for ($i=$lysi; $i<$next; $i++)
			{
	 			if ($i <= $AllPage) {
	 				$ky = $i-1 ;
	            	$this->DATA['no'][$ky] = array(
						 	'num' => $i, 
						 	'url' => self::QueryString_filter( array($this->config['pageVariable']=>$i) )//$this->qs->setVar($this->config['pageVariable'],$i)
					);
		            if ($i == $this->currentPage) 
		            {
		                $this->DATA['no'][$ky]['current']= 1;
		            }
	 			}
			}
			if ($next-1< $AllPage) {
				$this->DATA['next'] = array(
					 	'num' => $next, 
					 	'url' => self::QueryString_filter( array($this->config['pageVariable']=>$next) )//$this->qs->setVar($this->config['pageVariable'],$next)
			 	);
			}
		}
		
		return $this->DATA ;
    }

    public function getOffset() {
        return ($this->config['itemPerPage'] * ($this->currentPage - 1));
    }

    public static function refValues($arr)
	{
        if (strnatcmp(phpversion(),'5.3') >= 0) // PHP 5.3+이상에서 호환
        {
            $refs = array();
            foreach( $arr as $key => $value)
                $refs[] = &$arr[$key];
                //$refs[$key] = &$arr[$key];
            return $refs;
        }
        return $arr;
    }
}
?>
