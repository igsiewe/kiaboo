<!DOCTYPE html>
<html>
<head>
    <title>{{ config('app.name') }}</title>
</head>
<body>
<h1>{{ $data['title'] }}</h1>

<p>Bonjour M./Mme {{ $data['name'] }}, </p>
<p>Veuillez trouver ci-dessous vos paramètres de connexion au back-office de Kiaboo. </p>
Nom d'utilisateur : {{ $data['login'] }} </p>
Mot de passe :  {{ $data['password'] }} </p>
<p>Merci de le changer dès votre première connexion</p>
Cordialement</p>

PS : Ceci est un mail automatique, merci de ne pas y répondre

</body>
</html>