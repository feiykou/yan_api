<?php


namespace app\api\controller\v1;
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelCustomer extends Base
{
    public function importData()
    {
        $excel = new Excel();
    }
}