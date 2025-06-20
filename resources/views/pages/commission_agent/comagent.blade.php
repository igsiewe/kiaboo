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
                            <i class="fa fa-gift fa-2x"></i>
                            </span>
                        <span class="d-inline-block"><h4><b>&nbsp;&nbsp;COMMISSIONS PERCUES PAR LES AGENTS</b></h4></span>
                    </div>

                </div>
            </div><p/>
            <div class="row">
                <div class="col-md-12 col-lg-12">
                    <div class="card-header stat-widget card-shadow-danger border-danger">
                        <div class="widget-chat-wrapper-outer">
                            <div class="widget-chart-content">
                                <form action="{{route("listAgentCommissions.search")}}" id="frmFiltre" name="frmFiltre" method="post">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-12 col-lg-3">
                                            <div class="form-group">
                                                <label class="form-label" for="distributeur" >Distributeur</label>
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
                                                    <button type="button" title="filter" id="btnSearch" onclick="javascript:checkDate();" class="btn btn-outline-danger form-control"><i class="fa fa-search"></i></button>
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
                            <span><h6><i class='icon fa fa-ban'></i>{{$errors->first()}}</h6></span>
                        </div>
                    </div>
                </div>
            @endif
            @if(session('error'))
                <div class="row">
                    <div class="col-12 col-sm-12">
                        <div class='alert alert-danger alert-dismissible'>
                            <button type='button' class='close' data-bs-dismiss='alert' aria-hidden='true' title="Close">×</button>
                            <span><h6><i class='icon fa fa-ban'></i>{{session('error')}}</h6></span>
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
                                    @if(Auth::user()->type_user_id !=\App\Http\Enums\UserRolesEnum::AGENT->value)
                                    <button type="button" title="Commissions à percevoir par les agents" class="btn btn-kiaboo" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
                                        <i class="fa fa-hands-helping"></i>  Commissions à percevoir par les agents
                                    </button>
                                    @endif
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
                                            <th scope="col" nowrap>Référence</th>
                                            <th scope="col" nowrap>Agent</th>
                                            <th scope="col" nowrap>Balance Before</th>
                                            <th scope="col" nowrap>Commission</th>
                                            <th scope="col" nowrap>Balance After</th>
                                            @if(\Illuminate\Support\Facades\Auth::user()->type_user_id !=\App\Http\Enums\UserRolesEnum::DISTRIBUTEUR->value)
                                                <th scope="col" nowrap>Distributeur</th>
                                            @endif
                                            <th scope="col"></th>
                                        </tr>
                                        </thead>
                                        <tbody>

                                        @isset($commissions_agent)
                                            @if($commissions_agent->isNotEmpty())
                                                @foreach($commissions_agent as $c)
                                                    <tr>
                                                        <td align="right">{{$loop->iteration}}</td>
                                                        <td nowrap align="center">{{$c->date_transaction}}</td>
                                                        <td nowrap>#{{$c->reference}}</td>
                                                        <td nowrap align="left" title="{{$c->name." ".$c->surname}}">{{$c->telephone." | ".$c->name." ".$c->surname}}</td>
                                                        <td align="right" nowrap>{{number_format($c->balance_before,0,","," ")." ".$money}}</td>
                                                        <td nowrap style="color: black" align="right" nowrap>{{number_format($c->credit,0,","," ")." ".$money}}</td>

                                                        <td nowrap align="right" nowrap>{{number_format($c->balance_after,0,","," ")." ".$money}}</td>
                                                        @if(\Illuminate\Support\Facades\Auth::user()->type_user_id !=\App\Http\Enums\UserRolesEnum::DISTRIBUTEUR->value)
                                                            <td nowrap>{{$c->name_distributeur}}</td>
                                                        @endif

                                                        <td align="center" nowrap>
                                                            <a type="button" class="btn" style="border: none; color: red"  data-bs-toggle="modal" data-bs-target="#staticBackdropDetail" onclick="getdetailCommission({{"'".$c->reference."'"}})">
                                                                <i class="fa fa-list"></i>
                                                            </a>
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
                                                @isset($commissions_agent)
                                                    @if($commissions_agent->isNotEmpty())
                                                        <div style="text-align: center">{{"Nombre de demandes = ".number_format($commissions_agent->count(),0,',',' ')." / Commission totale = ".number_format($commissions_agent->sum("credit"),0,","," ")." ".$money}} </div>
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
    @if(Auth::user()->type_user_id !=\App\Http\Enums\UserRolesEnum::AGENT->value){
    <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Commissions à percevoir par les agents</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table id="add-row"  class="table table-hover table-striped table-bordered dataTable dtr-inline" role="grid" aria-sort="false">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Référence</th>
                            <th>Agents</th>
                            <th>Volume transactions</th>
                            <th>Commissions à percevoir</th>
                        </tr>
                        </thead>
                        <tbody>

                        @isset($commission_non_percue)
                            @if($commission_non_percue->isNotEmpty())
                                @foreach($commission_non_percue as $com)
                                    <tr>
                                        <td align="right">#{{$loop->iteration}}</td>
                                        <td align="center">{{$com->reference}}</td>
                                        <td align="left">{{$com->name." ".$com->surname}}</td>
                                        <td align="right">{{number_format($com->volume,0,","," ")." ".$money}}</td>
                                        <td align="right">{{number_format($com->commission,0,","," ")." ".$money}}</td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td colspan="3"></td>
                                    <td align="right">{{number_format($commission_non_percue->sum("volume"),0,","," ")." ".$money}}</td>
                                    <td align="right">{{number_format($commission_non_percue->sum("commission"),0,","," ")." ".$money}}</td>
                                </tr>
                            @endif
                        @endif
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    @isset($commission)
                        @if($commission->isNotEmpty())
                            <a href="{{route("setRemboursementCommissionDistributeur")}}" type="button" title="Cliquez pour collecter vos commissions" class="btn btn-outline-success">Collecter</a>
                        @endif
                    @endif
                    <button type="button" title="Cliquez pour fermer" class="btn btn-outline-danger" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
    @endif
    <div class="modal fade" id="staticBackdropDetail" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Détails des opérations</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="detailTopUp">
                            <div class='loader'>
                                <div class='spinner-grow text-primary' role='status'>
                                    <span class='sr-only'>Loading...</span>
                                </div>
                            </div>
                    </div>
                </div>
                 <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Fermer</button>
                 </div>
            </div>
        </div>
    </div>

    <script>
        function getdetailCommission(reference) {
            $("#detailTopUp").html("");
            $.ajax({
                url: "/commissions/percues/agent/detail/"+reference,
                type: "GET",
                success: function (data) {
                    $("#detailTopUp").html(data);
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


    </script>
@endsection
