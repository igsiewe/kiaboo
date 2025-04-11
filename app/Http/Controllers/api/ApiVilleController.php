<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiVilleController extends Controller
{
  public function getListVille(){
      $villes = DB::table('villes')->where("status",1)->select("id","name_ville")->orderBy("name_ville")->get();
      $quartiers = DB::table('quartiers')->where("status",1)->select("id","name_quartier","ville_id")->orderBy("name_quartier")->get();
        return response()->json([
            'status' => true,
            'message' => 'List of Villes',
            'villes' => $villes,
            'quartiers' => $quartiers
        ]);
  }
}
