@isset($detailutilisateur)

    <div class="modal-body">
        Voulez-vous réinitialiser le mot de passe de cet utilisateur ?
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" id="3" data-bs-dismiss="modal">Non</button>
        <form action="{{ route('InitPasswordUserProfil', [$detailutilisateur->id]) }}" id="frmValidateProspect" name="frmValidateProspect">
            @csrf
            <button type="button" class="btn btn-success" id="4" onclick="validerReinitialisationPassword()">Oui</button>
        </form>
    </div>

    <script>
        function validerReinitialisationPassword() {
            // Fermer la modale
            const modal = bootstrap.Modal.getInstance(document.getElementById('confirmationUpdatePassword'));
            modal.hide();

            // Soumettre le formulaire
            document.getElementById('frmInitPassword').submit();
        }
    </script>
@endisset
