<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nieuwe Overlastmelding ontvangen</title>
</head>
<body>
    <h1>Overlastmelding #{{ $melding->id }} ontvangen</h1>

    <p>Er is een melding ingediend via het publiek formulier voor sportvisserij en dierenwelzijn.</p>

    <ul>
        <li><strong>Melding ID:</strong> {{ $melding->id }}</li>
        <li><strong>Categorie:</strong> {{ $melding->categorie }}</li>
        <li><strong>Status:</strong> {{ $melding->status }}</li>
        <li><strong>Beschrijving:</strong> {{ $melding->beschrijving }}</li>
        <li><strong>Datum/tijd incident:</strong> {{ optional($melding->melding_datum_tijd)->format('d-m-Y H:i') ?? '-' }}</li>
        <li><strong>Locatie adres:</strong> {{ $melding->locatie_adres ?? '-' }}</li>
        <li><strong>Lat/Lng:</strong> {{ optional($melding->locatie_details)['latitude'] ?? '-' }}, {{ optional($melding->locatie_details)['longitude'] ?? '-' }}</li>
        <li><strong>Melder anoniem:</strong> {{ $melding->melder_anoniem ? 'Ja' : 'Nee' }}</li>
        <li><strong>Ingediend door:</strong> {{ $melding->melder_naam ?? 'Niet opgegeven' }}</li>
    </ul>

    @if($melding->fotos)
    <h3>Bijlagen:</h3>
    <ul>
        @foreach($melding->fotos as $foto)
        <li>{{ $foto }}</li>
        @endforeach
    </ul>
    @endif

    <p>
        <a href="{{ route('beheer.overlast-meldingen.show', $melding->id) }}" style="background-color: #3490dc; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Bekijk melding</a>
    </p>

    <p>Bedankt,<br>{{ config('app.name') }}</p>
</body>
</html>