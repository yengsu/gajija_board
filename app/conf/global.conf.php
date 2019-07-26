;<?
;die();
;/*
[company]
oid = 1; 업체 UNIQUE코드(미구현)
title = ":::: 홈페이지 방문을 환영합니다. ::::"
ckeywords = ""
cname = "(주)DEVGX"
ctel = 055-238-9456	; 대표전화
cfax = 055-238-9450	; 팩스번호

[doc]
charset="UTF-8"

;conf/database.conf.php 참조
;dbms에 mysql 또는 MariaDB 2가지모두 mysql로 이름 통일 
[db]
kind="default"

[menu]
depth=1 ; 서브메뉴 노출시킬 깊이(depth)

[design]
theme = "WEB"	; 디자인 테마

[board]
;-------------------
; tbl_type = 게시판 테이블 사용종류
value 
	all : dgx_board 테이블 하나로 모두사용 
	single: dgx_board_??? 처럼 각테이블별 사용
;-------------------
skin_home = "html/board/skin"  ; 게시판 & 갤러리 스킨 홈디렉토리
tbl_type = "single" 

[layout]
main = "@main"

[pay]
center_email = "ceo@aaaaa.kr"
tak_price = ""	;기본 택배비
at_shop_id = ""	;상점 ID
at_cross_key = ""	;상점 PW

;*/
;?>