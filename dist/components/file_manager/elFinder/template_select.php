<?php
if( is_dir("../../../../tmp") ) session_save_path("../../../../tmp");
if (!session_id())
	session_start();

if( !isset($_SESSION['ADM']) ) echo die('not grant') ;

if($_REQUEST["mode"] == "tpl" || $_REQUEST["mode"] == "body_attach"){
	$connector = "connector.Template.php" ;
}else{
	exit;
}
$connector = "connector.Template.php" ;

?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>elFinder 2.1.x source version with PHP connector</title>
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=2" />

		<!-- Require JS (REQUIRED) -->
		<!-- Rename "main.default.js" to "main.js" and edit it if you need configure elFInder options or any things -->
		<!-- <script data-main="./main.default.js" src="//cdnjs.cloudflare.com/ajax/libs/require.js/2.3.2/require.min.js"></script> -->
		<!-- <script data-main="./main.cke.js" src="//cdnjs.cloudflare.com/ajax/libs/require.js/2.3.2/require.min.js"></script> -->
		<!-- elFinder CSS (REQUIRED) -->
		
		<!-- jQuery and jQuery UI (REQUIRED) -->
		<script type="text/javascript" src="/js/jquery/jquery.min.js"></script>
		
		<link rel="stylesheet" type="text/css" media="screen" href="/dist/components/jqueryui/jquery-ui.css">
		<script type="text/javascript" src="/dist/components/jqueryui/jquery-ui.min.js"></script>

		<!-- elFinder CSS (REQUIRED) -->
		<link rel="stylesheet" type="text/css" media="screen" href="css/elfinder.min.css">
		<link rel="stylesheet" type="text/css" media="screen" href="css/theme.css">

		<!-- elFinder JS (REQUIRED) -->
		<script type="text/javascript" src="js/elfinder.min.js"></script>

		<!-- elFinder translation (OPTIONAL) -->
		<!-- <script type="text/javascript" src="js/i18n/elfinder.da.js"></script> -->
		<script type="text/javascript" src="js/i18n/elfinder.ko.js"></script>
		
		
		<script src="./js/jquery.elfinder.js"></script>
		<script>
			function getUrlParam(paramName) {
	            var reParam = new RegExp('(?:[\?&]|&)' + paramName + '=([^&]+)', 'i') ;
	            var match = window.location.search.match(reParam) ;
	            //console.log('urlparsing', match);
	            return (match && match.length > 1) ? match[1] : '' ;
    		}
			$().ready(function() {
				var funcNum = getUrlParam('FuncNum');
				var formEle = getUrlParam('formEle');
				var mode = getUrlParam('mode');
				var basedir = "<?=$_REQUEST['basedir']?>";

				if(basedir) param_basedir = "&basedir="+encodeURIComponent(basedir) ;
				else param_basedir = "";
	
				
				var elf = $('#elfinder').elfinder({
	        		url : 'php/<?=$connector?>?mode=' + mode + param_basedir  // connector URL (REQUIRED)
	               ,getFileCallback : function(file) {
	                    /*
	                    file object
	                    	baseUrl : "/html/"
		                    hash : "l1_YWRtL3Byb2R1Y3QvaWNvblJlZy5odG1s"
	                    	mime : "text/html"
	                    	name : "4.body.top.htm"
	                    	path : "_attach\4.body.top.htm"
		                    phash : "l1_YWRtL3Byb2R1Y3Q"
	                    	size : "135"
                    		ts : 1499347948
	                    	url : "/html/_attach/4.body.top.htm"
                    		read : 1
                    		write : 1
	                    */
	                   	/* if(mode == 'tpl') filename = file.url.substr(1) ;
						if(mode == 'body_attach') filename = file.name ; */
	                   	window.opener.callFunction(funcNum, mode, formEle, basedir, file);
	                   	window.close();
	                }
	                 ,commandsOptions : {
							edit : {
								extraOptions : {
									// set API key to enable Creative Cloud image editor
									// see https://console.adobe.io/
									creativeCloudApiKey : '',
									// browsing manager URL for CKEditor, TinyMCE
									// uses self location with the empty value
									managerUrl : ''
								}
							}
							,quicklook : {
								// to enable preview with Google Docs Viewer
								googleDocsMimes : ['application/pdf', 'image/tiff', 'application/vnd.ms-office', 'application/msword', 'application/vnd.ms-word', 'application/vnd.ms-excel', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
							}
						}
				 }).elfinder('instance');
			});
		</script>
	</head>
	<body>

		<!-- Element where elFinder will be created (REQUIRED) -->
		<div id="elfinder"></div>

	</body>
</html>
