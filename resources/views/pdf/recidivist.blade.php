<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Dossier {{ $vispasnummer }}</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; color: #333; }
        .header { margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #d32f2f; }
        .meta { margin-top: 10px; font-size: 12px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; vertical-align: top; }
        th { background-color: #f5f5f5; font-weight: bold; }
        .type { font-weight: bold; }
        .details { font-size: 12px; color: #666; font-style: italic; margin-top: 4px; }
        .logo { float: right; height: 60px; margin-top: -10px; }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 400px;
            opacity: 0.1;
            z-index: -1000;
        }
    </style>
</head>
<body>
    <img src="{{ public_path('images/logo.png') }}" class="watermark" alt="Watermark">
    <div class="header">
        {{-- Zorg ervoor dat 'images/logo.png' in de 'public' map staat --}}
        <img src="{{ public_path('images/logo.png') }}" class="logo" alt="Logo">
        <h1>Recidive Dossier</h1>
        <div class="meta">
            <strong>Vispasnummer:</strong> {{ $vispasnummer }}<br>
            <strong>Gegenereerd op:</strong> {{ $generated_at }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="15%">Datum</th>
                <th width="20%">Water</th>
                <th width="30%">Overtreding</th>
                <th width="20%">Maatregel</th>
                <th width="15%">Controleur</th>
            </tr>
        </thead>
        <tbody>
            @forelse($violations as $v)
            <tr>
                <td>
                    {{ \Carbon\Carbon::parse($v->controleRonde->start_tijd)->format('d-m-Y') }}<br>
                    <small>{{ \Carbon\Carbon::parse($v->controleRonde->start_tijd)->format('H:i') }}</small>
                </td>
                <td>{{ $v->controleRonde->water->naam ?? 'Onbekend' }}</td>
                <td>
                    <div class="type">{{ $v->overtredingType->code ?? '' }} - {{ $v->overtredingType->omschrijving ?? '' }}</div>
                    @if($v->details)
                        <div class="details">{{ $v->details }}</div>
                    @endif
                </td>
                <td>{{ $v->genomen_maatregel }}</td>
                <td>{{ $v->controleRonde->user->name ?? 'Onbekend' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; font-style: italic;">Geen overtredingen gevonden.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
