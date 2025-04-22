<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\prospect;
use Illuminate\Http\Request;

class WebProspectontroller extends Controller
{
   public function getListProspect(){
       $listProspect = prospect::with('ville', 'quartier', 'ville_piece')->orderBy('id', 'DESC')->get();
       return view('pages.prospect.listprospect', compact('listProspect'));
   }

   public function valideProspect($id){

   }

    public function editProspect($id){
        $editProspect = prospect::with('ville', 'quartier', 'ville_piece')->where('id', $id)->first();
        return view('pages.prospect.editprospect', compact('editProspect'));
    }

}
