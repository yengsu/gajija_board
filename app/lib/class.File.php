<? 
/** 
* File handling class 
* 
* @author    Sven Wagener <wagener_at_indot_dot_de> 
* @include      Funktion:_include_ 
* http://www.phpclasses.org/package/1501-PHP-File-manipulation-and-form-upload-handling.html#view_files
*/ 
class File{ 
    var $file; 
    var $binary; 
    var $name; 
    var $size; 
     
    var $debug; 
    var $action_before_reading=false; 

    function __construct(){
    }
	public function __destruct()
	{
		$this->close();
		//unset($this);
	}
	public function __call($method, $args){
	}
	 /** 
    * Constructor of class 
    * @param string $filename The name of the file 
    * @param boolean $binarty Optional. If file is a binary file then set TRUE, otherwise FALSE 
    * @desc Constructor of class 
    */ 
	public function file($filename, $mode='a+', $perms = 0707, $binary=false){
		$this->action_before_reading=false; 
		$this->clearStatCache();
		$this->close();
		
		$this->name=$filename; 
        $this->binary=$binary; 

        if($binary){ 
			 $path_parts = pathinfo($filename) ;
			 $dirs = $path_parts['dirname'] ;
            if( !is_dir($dirs) ) {
            	 $oldumask = umask(0); 
            	mkdir( $dirs ,$perms, true) ;
            	umask($oldumask); 
            }
            
            $this->file=@fopen($filename, $mode.'b'); 
            @chmod($filename, $perms);
            if(!$this->file){ 
                $this->file=@fopen($filename,"rb"); 
            } 
        }else{ 
			 $path_parts = pathinfo($filename) ;
			 $dirs = $path_parts['dirname'] ;
            if( !is_dir($dirs) ) {
            	 $oldumask = umask(0); 
            	mkdir( $dirs ,$perms, true) ;
            	umask($oldumask); 
            }

            $this->file=fopen($filename, $mode); 
            @chmod($filename, $perms);
            if(!$this->file){ 
                $this->file=@fopen($filename,"r"); 
            }
        } 
	}
	public function delete($file) {
		if($file) $this->name = $file ;
		if (is_resource($this->file)) {
			fclose($this->file);
			$this->file = null;
		}
		if ($this->exists()) {	
			unlink($this->name);
			// 디렉토리에 파일이 존재하지않으면 폴더삭제
			if( preg_match( '/\//', $file) ){
				$path_parts = pathinfo($file) ;
				$dirs = $path_parts['dirname'] ;
				$files = array_diff(scandir($dirs), array('..', '.'));
				$files_cnt = count($files);
				if( $files_cnt < 1) rmdir($dirs);
			}
			return true;
		}
		return false;
	}
	public function close() {
		if (!is_resource($this->file)) {
			return true;
		}
		return fclose($this->file);
	}
	public function exists() {
		$this->clearStatCache();
		return (file_exists($this->name) && is_file($this->name));
	}
	/**
	 * Get the mime type of the file. Uses the finfo extension if
	 * its available, otherwise falls back to mime_content_type
	 *
	 * @return false|string The mimetype of the file, or false if reading fails.
	 */
	public function mime() {
		if (!$this->exists()) {
			return false;
		}
		if (function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME);
			$finfo = finfo_file($finfo, $this->name);
			finfo_close($finfo);
			if (!$finfo) {
				return false;
			}
			list($type, $charset) = explode(';', $finfo);
			return $type;
		}
		if (function_exists('mime_content_type')) {
			return mime_content_type($this->name);
		}
		return false;
	}
    /** 
    * Returns the filesize in bytes 
    * @return int $filesize The filesize in bytes 
    * @desc Returns the filesize in bytes 
    */ 
    function size(){ 
        return filesize($this->name); 
    } 
     
    /** 
    * Returns the timestamp of the last change 
    * @return int timestamp $timestamp The time of the last change as timestamp 
    * @desc Returns the timestamp of the last change 
    */ 
    function time(){ 
        return fileatime($this->name); 
    } 
     
    /** 
    * Returns the filename 
    * @return string $filename The filename 
    * @desc Returns the filename 
    */ 
    function name(){ 
        return $this->name; 
    } 
     
    /** 
    * Returns user id of the file 
    * @return string $user_id The user id of the file 
    * @desc Returns user id of the file 
    */ 
    function owner_id(){ 
        return fileowner($this->name); 
    } 
     
    /** 
    * Returns group id of the file 
    * @return string $group_id The group id of the file 
    * @desc Returns group id of the file 
    */ 
    function group_id(){ 
        return filegroup($this->name); 
    } 
     
    /** 
    * Returns the suffix of the file 
    * @return string $suffix The suffix of the file. If no suffix exists FALSE will be returned 
    * @desc Returns the suffix of the file 
    */ 
    function suffix(){ 
        $file_array=split("\.",$this->name); // Splitting prefix and suffix of real filename 
        $suffix=$file_array[count($file_array)-1]; // Returning file type 
        if(strlen($suffix)>0){ 
            return $suffix; 
        }else{ 
            return false; 
        } 
    } 
     
    /** 
    * Sets the actual pointer position 
    * @return int $offset Returns the actual pointer position 
    * @desc Returns the actual pointer position 
    */ 
    function pointer_set($offset){ 
        $this->action_before_reading=true; 
        return fseek($this->file,$offset); 
    } 
     
    /** 
    * Returns the actual pointer position 
    * @param int $offset Returns the actual pointer position 
    * @desc Returns the actual pointer position 
    */ 
    function pointer_get(){ 
        return ftell($this->file); 
    } 
	function fread(){
		return fread($this->file, $this->size());
	}
	/*
	readfile - output buffer
	*/
	function readfile(){ 
		ob_start();
		readfile($this->name);
		$data = ob_get_contents();
		ob_end_clean();
		//echo $data;exit;
        return $data;
    }
    /** 
    * Reads a line from the file 
    * @return string $line A line from the file. If is EOF, false will be returned 
    * @desc Reads a line from the file 
    */ 
    function read_line(){ 
        if($this->action_before_reading){ 
            if(rewind($this->file)){ 
                $this->action_before_reading=false; 
                return fgets($this->file); 
            }else{ 
                $this->halt("Pointer couldn't be reset"); 
                return false; 
            } 
        }else{ 
            return fgets($this->file); 
        } 
    } 
     
    /** 
    * Reads data from a binary file 
    * @return string $line Data from a binary file 
    * @desc Reads data from a binary file 
    */ 
    function read_bytes($bytes,$start_byte=0){ 
        if(is_int($start_byte)){ 
            if(rewind($this->file)){ 
                if($start_byte>0){ 
                    $this->pointer_set($start_byte); 
                    return fread($this->file,$bytes); 
                }else{ 
                    return fread($this->file,$bytes); 
                } 
            }else{ 
                $this->halt("Pointer couldn't be reset"); 
                return false; 
            } 
        }else{ 
            $this->halt("Start byte have to be an integer"); 
            return false; 
        } 
    } 
     
    /** 
    * Writes data to the file 
    * @param string $data The data which have to be written 
    * @return boolean $written Returns TRUE if data could be written, FALSE if not 
    * @desc Writes data to the file 
    */ 
    function write($data){ 
        $this->action_before_reading=true; 
        if(strlen($data)>0){ 
            if($this->binary){ 
                $bytes=fwrite($this->file,$data); 
                if(is_int($bytes)){ 
                    return $bytes; 
                }else{ 
                    $this->halt("Couldn't write data to file, please check permissions"); 
                    return false; 
                } 
            }else{ 
                $bytes=fputs($this->file,$data); 
                if(is_int($bytes)){ 
                    return $bytes; 
                }else{ 
                    $this->halt("Couldn't write data to file, please check permissions"); 
                    return false; 
                } 
            } 
        }else{ 
            $this->halt("Data must have at least one byte"); 
        } 
    } 
     
    /** 
    * Copies a file to the given destination 
    * @param string $destination The new file destination 
    * @return boolean $copied Returns TRUE if file could bie copied, FALSE if not 
    * @desc Copies a file to the given destination 
    */ 
    function copy($destination, $orginal=''){ 

    	//$original 추가
    	if(!empty($orginal)){
    		if( !is_file($original) ) echo 'no';
    		else echo 'yes';
    		if( !is_file($original) ) return false ;
    		if(copy($orginal,$destination)){ 
                return true; 
			}else{ 
                $this->halt("Couldn't copy file to destination, please check permissions"); 
                return false; 
			} 
    	}
        if(strlen($destination)>0){ 
            if(copy($this->name,$destination)){ 
                return true; 
            }else{ 
                $this->halt("Couldn't copy file to destination, please check permissions"); 
                return false; 
            } 
        }else{ 
            $this->halt("Destination must have at least one char"); 
        } 
    } 
    /** 
    * 파일안의 문자열 검색
    * 
    * @param string $string The string which have to be searched 
    * @return array $found_bytes Pointer offsets where string have been found. On no match, function returns false 
    * @desc Searches a string in file 
    */ 
    function search($string){ 
        if(strlen($string)!=0){ 
             
            $offsets=array(); 
             
            $offset=$this->pointer_get(); 
            rewind($this->file); 
             
            // Getting all data from file 
            $data=fread($this->file,$this->size()); 
             
            // Replacing \r in windows new lines 
            $data=preg_replace("[\r]","",$data); 
             
            $found=false; 
            $k=0; 
             
            for($i=0;$i<strlen($data);$i++){ 
                 
                $char=$data[$i]; 
                $search_char=$string[0]; 
                 
                // If first char of string have been found and first char havn't been found 
                if($char==$search_char && $found==false){ 
                    $j=0; 
                    $found=true; 
                    $found_now=true; 
                }                 
                 
                // If beginning of the string have been found and next char have been set 
                if($found==true && $found_now==false){ 
                    $j++; 
                    // If next char have been found 
                    if($data[$i]==$string[$j]){ 
                        // If complete string have been matched 
                        if(($j+1)==strlen($string)){ 
                            $found_offset=$i-strlen($string)+2; 
                            $offsets[$k++]=$found_offset; 
                        }                         
                    }else{ 
                        $found=false; 
                    } 
                     
                } 
                 
                $found_now=false;                 
            } 
             
            $this->pointer_set($offset); 
             
            return $offsets; 
        }else{ 
            $this->halt("Search String have to be at least 1 chars"); 
        } 
    } 
     
    /** 
    * Prints out a error message 
    * @param string $message all occurred errors as array 
    * @desc Returns all occurred errors 
    */ 
    function halt($message){ 
        if($this->debug){ 
            printf("File error: %s\n", $message); 
            if($this->error_nr!="" && $this->error!=""){ 
                printf("MySQL Error: %s (%s)\n",$this->error_nr,$this->error); 
            } 
            die ("Session halted."); 
        } 
    } 
     
    /** 
    * Switches to debug mode 
    * @param boolean $switch 
    * @desc Switches to debug mode 
    */ 
    function debug_mode($debug=true){ 
        $this->debug=$debug; 
        if(!$this->file){ 
            $this->halt("File couln't be opened, please check permissions"); 
        } 
    }
    /**
	 * Copies a folder and its content recursively
	 *
	 * @param string $source
	 * @param string $dest
	 */
	public static function copyFolder($dest, $source) { // recursive function
		if(is_dir( $source)) { // if its directory
           @mkdir($dest); //make it      
           $d = dir($source); // create directory object        
           while(FALSE!==($entry=$d->read())) { // read each file of directory
              if($entry=='.' || $entry == '..') { //exclude root and parent
                 continue;
              }        
              $newentry = $source . '/' . $entry;  // get sub         
              if(is_dir($newentry)) { // if sub is directory
                  self::copyFolder($newentry,$dest.'/'.$entry); // then go recursive
                  continue;
              }
              copy($newentry, $dest.'/'.$entry ); //else copy file
           }         
           $d->close();
        }
        else {
          copy($source,$dest); // copy file
        }
	}
	
	/**
	 * Clear PHP's internal stat cache
	 *
	 * For 5.3 onwards its possible to clear cache for just a single file. Passing true
	 * will clear all the stat cache.
	 *
	 * @param boolean $all Clear all cache or not
	 * @return void
	 */
	public function clearStatCache($all = false) {
		if ($all === false && version_compare(PHP_VERSION, '5.3.0') >= 0) {
			return clearstatcache();
			//return clearstatcache(true, $this->file);
		}

		return clearstatcache();
	}
	
	/**
	*
	* Directory & File
	*
	* result :
					Array
					(
				    [0] => common.js
				    [1] => freewall.js
				    [2] => func.js
				    [jquery] => Array
				        (
				            [0] => jquery-1.11.1.min.js
				            [1] => jquery-1.9.0.min.js
				        )
					)
	*
	* 아래 추가사항(개발예정)
	*			"type" => mime_content_type(),
	*	       "size" => filesize(),
	*	       "lastmod" => filemtime()
	* 
	*/
	public static function dirToArray($dir) { 

		$result = array(); 
	
		$cdir = scandir($dir); 
		foreach ($cdir as $key => $value) 
		{ 
		   if (!in_array($value,array(".",".."))) 
		   { 
		      if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) 
		      { 
		         $result[$value] = self::dirToArray($dir . DIRECTORY_SEPARATOR . $value); 
		      } 
		      else 
		      { 
		         $result[] = $value; 
		      } 
		   } 
		} 
	
		return $result; 
	}
	
	/**
	*
	* result :
	*
					Array
					(
				    [0] => /home/olives/public_html/js/func.js
				    [1] => /home/olives/public_html/js/jquery.js
				    [2] => /home/olives/public_html/js/jquery/jquery.easing.min.js
				    [3] => /home/olives/public_html/js/jquery/jquery.sortable.min.js
				    )
	*
	*/
	public static function list_directory($dir) {
		$file_list = array();
		$stack[] = $dir;

		while ($stack) {
		    $current_dir = array_pop($stack);
		    if ($dh = opendir($current_dir)) {
		        while (($file = readdir($dh)) !== false) {
		            if ($file !== '.' AND $file !== '..') {
		                $current_file = "{$current_dir}/{$file}";
		                $report = array();
		                if (is_file($current_file)) {
		                    $file_list[] = "{$current_dir}/{$file}";
		                } elseif (is_dir($current_file)) {
		                    $stack[] = $current_file;
		                    $file_list[] = "{$current_dir}/{$file}/";
		                }
		            }
		        }
		    }
		}

		return $file_list;
	}
	/**
	 * 디렉토리 모두 검색(하위디렉토리 포함) 
	 * 
	 * @param string $dir (디렉토리 명)
	 * @param resource &$dir_array ( Callback 변수 : 재귀 순환하면서 저장 )
	 * @uses 
	 * 		File::find_files("home/olives/public_html", $data) ;
	 * 		$data 변수에 값이 저장됨
	 * @example
	 * 		result -->
	 * 						Array
							(
							    [/home/olives/public_html/js] => Array
							        (
							            [0] => common.js
							            [2] => func.js
							            [4] => jquery.js
							            [5] => strings.js
							        )
							
							    [/home/olives/public_html/js/jquery] => Array
							        (
							            [0] => jquery-1.11.1.min.js
							            [1] => jquery-1.9.0.min.js
							            [2] => jquery-ui.min.js
							        )

	 */
	public static function find_files($dir, &$dir_array)
	{
		// Create array of current directory
		$files = scandir($dir);
		
		if(is_array($files))
		{
		    foreach($files as $val)
		    {
		        // Skip home and previous listings
		        if($val == '.' || $val == '..')
		            continue;
		        
		        // If directory then dive deeper, else add file to directory key
		        if(is_dir($dir.'/'.$val))
		        {
		            // Add value to current array, dir or file
		            $dir_array[$dir][] = $val;
		            
		            self::find_files($dir.'/'.$val, $dir_array);
		        }
		        else
		        {
		            $dir_array[$dir][] = $val;
		        }
		    }
		}
		ksort($dir_array);
	}
	/**
	 * 해당 하위 전체 디렉토리(glob 이용)
	 *
	 * @param string $base_dir(검색할 디렉토리)
	 * @param string $remove_str (디렉토리 문자열에서 삭제할 문자열)
	 * @return string[]
	 * @example
	 * 		result ->
	 * 				Array
					(
					    [0] => html/board/skin
					    [1] => html/board/skin/boardComm
					    [2] => html/board/skin/boardNormal
					    [3] => html/board/skin/default
					    ....
					)
	 */
	public static function find_dirsGlob($base_dir, $remove_str='')
	{
		$directories = array();
		$folders = glob($base_dir . '/*' , GLOB_ONLYDIR);
		foreach($folders as $file) {
			//$dir = $base_dir.DIRECTORY_SEPARATOR.$file;
			if(is_dir($file)) {
				if( !empty($remove_str) ){
					$directories []= str_replace($remove_str, '',$file);
					$directories = array_merge($directories, self::find_dirsGlob($file, $remove_str));
				}else{
					$directories []= $file;
					$directories = array_merge($directories, self::find_dirsGlob($file));
				}
			}
		}
		return $directories;
	}
	/**
	 * 해당 하위 전체 디렉토리(scandir 이용)
	 *
	 * @param string $base_dir(검색할 디렉토리)
	 * @param string $remove_str (디렉토리 문자열에서 삭제할 문자열)
	 * @return string[]
	 * @example
	 * 		result ->
	 * 				Array
					 (
					 [0] => html/board/skin
					 [1] => html/board/skin/boardComm
					 [2] => html/board/skin/boardNormal
					 [3] => html/board/skin/default
					 ....
					 )
	 */
	public static function find_dirsScan($base_dir, $remove_str='') {
		$directories = array();
		foreach(scandir($base_dir) as $file) {
			if($file == '.' || $file == '..') continue;
			//$dir = $base_dir.DIRECTORY_SEPARATOR.$file;
			$dir = $base_dir.'/'.$file;
			if(is_dir($dir)) {
				if( !empty($remove_str) ){
					$directories []= str_replace($remove_str, '',$dir);;
					$directories = array_merge($directories, self::find_dirsScan($dir, $remove_str));
				}else{
					$directories []= $dir;
					$directories = array_merge($directories, self::find_dirsScan($dir));
				}
			}
		}
		return $directories;
	}
	/**
	 * 디렉토리 모두 검색(하위디렉토리 포함) 
	 * 
	 * @param string $directory(디렉토리명)
	 * 
	 * @example
	  * 		result -->
								Array
								(
								    [skin] => Array
								        (
								            [boardComm1] => Array
								                (
								                    [0] => edit.htm
								                    [1] => list.htm
								                    [2] => view.htm
								                )
								
								            [boardNormal2] => Array
								                (
								                    [0] => edit.htm
								                    [1] => list.htm
								                )
								
								            [default] => Array
								                (
								                    [0] => edit.htm
								                    [1] => list.htm
								                    [2] => list_.htm
								                    [3] => view.htm
								                )
								
								        )
								
								)
	 */
	public static function scandir_recursive($directory) 
	{ 
	    $folderContents = array(); 
	    $directory = realpath($directory).DIRECTORY_SEPARATOR; 
	
	    foreach (scandir($directory) as $folderItem) 
	    { 
	        if ($folderItem != "." AND $folderItem != "..") 
	        { 
	            if (is_dir($directory.$folderItem.DIRECTORY_SEPARATOR)) 
	            { 
	                $folderContents[$folderItem] = self::scandir_recursive( $directory.$folderItem."\\"); 
	            } 
	            else 
	            { 
	                $folderContents[] = $folderItem; 
	            } 
	        } 
	    } 
	
	    return $folderContents; 
	} 
	/**
	 * 디렉토리-파일 상세 조회
	 * 
	 * @param string $dir (검색할 디렉토리명)
	 * @param boolean $recurse ( 하위디렉토리 있으면 재귀호출하여 실행할지 )
	 * @param boolean $depth ( 하위디렉토리 있으면 어느 깊이까지 검색할지 )
	 * @return multitype:multitype:string number NULL
	 * @example
	 * result --->
						Array
						(
						    [0] => Array
						        (
						            [name] => /home/olives/public_html/js/func.js
						            [type] => text/plain
						            [size] => 972
						            [lastmod] => 1407588453
						        )
						)
	 */
	public function getFileList($dir, $recurse=false, $depth=false)
	{
		$this->clearStatCache();

		$retval = array();

		 // add trailing slash if missing
		 if(substr($dir, -1) != "/") $dir .= "/";

		 // open pointer to directory and read list of files
		 $d = @dir($dir) or die("getFileList: Failed opening directory $dir for reading");
		 while(false !== ($entry = $d->read())) {
		   // skip hidden files
		   if($entry[0] == ".") continue;
		   if(is_dir("$dir$entry")) {
		     $retval[] = array(
		       "name" => "$dir$entry/",
		       "type" => filetype("$dir$entry"),
		       "size" => 0,
		       "lastmod" => filemtime("$dir$entry")
		     );
		     if($recurse && is_readable("$dir$entry/")) {
		       if($depth === false) {
		         $retval = array_merge($retval, self::getFileList("$dir$entry/", true));
		       } elseif($depth > 0) {
		         $retval = array_merge($retval, self::getFileList("$dir$entry/", true, $depth-1));
		       }
		     }
		   } elseif(is_readable("$dir$entry")) {
		     $retval[] = array(
		       "name" => "$dir$entry",
		       "type" => mime_content_type("$dir$entry"),
		       "size" => filesize("$dir$entry"),
		       "lastmod" => filemtime("$dir$entry")
		     );
		   }
		 }
		 $d->close();
		
		 return $retval;
	}
	
	public static function listFolderContent($dir,$path=''){
		$r = array();
		$list = scandir($dir);
		foreach ($list as $item) {
			if($item!='.' && $item!='..'){
				if(is_file($path.$item)){
					$r['files'][] = $path.$item;
				}elseif(is_dir($path.$item)){
					$r['folders'][] = $path.$item;
					$sub = self::listFolderContent($path.$item,$path.$item.'/');
					if(isset($sub['files']) && count($sub['files'])>0)
						$r['files'] = isset ($r['files'])?array_merge ($r['files'], $sub['files']):$sub['files'];
					if(isset($sub['folders']) && count($sub['folders'])>0)
						$r['folders'] = array_merge ($r['folders'], $sub['folders']);
				}
			}
		}
		return $r;
	}
	
	/**
	 * 디렉토리 생성 (단, 마지막 디렉토리만 퍼미션 적용)
	 * 
	 * @param string $CreateDir (생성할 디렉토리명)
	 * @param string $perm (퍼미션)
	 * @return void
	 */
	public function createDir($CreateDir, $perm=0777)
	{
		if (!is_dir($CreateDir)) {
			mkdir($CreateDir, $perm, true);
			//$old = umask(0);
			chmod($CreateDir, intval($perm, 8));
			//chmod($CreateDir, $perm);
			//umask($old);
		}
	}
	
	/**
	 * 디렉토리 생성 (단, 각각 하위 디렉토리별 퍼미션 적용)
	 * @param string $CreateDirs
	 * @param string $perm (퍼미션)
	 * @return void
	 */
	public function createDirs($CreateDirs, $perm=0777)
	{
		if( is_dir($CreateDirs) ) return false ;
		
		$folders = explode('/' ,$CreateDirs);
		$mkDir = "" ;

		foreach($folders as $folder)
		{
			if($folder){
				$mkDir = $mkDir . $folder ."/";

				if(!is_dir($mkDir)){
					$old = umask(0);
					mkdir($mkDir, $perm, true);
					umask($old);
				}
			}
		}
	}
			
	/**
	 * 디렉토리 제거
	 * 
	 * @param string $RemoveDir
	 * @return void
	 */
	public function removeDir($RemoveDir) {
		$files = glob($RemoveDir . '/*');
		foreach ($files as $file) {
			is_dir($file) ? self::removeDir($file) : unlink($file);
		}
		rmdir($RemoveDir);
		return;
	}
	/**
	 * UTF-8 저장 (마지막에 BOM 포함하여 저장)
	 * 
	 * @param string $filename
	 * @param string $content
	 * @return void
	 */
	public function writeUTF8File($filename,$content) { 
        $f=fopen($filename,"w"); 
        # Now UTF-8 - Add byte order mark 
        fwrite($f, pack("CCC",0xef,0xbb,0xbf)); 
        fwrite($f,$content); 
        fclose($f); 
        return ;
	}
	
	/**
	 * 파일명 분리
	 * 
	 * @param string $filename
	 * @return array
	 * 		(
	 * 			"filename" => 파일명,
	 			"ext" => 확장자
	 		);
	 */
	public function File_Separator($filename){
		preg_match('/\.([^\.]*$)/', $filename, $extension);
		$file_ext = strtolower($extension[1]);
		$file_name = substr($filename, 0, ((strlen($filename) - strlen($file_ext)))-1);
		return array(
				"filename" => $file_name,
				"ext" => $file_ext
		);
	}
	/**
	 * 파일 사이즈 자동변환( B, KB, MB, GB, TB )
	 * 
	 * @param integer $bytes 바이트
	 * @param integer $precision 소숫점 표시 자리수(기본 2자리)
	 * @return string ex) 10.25 MB
	 * 
	 * @tutorial https://wiki.ubuntu.com/UnitsPolicy
	 * @link https://stackoverflow.com/questions/5501427/php-filesize-mb-kb-conversion/5501447
	 */
	public static function format_bytes($bytes, $precision = 2) {
		$units = array('B', 'KB', 'MB', 'GB', 'TB');
		
		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1000));
		$pow = min($pow, count($units) - 1);
		
		$bytes /= pow(1000, $pow);
		
		return round($bytes, $precision) . ' ' . $units[$pow];
	}
}