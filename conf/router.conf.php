;<?
;die();
;/*

;================================
;HTTP methods and RESTful routing
;================================
;GET - view
;POST - create
;PUT - update
;DELETE - delete


[default]
	Controller[route] = "(/controller<[A-Za-z0-9_]{2,15}>)"
	Controller-Action[route] = "(/controller<[A-Za-z0-9_]{2,15}>)(/action<[0-9A-Za-z_]+>)"
	
	Plugins-Folder-Plugin-Action-Code[route] = "/(folder<plugins\/[0-9A-Za-z_-]+>)(/plugin<[0-9A-Za-z_]+>)(/action<[0-9A-Za-z_]+>)(/code<[0-9A-Za-z_-]{1,20}>)"
	ViewController-Layout-Action[route] = "(/controller<view>(/layout<[A-Za-z0-9_]+>(/folder<[^ .+=\\\][0-9A-Za-z_\/]{1,70}\/>(action<([^\/.+=\\\][0-9A-Za-z_]+)\/?$>))))"
	PublisingController-Layout-Action[route] = "(/controller<pub>(/lang<[A-Za-z_]+>(/layout<[A-Za-z0-9_]+>(/folder<[^ .+=\\\][0-9A-Za-z_\/]{1,70}\/>(action<([^\/.+=\\\][0-9A-Za-z_]+)\/?$>)))))"
	
	;Folder-Controller-Action-Code[route] = "(/folder<[0-9A-Za-z_]+>/controller<[0-9A-Za-z_]{2,15}>/action<[0-9A-Za-z_]+>(/code<[0-9A-Za-z_-]{1,20}>))"
	BrandOrPlan-Action-Code[route] = "(/controller<Brand|Plan>/action<[0-9A-Za-z_]+>/code<[0-9A-Za-z_-]{1,20}>)"

	;Board-Controller-Action-Code[route] = "(/folder<board>)(/controller<[0-9A-Za-z_]{2,15}>)(/action<[0-9A-Za-z_-]{1,20}>/code<lst|write|view|edit>)"
	
	Folder-Controller-Action[route] = "(/folder<[^ .+=\\\][0-9A-Za-z_]+>/controller<[0-9A-Za-z_]{2,15}>/action<[A-Za-z_]+>)"
	Folder-Controller-Action-Code[route] = "(/folder<[A-Za-z_-]{2,15}>)(/controller<[0-9A-Za-z_]{2,15}>)(/action<[A-Za-z_]+>)(/code<[0-9]{1,20}>)"
	Folder-Folder-Controller-Action-Code[route] = "(/folder<[^ .+=\\\][0-9A-Za-z_]+\/[^ .+=\\\][0-9A-Za-z_]+>)(/controller<[0-9A-Za-z_]{2,20}>)(/action<[0-9A-Za-z_]+>)(/code<[0-9A-Za-z]{1,20}>)"
	
	; -----------------
	; Admin Page 
	; 
	; [folder] => admin/board  
	; [controller] => BoardAdmin
	; [action] => lst
	; -----------------
	Admin-Folder-Controller-Action-Code[route] = "/(folder<adm\/[0-9A-Za-z_-]+>)(/controller<[0-9A-Za-z_]+>)(/action<[0-9A-Za-z_]+>)(/code<[0-9A-Za-z_-]{1,20}>)" ; /admin/폴더명/컨트롤러/action명/코드번호
	
;*/
;?>