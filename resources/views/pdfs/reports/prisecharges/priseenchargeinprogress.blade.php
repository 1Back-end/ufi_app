@php use App\Helpers\FormatPrice; @endphp

@extends('pdfs.layouts.template')

@section('content')

<h1 class="fs-3 fw-bold text-center text-uppercase text-decoration-underline">
    LISTE DES PRISES EN CHARGES EN COURS
</h1>

<p class="fst-italic text-end">Date d'impression: {{ now()->format('d/m/Y H:i') }}</p>

<h2 class="fw-bold text-center fs-5">
    Période > {{ now()->format('d/m/Y') }}
</h2>

@foreach($assurances as $assurance)
    <div class="border border-1 border-black p-2 mb-1">
        <p class="d-flex gap-2 fw-bold align-items-center">
            <span>Nom Assurance: </span>
            <span>{{ $assurance->nom }}</span>
        </p>

        <p class="d-flex gap-2 fw-bold align-items-center">
            <span>Adresse: </span>
            <span>{{ $assurance->adresse }}</span>
        </p>
    </div>

    <table class="table table-bordered mb-3">
        <thead>
        <tr>
            <th>Nom patient</th>
            <th>Téléphone</th>
            <th>Date de naissance</th>
            <th>Numéro PC</th>
            <th>Période</th>
            <th>Taux</th>
        </tr>
        </thead>
        <tbody>
            @foreach($assurance->priseEnCharges as $priseEnCharge)
                <tr>
                    <td>{{ $priseEnCharge->client->nomcomplet_client }}</td>
                    <td>
                        <span class="d-flex flex-column">
                            <span>{{ $priseEnCharge->client->tel_cli }}</span>
                            <span>{{ $priseEnCharge->client->tel2_cli }}</span>
                        </span>
                    </td>
                    <td>{{ $priseEnCharge->client->date_naiss_cli }}</td>
                    <td>{{ $priseEnCharge->code }}</td>
                    <td>
                        {{ $priseEnCharge->date_debut->format('d/m/Y') }} - {{ $priseEnCharge->date_fin->format('d/m/Y') }}
                    </td>
                    <td>{{ $priseEnCharge->taux_pc }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endforeach

@endsection
