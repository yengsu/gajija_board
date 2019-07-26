<?php

class ExceptionHandlers extends Exception
{
	public function __construct()
	{

	}
	/**
	 * 출력
	 */
	public function printException()
	{
		/* $err = @end(debug_backtrace(false,3)) ;
		if( !empty($err) ){
			$title .= "<br>".$err['file']
			." [<span style='color:red;'> ".$err['line']." line ".$err["function"]." function Error</span> ] "
					."<br>";
		}
		echo $title; */
	}
	public static function warning($code)
	{
		switch($code)
		{
			case 204 : $msg = 'No content, 클라언트 요구을 처리했으나 전송할 데이터가 없음' ;break ;
			case 205: $msg = 'Reset content' ;break ;
			case 206: $msg = 'Partial content' ;break ;
			case 300: $msg = 'Multiple choices, 최근에 옮겨진 데이터를 요청' ;break ;
			case 301: $msg = 'Moved permanently, 요구한 데이터를 변경된 임시 URL에서 찾았음' ;break ;
			case 302: $msg = 'Moved temporarily, 요구한 데이터가 변경된 URL에 있음을 명시' ;break ;
			case 303: $msg = 'See other, 요구한 데이터를 변경하지 않았기 때문에 문제가 있음' ;break ;
			case 400: $msg = 'Bad request, 클라이언트의 잘못된 요청으로 처리할 수 없음' ;break ;
			case 401: $msg = 'Unauthorized, 클라이언트의 인증 실패' ;break ;
			case 403: $msg = 'Forbidden, 접근이 거부된 문서를 요청함' ;break ;
			case 404: $msg = 'Not found, 문서를 찾을 수 없음' ;break ;
			case 405: $msg = 'Method not allowed, 리소스를 허용안함' ;break ;
			case 406: $msg = 'Not acceptable, 허용할 수 없음' ;break ;
			case 408: $msg = 'Request timeout, 요청시간이 지남' ;break ;
			case 410: $msg = 'Gone, 영구적으로 사용할 수 없음' ;break ;
			case 413: $msg = 'Request entity too large,' ;break ;
			case 414: $msg = 'Request-URI too long, URL이 너무 김' ;break ;
			case 415: $msg = 'Unsupported media type' ;break ;
			case 500: $msg = 'Internal server error, 내부서버 오류(잘못된 스크립트 실행시)' ;break ;
			case 501: $msg = 'Not implemented, 클라이언트에서 서버가 수행할 수 없는 행동을 요구함' ;break ;
			case 502: $msg = 'Bad gateway, 서버의 과부하 상태' ;break ;
			case 503: $msg = 'Service unavailable, 외부 서비스가 죽었거나 현재 멈춤 상태' ;break ;
		}
		echo responseClient($msg);exit;
	}
	//header($_SERVER['SERVER_PROTOCOL'] . ' 500 okInternal Server Error', true, 500);
	/**
	 * 클라이언트에 보내는 방식
	 */
	public function responseClient()
	{
		if($request_type == 'ajax' || REQUEST_WITH == 'AJAX' )
		{
			echo $this->json_Callback($data, $jsonCallback_param) ;
			exit;
		}else{
			
		}
	}
}