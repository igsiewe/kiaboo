@isset($distributeur)
    <form action="{{route("setUpdateDistributeur",[$distributeur->id])}}" id="frmUpdateDistributeur" name="frmUpdateDistributeur">
        @csrf
        <div class="modal-header">
            <h5 class="modal-title" id="staticBackdropLabel">Editer le distributeur</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">

            <div class="row">
                <div class="col-md-12 col-lg-12">
                    <div class="form-group">
                        <label class="form-label" for="name_distributeur">Distributeur</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-handshake"></i></span>
                        <input type="text" id="name_distributeur" name="name_distributeur" class="form-control" placeholder="Distributeur *" aria-label="Distributeur" required value="{{$distributeur->name_distributeur}}">
                    </div>
                    </div>
                </div>

            </div><p/>
            <div class="row">
                <div class="col-md-12 col-lg-6">
                    <div class="form-group">
                        <label class="form-label" for="name_contact">Nom contact</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-user"></i></span>
                        <input type="text" id="name_contact" name="name_contact" class="form-control" placeholder="Nom contact *" aria-label="Nom contact" required value="{{$distributeur->name_contact}}">
                    </div>
                    </div>
                </div>

                <div class="col-md-12 col-lg-6">
                    <div class="form-group">
                        <label class="form-label" for="surname_contact">Prénom contact</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-user"></i></span>
                        <input type="text" id="surname_contact" name="surname_contact" class="form-control" placeholder="Prénom contact *" aria-label="Prénom contact" required value="{{$distributeur->surname_contact}}">
                    </div>
                    </div>
                </div>
            </div><p/>
            <div class="row">
                <div class="col-md-12 col-lg-6">
                    <div class="form-group">
                        <label class="form-label" for="telephone">Téléphone</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-phone"></i></span>
                        <input type="number" id="telephone" name="telephone" readonly disabled class="form-control" placeholder="Téléphone *" aria-label="Téléphone" required value="{{$distributeur->phone}}">
                    </div>
                    </div>
                </div>
                <div class="col-md-12 col-lg-6">
                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                        <input type="text" id="email" name="email" class="form-control" placeholder="Email *" aria-label="email" required value="{{$distributeur->email}}">
                    </div>
                    </div>
                </div>
            </div><p/>

            <div class="row">
                <div class="col-md-12 col-lg-12">
                    <div class="form-group">
                        <label class="form-label" for="region">Région</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text" id="basic-addon1"><i class="fa fa-flag"></i></span>
                        <select class="form-select" name="region" id="region" onchange="" onclick="" required>
                            @isset($regions)
                                @if($regions)
                                    <option  value="" >Sélectionner une region</option>
                                    @foreach($regions as $s)

                                        @if($s->id == $distributeur->region_id)
                                            <option  value="{{ $s->id }}" selected>{{$s->name_region}}</option>
                                        @else
                                            <option  value="{{ $s->id }}" >{{$s->name_region}}</option>
                                        @endif
                                    @endforeach
                                @endif
                            @endisset
                        </select>
                    </div>
                    </div>
                </div>

            </div>
            <p/>
            <div class="row">
                <div class="col-md-12 col-lg-12">
                    <div class="form-group">
                        <label class="form-label" for="adresse">Adresse</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-address-card"></i></span>
                        <textarea  id="adresse" name="adresse" class="form-control" maxlength="250"  placeholder="Adresse *" aria-label="adresse" required> {{$distributeur->adresse}}</textarea>
                    </div>
                    </div>
                </div>
            </div>
            <p/>
            <div class="row">
                <div class="col-md-12 col-lg-12">
                    <div class="form-group">
                        <label class="form-label" for="plafond_alerte">Plafond alerte</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-money-bill"></i></span>
                        <input type="number" id="plafond_alerte" name="plafond_alerte" class="form-control" placeholder="Plafond alerte *" aria-label="Plafond alerte" required value="{{$distributeur->plafond_alerte}}">
                    </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="modal-footer">
            <button type="button" class="btn btn-outline-danger" title="Fermer" data-bs-dismiss="modal">Fermer</button>
            <button type="submit" class="btn btn-danger" title="Enregistrer">Enregistrer</button>
        </div>
    </form>
@endisset
