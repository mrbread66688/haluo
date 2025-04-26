<?php
require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// 创建一个新的 Spreadsheet 对象
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Hello World!');

// 保存为 Excel 文件
$writer = new Xlsx($spreadsheet);
$writer->save('hello_world.xlsx');

echo "Excel 文件已生成：hello_world.xlsx";