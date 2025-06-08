<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebServiceController extends Controller
{
   public function getServicePartenaire($idPartenaire){

       if($idPartenaire != null || $idPartenaire != "" || $idPartenaire != "0") {
           $listservices = Service::where("partenaire_id", $idPartenaire)->orderBy("name_service")->get();
       }
       else{
           $listservices = Service::all()->sortBy("name_service");
       }

       return view('pages.transactions.show_service_partenaire', compact('listservices'));
   }
}
