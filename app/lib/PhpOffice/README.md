공식 홈페이지 : https://phpspreadsheet.readthedocs.io/en/develop/
개발 GitHub : https://github.com/PHPOffice/PhpSpreadsheet


## Requirements - 설치시 요구사항(Software requirements)

The following software is required to develop using PhpSpreadsheet:

PHP version 5.6 or newer
PHP extension php_zip enabled
PHP extension php_xml enabled
PHP extension php_gd2 enabled (if not compiled in)
PHP version support
Support for PHP versions will only be maintained for a period of six months beyond the end-of-life of that PHP version

### Composer Installation

Use composer to install PhpSpreadsheet into your project:

★ 폴더 생성후 아래 명령어로 실행하여 설치 ★
	
	composer require phpoffice/phpspreadsheet
	
## Examples ##

현재 소스에서 적용방법

	```php

	require_once "PhpOffice/vendor/autoload.php" ;
	
	use PhpOffice\PhpSpreadsheet\Spreadsheet;
	use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
	
	$spreadsheet = new Spreadsheet();
	$sheet = $spreadsheet->getActiveSheet();
	$sheet->setCellValue('A1', 'Hello World !');
	
	$writer = new Xlsx($spreadsheet);
	$writer->save('hello world.xlsx');
	```

Hello World
This would be the simplest way to write a spreadsheet:

	```php

	require 'vendor/autoload.php';
	
	use PhpOffice\PhpSpreadsheet\Spreadsheet;
	use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
	
	$spreadsheet = new Spreadsheet();
	$sheet = $spreadsheet->getActiveSheet();
	$sheet->setCellValue('A1', 'Hello World !');
	
	$writer = new Xlsx($spreadsheet);
	$writer->save('hello world.xlsx');
	```
-------------------------------------
Learn by example

A good way to get started is to run some of the samples. Serve the samples via PHP built-in webserver:

	php -S localhost:8000 -t vendor/phpoffice/phpspreadsheet/samples

Then point your browser to:

	http://localhost:8000/

The samples may also be run directly from the command line, for example:

	php vendor/phpoffice/phpspreadsheet/samples/Basic/01_Simple.php