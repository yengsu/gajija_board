<?php
namespace Gajija\service\board\admin ;
use Gajija\model\board\admin\BoardInfo_model;
use Gajija\service\_traits\db\Service_DBCommNest_Trait;
use Gajija\service\_traits\Service_Comm_Trait;
use Gajija\service\_traits\Service_Board_Trait;


/**
 * 게시판 모델
 */
/**
 * 게시판(Board)
 * :: 일반형, 계층형.... 
 * 
 * @author youngsu lee
 * @email yengsu@gmail.com
 */
class BoardInfo_service extends BoardInfo_model
{
		use Service_Comm_Trait, Service_DBCommNest_Trait, Service_Board_Trait;
		/**
		 * 게시판 환경정보 Class Object
		 * @var object 
		 */
		//public $Board_info ;
		/**
		 * 첨부파일 저장 경로
		 * @var string ( default :  'html/_attach/board/' )
		 */		
		//private static $attach_basedir = 'html/_attach/board/' ;
		/**
		 * 게시판 스킨 기본경로
		 * @var string
		 */
		//public static $skin_basedir = 'html/board/skin/' ;

}