@isset($detailOperation)


            <div class="modal-header">
                <h5 class="modal-title">REMBOURSEMENT - N°{{$detailOperation->count()>0?$detailOperation->first()->ref_remb_com_distributeur:""}} {{" - DISTRIBUTEUR : ".$detailOperation->first()->name_distributeur}}</h5>

            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="add-row"  class="table table-hover table-striped table-bordered dataTable dtr-inline" role="grid" aria-sort="false">
                        <thead>
                        <tr>
                            <th scope="col" nowrap>#</th>
                            <th scope="col" nowrap>Date</th>
                            <th scope="col" nowrap>Référence</th>
                            <th scope="col" nowrap>Service</th>
                            <th scope="col" nowrap>Montant</th>
                            <th scope="col" nowrap>Commission distributeur</th>
                        </tr>
                        </thead>
                        <tbody>

                        @isset($detailOperation)
                            @if($detailOperation->isNotEmpty())
                                @foreach($detailOperation as $c)
                                    <tr>
                                        <td align="right">{{$loop->iteration}}</td>
                                        <td nowrap align="center">{{$c->date_transaction}}</td>
                                        <td nowrap title="Référence partenaire : {{$c->reference_partenaire}}">#{{$c->reference}}</td>
                                         <td align="left" nowrap>{{$c->name_service}}</td>
                                        <td nowrap style="color: black" align="right" nowrap>{{number_format($c->credit==0?$c->debit:$c->credit,0,","," ")." ".$money}}</td>
                                        <td nowrap align="right" nowrap>{{number_format($c->commission_distributeur,0,","," ")." ".$money}}</td>

                                    </tr>
                                @endforeach
                            @endif
                        @endisset

                        </tbody>
                    </table>
                    <table id="add-row"  class="table table-hover table-striped table-bordered dataTable dtr-inline" role="grid" aria-sort="false">
                        <tr>
                            <td width="100%">
                                @isset($detailOperation)
                                    @if($detailOperation->isNotEmpty())
                                        <div style="text-align: center">{{"Nombre de transactions = ".number_format($detailOperation->count(),0,',',' ')." / Commission Agent = ".number_format($detailOperation->sum("commission_agent"),0,","," ")." ".$money." / Commission Distributeur = ".number_format($detailOperation->sum("commission_distributeur"),0,","," ")." ".$money}} </div>
                                    @endif
                                @endisset
                            </td>
                        </tr>
                    </table>
                </div>
            </div>



@endisset
