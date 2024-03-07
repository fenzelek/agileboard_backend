<html>

<head>
    
</head>
<body>
<p>Hello</p>

<p>Click here to reset your password: <a href="{{ $link = str_replace([':token', ':email'], [$token, urlencode($user->getEmailForPasswordReset())], Request::input('url')) }}"> {{ $link }} </a></p>

<p>This link will expire in {{config('auth.passwords.users.expire')}} minutes.</p>
</body>
</html>
