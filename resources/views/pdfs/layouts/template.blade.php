<!doctype html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <style>
        {!! $bootstrap !!}
    </style>

    <style>
        body,
        html {
            height: 100%;
            margin: 0;
            padding: 0;
            font-size: 3mm !important;
            font-family: "Times New Roman", serif;
        }

        header {
            font-family: 'Helvetica', serif;
        }

        h1 {
            font-size: 6mm !important;
        }

        table {
            font-size: 3mm !important;
        }

        img {
            width: auto;
            height: auto;
        }

        .print-wrapper {
            position: relative;
            min-height: 100%;
            padding-bottom: 10mm;
            box-sizing: border-box;
        }

        .print-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 10mm;
            text-align: center;
            background-color: white;
            border-top: 1mm solid rgb(15, 187, 105);
            opacity: .5;
            font-size: 2.5mm;
        }
    </style>
</head>

<body>
    {{-- Header --}}
    <header class="row">
        <div class="col-4">
            @if($logo)
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path($logo))) }}" alt="" class="w-50">
            @endif
        </div>

        <div class="text-center col text-success">
            <div class="fs-1 text-uppercase">
                {{ $centre->name }}
            </div>

            <hr class="my-1 border border-success border-1 opacity-75 col-12">

            <div class="">
                - {{ $centre->address }} - {{ $centre->town }}
            </div>

            <div class="">
                BP: {{ $centre->postal_code }} {{ $centre->town }} -
                Tél. {{ $centre->tel }} {{ $centre->tel2 ? '/' . $centre->tel2 : '' }}
                / Fax: {{ $centre->fax ?? '' }}
            </div>

            <div class="">
                Email: {{ $centre->email }}
            </div>

            <div class="">
                Autorisation n° {{ $centre->autorisation }}
                NUI: {{ $centre->contribuable }}
            </div>
        </div>
    </header>

    @yield('content')

</body>

</html>
