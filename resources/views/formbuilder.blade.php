<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SATU FORM</title>
    @include('formbuilder::partials.styles')
</head>
<body>
    @include('formbuilder::partials.views')
    @include('formbuilder::partials.scripts')
</body>
</html>
