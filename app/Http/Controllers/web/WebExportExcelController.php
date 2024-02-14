<?php

namespace App\Http\Controllers\web;

use App\Exports\ApporivsionnementExport;
use App\Exports\RechargeExport;
use App\Exports\TransactionExport;
use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class WebExportExcelController extends BaseController
{
    public function exportTransaction(){
        return Excel::download(new TransactionExport, 'transaction.xlsx');
    }

    public function exportApprovisionnement(){
        return Excel::download(new ApporivsionnementExport, 'approvisionnement.xlsx');
    }

    public function exportRecharge(){
        return Excel::download(new RechargeExport, 'recharge.xlsx');
    }
}
