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
                            <i class="fa fa-list fa-2x"></i>
                            </span>
                        <span class="d-inline-block"><h4><b>&nbsp;&nbsp;TRANSACTTIONS HISTORY</b></h4></span>
                    </div>
                </div>
            </div><p/>

            <div class="row">
                <div class="col-md-12 col-lg-12">
                    <div class="card-header stat-widget card-shadow-danger border-danger">
                        <div class="widget-chat-wrapper-outer">
                            <div class="widget-chart-content">
                                <form action="{{route("listTransactions.filtre")}}" id="frmFiltre" name="frmFiltre" method="post">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-12 col-lg-2">
                                            <div class="form-group">
                                                <label class="form-label" for="partenaire">Partenaires</label>

                                                    <select class="form-select"  id="partenaire" name="partenaire" onchange="javascript:getService(this.value)">
                                                        <option value="">Tous les partenaires</option>

                                                        @if($listpartenaires)
                                                            @foreach($listpartenaires as $s)
                                                                @if(isset($partenaire))
                                                                    @if($s->id == $partenaire)
                                                                        <option  value="{{ $s->id }}" selected >{{$s->name_partenaire}}</option>
                                                                    @else
                                                                        <option  value="{{ $s->id }}" >{{$s->name_partenaire}}</option>
                                                                    @endif
                                                                @else
                                                                    <option  value="{{ $s->id }}" >{{$s->name_partenaire}}</option>
                                                                @endif
                                                            @endforeach
                                                        @endif
                                                    </select>
                                            </div>
                                        </div>

                                        <div class="col-md-12 col-lg-2">
                                            <div class="form-group">
                                                <label class="form-label" for="service">Services</label>
                                                <div id="services">
                                                    <select class="form-select"  id="service" name="service" onchange="">
                                                        <option value="">Tous les services</option>
                                                        @if($listservices)
                                                            @foreach($listservices as $s)
                                                                @if(isset($service))
                                                                    @if($s->id == $service)
                                                                        <option  value="{{ $s->id }}" selected >{{$s->name_service}}</option>
                                                                    @else
                                                                        <option  value="{{ $s->id }}" >{{$s->name_service}}</option>
                                                                    @endif
                                                                @else
                                                                    <option  value="{{ $s->id }}" >{{$s->name_service}}</option>
                                                                @endif
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6 col-lg-2">
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
                                        <div class="col-md-6 col-lg-2">
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
                                            <div class="form-group">
                                                <label class="form-label" for="agent">Agent</label>
                                                <select class="form-select" name="agent" id="agent" onchange="">
                                                    @isset($listagents)
                                                        @if($listagents)
                                                            <option  value="" >Tous les agents</option>
                                                            @foreach($listagents as $s)
                                                                @if(isset($agent))
                                                                    @if(Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::DISTRIBUTEUR->value)
                                                                        @if($s->id == $agent)
                                                                            <option  value="{{ $s->id }}" selected >{{$s->telephone." | ".$s->name." ".$s->surname}}</option>
                                                                        @else
                                                                            <option  value="{{ $s->id }}" >{{$s->telephone." | ".$s->name." ".$s->surname}}</option>
                                                                        @endif
                                                                    @else
                                                                        @if($s->id == $agent)
                                                                            <option  value="{{ $s->id }}" selected >{{$s->telephone." | ".$s->name." ".$s->surname." | ".$s->distributeur->name_distributeur}}</option>
                                                                        @else
                                                                            <option  value="{{ $s->id }}" >{{$s->telephone." | ".$s->name." ".$s->surname." | ".$s->distributeur->name_distributeur}}</option>
                                                                        @endif
                                                                    @endif
                                                                @else
                                                                    <option  value="{{ $s->id }}" >{{$s->telephone." | ".$s->name." ".$s->surname." | ".$s->distributeur->name_distributeur}}</option>
                                                                @endif
                                                            @endforeach
                                                        @endif
                                                    @endisset
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-12 col-lg-2">
                                            <div class="input-icon">
                                                <label class="form-label" for="btnSearch">&nbsp;</label>
                                                <div class="btn-list">
                                                    <button type="button" id="btnSearch" title="Cliquez pour filitrer" onclick="javascript:checkDate();" class="btn btn-outline-danger form-control"><i class="fa fa-search"></i></button>
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
                        <div class='alert alert-danger alert-dismissable'>
                            <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
                            <span>{{$errors->first()}}</span>
                        </div>
                    </div>
                </div>
                <br/>
            @endif
            <div class="row">
                <div class="col-md-12 col-lg-12">
                    <div class="card-header">
                        <div class="card-group">
                            <div class="btn-actions-pane-right">
                                <div role="group" class="btn-group-sm btn-group-lg">
                                    <form action="{{route("export.transactions")}}" method="GET" name="exportform" enctype="multipart/form-data">
                                        @csrf
                                        <button type="submit" class="btn btn-danger" title="Exporter"><i class="fa fa-download"></i>  Tout exporter </button>
                                        <input type="hidden" name="excelFiltre" id="excelFiltre" @if(isset($excelFiltre)) value="{{$excelFiltre}}" @else value="0" @endif>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-header">
                        <div class="card table-widget">
                            <div class="card-body">
                                <div class="table-responsive">
                                        <table id="add-row" class="table table-hover table-striped table-bordered dataTable dtr-inline" role="grid">
                                            <thead>
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">Date</th>
{{--                                                <th scope="col">TransactionID</th>--}}
                                                <th scope="col" title="Partner transaction ID">PartnerTransID</th>
                                                <th scope="col">Service</th>
                                                <th scope="col">Amount</th>
                                                <th scope="col">Balance After</th>
                                                <th scope="col">Customer</th>
                                                <th scope="col">Agent</th>
{{--                                                @if(\Illuminate\Support\Facades\Auth::user()->type_user_id != \App\Http\Enums\UserRolesEnum::DISTRIBUTEUR->value)--}}
{{--                                                    <th scope="col">Distributeur</th>--}}
{{--                                                @endif--}}
                                                <th scope="col" class="sorting_asc_disabled sorting_desc_disabled"></th>
                                            </tr>
                                            </thead>
                                            <tbody>

                                            @isset($transactions)
                                                @if($transactions->isNotEmpty())
                                                    @foreach($transactions as $c)
                                                        <tr>
                                                            <td align="right">{{$loop->iteration}}</td>
                                                            <td align="center">{{$c->date_transaction}}</td>
{{--                                                            <td>#{{$c->reference}}</td>--}}
                                                            <td>#{{$c->reference_partenaire}}</td>
                                                            <th scope="row"><img src="{{asset("assets/partenaires/".$c->service->logo_service)}}" alt="img">{{$c->service->name_service}}</th>
                                                            @if($c->debit==0)
                                                                <td nowrap style="color: black" align="right">{{number_format($c->credit,0,',',' ')." ".$money}}</span></td>
                                                            @else
                                                                <td nowrap style="color: red" align="right">{{number_format($c->debit,0,',',' ')." ".$money}}</span></td>
                                                            @endif
                                                            <td align="right">{{number_format($c->balance_after,0,',',' ')." ".$money}}</td>
                                                            <td align="center">{{substr($c->customer_phone, 0, 3) . '***' . substr($c->customer_phone, -3)}}</td>

                                                            <td>{{$c->auteur->telephone}}</td>
{{--                                                            @if(\Illuminate\Support\Facades\Auth::user()->type_user_id != \App\Http\Enums\UserRolesEnum::DISTRIBUTEUR->value)--}}
{{--                                                            <td>{{$c->auteur->distributeur->name_distributeur}}</td>--}}
{{--                                                            @endif--}}
                                                            <td align="center">
                                                                <button type="button" class="btn" style="border: none; color: red" title="Afficher le détail" data-bs-toggle="modal" data-bs-target="#staticBackdrop" onclick="getDetailTransaction({{$c->id}})">
                                                                    <i class="fa fa-eye"></i>
                                                                </button>
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
                                                            <div style="text-align: center">{{"Envoi = ".number_format($transactions->sum("debit"),0,',',' ')." ".$money." / Retrait = ".number_format($transactions->sum("credit"),0,',',' ')." ".$money." / Nombre de transactions = ".number_format($transactions->count(),0,',',' ')." / Commission distributeur = ".number_format($transactions->sum("commission_distributeur"),0,',',' ')." ".$money}} </div>
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

            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div id="detailTransactions">
                    <div class="modal-header">
                        <h5 class="modal-title" id="staticBackdropLabel">Détail de la transaction N°</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="#" id="frmTransaction" name="frmTransaction">
                        @csrf
                        <div class="modal-body">
                           <table>
                               <tr><td nowrap>Date heure : </td><td nowrap></td><td nowrap>Statut : </td><td nowrap></td></tr>
                               <tr><td nowrap>Service : </td><td nowrap></td><td nowrap>Agent : </td><td nowrap></td></tr>
                               <tr><td nowrap>Montant : </td><td nowrap></td><td nowrap>Client : </td><td nowrap></td></tr>
                               <tr><td nowrap>Solde avant : </td><td nowrap></td><td nowrap>Solde après : </td><td nowrap></td></tr>
                               <tr><td nowrap>Commission Distributeur : </td><td></td><td nowrap>Commission Agent : </td><td nowrap></td></tr>
                           </table>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Fermer</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
    <script language="javascript" type="text/javascript">

        function getDetailTransaction(id) {
            $("#detailTransactions").html("");
            $.ajax({
                url: "/transactions/transaction/edit/"+id,
                type: "GET",
                success: function (data) {
                    $("#detailTransactions").html(data);
                }
            });
        }

        function getService(idPartenaire) {
            document.getElementById("service").options.length=0;
            $.ajax({
                url: "/services/partenaire/"+idPartenaire,
                type: "GET",
                success: function (data) {
                    $("#services").html(data);
                }
            });
        }



    </script>

@endsection
