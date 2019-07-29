<?php 

if($_POST['property'] == 'process_db'){
	
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<title>설치 install</title>
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- 모바일 주소표시줄 Bar 색상: Chrome, Firefox OS, Opera -->
<meta name="theme-color" content="#dd4b39">

<script type="text/javascript" src="/js/jquery/jquery-3.3.1.min.js"></script>

<!--font-awesome-->
<link href="/dist/components/fontawesome-free-5.0.13/css/fontawesome-all.min.css" rel="stylesheet" type="text/css" media="all">

<!--  jquery common library -->
<script type="text/javascript" src="/js/jquery/jquery.func.js?ver=0.0.2"></script>


<!--  theme common library -->
<link href="/css/reset.css" rel="stylesheet" type="text/css" media="all">
<link href="/css/base/common.css?ver=0.0.4" rel="stylesheet" type="text/css" media="all">
<link href="/css/base/app.css?ver=0.0.3" rel="stylesheet" type="text/css" media="all">
<link href="/css/base/layout.line_center.css" rel="stylesheet" type="text/css" media="all">

<!-- 상단메뉴 -->
<link href="/css/base/top.menu.css?ver=0.0.1" rel="stylesheet" type="text/css" media="all">


<script type="text/javascript" src="/theme/{C.THEME}/js/app.js"></script>

<style type="text/css">

.title {
    width: 110px;
}

</style>

</head>
<body class="col">
	
	<!--header Start-->
	<header class="header flex0">

		<div id="top-line"></div>
			<div class="header-top">
			
				<div class="wrapper-center container d-flex">
				
					<div style="width:35%;">
						<a href="/" class="navbar-brand-logo"><img src="/images/install/logo_pink.svg" style="width:120px;" class="img-responsive" alt="logo"></a>
					</div>
					
				</div>
				
			</div>
		</div>

	</header>
		
	<!--body Start-->
	<main class="row">

		<article id="block-db" class="flex1">
			<div class="container h-center v-center-items height-100">
				
			<!--  ####################### -->
    			<div class="col p-30" style="border: 2px solid #ccc;">
		      		<div class="join">
    		      
    		        <form id="formWrite" name="formWrite" method="post" enctype="multipart/form-data">
    					<input type="hidden" name="property" value="process_db">
    						<div class="form-inline">
    				            <label for="frm_writer" class="column title">호스트명</label>
    				            <div class="data">
    				            	<div class="grow1">
    					            	<input type="text" class="InputAddOn-field" id="muserid" name="muserid" placeholder="호스트명을 입력하세요." required style="width: 200px;" autocomplete="off">
    					            </div>
    				            </div>
    						</div>
    			           <div class="form-inline">
    				            <label for="frm_writer" class="title">DB 아이디</label>
    				            <div class="data">
    				            	<input type="password" data-minlength="6" id="muserpw" name="muserpw" placeholder="DB 아이디를 입력하세요." style="width: 200px;" autocomplete="off" required>
    				            </div>
    						</div>
    						<div class="form-inline">
    				            <label for="frm_writer" class="title">DB 비밀번호</label>
    				            <div class="data">
    				            	<input type="password" data-minlength="6" id="muserpw" name="muserpw" placeholder="DB 비밀번호를 입력하세요." style="width: 200px;" autocomplete="off" required>
    				            </div>
    						</div>
    						<div class="form-inline m-t-20">
    				            <div class="data flex1">
    				            	<input type="button" class="btn btn-dark" style="width:100%;" value="등록">
    				            </div>
    						</div>
    						
    				</form>
    				
    				</div>
				</div>

			</div>
		</article>

	</main>
					
	<footer class="flex0">
					
		<div id="footer">
			<div class="row v-center-items">
				<div class="h-left">
					<img src="/images/install/logo_gray.svg" style="height:28px" class="img-responsive" alt="logo">
				</div>
				<div class="h-right flex1">
					<p class="mb-0"><a class="fweight-700" href="#"></a> Powered by: <a class="fweight-700" href="#">youngsu lee</a></p>
				</div>
			</div>
		</div>
					
	</footer>
</body>
</html>