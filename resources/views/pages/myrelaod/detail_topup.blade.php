@isset($approvisionnement)

            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Détail approvisionnement - N°{{$approvisionnement->reference}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="#" id="frmTransaction" name="frmTransaction">
                @csrf

                <div class="modal-body">
                    <div class="card card-header">
                        <table width="100%"  cellspacing="5" cellpadding="5">
                            <tr><td colspan="4" align="center"><h3><i class="fa fa-handshake"></i> {{$approvisionnement->distributeur->name_distributeur}}</h3></td></tr>
                            <tr><td nowrap >Référence : </td><td colspan="3" nowrap><span style="text-decoration-line: underline; display: flex">{{$approvisionnement->reference}}</span></td></tr>
                            <tr><td nowrap >Date initiation : </td><td nowrap><span style="text-decoration-line: underline; display: flex">{{$approvisionnement->date_operation}}</span></td><td nowrap>Statut : </td>
                                @if($approvisionnement->status==0)
                                    <td><span class="badge bg-primary"><i class="fa fa-clock"></i> In progress</span></td>
                                 @elseif($approvisionnement->status==1)
                                    <td nowrap><span class="badge bg-success"><i class="fa fa-check-circle"></i> Validated</span></td>
                                @else
                                    <td><span class="badge bg-warning"><i class="fa fa-ban"></i> Rejected</span></td>
                                @endif
                            </tr>

                            <tr><td nowrap>Montant : </td><td nowrap>{{number_format($approvisionnement->amount,0,","," ")." ".$money}}</td>
                                <td nowrap>Opérateur : </td><td nowrap><span style="text-decoration-line: underline; display: flex">{{$approvisionnement->createdBy->name." ".$approvisionnement->createdBy->surname}}</span></td></tr>
                            @if($approvisionnement->status==1)
                            <tr><td nowrap>Solde avant : </td><td nowrap><span style="text-decoration-line: underline; display: flex">{{number_format($approvisionnement->balance_before,0,","," ")." ".$money}}</span></td><td nowrap>Solde après : </td><td nowrap><span style="text-decoration-line: underline; display: flex">{{number_format($approvisionnement->balance_after,0,","," ")." ".$money}}</span></td></tr>
                            @endif
                        </table>
                    </div>
                </div>

                <div class="modal-footer">
                    @if(\Illuminate\Support\Facades\Auth::user()->type_user_id ==\App\Http\Enums\UserRolesEnum::BACKOFFICE->value || \Illuminate\Support\Facades\Auth::user()->type_user_id ==\App\Http\Enums\UserRolesEnum::SUPADMIN->value)
                        @if($approvisionnement->status==0)
                            @if($action==1)
                                <a href="{{route("CancelTopUpDistributeur",[$approvisionnement->id])}}" type="button" class="btn btn-outline-warning" title="Cliquez pour rejeter la demande" >Rejeter</a>
                            @endif
                            @if($action==2)
                                <a href="{{route("validateTopUpDistributeur",[$approvisionnement->reference])}}" type="button" class="btn btn-outline-success" title="Cliquez pour valider la demande" >Valider</a>
                            @endif
                        @endif
                    @endif
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Fermer</button>
                </div>

            </form>

@endisset
