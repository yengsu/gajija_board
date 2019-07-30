;<?
;die();
;/*

;###############
;          사용법
;###############
;[레이아웃명]
;각 블럭구간별 이름 = "@_layout/main/블럭별 파일"
;;###############

[blank]
LAYOUT = "@_layout/blank.html"

; Generic standalone htmls
[generic]
LAYOUT = "@_layout/generic.html"

[blank_base]
LAYOUT = "@_layout/blank_base.html"
HEAD_INC = "@_layout/base/head_inc.html" ;Empty 영역

;메인페이지 레이아웃(럭스드롭:명품)
;[main]
;LAYOUT = "@_layout/main/layout.main.html" ;기본 레이아웃
;HEADER = "@_layout/main/main.header.html" ; 헤더 영역
;FOOTER = "@_layout/main/main.footer.html" ;하단 영역

[base]
LAYOUT = "@_layout/base.html"
HEAD_COMMON_INC = "@_layout/gajija/head_common.html" ;head의 css, javascript include 영역
HEAD_MAIN_INC = "@_layout/gajija/head_main.html" ;상단메뉴 영역

[main]
LAYOUT = "@_layout/gajija/main/layout.main.html"
HEAD_COMMON_INC = "@_layout/gajija/head_common.html" ;head의 css, javascript include 영역
HEAD_MAIN_INC = "@_layout/gajija/head_main.html" ;상단메뉴 영역
NAV_COMMON = "@_layout/gajija/nav_common.html"; 공통메뉴
TOP_COMMON_INC = "@_layout/gajija/top_common.html" ;상단메뉴 영역
FOOTER = "@_layout/gajija/footer.html" ;상단메뉴 영역

[sub]
LAYOUT = "@_layout/gajija/sub/layout_sub.html"
HEAD_COMMON_INC = "@_layout/gajija/head_common.html" ;head의 css, javascript include 영역
HEAD_MAIN_INC = "@_layout/gajija/head_main.html" ;헤더메인 영역
NAV_COMMON = "@_layout/gajija/nav_common.html"; 공통메뉴
TOP_COMMON_INC = "@_layout/gajija/top_common.html" ;상단 영역
LEFT_MENU = "@_layout/gajija/left_menu.html" ;왼쪽메뉴
BODY_TOP_BAR = "@_layout/gajija/body_top_bar.html" ; 메인 상단 bar 영역
FOOTER = "@_layout/gajija/footer.html" ;상단메뉴 영역

[one]
LAYOUT = "@_layout/gajija/one/layout_one.html"
HEAD_COMMON_INC = "@_layout/gajija/head_common.html" ;head의 css, javascript include 영역
HEAD_MAIN_INC = "@_layout/gajija/head_main.html" ;상단메뉴 영역
TOP_COMMON_INC = "@_layout/gajija/top_common.html" ;상단메뉴 영역
NAV_COMMON = "@_layout/gajija/nav_common.html"; 공통메뉴
FOOTER = "@_layout/gajija/footer.html" ;상단메뉴 영역

[install]
LAYOUT = "_layout/install/layout_one.html"
HEAD_COMMON_INC = "_layout/install/head_common.html" ;head의 css, javascript include 영역
HEAD_MAIN_INC = "_layout/install/head_main.html" ;상단메뉴 영역
TOP_COMMON_INC = "_layout/install/top_common.html" ;상단메뉴 영역
NAV_COMMON = "_layout/install/nav_common.html"; 공통메뉴
FOOTER = "_layout/install/footer.html" ;상단메뉴 영역
;##################################################
;관리자페이지 레이아웃
[adm]
LAYOUT = "@_layout/adm/layout.adm.html"
MENU = "@_layout/adm/adm.menu.html" ; 왼쪽메뉴 영역
;MENU_SUB = "@_layout/adm/adm.menu.member.html" ; 왼쪽메뉴 영역

[admin_sub]
LAYOUT = "@_layout/adm/sub/layout.sub.html"
COMMON_HEAD = "@_layout/adm/head_common.html" ;헤더(head)의 css, javascript include 영역
COMMON_TOP = "@_layout/adm/top_common.html" ;상단 영역
COMMON_LEFT_MENU = "@_layout/adm/left_menu.html" ;왼쪽메뉴 
COMMON_FOOTER = "@_layout/adm/footer.html" ;하단 영역


;관리자 - 로그인
[adm_login]
LAYOUT = "@_layout/adm/layout.adm.login.html"
;##################################################
;*/
;?>
