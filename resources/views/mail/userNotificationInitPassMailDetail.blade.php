<!DOCTYPE html>
<html>
<head>
    <title>{{ config('app.name') }}</title>
</head>
<body>
<h1>{{ config('app.name')  }}</h1>

<br>Bonjour M./Mme {{ $data['name'] }}, </br><p><p/>
<br>Votre mot de passe vient d'être réinitialisé. Si vous n'êtes pas à l'origine de cette demande, veuillez informer immédiatement Kiaboo. </br><p><p/>
Nouveau mot de passe :  {{ $data['password'] }} <br/>
<br>Merci de le changer dès votre première connexion</br><p><p/>
Cordialement<br/>
PS : Ceci est un mail automatique, merci de ne pas y répondre<br/><br/>
<img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('/assets/images/logoMail.png'))) }}" class="logo" alt="kiaboo">
</body>
</html>