<!DOCTYPE html>
<html>
<head>
    <title>{{ config('app.name') }}</title>
</head>
<body>
<h1>{{ config('app.name')  }}</h1>

<br>Bonjour M./Mme, </br><p/>
<br>Votre compte {{ $data['nameAgent'] }} vient d'être approvisionné par {{ $data['nameDistributeur'] }}</br><p><p/>
IdTransaction : {{ $data['idTransaction'] }} <br/>
Montant rechargé :  {{ $data['amount'] }} F CFA<br/>
Nouveau solde :  {{ $data['newBalance'] }} F CFA<br/>
<br>Merci pour votre confiance</br><p><p/>
Cordialement<br/>
<br/>

PS : Ceci est un mail automatique, merci de ne pas y répondre<br/><br/>
{{--<img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('/assets/images/logoMail.png'))) }}" class="logo" alt="kiaboo">--}}
<img src="data:image/png;base64,{{ base64_encode(file_get_contents(asset('/assets/images/logoMail.png')) ) }}" class="logo" alt="kiaboo">
{{$image = base64_encode( file_get_contents(asset('/assets/images/logoMail.png') ))}}
</body>
</html>