;<?
;die();
;/*
;class.Strings.php 참조 : tag_remove()


[remove_tag]
iframe = "/<iframe(.*?)<\/iframe>/is"
iframe2 = "/<iframe(.*?)>/is"
script = "/<\s*script.*?\/script\s*>/is"
scriptin = "/javascript:[^\"\']+/i"
meta = "/<meta(.*?)>/is"
style = "/<style(.*?)<\/style>/is"
head = "/<\s*head[^>]*?>.*?<\s*\/head\s*>/siu"
ontag = "/(onabort|onactivate|onafterprint|onafterupdate|onbeforeactivate|onbeforecopy|onbeforecut|onbeforedeactivate|onbeforeeditfocus|onbeforepaste|onbeforeprint|onbeforeunload|onbeforeupdate|onblur|onbounce|oncellchange|onchange|onclick|oncontextmenu|oncontrolselect|oncopy|oncut|ondataavaible|ondatasetchanged|ondatasetcomplete|ondblclick|ondeactivate|ondrag|ondragdrop|ondragend|ondragenter|ondragleave|ondragover|ondragstart|ondrop|onerror|onerrorupdate|onfilterupdate|onfinish|onfocus|onfocusin|onfocusout|onhelp|onkeydown|onkeypress|onkeyup|onlayoutcomplete|onload|onlosecapture|onmousedown|onmouseenter|onmouseleave|onmousemove|onmoveout|onmouseover|onmouseup|onmousewheel|onmove|onmoveend|onmovestart|onpaste|onpropertychange|onreadystatechange|onreset|onresize|onresizeend|onresizestart|onrowexit|onrowsdelete|onrowsinserted|onscroll|onselect|onselectionchange|onselectstart|onstart|onstop|onsubmit|onunload)[ \\t\\n]*=[ \\t\\n]*[\"\'][^\"\']*[\"\']/i"

;script = "!<script(.*?)<\/script>!is"
;space = "/\s{2,}/"		;연속된 공백 1개로
;style2 = "/ style=([^\"\']+) /"		;태그안에 style= 속성 제거 (style=border:0... 따옴표가 없을때)
;style3 = "/ style=(\"|\')?([^\"\']+)(\"|\')?/"		;태그안에 style= 속성 제거 (style="border:0..." 따옴표 있을때)
;tags = "all"		;모든 태그 제거(strip_tags 함수 적용)

;*/
;?>