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

<!--  Cookie -->
<script type="text/javascript" src="/js/jquery/jquery.cookie.js"></script>
<!--  countdown -->
<script type="text/javascript" src="/js/jquery/jquery.countdown.min.js"></script>
<!--  jquery common library -->
<script type="text/javascript" src="/js/jquery/jquery.func.js?ver=0.0.2"></script>


<!--  theme common library -->
<link href="/css/reset.css" rel="stylesheet" type="text/css" media="all">
<link href="/css/install/common.css?ver=0.0.4" rel="stylesheet" type="text/css" media="all">
<link href="/css/install/css/app.css?ver=0.0.3" rel="stylesheet" type="text/css" media="all">
<link href="/css/install/layout.line_center.css" rel="stylesheet" type="text/css" media="all">

<!-- 상단메뉴 -->
<link href="/css/install/top.menu.css?ver=0.0.1" rel="stylesheet" type="text/css" media="all">


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
		<article id="body" class="flex1">
			<div class="container h-center v-center-items height-100">
				
			<!--  ####################### -->
    			<div class="join-container col">
		      		<div class="join">
    		      
    		        <form id="formWrite" name="formWrite" action="{Doc["baseURL"]}/{Doc["Action"]}{? Doc["CODE"]}/{Doc["CODE"]}{/}{Doc["queryString"]}" method="post" enctype="multipart/form-data">
    					
    						<div class="form-inline">
    				            <label for="frm_writer" class="column title">아이디</label>
    				            <div class="data">
    				            	<div class="grow1">
    					            	<input type="text" class="InputAddOn-field" id="muserid" name="muserid" placeholder="이메일 주소" required autocomplete="off">
    					            </div>
    				            </div>
    						</div>
    						<div id="Auth-block" class="form-inline hide">
    							<label class="col-form-label col-sm-4"></label>
    							<div class="data">
    								<div class="input-group">
    				            		<input type="text"  style="border: 2px solid #e28a8a;" id="MAuthCode" name="MAuthCode" placeholder="인증번호" autocomplete="off">
    				            		<div class="input-group-append">
    				            			<a href="#" id="btn-AuthConfirm" class="btn btn-default" style="padding:9px 10px; border: 1px solid #e28a8a; background: #e28a8a; color: #fff;">확인</a>
    									</div>
    									<div class="join-time "  style="border:0;">남은시간 <span id="Lifetime-count" style="color:red;font-weight:bold;"></span></div>
    								</div>
    							</div>
    						</div>
    			           <div class="form-inline">
    				            <label for="frm_writer" class="title">비밀번호</label>
    				            <div class="data">
    				            	<input type="password" data-minlength="6" id="muserpw" name="muserpw" placeholder="비밀번호 영문,숫자,특수문자 6~15자" autocomplete="off" required>
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