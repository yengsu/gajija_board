DROP TABLE IF EXISTS `board`;
CREATE TABLE `board` (
  `serial` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `oid` int(11) DEFAULT '0' COMMENT '업체코드',
  `mcode` int(11) DEFAULT '0' COMMENT '메뉴(분류)코드',
  `bid` varchar(25) CHARACTER SET utf8 DEFAULT '',
  `cate` int(11) DEFAULT '0',
  `parent` int(11) DEFAULT '0' COMMENT '소속된 게시글번호',
  `family` int(11) DEFAULT '0' COMMENT '상위게시물코드(그룹코드)',
  `indent` int(11) DEFAULT '0' COMMENT '들여쓰기 수준(Childe Node level)',
  `lft` int(11) DEFAULT '1',
  `rgt` int(11) DEFAULT '2',
  `userid` varchar(30) CHARACTER SET utf8 DEFAULT '' COMMENT '회원 아이디',
  `writer` varchar(45) CHARACTER SET utf8 DEFAULT '' COMMENT '작성자 명',
  `pwd` varchar(45) CHARACTER SET utf8 DEFAULT '' COMMENT '비밀번호',
  `sec_pwd` varchar(100) CHARACTER SET utf8 DEFAULT '' COMMENT '비밀글 비밀번호',
  `title` varchar(150) CHARACTER SET utf8 DEFAULT '' COMMENT '타이틀명',
  `usehtml` tinyint(1) DEFAULT '0' COMMENT 'html 사용유무(사용:1,미사용:0)',
  `memo` text CHARACTER SET utf8 COMMENT '내용글',
  `noti` tinyint(1) DEFAULT '0' COMMENT '공지[알림](0:No / 1:Yes)',
  `attach_path` varchar(50) CHARACTER SET utf8 DEFAULT '' COMMENT '첨부파일 폴더',
  `attach_files` varchar(255) CHARACTER SET utf8 DEFAULT '' COMMENT '첨부파일(파일,파일,파일,파일.....)',
  `sec` tinyint(1) DEFAULT '0' COMMENT '비밀글쓰기 사용 (사용:1/미사용:0)',
  `viewcnt` int(11) DEFAULT '0' COMMENT '조회수',
  `parent_del` tinyint(1) DEFAULT '0' COMMENT '부모글을 삭제한경우 (1:true,0:false)',
  `ip` varchar(45) CHARACTER SET utf8 DEFAULT '' COMMENT 'IP Address',
  `firstdate` int(11) DEFAULT '0' COMMENT '최초 등록일자',
  `regdate` int(11) DEFAULT '0' COMMENT '등록/업데이트 일자',
  PRIMARY KEY (`serial`),
  KEY `idx_base` (`serial`,`mcode`),
  KEY `idx_group` (`mcode`,`family`),
  KEY `idx_title` (`title`),
  KEY `idx_family` (`family`),
  KEY `idx_lft` (`lft`),
  KEY `idx_rgt` (`rgt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='게시판 정보';


DROP TABLE IF EXISTS `board_cate`;
CREATE TABLE `board_cate` (
  `serial` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `oid` int(11) DEFAULT '0',
  `family` int(11) DEFAULT '0' COMMENT '상위게시물코드(그룹코드)',
  `parent` int(11) DEFAULT '0' COMMENT '소속된 게시글번호',
  `indent` int(11) DEFAULT '0' COMMENT '들여쓰기 수준(Childe Node level)',
  `lft` int(10) unsigned DEFAULT '1',
  `rgt` int(10) unsigned DEFAULT '2',
  `title` varchar(45) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `grant_read` tinyint(1) DEFAULT '0' COMMENT '권한등급-읽기',
  `imp` tinyint(1) DEFAULT '0' COMMENT '노출유무( True:1 / False:0 )',
  PRIMARY KEY (`serial`),
  KEY `idx_family` (`family`),
  KEY `idx_indent` (`indent`),
  KEY `idx_lft` (`lft`),
  KEY `idx_rgt` (`rgt`),
  KEY `idx_imp` (`imp`),
  KEY `idx_grant_read` (`grant_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='전체 보드 카테고리';


DROP TABLE IF EXISTS `board_info`;
CREATE TABLE `board_info` (
  `serial` int(11) NOT NULL AUTO_INCREMENT,
  `oid` int(11) DEFAULT '0' COMMENT '업체코드',
  `bid` varchar(25) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '게시판 ID (uniq)',
  `mcode` int(11) DEFAULT '0' COMMENT '메뉴(분류)코드',
  `cate` int(11) DEFAULT '0' COMMENT '게시판 분류(카테고리) 코드',
  `skin_grp` varchar(45) CHARACTER SET utf8 DEFAULT '' COMMENT '스킨 그룹(그룹 디렉토리명)',
  `skin_name` varchar(40) CHARACTER SET utf8 DEFAULT '' COMMENT '스킨명',
  `title` varchar(45) CHARACTER SET utf8 DEFAULT '' COMMENT '타이틀명',
  `table_name` varchar(45) CHARACTER SET utf8 DEFAULT '' COMMENT 'DB테이블 명',
  `listscale` tinyint(3) DEFAULT '0' COMMENT '출력 게시물 수',
  `pagescale` tinyint(3) DEFAULT '0' COMMENT '출력 페이징블럭수',
  `title_len` int(11) DEFAULT '0' COMMENT '타이틀(제목) 길이',
  `indent` tinyint(1) DEFAULT '0' COMMENT '계층형 (사용:1/미사용:0)',
  `comments` tinyint(1) DEFAULT '0' COMMENT '댓글 사용유무(사용:1/미사용:0)',
  `sec` tinyint(1) DEFAULT '0' COMMENT '비밀 게시판( true:1/false:0)',
  `sec_pwd` varchar(45) CHARACTER SET utf8 DEFAULT '' COMMENT '비밀글쓰기 사용 (사용:1/미사용:0)',
  `mbr_type` tinyint(1) DEFAULT '0' COMMENT '운영형태(회원용:1 / 전체:0)',
  `editor` tinyint(1) DEFAULT '0' COMMENT '위지윅(WYSIWYG)에디터 사용유무(사용:1/미사용:0)',
  `upload_path` varchar(45) CHARACTER SET utf8 DEFAULT '' COMMENT '업로드 기본 파일경로',
  `upload_file_cnt` tinyint(1) DEFAULT '0' COMMENT '업로드 파일 갯수',
  `attach_path` varchar(45) CHARACTER SET utf8 DEFAULT '' COMMENT '첨부파일 기본경로',
  `attach_top` varchar(45) CHARACTER SET utf8 DEFAULT '' COMMENT '상단 첨부파일',
  `attach_bottom` varchar(45) CHARACTER SET utf8 DEFAULT '' COMMENT '하단 첨부파일',
  `noti_lev` tinyint(1) DEFAULT '0' COMMENT '공지사항 읽기권한 적용유무',
  `memo` varchar(255) CHARACTER SET utf8 DEFAULT '' COMMENT '메모',
  `regdate` int(11) DEFAULT '0' COMMENT '작성일자',
  PRIMARY KEY (`serial`),
  KEY `idx_bid` (`oid`,`bid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='게시판 환경설정';


DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments` (
  `serial` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `oid` int(11) DEFAULT '0' COMMENT '업체코드',
  `mcode` int(11) DEFAULT '0' COMMENT '메뉴(분류)코드',
  `bid` varchar(25) CHARACTER SET utf8 DEFAULT '',
  `cate` int(6) DEFAULT '0',
  `bserial` int(11) DEFAULT '0' COMMENT '게시판 serial',
  `parent` int(11) DEFAULT '0' COMMENT '소속된 게시글번호',
  `family` int(11) DEFAULT '0' COMMENT '상위게시물코드(그룹코드)',
  `indent` int(11) DEFAULT '0' COMMENT '들여쓰기 수준(Childe Node level)',
  `lft` int(11) DEFAULT '1',
  `rgt` int(11) DEFAULT '2',
  `userid` varchar(30) CHARACTER SET utf8 DEFAULT '' COMMENT '회원 아이디',
  `writer` varchar(45) CHARACTER SET utf8 DEFAULT '' COMMENT '작성자 명',
  `pwd` varchar(45) CHARACTER SET utf8 DEFAULT '' COMMENT '비밀번호',
  `title` varchar(150) CHARACTER SET utf8 DEFAULT '' COMMENT '타이틀명',
  `usehtml` tinyint(1) DEFAULT '0' COMMENT 'html 사용유무(사용:1,미사용:0)',
  `memo` text CHARACTER SET utf8 COMMENT '내용글',
  `attach_path` varchar(45) CHARACTER SET utf8 DEFAULT '' COMMENT '첨부파일 폴더',
  `attach_files` varchar(255) CHARACTER SET utf8 DEFAULT '' COMMENT '첨부파일(파일,파일,파일,파일.....)',
  `sec` tinyint(1) DEFAULT '0' COMMENT '비밀글쓰기 사용 (사용:1/미사용:0)',
  `parent_del` tinyint(1) DEFAULT '0' COMMENT '부모글을 삭제한경우 (1:true,0:false)',
  `ip` varchar(45) CHARACTER SET utf8 DEFAULT '' COMMENT 'IP Address',
  `firstdate` int(11) DEFAULT '0' COMMENT '최초 등록일자',
  `regdate` int(11) DEFAULT '0' COMMENT '등록/업데이트 일자',
  PRIMARY KEY (`serial`),
  KEY `oid_idx` (`oid`),
  KEY `lft_idx` (`lft`),
  KEY `rgt_idx` (`rgt`),
  KEY `family_idx` (`family`),
  KEY `flr_idx` (`family`,`lft`,`rgt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='댓글';


DROP TABLE IF EXISTS `comments_info`;
CREATE TABLE `comments_info` (
  `serial` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `oid` int(11) DEFAULT '0' COMMENT '업체코드',
  `bid` varchar(25) CHARACTER SET utf8 DEFAULT '' COMMENT '게시판 아이디',
  `mcode` int(11) DEFAULT '0' COMMENT '메뉴코드',
  `cate` int(11) DEFAULT '0',
  `skin_grp` varchar(15) CHARACTER SET utf8 DEFAULT '' COMMENT '게시판 스킨분류(gallery,board....)',
  `skin_name` varchar(30) CHARACTER SET utf8 DEFAULT '' COMMENT '게시판 스킨명',
  `title` varchar(45) CHARACTER SET utf8 DEFAULT '' COMMENT '게시판 타이틀명',
  `table_name` varchar(25) CHARACTER SET utf8 DEFAULT 'comments' COMMENT 'DB 테이블명',
  `listscale` tinyint(3) DEFAULT '10' COMMENT '기본 게시물 수',
  `pagescale` tinyint(3) DEFAULT '10' COMMENT '기본 페이지 번호 수',
  `title_len` int(11) DEFAULT '0' COMMENT '기본 제목길이 수',
  `indent` tinyint(1) DEFAULT '0' COMMENT '계층형(질문과답)게시판 사용유무 : 사용(1),미사용(0)',
  `comments` tinyint(1) DEFAULT '0' COMMENT '댓글사용유무 : 사용(1),미사용(0)',
  `sec` tinyint(1) DEFAULT '0' COMMENT '비밀 댓글( true:1/false:0)',
  `sec_pwd` varchar(45) CHARACTER SET utf8 DEFAULT '' COMMENT '비밀글쓰기 사용 (사용:1/미사용:0)',
  `mbr_type` tinyint(1) DEFAULT '0' COMMENT '회원제 유무(회원제인 경우:1 / 비회원제인경우:0)',
  `editor` tinyint(1) DEFAULT '0' COMMENT '위지윅(WYSIWYG)에디터 사용유무(사용:1/미사용:0)',
  `upload_path` varchar(45) CHARACTER SET utf8 DEFAULT 'upload/comments' COMMENT '파일업로드 경로',
  `upload_file_cnt` tinyint(1) DEFAULT '0' COMMENT '업로드 첨부파일 갯수',
  `attach_path` varchar(45) CHARACTER SET utf8 DEFAULT 'html/_attach/comments/' COMMENT '상단,하단-파일경로',
  `attach_top` varchar(45) CHARACTER SET utf8 DEFAULT '' COMMENT '게시판 상단파일 : 게시판ID.header.htm',
  `attach_bottom` varchar(45) CHARACTER SET utf8 DEFAULT '' COMMENT '게시판 하단파일 : 게시판ID.footer.htm',
  `noti_lev` tinyint(1) unsigned DEFAULT '0' COMMENT '공지[알림] 쓰기등급 (사용:1/미사용:0)',
  `noti_grant_apply` tinyint(1) DEFAULT '0' COMMENT '공지사항 읽기권한 적용유무',
  `memo` varchar(255) CHARACTER SET utf8 DEFAULT '' COMMENT '간략한 설명 또는 코멘트',
  `regdate` int(11) DEFAULT '0' COMMENT '게시판 생성일자',
  PRIMARY KEY (`serial`),
  KEY `idx_bid` (`oid`,`bid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='댓글 환경정보';


DROP TABLE IF EXISTS `grants`;
CREATE TABLE `grants` (
  `serial` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `oid` int(11) NOT NULL DEFAULT '0' COMMENT '업체코드',
  `group_name` varchar(45) NOT NULL DEFAULT '' COMMENT '분류명 (board ....)',
  `kind_code` varchar(40) NOT NULL DEFAULT '' COMMENT '종류의 코드',
  `mbr_lev` int(11) DEFAULT '0' COMMENT '회원등급(레벨)',
  `mbr_id` varchar(30) DEFAULT '',
  `grant_read` int(11) unsigned DEFAULT '0' COMMENT '읽기권한',
  `grant_write` int(11) unsigned DEFAULT '0' COMMENT '쓰기권한',
  `grant_update` int(11) unsigned DEFAULT '0' COMMENT '수정권한',
  `grant_delete` int(11) unsigned DEFAULT '0' COMMENT '삭제권한',
  PRIMARY KEY (`serial`),
  UNIQUE KEY `idx_insertUpdate` (`oid`,`group_name`,`kind_code`),
  KEY `idx_group_name` (`group_name`),
  KEY `idx_kind_code` (`group_name`,`kind_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='권한부여';

DROP TABLE IF EXISTS `member_grade`;
CREATE TABLE `member_grade` (
  `serial` int(11) NOT NULL AUTO_INCREMENT,
  `oid` int(11) DEFAULT '0' COMMENT '업체코드',
  `grade_code` int(11) unsigned DEFAULT '0' COMMENT '등급코드',
  `grade_name` varchar(45) DEFAULT '',
  `benefit_point_rate` float DEFAULT '0' COMMENT '등급혜택: 실결제금액의 %포인트 적립',
  PRIMARY KEY (`serial`),
  KEY `idx_grade_code` (`oid`,`grade_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='회원 등급 설정';

DROP TABLE IF EXISTS `member`;
CREATE TABLE `member` (
  `serial` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `oid` int(11) DEFAULT '0' COMMENT '업체코드',
  `family` int(11) DEFAULT '0',
  `is_admin` tinyint(1) DEFAULT '0' COMMENT '관리자 (true:1, false:0)',
  `grade` int(11) unsigned DEFAULT '0' COMMENT '회원등급 : member_grade TB의 grade_code',
  `lev` tinyint(4) DEFAULT '0' COMMENT '회원레벨',
  `userid` varchar(40) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `userpw` varchar(70) CHARACTER SET utf8 DEFAULT '',
  `username` varchar(30) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '회원성명',
  `usernick` varchar(45) CHARACTER SET utf8 DEFAULT '' COMMENT '닉네임',
  `htel` varchar(25) CHARACTER SET utf8 DEFAULT '',
  `hp` varchar(25) CHARACTER SET utf8 DEFAULT '' COMMENT '핸드폰번호',
  `hzip` varchar(45) CHARACTER SET utf8 DEFAULT '',
  `haddr1` varchar(50) CHARACTER SET utf8 DEFAULT '',
  `haddr2` varchar(100) CHARACTER SET utf8 DEFAULT '',
  `sex` tinyint(1) DEFAULT '0' COMMENT '1:남성(male) / 2:여성(female)',
  `birthday` int(11) DEFAULT '0',
  `profile_photo` varchar(100) CHARACTER SET utf8 DEFAULT '',
  `oauth_login` tinyint(1) DEFAULT '0' COMMENT 'oauth SNS 로그인 (true:1 / false:0)',
  `locale` varchar(10) CHARACTER SET utf8 DEFAULT '' COMMENT '국적(ko,eng....)',
  `agree_news` tinyint(1) DEFAULT '0' COMMENT '소식지받기(sms,mail) 1:true / 0:false',
  `authen` tinyint(1) DEFAULT '0' COMMENT '인증했는지(email또는  sms)',
  `authen_sms` varchar(15) CHARACTER SET utf8 DEFAULT '' COMMENT '폰 인증번호',
  `authen_email` varchar(40) COLLATE utf8_unicode_ci DEFAULT '' COMMENT 'email 인증번호',
  `total_point` int(11) DEFAULT '0' COMMENT '총 적립포인트',
  `ip` varchar(20) CHARACTER SET utf8 DEFAULT '',
  `recent_login` int(11) DEFAULT '0' COMMENT '최근 로그인 날짜',
  `withdrawal` tinyint(1) DEFAULT '0' COMMENT '회원탈퇴(1:true / 0:false)',
  `withdrawal_date` int(11) DEFAULT '0' COMMENT '탈퇴일자',
  `regdate` int(11) DEFAULT '0',
  PRIMARY KEY (`serial`),
  UNIQUE KEY `userid_UNIQUE` (`userid`),
  KEY `idx_userpw` (`userpw`),
  KEY `idx_regdate` (`regdate`),
  KEY `idx_ouserid` (`oid`,`userid`),
  KEY `idx_userid` (`userid`),
  KEY `idx_is_admin` (`is_admin`),
  KEY `idx_agree_news` (`agree_news`),
  KEY `idx_withdrawal` (`withdrawal`),
  KEY `idx_chk_provider` (`oauth_login`),
  KEY `idx_authen_sms` (`authen_sms`),
  KEY `idx_authen_email` (`authen_email`),
  KEY `fk_mbr_grade_idx` (`grade`),
  KEY `idx_mbr_grade` (`oid`,`grade`),
  CONSTRAINT `fk_mbr_grade` FOREIGN KEY (`oid`, `grade`) REFERENCES `member_grade` (`oid`, `grade_code`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='회원정보';


DROP TABLE IF EXISTS `member_config`;
CREATE TABLE `member_config` (
  `oid` int(11) NOT NULL DEFAULT '0',
  `grade_date` int(11) NOT NULL DEFAULT '0' COMMENT '[회원등급] 평가 기간(최근??개월간:3,6,12)',
  PRIMARY KEY (`oid`),
  UNIQUE KEY `uniq_oid` (`oid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='회원 환경설정';

DROP TABLE IF EXISTS `member_sns`;
CREATE TABLE `member_sns` (
  `oid` int(11) DEFAULT '0' COMMENT '업체코드',
  `family` int(11) DEFAULT '0',
  `oauth_provider` varchar(30) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT 'google, facebook',
  `oauth_uid` varchar(40) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT 'oauth 회원 ID',
  `userid` varchar(40) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `username` varchar(30) CHARACTER SET utf8 DEFAULT '' COMMENT '회원성명',
  `hp` varchar(25) CHARACTER SET utf8 DEFAULT '' COMMENT '핸드폰번호',
  `authen` tinyint(1) DEFAULT '0' COMMENT '인증했는지(email또는  sms)',
  `authen_sms` varchar(15) CHARACTER SET utf8 DEFAULT '' COMMENT '폰 인증번호',
  `authen_email` varchar(40) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT '' COMMENT 'email 인증번호',
  `locale` varchar(10) CHARACTER SET utf8 DEFAULT '' COMMENT '국적(ko,eng....)',
  `sex` tinyint(1) DEFAULT '0' COMMENT '1:남성(male) / 2:여성(female)',
  `birthday` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `profile_photo` varchar(120) CHARACTER SET utf8 DEFAULT '',
  `recent_login` int(11) DEFAULT '0' COMMENT '최근 로그인 날짜',
  `ip` varchar(20) CHARACTER SET utf8 DEFAULT '',
  PRIMARY KEY (`oauth_provider`,`oauth_uid`),
  KEY `idx_oauth_provider` (`oauth_provider`),
  KEY `idx_oauth_uid` (`oauth_uid`),
  KEY `idx_oauth` (`oauth_provider`,`oauth_uid`),
  KEY `idx_email` (`userid`),
  KEY `idx_authen_email` (`authen_email`),
  KEY `idx_authen_sms` (`authen_sms`),
  CONSTRAINT `fk_userid` FOREIGN KEY (`userid`) REFERENCES `member` (`userid`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='회원 외부 Api';


DROP TABLE IF EXISTS `menu`;
CREATE TABLE `menu` (
  `serial` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `oid` int(11) DEFAULT '0' COMMENT '업체코드',
  `mcode` int(11) DEFAULT '0' COMMENT '메뉴코드',
  `family` int(10) unsigned DEFAULT '0',
  `parent` int(11) DEFAULT '0' COMMENT '소속된 게시글번호',
  `indent` int(11) DEFAULT '0' COMMENT '들여쓰기 수준(Childe Node level)',
  `lft` int(11) DEFAULT '1',
  `rgt` int(11) DEFAULT '2',
  `title` varchar(45) CHARACTER SET utf8 DEFAULT '' COMMENT '메뉴명',
  `url` varchar(150) CHARACTER SET utf8 DEFAULT '' COMMENT '페이지 URL주소',
  `url_target` varchar(20) CHARACTER SET utf8 DEFAULT '' COMMENT '페이지 URL 타켓',
  `layout` varchar(45) CHARACTER SET utf8 DEFAULT '' COMMENT '레이아웃명',
  `tpl` varchar(45) CHARACTER SET utf8 DEFAULT '' COMMENT '본문에 들어갈 템플릿파일',
  `used` tinyint(1) DEFAULT '0' COMMENT '사용유무(1:사용 , 0:미사용)',
  `imp` tinyint(1) DEFAULT '0' COMMENT '노출유무(1:노출, 0:미노출)',
  `attach_basedir` varchar(45) CHARACTER SET utf8 DEFAULT '' COMMENT '첨부파일 경로',
  `attach_top` varchar(45) CHARACTER SET utf8 DEFAULT '' COMMENT '본문 상단 내용',
  `attach_bottom` varchar(45) CHARACTER SET utf8 DEFAULT '' COMMENT '본문 하단 내용',
  `grant_read` int(11) DEFAULT '0' COMMENT '권한등급-읽기',
  `grant_write` int(11) DEFAULT '0' COMMENT '권한등급-쓰기',
  PRIMARY KEY (`serial`),
  KEY `idx_parent` (`parent`),
  KEY `idx_indent` (`indent`),
  KEY `idx_lft` (`lft`),
  KEY `idx_rgt` (`rgt`),
  KEY `idx_family` (`family`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='메뉴관리';


DROP TABLE IF EXISTS `popups`;
CREATE TABLE `popups` (
  `serial` int(11) NOT NULL AUTO_INCREMENT,
  `oid` varchar(45) NOT NULL DEFAULT '0' COMMENT '업체코드',
  `title` varchar(45) DEFAULT '' COMMENT '타이틀명',
  `attach_basedir` varchar(45) DEFAULT '' COMMENT '첨부파일 경로',
  `attach_file` varchar(45) DEFAULT '',
  `memo` text COMMENT '내용글',
  `output` varchar(15) DEFAULT '' COMMENT '출력형식 : layer,win',
  `width` int(11) DEFAULT '0',
  `height` int(11) DEFAULT '0',
  `imp` tinyint(1) DEFAULT '0' COMMENT '노출유무(1:노출, 0:미노출)',
  `sdate` int(11) DEFAULT '0' COMMENT '시작일자',
  `edate` int(11) DEFAULT '0' COMMENT '종료일자',
  `regdate` int(11) DEFAULT '0' COMMENT '등록/업데이트 일자',
  PRIMARY KEY (`serial`),
  KEY `idx_imp` (`imp`),
  KEY `idx_date` (`sdate`,`edate`),
  KEY `idx_sdate` (`sdate`),
  KEY `idx_edate` (`edate`),
  KEY `idx_title` (`title`),
  KEY `idx_oid` (`oid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='팝업관리';
