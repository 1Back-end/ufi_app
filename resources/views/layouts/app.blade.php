<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Document</title>

    @yield(config('LaravelLogger.bladePlacementCss'))
</head>
<body>
    <div class="p-2">
        @yield('template_title')

        @yield('content')
    </div>

    @yield(config('LaravelLogger.bladePlacementJs'))
</body>
</html>
