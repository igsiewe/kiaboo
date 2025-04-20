@isset($transactions)


            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Détail recharge agent - N°{{$transactions->reference}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="#" id="frmTransaction" name="frmTransaction">
                @csrf

                <div class="modal-body">
                    <div class="card card-header">
                        <table width="100%"  cellspacing="5" cellpadding="5">
                            <tr><td colspan="4"><img src="{{asset("assets/partenaires/".$transactions->logo_service)}}" height="50px" width="50px" alt="img"></td></tr>
                            <tr><td nowrap >Référence : </td><td colspan="3" nowrap><span style="text-decoration-line: underline; display: flex">{{$transactions->reference}}</span></td></tr>
                            <tr><td nowrap >Date heure : </td><td nowrap><span style="text-decoration-line: underline; display: flex">{{$transactions->date_transaction}}</span></td><td nowrap>Statut : </td><td nowrap><span class="badge bg-success">{{$transactions->description}}</span></td></tr>
                            <tr><td nowrap>Service : </td><td nowrap><span style="text-decoration-line: underline; display: flex">{{$transactions->name_service}}</span></td><td nowrap>Agent : </td><td nowrap><span style="text-decoration-line: underline; display: flex">{{$transactions->name_agent." ".$transactions->surname_agent."(".$transactions->ref_agent.")"}}</span></td></tr>
                            <tr><td nowrap>Montant : </td><td nowrap>
                                    @if($transactions->debit==0)
                                        <span style="text-decoration-line: underline; display: flex">{{number_format($transactions->credit,0,',',' ')." ".$money}}</span>
                                    @else
                                        <span style="text-decoration-line: underline; display: flex">{{number_format($transactions->debit,0,',',' ')." ".$money}}</span>
                                    @endif
                                </td>
                                <td nowrap>Opérateur : </td><td nowrap><span style="text-decoration-line: underline; display: flex">{{$transactions->name_operateur." ".$transactions->surname_operateur}}</span></td></tr>
                            <tr><td nowrap>Solde avant : </td><td nowrap><span style="text-decoration-line: underline; display: flex">{{number_format($transactions->balance_before_partenaire,0,","," ")." ".$money}}</span></td><td nowrap>Solde après : </td><td nowrap><span style="text-decoration-line: underline; display: flex">{{number_format($transactions->balance_after_partenaire,0,","," ")." ".$money}}</span></td></tr>
                             </table>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Fermer</button>

                </div>

            </form>


@endisset
