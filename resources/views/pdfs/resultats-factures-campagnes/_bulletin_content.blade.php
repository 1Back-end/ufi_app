
<header class="d-flex align-items-center size" style="font-family: 'Times New Roman', serif">
    <div class="w-25">
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path($logo))) }}" alt=""
             class="img-fluid w-50">
    </div>

    <div class="text-center" style="line-height: 18px">
        <div class="fs-3 text-uppercase fw-bold">
            {{ $centre->name }}
        </div>

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
            NIU: {{ $centre->contribuable }}
        </div>
    </div>
</header>

<div class="mt-1 w-100" style="border-top: 1px double rgba(0, 0, 0, 0.4); margin-bottom: 1px"></div>
<div class="mb-1 w-100" style="border-top: 1px double rgba(0, 0, 0, 0.4);"></div>

{{-- Section Informations --}}
<div class="p-2 mb-2" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 4px;">
    <div class="row">
        <div class="col-6">
            <table class="info-table w-100">
                <tbody>
                <tr>
                    <td class="text-muted" style="width: 35%">Dossier:</td>
                    <td class="fw-bold text-primary">{{ $resultat_facture_campagne->reference }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Patient:</td>
                    <td class="fw-bold" style="font-size: 2.3mm">{{ $resultat_facture_campagne->patient->fullname }}</td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="col-6 border-start ps-2">
            <table class="info-table w-100">
                <tbody>
                <tr>
                    <td class="text-muted" style="width: 45%">Imprimé le :</td>
                    <td class="fw-semibold">{{ now()->format('d/m/Y H:i') }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Période :</td>
                    <td class="fw-semibold" style="font-size: 2mm;">
                        Du {{ \Carbon\Carbon::parse($resultat_facture_campagne->factureCampagne?->campagne?->start_date)->format('d/m/Y') }}
                        au {{ \Carbon\Carbon::parse($resultat_facture_campagne->factureCampagne?->campagne?->end_date)->format('d/m/Y') }}
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Tableau des Résultats --}}
<table id="results" class="table border border-black mb-0" style="border-color: rgba(0, 0, 0, 0.3)">
    <thead>
    <tr style="background-color: #F1F5F9;">
        <th colspan="3" class="text-center py-1 text-primary" style="border-bottom: 1.5px solid #CBD5E1;">
            <strong style="font-size: 2.5mm;">
                {{ $resultat_facture_campagne->factureCampagne->campagne->full_name }}
            </strong>
        </th>
    </tr>
    <tr style="background-color: #E2E8F0;">
        <th class="text-center" scope="col" style="padding: 2px;">N°</th>
        <th class="text-start" scope="col" style="padding: 2px;">Examens</th>
        <th class="text-center" scope="col" style="padding: 2px;">Résultat</th>
    </tr>
    </thead>
    <tbody>
    @php $index = 1; @endphp
    @forelse($resultat_facture_campagne->factureCampagne->campagne->elements as $element)
        @if($element->type === 'examens' && $element->element)
            @php
                $examensCollection = collect($resultat_facture_campagne->examens ?? []);
                $resultat = $examensCollection->firstWhere('id', $element->element->id);
            @endphp
            <tr>
                <td class="text-center fw-medium text-secondary">{{ $index++ }}</td>
                <td class="fw-semibold text-dark text-start" style="font-size: 2.3mm;">{{ $element->element->name ?? '—' }}</td>
                <td class="text-center">
                    @if($resultat)
                        @if($resultat['result'] === true || $resultat['result'] === 'true')
                            <span class="text-danger fw-bold">Positif</span>
                        @elseif($resultat['result'] === 'weakly_positive')
                            <span class="text-warning fw-bold" style="color: #d97706 !important;">Faiblement positif</span>
                        @elseif($resultat['result'] === false || $resultat['result'] === 'false')
                            <span class="text-success fw-bold">Négatif</span>
                        @else
                            <span class="status-empty">—</span>
                        @endif
                    @else
                        <span class="status-empty">—</span>
                    @endif
                </td>
            </tr>
        @endif
    @empty
        <tr>
            <td colspan="3" class="text-center py-2 text-muted">Aucun examen trouvé</td>
        </tr>
    @endforelse
    </tbody>
</table>
