<!DOCTYPE html>
<html>
<head>
    <title>{{ config('app.name') }}</title>
</head>
<body>
<h1>{{ config('app.name')  }}</h1>

<br>Bonjour M./Mme {{ $data['name'] }}, </br>
<br>Veuillez trouver ci-dessous vos paramètres de connexion au back-office de Kiaboo. </br><p><p/>
Nom d'utilisateur : {{ $data['login'] }} <br/>
Mot de passe :  {{ $data['password'] }} <br/>
<br>Merci de le changer dès votre première connexion</br><p><p/>
Cordialement<br/>
PS : Ceci est un mail automatique, merci de ne pas y répondre<br/><br/>
<img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('/assets/images/logoMail.png'))) }}" class="logo" alt="kiaboo">
</body>
</html>