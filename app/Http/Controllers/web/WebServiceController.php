<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebServiceController extends Controller
{
   public function getServicePartenaire($idPartenaire){
       $listservices = Service::where("partenaire_id", $idPartenaire)->orderBy("name_service")->get();
       return view('pages.transactions.show_service_partenaire', compact('listservices'));
   }
}
