
@isset($editProspect)

    <form action="{{route("editProspect",[$editProspect->id])}}" id="frmUpdateProspect" name="frmUpdateProspect">
        @csrf
            <div class="modal-body">
                <div class='loader'>
                    <div class='spinner-grow text-primary' role='status'>
                        <span class='sr-only'>Loading...</span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 col-lg-8">
                        <div class="row">
                            <div class="col-md-12 col-lg-6">
                                <label class="form-label">Nom *</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-user"></i></span>
                                    <input type="text" id="name" name="name" disabled class="form-control" placeholder="Nom *" aria-label="Name" value="{{$editProspect->name}}" required>
                                </div>
                            </div>
                            <div class="col-md-12 col-lg-6">
                                <label class="form-label">Prénom *</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-user"></i></span>
                                    <input type="text" id="surname" name="surname" disabled class="form-control" value="{{$editProspect->surname}}" placeholder="Prénom *" aria-label="surname" required>
                                </div>
                            </div>
                        </div>
                        <p></p>
                        <div class="row">
                            <div class="col-md-12 col-lg-6">
                                <label class="form-label">Téléphone *</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-phone"></i></span>
                                    <input type="texte" id="telephone" readonly="true" disabled name="telephone" class="form-control" placeholder="Téléphone *" value="{{$editProspect->phone}}" aria-label="Téléphone" required>
                                </div>
                            </div>
                            <div class="col-md-12 col-lg-6">
                                <label class="form-label">Email *</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                                    <input type="text" id="email" name="email" disabled  class="form-control" placeholder="Email *" aria-label="email"  value="{{$editProspect->email}}"required>
                                </div>
                            </div>
                        </div>
                        <p></p>
                        <div class="row">
                            <div class="col-md-12 col-lg-6">
                                <label class="form-label">Type pièce</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-id-card"></i></span>
                                    <input type="texte" id="typepiece"  name="typepiece" disabled class="form-control" placeholder="Type pièce" value="{{$editProspect->type_piece}}" aria-label="Type pièce" required>
                                </div>
                            </div>
                            <div class="col-md-12 col-lg-6">
                                <label class="form-label">Numéro pièce</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-id-card"></i></span>
                                    <input type="texte" id="numcni"  name="numcni" disabled class="form-control" placeholder="Numéro CNI *" value="{{$editProspect->numero_piece}}" aria-label="Numéro pièce" required>
                                </div>
                            </div>
                        </div>
                        <p></p>
                        <div class="row">
                            <div class="col-md-12 col-lg-6">
                                <label class="form-label">Date validité</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                                    <input type="date" id="dateValidite"  name="dateValidite" disabled class="form-control" placeholder="Date validité" value="{{$editProspect->date_validite}}" aria-label="Date validité" required>
                                </div>
                            </div>
                            <div class="col-md-12 col-lg-6">
                                <label class="form-label">Ville édition</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-home"></i></span>
                                    <input type="text" id="villePiece"  name="villePiece" disabled class="form-control" placeholder="Ville pièce" value="{{$editProspect->ville_piece->name_ville}}" aria-label="Ville édition" required>
                                </div>
                            </div>
                        </div>
                        <p></p>
                        <div class="row">
                            <div class="col-md-12 col-lg-6">
                                <label class="form-label">Ville</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-home"></i></span>
                                    <input type="text" id="quartier" name="quartier" disabled class="form-control" value="{{$editProspect->quartier->ville->name_ville}}" placeholder="Ville" aria-label="Ville" required>
                                </div>
                            </div>

                            <div class="col-md-12 col-lg-6">
                                <label class="form-label">Quartier</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-city"></i></span>
                                    <input type="text" id="quartier" name="quartier" disabled class="form-control" value="{{$editProspect->quartier->name_quartier}}" placeholder="Quartier" aria-label="Quartier" required>
                                </div>
                            </div>
                        </div>
                        <p></p>
                        <div class="row">
                            <div class="col-md-12 col-lg-12">
                                <label class="form-label">Adresse</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-address-card"></i></span>
                                    <input type="text" id="adresse" name="adresse" disabled maxlength="150" class="form-control" placeholder="Adresse" aria-label="Adresse" required value="{{$editProspect->adresse}}">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 col-lg-4" style="align-content: center">
                        <div class="row">
                            <img src="data:image/png;base64,{{$editProspect->photo_recto}}" alt="Pièce recto" width="150px" height="220px">
                        </div>
                         <p></p>
                        <div class="row">
                            <img src="data:image/png;base64,{{$editProspect->photo_verso}}" alt="Pièce verso" width="150px" height="220px">
                        </div>
                    </div>
                </div>
                <p></p>
    </form>
            <div class="modal-footer">
                @if($editProspect->status==0)
                    <form action="{{route("valideProspect",[$editProspect->id])}}" id="frmValidateProspect" name="frmValidateProspect">
                        <button type="button" class="btn btn-primary" id="1" data-bs-toggle="modal" data-bs-target="#confirmationModal">
                            Valider
                        </button>
                    </form>

                    <form action="{{route("rejectedProspect",[$editProspect->id])}}" id="frmRejeteProspect" name="frmRejeteProspect">

                        <button type="button" class="btn btn-danger" id="2" data-bs-toggle="modal" data-bs-target="#rejetModal">
                            Rejeter
                        </button>
                    </form>
                @endif
                <button type="button" class="btn btn-outline-danger" id="7" data-bs-dismiss="modal">Fermer</button>
            </div>


    <div class="modal fade" id="confirmationModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-2" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: grey">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    Voulez-vous valider le passage de ce prospect en agent ?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="3" data-bs-dismiss="modal">Non</button>
                    <button type="button" class="btn btn-success" id="4" onclick="validerFormulaire()">Oui</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rejetModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-3" aria-labelledby="rejetModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: grey">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejetModalLabel">Rejet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    Voulez-vous valider le passage de ce prospect en agent ?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="5" data-bs-dismiss="modal">Non</button>
                    <button type="button" class="btn btn-success" id="6" onclick="rejeterFormulaire()">Oui</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function validerFormulaire() {
            // Fermer la modale
            const modal = bootstrap.Modal.getInstance(document.getElementById('confirmationModal'));
            modal.hide();

            // Soumettre le formulaire
            document.getElementById('frmValidateProspect').submit();
        }

        function rejeterFormulaire() {
            // Fermer la modale
            const modal = bootstrap.Modal.getInstance(document.getElementById('confirmationModal'));
            modal.hide();

            // Soumettre le formulaire
            document.getElementById('frmRejeteProspect').submit();
        }
    </script>

@endisset
