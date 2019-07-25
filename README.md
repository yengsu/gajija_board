# gajija_board


 
 ## 퍼미션설정 
 
 1. html 캐쉬처리 (707)
  			cache/
  				dynamic
  				template
  
 2. XSS방어관련 캐쉬처리 (707)  
    ; 캐쉬디렉토리 사용안하면 성능저하가 생김(디렉토리 변경시 참조: http://htmlpurifier.org/download#toclink1)
  		chmod 707 app/lib/htmlpurifier/library/HTMLPurifier/DefinitionCache/Serializer
  
 3. temp디렉토리 (707)
   		chmod 707 tmp/
   
 4. 데이타 저장디렉토리 (707)
   		chmod 707 datas/
   
 
 ## composer 설치 (해당 폴더의 파일참조:  Readme) 
 
 1. 폴더:	app/lib/PhpOffice
  		composer require phpoffice/phpspreadsheet
  
 2. 폴더: app/lib/Api/facebook
  		composer require facebook/graph-sdk
  
 3. 폴더: app/lib/Api/google
  		composer require google/apiclient:^2.0

