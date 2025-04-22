@isset($editProspect)
    {{$editProspect}}
    <form action="{{route("editProspect",[$editProspect->id])}}" id="frmUpdateProspect" name="frmUpdateProspect">
        @csrf
        <div class="modal-body">
            <div class='loader'>
                <div class='spinner-grow text-primary' role='status'>
                    <span class='sr-only'>Loading...</span>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 col-lg-6">
                    <label class="form-label">Nom *</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-user"></i></span>
                        <input type="text" id="name" name="name" class="form-control" placeholder="Nom *" aria-label="Name" value="{{$editProspect->name}}" required>
                    </div>
                </div>
                <div class="col-md-12 col-lg-6">
                    <label class="form-label">Prénom *</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-user"></i></span>
                        <input type="text" id="surname" name="surname" class="form-control" value="{{$editProspect->surname}}" placeholder="Prénom *" aria-label="surname" required>
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
                        <input type="text" id="email" name="email" class="form-control" placeholder="Email *" aria-label="email"  value="{{$editProspect->email}}"required>
                    </div>
                </div>
            </div>
            <p></p>
            <div class="row">
                <div class="col-md-12 col-lg-6">
                    <label class="form-label">Type pièce</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-id-card"></i></span>
                        <input type="texte" id="typepiece"  name="typepiece" class="form-control" placeholder="Type pièce value="{{$editProspect->type_piece}}" aria-label="Type pièce" required>
                    </div>
                </div>
                <div class="col-md-12 col-lg-6">
                    <label class="form-label">Numéro pièce</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-id-card"></i></span>
                        <input type="texte" id="numcni"  name="numcni" class="form-control" placeholder="Numéro CNI *" value="{{$editProspect->numero_piece}}" aria-label="Numéro pièce" required>
                    </div>
                </div>
                <div class="col-md-12 col-lg-6">
                    <label class="form-label">Date validité</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-id-card"></i></span>
                        <input type="date" id="dateValidite"  name="dateValidite" class="form-control" placeholder="Date validité" value="{{$editProspect->date_validite}}" aria-label="Date validité" required>
                    </div>
                </div>
                <div class="col-md-12 col-lg-6">
                    <label class="form-label">Ville édition</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-id-card"></i></span>
                        <input type="text" id="villePiece"  name="villePiece" class="form-control" placeholder="Ville pièce" value="{{$editProspect->ville_piece->name_ville}}" aria-label="Ville édition" required>
                    </div>
                </div>
            </div>
            <p></p>
            <div class="row">
                <div class="col-md-12 col-lg-6">
                    <label class="form-label">Ville</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-city"></i></span>
                        <input type="text" id="quartier" name="quartier" class="form-control" value="{{$editProspect->quartier->ville->name_ville}}" placeholder="Ville" aria-label="Ville" required>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 col-lg-6">
                    <label class="form-label">Quartier</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-city"></i></span>
                        <input type="text" id="quartier" name="quartier" class="form-control" value="{{$editProspect->quartier->name_quartier}}" placeholder="Quartier" aria-label="Quartier" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 col-lg-12">
                    <label class="form-label">Adresse</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-address-card"></i></span>
                        <textarea type="text" id="adresse" name="adresse" maxlength="150" class="form-control" placeholder="Adresse" aria-label="Adresse" required>{{$editProspect->adresse}}</textarea>
                    </div>
                </div>
            </div>
            <p/>

        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Fermer</button>
            <button type="submit" class="btn btn-sucess">Valider</button>
            <button type="submit" class="btn btn-warning">Rejeter</button>
        </div>
    </form>
@endisset
