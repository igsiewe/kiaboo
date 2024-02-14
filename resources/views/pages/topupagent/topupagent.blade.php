@extends('base')
@section('content')
    <div class='loader'>
        <div class='spinner-grow text-danger' role='status'>
            <span class='sr-only'>Loading...</span>
        </div>
    </div>
    <div class="page-content">
        <div class="main-wrapper">
            <div class="row">

                <div class="col-md-12 col-xl-12">
                    <div class="card-header stat-widget card-shadow-danger border-danger">
                            <span class="d-inline-block pr-2">
                            <i class="fa fa-wallet fa-2x"></i>
                            </span>
                        <span class="d-inline-block"><h4><b>&nbsp;&nbsp;RECHARGE DES AGENTS</b></h4></span>
                    </div>

                </div>
            </div><p/>
            <div class="row">
                <div class="col-md-12 col-lg-12">
                    <div class="card-header stat-widget card-shadow-danger border-danger">
                        <div class="widget-chat-wrapper-outer">
                            <div class="widget-chart-content">
                                <form action="{{route("topupAgent.filtre")}}" id="frmFiltre" name="frmFiltre" method="post">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-12 col-lg-3">
                                            <div class="form-group">
                                                <label class="form-label" for="distributeur">Distributeur</label>
                                                <select class="form-select" name="distributeur" id="distributeur" onchange="javascript:getMesAgents(this.value);">
                                                    @isset($listDistributeurs)
                                                        @if($listDistributeurs)

                                                            @if(Auth::user()->type_user_id !=\App\Http\Enums\UserRolesEnum::DISTRIBUTEUR->value){
                                                            <option  value="" >Tous les distributeurs</option>
                                                            @endif
                                                            @foreach($listDistributeurs as $s)
                                                                @if(isset($distributeur))
                                                                    @if($s->id == $distributeur)
                                                                        <option  value="{{ $s->id }}" selected >{{$s->name_distributeur}}</option>
                                                                    @else
                                                                        <option  value="{{ $s->id }}" >{{$s->name_distributeur}}</option>
                                                                    @endif
                                                                @else
                                                                    <option  value="{{ $s->id }}" >{{$s->name_distributeur}}</option>
                                                                @endif
                                                            @endforeach
                                                        @endif
                                                    @endisset
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-12 col-lg-3">
                                            <div class="form-group">
                                                <label class="form-label" for="agent">Agent</label>
                                                <div id="mesagents">
                                                    <select class="form-select" name="agent" id="agent" onchange="">
                                                        @isset($listagents)
                                                            @if($listagents->count()>0)
                                                                <option  value="" >Tous les agents</option>
                                                                @foreach($listagents as $s)
                                                                    @if(isset($agent))
                                                                        @if($s->id == $agent)
                                                                            <option  value="{{ $s->id }}" selected >{{$s->login." | ".$s->name." ".$s->surname}}</option>
                                                                        @else
                                                                            <option  value="{{ $s->id }}" >{{$s->login." | ".$s->name." ".$s->surname}}</option>
                                                                        @endif
                                                                    @else
                                                                        <option  value="{{ $s->id }}" >{{$s->login." | ".$s->name." ".$s->surname}}</option>
                                                                    @endif
                                                                @endforeach
                                                            @endif
                                                        @endisset
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-12 col-lg-2">
                                            <div class="form-group">
                                                <label class="form-label" for="startDate">Date début</label>
                                                <input type="date" name="startDate" id="startDate" class="form-control" placeholder="StartDate"
                                                       @if(isset($startDate))
                                                       value="{{$startDate}}"
                                                       @else
                                                       value="<?php echo date('Y-m-01'); ?>"
                                                    @endif>
                                            </div>
                                        </div>
                                        <div class="col-md-12 col-lg-2">
                                            <div class="form-group">
                                                <label class="form-label" for="endDate">Date fin</label>
                                                <input type="date" name="endDate" id="endDate" class="form-control" placeholder="EndDate" @if(isset($endDate))
                                                value="{{$endDate}}"
                                                       @else
                                                       value="<?php echo date('Y-m-d'); ?>"
                                                    @endif>
                                            </div>
                                        </div>
                                        <div class="col-md-12 col-lg-2">
                                            <div class="input-icon">
                                                <label class="form-label" for="btnSearch">&nbsp;</label>
                                                <div class="btn-list">
                                                    <button type="button" title="Search" id="btnSearch" onclick="javascript:checkDate();" class="btn btn-outline-danger form-control"><i class="fa fa-search"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div><p/>
            <div id="errorFiltre"></div>
            @if($errors->any())
                <div class="row">
                    <div class="col-12 col-sm-12">
                        <div class='alert alert-danger alert-dismissible'>
                            <button type="button" class="close" data-bs-dismiss="alert" aria-label="Close" title="Close">x</button>
                            <span><h6><i class='icon fa fa-ban'></i> Erreur! {{$errors->first()}}</h6></span>
                        </div>
                    </div>
                </div>
            @endif
            @if(session('error'))
                <div class="row">
                    <div class="col-12 col-sm-12">
                        <div class='alert alert-danger alert-dismissible'>
                            <button type='button' class='close' data-bs-dismiss='alert' aria-hidden='true' title="Close">×</button>
                            <span><h6><i class='icon fa fa-ban'></i> Erreur! {{session('error')}}</h6></span>
                        </div>
                    </div>
                </div>

            @endif

            @if(session('success'))
                <div class="row">
                    <div class="col-12 col-sm-12">
                        <div class='alert alert-success alert-dismissible'>
                            <button type='button' class='close' data-bs-dismiss='alert' aria-hidden='true' title="Close">×</button>
                            <span><h6><i class='icon fa fa-smile'></i> {{session('success')}}</h6></span>
                        </div>
                    </div>
                </div>

            @endif

            <div class="row">
                <div class="col-md-12 col-lg-12">
                    <div class="card-header">
                        <div class="card-group">
                            <div class="btn-actions-pane-right">
                                <div role="group" class="btn-group-sm btn-group-lg">
                                    <form action="#" method="POST" name="exportform" enctype="multipart/form-data" method="post">
                                        @csrf
                                        @if(\App\Http\Enums\UserRolesEnum::DISTRIBUTEUR->value==\Illuminate\Support\Facades\Auth::user()->type_user_id)
                                            <button type="button" title="Nouvelle recharge" class="btn btn-kiaboo" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
                                                <i class="fa fa-plus"></i>  Nouvelle recharge
                                            </button>
                                        @endif
                                    <a href="#" class="btn btn-danger" title="Etat des soldes des agents" data-bs-toggle="modal" data-bs-target="#staticBackdropSolde"><i class="fa fa-balance-scale"></i>  Etat des soldes </a>
                                    <a href="{{route("export.recharge")}}" class="btn btn-danger" title="Exporter"><i class="fa fa-download"></i>  Tout exporter </a>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-header">
                        <div class="card table-widget">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="add-row"  class="table table-hover table-striped table-bordered dataTable dtr-inline" role="grid" aria-sort="false">
                                        <thead>
                                        <tr>
                                            <th scope="col" nowrap>#</th>
                                            <th scope="col" nowrap>Date</th>
                                            <th scope="col" nowrap>TransactionID</th>
                                            <th scope="col" nowrap>Agent</th>
                                            <th scope="col" nowrap>Balance Before</th>
                                            <th scope="col" nowrap>Amount</th>
                                            <th scope="col" nowrap>Balance After</th>
                                            @if(\Illuminate\Support\Facades\Auth::user()->type_user_id !=\App\Http\Enums\UserRolesEnum::DISTRIBUTEUR->value)
                                                <th scope="col" nowrap>Distributeur</th>
                                            @endif
                                            <th scope="col" nowrap>Opérateur</th>
                                            <th scope="col"></th>
                                        </tr>
                                        </thead>
                                        <tbody>

                                        @isset($transactions)
                                            @if($transactions->isNotEmpty())
                                                @foreach($transactions as $c)
                                                    <tr>
                                                        <td align="right">{{$loop->iteration}}</td>
                                                        <td nowrap align="center">{{$c->date_transaction}}</td>
                                                        <td nowrap>#{{$c->reference}}</td>
                                                        <td nowrap align="left" title="{{$c->name_agent." ".$c->surname_agent}}">{{$c->telephone." | ".$c->name_agent." ".$c->surname_agent}}</td>
                                                        <td align="right" nowrap>{{number_format($c->balance_before_partenaire,0,","," ")." ".$money}}</td>
                                                        @if($c->debit==0)
                                                            <td nowrap style="color: black" align="right" nowrap>{{number_format($c->credit,0,","," ")." ".$money}}</td>
                                                        @else
                                                            <td nowrap style="color: black" align="right" nowrap>{{number_format($c->debit,0,","," ")." ".$money}}</td>
                                                        @endif
                                                        <td nowrap align="right" nowrap>{{number_format($c->balance_after_partenaire,0,","," ")." ".$money}}</td>
                                                        @if(\Illuminate\Support\Facades\Auth::user()->type_user_id !=\App\Http\Enums\UserRolesEnum::DISTRIBUTEUR->value)
                                                            <td nowrap>{{$c->name_distributeur}}</td>
                                                        @endif
                                                        <td nowrap>{{$c->name_operateur." ".$c->surname_operateur}}</td>
                                                        <td align="center" nowrap>
                                                            <a type="button" class="btn" style="border: none; color: red"  data-bs-toggle="modal" data-bs-target="#staticBackdropDetailTopUp" onclick="javascript:getDerailTopUp({{$c->id}})"><i class="fa fa-eye"></i></a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        @endisset

                                        </tbody>
                                    </table>
                                    <table class="table table-hover table-striped table-bordered dataTable dtr-inline" role="grid">
                                        <tr>
                                            <td style="border: none">

                                            </td>
                                            <td style="border: none">
                                                @isset($transactions)
                                                    @if($transactions->isNotEmpty())
                                                        <div style="text-align: center">{{"Nombre de recharges = ".number_format($transactions->count(),0,',',' ')." / Montant total = ".number_format(($transactions->sum("credit")+$transactions->sum("debit")),0,',',' ')." ".$money}} </div>
                                                    @endif
                                                @endisset
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal -->
    <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Approvisionner un agent</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('setTopUpAgent') }}" id="frmRecharge" name="frmRecharge" method="post">
                    @csrf
                    <div class="modal-body">
                        <div class="widget-chart-content">
                            <div class="row">
                                <div class="col-md-12 col-lg-12">
                                    <div class="input-group mb-12">
                                        <span class="input-group-text" id="basic-addon1"><i class="fa fa-user"></i></span>
                                        <select class="form-select" name="agent" id="agent" onchange="" required onclick="getdetailAgentTopUpd(this.value)">
                                            @isset($listagents)
                                                @if($listagents)
                                                    <option  value="" >Sélectionner un agent</option>
                                                    @foreach($listagents as $s)
                                                        <option  value="{{ $s->id }}" >{{$s->login." : ".$s->name." ".$s->surname}}</option>
                                                    @endforeach
                                                @endif
                                            @endisset
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <p/>
                            <div class="row">
                                <div class="col-md-12 col-lg-12">
                                    <div id="detailAgentTopUpd"></div>
                                </div>
                            </div>
                             <div class="row">
                                <div class="col-md-12 col-lg-12">
                                    <div class="input-group mb-12">
                                        <span class="input-group-text"><i class="fa fa-money-bill"></i></span>
                                        <input type="number" id="amount" name="amount" class="form-control" placeholder="Montant à approvsionner" aria-label="montant" required>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Recharger</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="staticBackdropSolde" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Etat des soldes des agents</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="#" id="frmRecharge" name="frmRecharge" method="post">
                    @csrf
                    <div class="modal-body">
                        <div class="widget-chart-content">
                            <div class="row">
                                <div class="col-md-12 col-lg-12">
                                    <table class="table table-hover table-striped table-bordered dataTable dtr-inline" role="grid">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            @if(\Illuminate\Support\Facades\Auth::user()->type_user_id != \App\Http\Enums\UserRolesEnum::DISTRIBUTEUR->value)
                                                <th scope="col">Distributeur</th>
                                            @endif
                                            <th>Agent</th>
                                            <th>Solde</th>
                                            <th>Seuil alerte</th>
                                            <th nowrap>Date last transaction</th>

                                        </tr>
                                        </thead>
                                        <tbody>
                                        @isset($listagents)
                                            @if($listagents->isNotEmpty())
                                                @foreach($listagents as $s)
                                                    <tr>
                                                        <td>{{$loop->iteration}}</td>
                                                        @if(\Illuminate\Support\Facades\Auth::user()->type_user_id != \App\Http\Enums\UserRolesEnum::DISTRIBUTEUR->value)
                                                            <th>{{$s->distributeur->name_distributeur}}</th>
                                                        @endif
                                                        <td>{{$s->name." ".$s->surname}}</td>
                                                        <td nowrap align="right" nowrap>{{number_format($s->balance_after,0,","," ")." ".$money}}</td>
                                                        @if($s->balance_after < $s->seuilapprovisionnement)
                                                            <td nowrap align="right" style="color: red" nowrap>{{number_format($s->seuilapprovisionnement,0,","," ")." ".$money}}</td>
                                                        @else
                                                            <td nowrap align="right" style="color: black" nowrap>{{number_format($s->seuilapprovisionnement,0,","," ")." ".$money}}</td>
                                                        @endif

                                                        <td nowrap align="center" nowrap>{{$s->date_last_transaction}}</td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        @endisset

                                        </tbody>
                                    </table>
                                </div>
                            </div>


                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Annuler</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="staticBackdropDetailTopUp" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">

            <div class="modal-dialog modal-xl modal-dialog-centered">

                     <div class="modal-content">
                         <div id="detailTopUp">
                            <div class="modal-header">
                                <h5 class="modal-title" id="staticBackdropLabel">Détail recharge agent</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">

                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Fermer</button>
                            </div>
                        </div>
                </div>
            </div>

    </div>

    <script>
        function getdetailAgentTopUpd(id) {
            //  $("#detailAgentTopUpd").html("");
            $.ajax({
                url: "/approvisionnement/agent/edit/"+id,
                type: "GET",
                success: function (data) {
                    $("#detailAgentTopUpd").html(data);
                }
            });
        }

        function getMesAgents(idDistributeur) {
            document.getElementById("agent").options.length=0;
            $.ajax({
                url: "/agent/distributeur/"+idDistributeur,
                type: "GET",
                success: function (data) {
                    $("#mesagents").html(data);
                }
            });
        }

        function getDerailTopUp(id) {
             $.ajax({
                url: "/transactions/topup/agent/"+id,
                type: "GET",
                success: function (data) {
                    $("#detailTopUp").html(data);
                }
            });
        }

        function CancelAgentTopUp(id){

            if (confirm("Etes-vous sûr de vouloir annuler cette recharge ?")) {
                document.getElementById('canceltopup'+id).onclick();
            }

        }
    </script>
@endsection
