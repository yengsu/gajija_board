;<?
;die();
;/*
[company]
secret = "Gajija#_9504" ; 암호화/복호화 Key
oid = 1; 업체 UNIQUE코드(미구현)
title = ":::: 홈페이지 방문을 환영합니다. ::::"
ckeywords = ""
cname = "가지자" ; (★결제시 수취인으로 이용)
ctel = "0911222333"	; 대표전화 (★결제시 수취인으로 이용)
cfax = "000-000-0000"	; 팩스번호
czipcode = "00000" ; 우편번호  (★결제시 수취인으로 이용)
caddress = "Compay Address" ; 회사주소  (★결제시 수취인으로 이용)

[doc]
charset="UTF-8"

;conf/database.conf.php 참조
;dbms에 mysql 또는 MariaDB 2가지모두 mysql로 이름 통일 
[db]
kind="default"

[design]
theme = "gajija"	; 디자인 테마

[board]
;-------------------
; tbl_type = 게시판 테이블 사용종류
;value 
;	all : board 테이블 하나로 모두사용 
;	single: board_??? 처럼 각테이블별 사용
;-------------------
skin_home = "html/board/skin"  ; 게시판 & 갤러리 스킨 홈디렉토리
tbl_type = "single" 

[layout]
main = "@main"

; Master Email info
[email]
author="고객지원센타"
email="olivetreemail1440@gmail.com"
pwd="smarthan1440"

[email_template]
basedir="datas/templates/email/";  ; 저장경로
;*/
;?>