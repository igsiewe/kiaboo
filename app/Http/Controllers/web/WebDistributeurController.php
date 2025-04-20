<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Http\Enums\TypeServiceEnum;
use App\Http\Enums\UserRolesEnum;
use App\Models\Distributeur;
use App\Models\Region;
use App\Models\User;
use App\Models\ville;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WebDistributeurController extends Controller
{
    public function getDetailDistributeurTopUpd($id){
        $distributeur = Distributeur::where('id',$id)->get()->first();
        return view('pages.myrelaod.detail_distributeur',compact('distributeur'));
    }

    public function getListDistributeur(){
        $distributeurs = Distributeur::with('region','agents')->get();
        $regions = Region::all();
        return view('pages.distributeurs.listdistributeur',compact('distributeurs','regions'));
    }
    public function agentDistributeur($id){
        $money = "F CFA";
        $agents = User::with('distributeur')->where("type_user_id", UserRolesEnum::AGENT->value)->where("distributeur_id",$id)->orderBy("name")->orderBy("surname")->get();

        return view('pages.distributeurs.editlistagent',compact('agents','money'));
    }
    public function debloqueDistributeur($id){
        $distributeur = Distributeur::find($id);
        $distributeur->status = 1;
        $distributeur->updated_at = now();
        $distributeur->updated_by = auth()->user()->id;
        $distributeur->save();
        return redirect()->back()->with('success', 'Distributeur débloqué avec succès');
    }
    public function deleteDistributeur($id){
        $distributeur = Distributeur::find($id);

        //On vérifie si son solde est à 0
        if($distributeur->balance_after != 0){
            return redirect()->back()->withErrors('Impossible de supprimer ce distributeur car son solde n\'est pas à 0');
        }

        //On vérifie s'il n'a pas d'agent
        if($distributeur->agents->count() > 0){
            return redirect()->back()->withErrors('Impossible de supprimer ce distributeur car il a des agents');
        }

        //On vérifie s'il est dans la table transactions
        if($distributeur->transactions->count() > 0){
            return redirect()->back()->withErrors('Impossible de supprimer ce distributeur car il a des transactions');
        }
        $distributeur->delete();
        return redirect()->back()->with('success', 'Distributeur supprimé avec succès');
    }
    public function bloqueDistributeur($id){
        $distributeur = Distributeur::find($id);
        $distributeur->status = 0;
        $distributeur->updated_at = now();
        $distributeur->updated_by = auth()->user()->id;
        $distributeur->save();
        return redirect()->back()->with('success', 'Distributeur bloqué avec succès');
    }

    public function showDistributeur($id){
        $distributeur = Distributeur::find($id);
        $regions = Region::all();
        return view('pages.distributeurs.editdistributeur',compact('distributeur','regions'));
    }
    public function setNewDistributeur(Request $request){
        $validator = Validator::make($request->all(), [
            'name_distributeur' => 'required|string',
            'name_contact' => 'required|string',
            'surname_contact' => 'required|string',
            'telephone'=>'required|string',
            'email'=>'required|string',
            'region'=>'required|integer',
            'adresse'=>'required|string',

        ]);
        if(Auth::user()->status == 0){
            return redirect()->back()->withErrors('You cannot authorize to perform this operation');
        }
        if(Auth::user()->type_user_id != UserRolesEnum::BACKOFFICE->value && Auth::user()->type_user_id != UserRolesEnum::SUPADMIN->value){
            return redirect()->back()->withErrors('You cannot authorize to perform this operation');
        }

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors()->first());
        }

        $newDistributeur = new Distributeur();
        $newDistributeur->name_distributeur = $request->name_distributeur;
        $newDistributeur->name_contact = $request->name_contact;
        $newDistributeur->surname_contact = $request->surname_contact;
        $newDistributeur->phone = $request->telephone;
        $newDistributeur->email = $request->email;
        $newDistributeur->region_id = $request->region;
        $newDistributeur->adresse = $request->adresse;
        $newDistributeur->plafond_alerte =$request->plafond_alerte;
        $newDistributeur->status = 1;
        $newDistributeur->balance_before = 0;
        $newDistributeur->balance_after = 0;
        $newDistributeur->last_amount = 0;
        $newDistributeur->last_transaction_id = null;
        $newDistributeur->reference_last_transaction = null;
        $newDistributeur->date_last_transaction = Carbon::now();
        $newDistributeur->user_last_transaction_id = null;
        $newDistributeur->created_by = Auth::user()->id;
        $newDistributeur->updated_by = Auth::user()->id;
        $newDistributeur->save();
        return redirect()->back()->with('success', 'Distributeur created successfully');
    }

    public function setUpdateDistributeur(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'name_distributeur' => 'required|string',
            'name_contact' => 'required|string',
            'surname_contact' => 'required|string',
            'telephone'=>'required|string',
            'email'=>'required|string',
            'region'=>'required|integer',
            'adresse'=>'required|string',
        ]);

        if(Auth::user()->status == 0){
            return redirect()->back()->withErrors('You cannot authorize to perform this operation');
        }
        if(Auth::user()->type_user_id != UserRolesEnum::BACKOFFICE->value && Auth::user()->type_user_id != UserRolesEnum::SUPADMIN->value){
            return redirect()->back()->withErrors('You cannot authorize to perform this operation');
        }

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors()->first());
        }

        $updateDistributeur = Distributeur::find($id);
        if($updateDistributeur->status== 0){
            return redirect()->back()->withErrors('Please unblock this distributor to update it');
        }
        if($updateDistributeur !=null){
            $updateDistributeur->name_distributeur = $request->name_distributeur;
            $updateDistributeur->name_contact = $request->name_contact;
            $updateDistributeur->surname_contact = $request->surname_contact;
            $updateDistributeur->email = $request->email;
            $updateDistributeur->phone = $request->telephone;
            $updateDistributeur->region_id = $request->region;
            $updateDistributeur->plafond_alerte = $request->plafond_alerte;
            $updateDistributeur->adresse = $request->adresse;
            $updateDistributeur->updated_at = now();
            $updateDistributeur->updated_by = auth()->user()->id;
            $updateDistributeur->save();
            return redirect()->back()->with('success', 'Distributeur updated successfully');
        }else{
            return redirect()->back()->withErrors('Agent to be updated not found ');
        }

    }

    public function getListFiltreDistributeur(){
        $mesdistributeurs=Distributeur::all()->sortBy("name_distributeur");
        if(Auth::user()->type_user_id==UserRolesEnum::DISTRIBUTEUR->value){
            $mesdistributeurs=Distributeur::where("id",Auth::user()->distributeur_id)->orderBy("name_distributeur")->get();
        }
        return view('pages.utilisateurs.charge_list_distributeur', compact('mesdistributeurs'));
    }
}
