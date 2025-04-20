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
                            <i class="fa fa-credit-card fa-2x"></i>
                            </span>
                        <span class="d-inline-block"><h4><b>&nbsp;
                                    @if(\Illuminate\Support\Facades\Auth::user()->type_user_id==\App\Http\Enums\UserRolesEnum::DISTRIBUTEUR->value)
                                    MES APPROVISIONNEMENTS
                                    @else
                                    APPROVISIONNEMENTS DES DISTRIBUTEURS
                                    @endif
                                </b></h4></span>
                    </div>

                </div>
            </div><p/>
            <div class="row">
                <div class="col-md-12 col-lg-12">
                    <div class="card-header stat-widget card-shadow-danger border-danger">
                        <div class="widget-chat-wrapper-outer">
                            <div class="widget-chart-content">
                                <form action="{{route("listApprovisionnement.filtre")}}" id="frmFiltre" name="frmFiltre" method="post">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-12 col-lg-4">
                                            <div class="form-group">
                                                <label class="form-label" for="startDate">Date début</label>
                                                <input type="date" name="startDate" id="startDate" required class="form-control" placeholder="StartDate"
                                                       @if(isset($startDate))
                                                       value="{{$startDate}}"
                                                       @else
                                                       value="<?php echo date('Y-m-01'); ?>"
                                                    @endif>
                                            </div>
                                        </div>
                                        <div class="col-md-12 col-lg-4">
                                            <div class="form-group">
                                                <label class="form-label" for="endDate">Date fin</label>
                                                <input type="date" name="endDate" id="endDate" class="form-control" required placeholder="EndDate" @if(isset($endDate))
                                                value="{{$endDate}}"
                                                       @else
                                                       value="<?php echo date('Y-m-d'); ?>"
                                                    @endif>
                                            </div>
                                        </div>
                                        <div class="col-md-12 col-lg-2">
                                            <div class="form-group">
                                                <label class="form-label" for="status">Status</label>
                                                <select id="status" name="status" class="form-select" required>
                                                    @isset($status)
                                                        @if($status==0)
                                                            <option value=""></option>
                                                            <option value="1" data-icon="fa fa-edit">Validated</option>
                                                            <option value="2" data-icon="fa fa-edit">Rejected</option>
                                                            <option value="0" selected data-icon="fa fa-edit">In progress</option>
                                                        @endif
                                                        @if($status==1)
                                                            <option value=""></option>
                                                            <option value="1" selected data-icon="fa fa-edit">Validated</option>
                                                            <option value="2" data-icon="fa fa-edit">Rejected</option>
                                                            <option value="0" data-icon="fa fa-edit">In progress</option>
                                                        @endif
                                                        @if($status==2)
                                                                <option value=""></option>
                                                                <option value="1" data-icon="fa fa-edit">Validated</option>
                                                                <option value="2" selected data-icon="fa fa-edit">Rejected</option>
                                                                <option value="0" data-icon="fa fa-edit">In progress</option>
                                                        @endif
                                                    @else
                                                        <option value=""></option>
                                                        <option value="1" data-icon="fa fa-edit">Validated</option>
                                                        <option value="2" data-icon="fa fa-edit">Rejected</option>
                                                        <option value="0" selected data-icon="fa fa-edit">In progress</option>
                                                    @endif
                                                </select>
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
                                    @if(\Illuminate\Support\Facades\Auth::user()->type_user_id==\App\Http\Enums\UserRolesEnum::FRONTOFFICE->value || \Illuminate\Support\Facades\Auth::user()->type_user_id==\App\Http\Enums\UserRolesEnum::SUPADMIN->value)
                                        <button type="button" class="btn btn-kiaboo" data-bs-toggle="modal" data-bs-target="#staticBackdropAppro">
                                            <i class="fa fa-plus"></i>  Nouvel approvisionnement
                                        </button>
                                        <a href="#" class="btn btn-danger" title="Etat des soldes des distributeurs" data-bs-toggle="modal" data-bs-target="#staticBackdropSolde"><i class="fa fa-balance-scale"></i>  Etat des soldes </a>
                                    @endif
                                <button type="button" class="btn btn-kiaboo" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
                                    <i class="fa fa-clock"></i>  Relevé de compte
                                </button>
                                <a href="{{route("export.approvisionnement")}}" class="btn btn-danger" title="Exporter"><i class="fa fa-download"></i>  Tout exporter</a>
                                </form>
                             </div>
                        </div>
                        </div>
                    </div>
                    <div class="card-header">
                        <div class="card table-widget">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="add-row" class="table table-bordered table-hover table-striped dataTable dtr-inline" role="grid">
                                        <thead>
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col" title="Date inititation" nowrap>Date init.</th>
                                                <th scope="col">Reference</th>
                                                <th scope="col" nowrap>Distributeur</th>
                                                <th scope="col" nowrap>Amount</td>
                                                <th scope="col" nowrap>Balance Before</th>
                                                <th scope="col" nowrap>Balance After</th>
                                                <th scope="col">Status</th>
                                                <th scope="col" nowrap>Date action</th>
    {{--                                            <th scope="col">Opérateur</th>--}}
                                                <th scope="col"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @isset($listApprovisionnement)
                                            @if($listApprovisionnement->isNotEmpty())
                                                @foreach($listApprovisionnement as $c)
                                                    <tr>
                                                        <td align="right">{{$loop->iteration}}</td>
                                                        <td nowrap align="center" title="Demande initiée le {{$c->date_operation}}">{{$c->date_operation}}</td>
                                                        <td>#{{$c->reference}}</td>
                                                        <td nowrap>{{$c->distributeur->name_distributeur}}</td>
                                                        <td nowrap align="right">{{number_format($c->amount,0,","," ")." ".$money}}</td>

                                                        @if($c->status==0)
                                                            <td></td>
                                                            <td></td>
                                                            <td><span class="badge bg-primary"><i class="fa fa-clock"></i> In progress</span></td>
                                                            <td></td>
{{--                                                            <td>{{$c->createdBy!=null?$c->createdBy->name." ".$c->createdBy->surname:""}}</td>--}}
                                                        @elseif($c->status==1)
                                                            <td nowrap align="right">{{number_format($c->balance_before,0,","," ")." ".$money}}</td>
                                                            <td nowrap align="right">{{number_format($c->balance_after,0,","," ")." ".$money}}</td>
                                                            <td nowrap><span class="badge bg-success"><i class="fa fa-check-circle"></i> Validated</span></td>
                                                            <td nowrap align="right" title="Demande validée le {{$c->date_validation}}">{{$c->date_validation}}</td>
{{--                                                            <td>{{$c->validatedBy!=null?$c->validatedBy->name." ".$c->validatedBy->surname:""}}</td>--}}
                                                        @else
                                                            <td></td>
                                                            <td></td>
                                                            <td><span class="badge bg-warning"><i class="fa fa-ban"></i> Rejected</span></td>
                                                            <td nowrap align="right" title="Demande rejetée le {{$c->date_reject}}">{{$c->date_reject}}</td>
{{--                                                            <td>{{$c->rejectedBy!=null?$c->rejectedBy->name." ".$c->rejectedBy->surname:""}}</td>--}}
                                                        @endif

                                                        <td align="center" nowrap>
                                                            <a type="button" class="btn" style="border: none; color: red"  data-bs-toggle="modal" data-bs-target="#staticBackdropDetailTopUp" onclick="javascript:getDetailApprovisionnement({{$c->id}},0)"><i class="fa fa-eye"></i></a>
                                                            @if(\Illuminate\Support\Facades\Auth::user()->type_user_id ==\App\Http\Enums\UserRolesEnum::FRONTOFFICE->value || \Illuminate\Support\Facades\Auth::user()->type_user_id ==\App\Http\Enums\UserRolesEnum::BACKOFFICE->value || \Illuminate\Support\Facades\Auth::user()->type_user_id ==\App\Http\Enums\UserRolesEnum::SUPADMIN->value)
                                                                @if($c->status==0)
{{--                                                                    <a href="{{route("CancelTopUpDistributeur",[$c->id])}}" type="button" id="CancelTopUpDistributeur{{$c->id}}" onclick="return confirm('Voulez-vous annuler cet approvisionnement?');" title="Annuler cet approvisionnement" class="btn" style="border: none; color: red"><i class="fa fa-trash"></i></a>--}}
                                                                    <a type="button" class="btn" style="border: none; color: red"  data-bs-toggle="modal" data-bs-target="#staticBackdropDetailTopUp" onclick="javascript:getDetailApprovisionnement({{$c->id}},1)"><i class="fa fa-trash"></i></a>
                                                                    @if(\Illuminate\Support\Facades\Auth::user()->type_user_id ==\App\Http\Enums\UserRolesEnum::BACKOFFICE->value || \Illuminate\Support\Facades\Auth::user()->type_user_id ==\App\Http\Enums\UserRolesEnum::SUPADMIN->value)
                                                                          <a type="button" class="btn" style="border: none; color: red" title="Cliquez pour valider"  data-bs-toggle="modal" data-bs-target="#staticBackdropDetailTopUp" onclick="javascript:getDetailApprovisionnement({{$c->id}},2)"><i class="fa fa-save"></i></a>
                                                                    @else
                                                                          <a class="btn" style="border: none; color: red"><i class="fa fa-save" style="color: darkgrey"></i></a>
                                                                    @endif
                                                                @else
                                                                  <a class="btn" style="border: none; color: red"><i class="fa fa-trash" style="color: darkgrey"></i></a>
                                                                    <a class="btn" style="border: none; color: red"><i class="fa fa-save" style="color: darkgrey"></i></a>
                                                                @endif
                                                            @endif
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
                                                @isset($listApprovisionnement)
                                                    @if($listApprovisionnement->isNotEmpty())
                                                        <div style="text-align: center"><span class="badge bg-success"><i class="fa fa-check-circle"></i></span> {{"Traitées = ".number_format($listApprovisionnement->where("status",1)->count(),0,',',' ')." / Montant = ".number_format($listApprovisionnement->where("status",1)->sum("amount"),0,","," ")." ".$money}} <span class="badge bg-primary"><i class="fa fa-clock"></i></span> {{"En cours = ".number_format($listApprovisionnement->where("status",0)->count(),0,',',' ')." / Montant = ".number_format($listApprovisionnement->where("status",0)->sum("amount"),0,","," ")." ".$money}}</div>
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
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Relevé de compte</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 col-lg-12">
                                <div class="card-header stat-widget card-shadow-danger border-danger">
                                    <div class="widget-chat-wrapper-outer">
                                        <div class="widget-chart-content">
                                            <form action="" id="frmOperation" name="frmOperation" method="post">
                                                @csrf
                                                <div class="row">
                                                    <div class="col-md-12 col-lg-4">
                                                        <div class="form-group">
                                                            <label class="form-label"  for="startDate2">Date début</label>
                                                            <input type="date" name="startDate2" id="startDate2" class="form-control" placeholder="StartDate"
                                                                   @if(isset($startDate))
                                                                   value="{{$startDate}}"
                                                                   @else
                                                                   value="<?php echo date('Y-m-01'); ?>"
                                                                @endif>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-12 col-lg-4">
                                                        <div class="form-group">
                                                            <label class="form-label" for="endDate2">Date fin</label>
                                                            <input type="date" name="endDate2" id="endDate2" class="form-control" placeholder="EndDate" @if(isset($endDate))
                                                            value="{{$endDate}}"
                                                                   @else
                                                                   value="<?php echo date('Y-m-d'); ?>"
                                                                @endif>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-12 col-lg-4">
                                                        <div class="input-icon">
                                                            <label class="form-label"  for="btnSearch2">&nbsp;</label>

                                                                <button type="button" title="Search" id="btnSearch2" onclick="" class="btn btn-outline-danger form-control"><i class="fa fa-search"></i></button>

                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                               </div>
                        </div>
                    </div><p/>
                    <div class="card table-widget">
                      <div class="table-responsive">
                         <table id="add-row" class="table table-hover table-striped table-bordered dataTable dtr-inline" role="grid">
                            <thead>
                                <tr>
                                     <th>Date Heure</th>
                                     <th>Opération</th>
                                     <th>Débit</th>
                                     <th>Crédit</th>
                                     <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                @isset($listOperation)
                                    @if($listOperation->isNotEmpty())
                                        @foreach($listOperation as $c)
                                            <tr>
                                                <td align="center">{{$c->date_transaction}}</td>
                                                <td>{{$c->name_service}}</td>
                                                <td align="right">{{$c->debit==0?"":number_format($c->debit,0,","," ")." ".$money}}</td>
                                                <td align="right">{{$c->credit==0?"":number_format($c->credit,0,","," ")." ".$money}}</td>
                                                <td align="center">{{$c->customer_phone}}</td>
                                            </tr>
                                        @endforeach
                                    @endif
                                @endisset
                            </tbody>
                        </table>
                    </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
    @if(\Illuminate\Support\Facades\Auth::user()->type_user_id==\App\Http\Enums\UserRolesEnum::FRONTOFFICE->value || \Illuminate\Support\Facades\Auth::user()->type_user_id==\App\Http\Enums\UserRolesEnum::SUPADMIN->value)
    <div class="modal fade" id="staticBackdropAppro" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Approvisionner un distributeur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('approDistributeurInit') }}" id="frmRecharge" name="frmRecharge" method="post">
                    @csrf
                    <div class="modal-body">
                        <div class="widget-chart-content">
                            <div class="row">
                                <div class="col-md-12 col-lg-12">
                                    <div class="input-group mb-12">
                                        <span class="input-group-text" id="basic-addon1"><i class="fa fa-handshake"></i></span>
                                        <select class="form-select" name="distributeur" id="distributeur" onchange="" required onclick="getdetailDistributeurTopUpd(this.value)">
                                            @isset($listdistributeurs)
                                                @if($listdistributeurs)
                                                    <option  value="" >Sélectionner un distributeur</option>
                                                    @foreach($listdistributeurs as $s)
                                                        <option  value="{{ $s->id }}" >{{$s->name_distributeur}}</option>
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
    @endif
    <div class="modal fade" id="staticBackdropDetailTopUp" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">

            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div id="detailTopUp">
                        <div class="modal-header">
                            <h5 class="modal-title" id="staticBackdropLabel">Détail recharge distributeur</h5>
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
    @if(\Illuminate\Support\Facades\Auth::user()->type_user_id==\App\Http\Enums\UserRolesEnum::BACKOFFICE->value || \Illuminate\Support\Facades\Auth::user()->type_user_id==\App\Http\Enums\UserRolesEnum::SUPADMIN->value)
    <div class="modal fade" id="staticBackdropSolde" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Etat des soldes des distributeurs</h5>
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
                                            <th>Distributeur</th>
                                            <th>Solde</th>
                                            <th>Seuil alerte</th>
                                            <th nowrap>Date last transaction</th>

                                        </tr>
                                        </thead>
                                        <tbody>
                                        @isset($listdistributeurs)
                                            @if($listdistributeurs->isNotEmpty())
                                                @foreach($listdistributeurs as $s)
                                                    <tr>
                                                        <td>{{$loop->iteration}}</td>
                                                        <th>{{$s->name_distributeur}}</th>

                                                        <td nowrap align="right" nowrap>{{number_format($s->balance_after,0,","," ")." ".$money}}</td>
                                                        @if($s->balance_after < $s->plafond_alerte)
                                                            <td nowrap align="right" style="color: red" nowrap>{{number_format($s->plafond_alerte,0,","," ")." ".$money}}</td>
                                                        @else
                                                            <td nowrap align="right" style="color: black" nowrap>{{number_format($s->plafond_alerte,0,","," ")." ".$money}}</td>
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
    @endif
    <script language="javascript">
    function getdetailDistributeurTopUpd(id) {
        //  $("#detailAgentTopUpd").html("");
        $.ajax({
            url: "/approvisionnement/distributeur/edit/"+id,
            type: "GET",
            success: function (data) {
                $("#detailAgentTopUpd").html(data);
            }
        });
    }

    function getDetailApprovisionnement(id, action) {
        $.ajax({
            url: "/approvisionnement/distributeur/topup/"+id+"/"+action,
            type: "GET",
            success: function (data) {
                $("#detailTopUp").html(data);
            }
        });
    }
</script>
@endsection
