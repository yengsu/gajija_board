<?php
namespace Gajija\lib ;
# ini handler class
# coded by Alessandro Rosa
# e-mail : zandor_zz@yahoo.it
# site : http://alessandrorosa.altervista.org

# Copyright (C) 2008  Alessandro Rosa

# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 3 of the License, or
# any later version.

# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software Foundation,
# Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA

# Compiled with PHP 4.4.0

# July 24-26th 2008 : improved managements of single and double quotes by Ulrich Zdebel
# July 27th 2006 : fixed a management bug for entries including (double) quotes by Joao Borges
# August 5th 2006 : fixed a "=" term management bug by replacing all calls 
#                   to custom version of 'parse_ini_file' function member
# November 19th 2006: fixed a bug with the 'p' character handling

class INI_manager
{
	var $bi_a ;
	var $filePATH ;
	var $crlf ;

	public function __construct()
	{
          $info = strtolower( $_SERVER['HTTP_USER_AGENT'] );
          $this->crlf = ( strpos( strtolower( $info ), "windows", 0 ) === false ) ? "\n" : "\r\n" ;
          unset( $this->bi_a );
	}

	 // Implementation of parse_ini_file
   // by Hugo Goncalves (hugo_goncalves@portugalmail.pt)
	 
   private function parse_ini_file($filename)
	 {
	     unset( $this->bi_a );
	 
		// Allocate the result array
		$res = array();
		// Does the file exists and can we read it?
		if(file_exists($filename) && is_readable($filename))
		{
			// In the beggining we are not in a section
			$section = "";
			// Open the file
			$fd = @fopen($filename,"r");
			
			// Read each line
			while(!feof($fd))
			{
				// Read the line and trim it
				$line = trim(@fgets($fd, 4096 ));
				
				$len = strlen($line);
				// Only process non-blank lines
				if($len != 0)
				{
					// Only process non-comment lines
					if($line[0] != ';')
					{
						// Found a section?
						if( ( $line[0] == '[') && ($line[$len-1] == ']' ) )
						{
							// Get section name
							$section = substr($line,1,$len-2);
							// Check if the section is already included in result array						
							if(!isset($res[$section]))
							{
								// If not included create it
								$res[ $section ] = array();
							}
						}
						// Check for entries
						$pos = strpos($line,'=');
						// Found an entry
						if( $pos != false )
						{
							// get name of entry and [Joao Borges] delete any blank spaces (begin and end)
							$name = trim( substr( $line, 0, $pos ) );

							// get value of entry and [Joao Borges] delete blank spaces again
							$value = trim( substr( $line, $pos+1, $len - $pos - 1 ) );

              $value = stripslashes( $value );
              
              // follows some sort of inizialization for entries not including text
              if ( empty( $value ) ) $value = "" ;
              
              // syntax must be strictly followed !

              if ( strlen( $name ) > 0 )
              {
      							// Store entry if we are inside a section
      							if( strlen( $section ) > 0 )
      							{
        								$res[$section][$name] = $value;
      							}
      							else						
      							{
      								  $res[$name] = $value;
      							}
              }
						}
					}
				}				
			}
			// Close the file
			@fclose($fd);
		}
		return $res;
	}

      public function show_ini( $path )
      {
            $this->filePATH = $path ;
            $INIarray = $this->parse_ini_file( $path );
            
            $fileCONTENTS = "" ;
            
            $c1 = 0 ;
            if ( is_array( $INIarray ) )
            {
                foreach ( $INIarray as $i => $a )
                { 
                    $c2 = 0 ;
                    if ( is_array( $a ) )
                    {
                        foreach ( $a as $n => $value )
                        { 
                            if ( $c2 == 0 ) $fileCONTENTS .= "[$i]<br/>$this->CRLF$n=$value<br/>$this->CRLF";
                            else if ( strlen( $value ) != 0 ) $fileCONTENTS .= "$n=$value<br/>$this->CRLF";
                            $c2++;
                        }
                        
                        $fileCONTENTS .= "<br/>$this->CRLF" ;
                    }
                }
                
                $fileCONTENTS = substr( $fileCONTENTS, 0, strlen( $fileCONTENTS ) - ( 5 + strlen( "<br/>" ) ) );
            }
            
            echo "<code>$fileCONTENTS</code>" ;
      }

      public function save_ini( $path )
      {
            $fileCONTENTS = "" ;
            //echo '11<pre>';print_r($this->bi_a);exit;
            $c1 = 0 ;
            if ( is_array( $this->bi_a ) )
            {
                foreach ( $this->bi_a as $i => $a)
                { 
                    $c2 = 0 ;
                   
                   //echo '11<pre>';print_r($this->bi_a);
                    
                    if ( is_array( $a ) )
                    {
                        foreach ( $a as $n => $value )
                        { 
                            if ( $c2 == 0 ){
                            	$fileCONTENTS .= "[".$i."]".PHP_EOL.$n."=".$value['VALUE'] ;
                            	if( isset($value['COMMENT']) ) $fileCONTENTS .= "\t;".$value['COMMENT'];
                            	$fileCONTENTS .= PHP_EOL ;
                            }else if ( strlen( $value['VALUE'] ) != 0 ){
                            	 
                            	$fileCONTENTS .= $n."=".$value['VALUE'] ;
                            	if( isset($value['COMMENT']) ) $fileCONTENTS .= "\t;".$value['COMMENT'];
                            	$fileCONTENTS .= PHP_EOL ;
                            }
                            
                            $c2++;
                        }
                        
                        $fileCONTENTS .= PHP_EOL ;
                    }
                }
                //echo '<pre>';print_r($fileCONTENTS) ;exit;
                $fileCONTENTS = ";<?".PHP_EOL.";die();".PHP_EOL.";/*".PHP_EOL. trim($fileCONTENTS).PHP_EOL."*/".PHP_EOL.";?>";
                //echo '<pre>';print_r($fileCONTENTS) ;exit;
                self::writeUTF8File($path, $fileCONTENTS);
/*
                $hFile = @fopen( $path, "w+" );
                @fwrite( $hFile, $fileCONTENTS );
                @fclose( $hFile );
*/                
                unset( $this->bi_a );
            }
      }

      public function get_ini_array( $path )
      {
			$INIarray = '';
			if( is_file($path) ){
				$this->filePATH = $path ;
				$INIarray = $this->parse_ini_file( $path );
			}

			return $INIarray ;
            /*
            $fileCONTENTS = "" ;
            
            $c1 = 0 ;
            
            if ( is_array( $this->bi_a ) )
            {
                foreach ( $this->bi_a as $i => $a)
                { 
                    $c2 = 0 ;
                    
                    if ( is_array( $a ) )
                    {
                        foreach ( $a as $n => $value )
                        { 
                            if ( $c2 == 0 ) $fileCONTENTS .= "[$i]$this->crlf$n=$value$this->crlf";
                            else if ( strlen( $value ) != 0 ) $fileCONTENTS .= "$n=$value$this->crlf";
                            $c2++;
                        }
                        
                        $fileCONTENTS .= $this->crlf ;
                    }
                }
                
                unset( $this->bi_a );
            }
            */
      }

      //////////////////////////////////////////////////////////
      public function find_entry( $path, $keyNAME, $entryNAME )
      {
            $this->filePATH = $path ;
            $INIarray = $this->parse_ini_file( $path );
            
            if ( is_array( $INIarray ) )
            {
                foreach ( $INIarray as $i => $a )
                {
                    if ( is_array( $a ) )
                    {
                        foreach ( $a as $n => $value )
                        { 
                            if ( strcmp( $i, $keyNAME ) == 0 && strcmp( $n, $entryNAME ) == 0 )
                            { 
                                return true ;
                            }
                        }
                    }
                }
            }

            return false ;
      }
      //////////////////////////////////////////////////////////
      public function get_entry( $path, $keyNAME, $entryNAME )
      {
            $this->filePATH = $path ;
            $INIarray = $this->parse_ini_file( $path );
      
            if ( is_array( $INIarray ) )
            {
                foreach ( $INIarray as $i => $a )
                { 
                    if ( is_array( $a ) )
                    {
                        foreach ( $a as $n => $value )
                        { 
                            if ( strcmp( $i, $keyNAME ) == 0 && strcmp( $n, $entryNAME ) == 0 )
                            { 
                                // [Ulrich Zdebel] Bugfix: doublequotes were not correctly managed
                                return stripslashes( $value ) ;
                            }
                        }
                    }
                }
            }

            return "" ;
      }
      //////////////////////////////////////////////////////////
      public function add_entry( $path, $keyNAME, $entryNAME, $entryVALUE, $entryCOMMENT=NULL )
      {
      	//$cc = func_get_args();
      	//array_pop($INI_array);
      	
            if ( $this->find_entry( $path, $keyNAME, $entryNAME ) )
            {
                $this->set_entry( $path, $keyNAME, $entryNAME, $entryVALUE, $entryCOMMENT ) ;
                return ;
            }
            
           //echo 'ss<pre>';print_r( $INI_array);
            $this->filePATH = $path ;
            
            $INIarray = $this->parse_ini_file( $path );

            $this->bi_a = array();
            
            $bKEYfound = false ;
            $bKEYadded = false ;
            
            if ( is_array( $INIarray ) )
            {
                foreach ($INIarray as $i => $a)
                { 
                    if ( is_array( $a ) )
                    {
                          foreach ($a as $n => $value)
                          { 
                              if ( strcmp( $i, $keyNAME ) == 0 ) $bKEYfound = true ;
                              
                              $this->bi_a[$i][$n]['VALUE'] = $a[$n] ;
                              
                          }
                      
                          if ( $bKEYfound )
                          {  
                              $this->bi_a[$i][$entryNAME]['VALUE'] = $entryVALUE ;
                              if(!empty($entryCOMMENT)) $this->bi_a[$i][$entryNAME]['COMMENT'] = $entryCOMMENT ;
                              $bKEYfound = false ;
                              $bKEYadded = true ;
                              
                          }
                    }
                }
                //echo '<pre>';print_r($this->bi_a);
            }
            //echo '<pre>';print_r($this->bi_a) ;exit;

          if ( !$bKEYadded ) $this->bi_a[$keyNAME][$entryNAME]['VALUE'] = $entryVALUE ;

          $this->save_ini( $path );
      }


      //////////////////////////////////////////////////////////
      public function set_entry( $path, $keyNAME, $entryNAME, $entryVALUE, $entryCOMMENT=NULL )
      {
            $this->filePATH = $path ;
            
            $INIarray = $this->parse_ini_file( $path );
            //echo '11<pre>';print_r($INIarray);exit;
            $this->bi_a = array();

            if ( is_array( $INIarray ) )
            {
                foreach ($INIarray as $i => $a)
                { 
                    if ( is_array( $a ) )
                    {
                        foreach ($a as $n => $value)
                        { 
                            if ( strcmp( $i, $keyNAME ) == 0 && strcmp( $n, $entryNAME ) == 0 )
                            { 
                                $this->bi_a[$i][$n]['VALUE'] = $entryVALUE ;
                                if( !empty($entryCOMMENT) ) $this->bi_a[$i][$n]['COMMENT'] = $entryCOMMENT ;
                            }
                            else
                            {
                                $this->bi_a[$i][$n]['VALUE'] = $a[$n] ;
                                //$this->bi_a[$i][$n]['COMMENT'] = $entryCOMMENT ;
                            } 
                        }
                    }
                }
            }
            
          $this->save_ini( $path );
      }

      //////////////////////////////////////////////////////////
      public function delete_entry( $path, $keyNAME, $entryNAME )
      {
            $this->filePATH = $path ;
            $INIarray = $this->parse_ini_file( $path );
      
            $this->bi_a = array();
            
            if ( is_array( $INIarray ) )
            {
                foreach ($INIarray as $i => $a)
                { 
                    if ( is_array( $a ) )
                    {
                        foreach ($a as $n => $value)
                        { 
                            if ( strcmp( $i, $keyNAME ) == 0 && strcmp( $n, $entryNAME ) == 0 )
                            { 
                                // don't do anything !
                            }
                            else $this->bi_a[$i][$n]['VALUE'] = $a[$n] ;
                        }
                    }
                }
            }
//	echo '<pre>';print_r($this->bi_a);exit;
          $this->save_ini( $path );
      }

      public function delete_key( $path, $keyNAME )
      {
            $fileCONTENTS = "" ;

            $this->filePATH = $path ;
            $INIarray = $this->parse_ini_file( $path );
           
            if ( is_array( $INIarray ) )
            {
                foreach ($INIarray as $i => $a)
                { 
                    $c2 = 0 ;
                    
                    if ( is_array( $a ) )
                    {
                        foreach ($a as $n => $value)
                        { 
                            if (strcmp( $i, $keyNAME ) != 0 ) 
                            {
                                if ( $c2 == 0 ) $fileCONTENTS .= "[$i]$this->crlf$n=$value$this->crlf";
                                else if ( strlen( $value ) != 0 ) $fileCONTENTS .= "$n=$value$this->crlf";
                                $c2++;
                            }    
                        }
                        
                        $fileCONTENTS .= $this->crlf ;
                    }
                }
            }
            $fileCONTENTS = ";<?".$this->crlf.";die();".$this->crlf.";/*".$this->crlf.$fileCONTENTS.";*/".$this->crlf.";?>";
            
            self::writeUTF8File($path, $fileCONTENTS);
            /*
            $hFile = @fopen( $path, "w+" );
            @fwrite( $hFile, $fileCONTENTS );
            @fclose( $hFile );
            */
     }

      public function delete_all_keys( $path )
      {
            $fileCONTENTS = "" ;

            $this->filePATH = $path ;
            $INIarray = $this->parse_ini_file( $path );
            
            if ( is_array( $INIarray ) )
            {
                foreach ($INIarray as $i => $a)
                { 
                    $c2 = 0 ;
                    
                    if ( is_array( $a ) )
                    {
                      foreach ($a as $n => $value)
                      { 
                          if ( $c2 == 0 ) $fileCONTENTS .= "[$i]$this->crlf";
                          $c2++ ;
                      }
                      
                      $fileCONTENTS .= $this->crlf ;
                    }
                }
            }
           
            $fileCONTENTS = ";<?".$this->crlf.";die();".$this->crlf.";/*".$this->crlf.trim($fileCONTENTS).";*/".$this->crlf.";?>";

            self::writeUTF8File($path, $fileCONTENTS);
            /*
            $hFile = @fopen( $path, "w+" );
            @fwrite( $hFile, $fileCONTENTS );
            @fclose( $hFile );
            */
     }
     public function writeUTF8File($filename,$content) { 
     	ob_start();
        $f=@fopen($filename,"w+"); 
        @fwrite($f,$content); 
        @fclose($f); 
        ob_end_clean();
        @chmod($filename, 0707);
        return ;
	}
}
?>