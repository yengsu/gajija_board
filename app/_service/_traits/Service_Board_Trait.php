<?php
namespace Gajija\service\_traits ;
/**
 * Service-게시판 공용 메서드
 * 
 * @author young lee
 * @email yengsu@gmail.com
  */
trait Service_Board_Trait
{
		/**
		 * 첨부파일 저장 경로
		 * @var string ( default :  'html/_attach/board/' )
		 */
		//public static $attach_basedir = 'html/_attach/board/' ;
		public static $attach_basedir = 'theme/'.THEME.'/_attach/board/';
		/**
		 * 게시판 스킨 기본경로
		 * @var string
		 */
		public static $board_skin_basedir = 'html/board/skin/' ;
		public static $comments_skin_basedir = 'html/comments/skin/' ;
	
		/**
		 * 스킨 디렉토리[게시판] 검색 (바로 하위 디렉토리만 얻기)
		 * 
		 * @param string $base_dir (검색할 디렉토리)
		 * @return array $directories
		 */
		public static function get_skins($base_dir)
		{
			$directories = array();
			if( is_dir($base_dir) )
			{
				foreach(scandir($base_dir) as $file) {
					if($file == '.' || $file == '..') continue;
					//$dir = $base_dir.DIRECTORY_SEPARATOR.$file;
					$dir = $base_dir.'/'.$file;
					if(is_dir($dir)) {
						$directories []= $file;
					}
				}
			}
			return (array) $directories;
		}
		
}