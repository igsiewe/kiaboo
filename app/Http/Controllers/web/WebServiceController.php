<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebServiceController extends Controller
{
   public function getServicePartenaire($idPartenaire){

       if($idPartenaire != null || $idPartenaire != "" || $idPartenaire != "0"  || $idPartenaire <> 0) {
           $listservices = Service::where("partenaire_id", $idPartenaire)->orderBy("name_service")->get();
           dd("1", $idPartenaire, $listservices);
       }
       else{
           $listservices = Service::all()->sortBy("name_service");
           dd("2", $idPartenaire, $listservices);
       }

       return view('pages.transactions.show_service_partenaire', compact('listservices'));
   }
}
