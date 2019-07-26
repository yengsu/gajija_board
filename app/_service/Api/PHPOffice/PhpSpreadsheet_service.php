<?
namespace Gajija\service\Api\PHPOffice ;

/**
 * @desc PHPOffice - Excel Service 
 * 
 * @tutorial https://phpspreadsheet.readthedocs.io/en/latest/
 * 
 * @author yengsu lee
 * @email yengsu@gmail.com
 */
class PhpSpreadsheet_service
{
    /**
     * Api 제공업체 이름
     * @var string
     */
    public static $Provider = "PhpSpreadsheet" ; 
    
	public function __construct()
	{
		//throw new \Exception ( "SMS 서비스준비중입니다..", 501);
	    $this->init() ;
	}
	public function __destruct()
	{
	    foreach($this as $k => $obj){
	        unset($this->$k);
	    }
	}
	
	public function init()
	{
	    /** Error reporting */
	    //error_reporting(E_ALL);
	    error_reporting(0);
	    ini_set('display_errors', TRUE);
	    ini_set('display_startup_errors', TRUE);
	    //date_default_timezone_set('Asia/Seoul');
	    if (PHP_SAPI == 'cli')
	        die('This example should only be run from a Web Browser');
	        
	        require_once "PhpOffice/vendor/autoload.php" ;
	}
	
	/* protected function hasLoaded(){
	    if( ! $this->client instanceof \Spreadsheet )
	        $this->load();
	} */

}
