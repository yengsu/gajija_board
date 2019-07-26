/**
 * @license Copyright (c) 2003-2017, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

var CKEDITOR_config = {
	language : 'ko',
	// Define changes to default configuration here. For example:
	// language : 'fr';
	// uiColor : '#AADC6E';
	// startupMode : 'source' ;
	//removeButtons : 'Scayt,Form,Checkbox,TextField,Radio,Textarea,Select,Button,ImageButton,HiddenField,Language,About,Save';
	//skin : 'office2013';
	allowedContent : true,
	//enterMode : CKEDITOR.ENTER_BR,
	skin : 'bootstrapck',
	
	filebrowserBrowseUrl : '/dist/components/file_manager/elFinder/elfinder.php',

	toolbar : [
		{ name: 'document', groups: [ 'mode', 'document', 'doctools' ], items: [ 'Source','CodeSnippet' ] },
		{ name: 'tools', items: [ 'Maximize', 'ShowBlocks', 'NewPage' ] },
		//{ name: 'clipboard', groups: [ 'clipboard', 'undo' ], items: [ 'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo' ] },
		//{ name: 'editing', groups: [ 'find', 'selection', 'spellchecker' ], items: [ 'Scayt' ] },
		{ name: 'links', items: [ 'Link', 'Unlink' ] },
		{ name: 'insert', items: [ 'Image', 'Table', 'SpecialChar' ] },//, 'EqnEditor'
		{ name: 'aligns', items: [ 'JustifyLeft',	'JustifyCenter', 'JustifyRight', 'JustifyBlock' ] },
		
		
		{ name: 'others', items: [ '-' ] },
		//'/',
		{ name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ], items: [ 'Bold', 'Italic', 'Strike', '-', 'RemoveFormat' ] },
		//{ name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi' ], items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote' ] },
		//{ name: 'styles', items: [ 'Font', 'FontSize', 'TextColor', 'BGColor', 'Styles' ] }
		{ name: 'styles', items: [ 'FontSize', 'TextColor', 'BGColor', 'Styles' ] }
		//{ name: 'about', items: [ 'About' ] }
	],
	extraPlugins : 'codemirror,tableresize,quicktable,codesnippet,widget,lineutils,widgetselection,clipboard,dialog,eqneditor',
	//extraPlugins : 'codemirror,tableresize,quicktable,widget,lineutils,widgetselection,clipboard,dialog,eqneditor',
	//codeSnippet_theme: 'github',
	//codeSnippet_theme: 'monokai_sublime',
	//codeSnippet_theme: 'dark',
	codeSnippet_theme: 'dracula',
	
	//quick table option
	qtRows : 15,
	qtColumns : 20,
	qtBorder : '1',
	qtWidth : '90%',
	qtStyle : { 'border-collapse' : 'collapse' },
	qtClass : 'test',
	qtCellPadding : '0',
	qtCellSpacing : '0'
	
	//extraPlugins : 'tableresize';
	
	,codemirror : {

	    // Set this to the theme you wish to use (codemirror themes)
	    theme: 'default',

	    // Whether or not you want to show line numbers
	    lineNumbers: true,

	    // Whether or not you want to use line wrapping
	    lineWrapping: true,

	    // Whether or not you want to highlight matching braces
	    matchBrackets: true,

	    // Whether or not you want tags to automatically close themselves
	    autoCloseTags: true,

	    // Whether or not you want Brackets to automatically close themselves
	    autoCloseBrackets: true,

	    // Whether or not to enable search tools, CTRL+F (Find), CTRL+SHIFT+F (Replace), CTRL+SHIFT+R (Replace All), CTRL+G (Find Next), CTRL+SHIFT+G (Find Previous)
	    enableSearchTools: true,

	    // Whether or not you wish to enable code folding (requires 'lineNumbers' to be set to 'true')
	    enableCodeFolding: true,

	    // Whether or not to enable code formatting
	    enableCodeFormatting: true,

	    // Whether or not to automatically format code should be done when the editor is loaded
	    autoFormatOnStart: true,

	    // Whether or not to automatically format code should be done every time the source view is opened
	    autoFormatOnModeChange: true,

	    // Whether or not to automatically format code which has just been uncommented
	    autoFormatOnUncomment: true,

	    // Define the language specific mode 'htmlmixed' for html including (css, xml, javascript), 'application/x-httpd-php' for php mode including html, or 'text/javascript' for using java script only
	    mode: 'htmlmixed',
	    //parserfile : ["/dist/editor/ckeditor/plugins/codemirror/js/codemirror.mode.htmlmixed.min.js", "/dist/editor/ckeditor/plugins/codemirror/js/codemirror.mode.javascript.min.js", "/dist/editor/ckeditor/plugins/codemirror/js/codemirror.mode.php.min.js"],

	    // Whether or not to show the search Code button on the toolbar
	    showSearchButton: true,

	    // Whether or not to show Trailing Spaces
	    showTrailingSpace: true,

	    // Whether or not to highlight all matches of current word/selection
	    highlightMatches: true,

	    // Whether or not to show the format button on the toolbar
	    showFormatButton: true,

	    // Whether or not to show the comment button on the toolbar
	    showCommentButton: true,

	    // Whether or not to show the uncomment button on the toolbar
	    showUncommentButton: true,

	    // Whether or not to show the showAutoCompleteButton button on the toolbar
	    showAutoCompleteButton: true,
	    
	 // Whether or not to highlight the currently active line
	    styleActiveLine: true
	}
};
/*window.onload = function(){
CKEDITOR.editorConfig = function( config ) {
	//config.protectedSource.push(/<\?[\s\S]*?\?>/g); // PHP Code
	//config.protectedSource.push(/<code>[\s\S]*?<\/code>/gi); // Code tags
	//config.protectedSource.push( /<code[\s\S]*?\/code>/g );
	
	
	config.enterMode = CKEDITOR.ENTER_BR;
}
};*/