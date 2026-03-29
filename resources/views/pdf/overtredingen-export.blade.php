<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Export Overtredingen</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; color: #333; }
        .header { margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #d32f2f; }
        .meta { margin-top: 10px; font-size: 12px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: top; }
        th { background-color: #f5f5f5; font-weight: bold; }
        tbody tr:nth-child(even) { background-color: #f9f9f9; }
        .logo { float: right; height: 60px; margin-top: -10px; }
        .section-title { font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 10px; color: #333; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .overtreding { margin-bottom: 20px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        .overtreding-header { background-color: #f5f5f5; padding: 10px; margin: -15px -15px 15px -15px; border-radius: 5px 5px 0 0; }
        .overtreding-title { font-weight: bold; font-size: 16px; margin: 0; }
        .overtreding-meta { font-size: 12px; color: #666; margin-top: 5px; }
        .field-group { margin-bottom: 10px; }
        .field-label { font-weight: bold; display: inline-block; width: 120px; }
        .field-value { display: inline-block; }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 400px;
            opacity: 0.1;
            z-index: -1000;
        }
        .footer {
            position: fixed;
            bottom: 0px;
            left: 0px;
            right: 0px;
            height: 20px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <img src="{{ public_path('images/logo.png') }}" class="watermark" alt="Watermark">
    <div class="header">
        <img src="{{ public_path('images/logo.png') }}" class="logo" alt="Logo">
        <h1>Export Overtredingen</h1>
        <div class="meta">
            <strong>Gegenereerd op:</strong> {{ $generated_at }}<br>
            <strong>Gegenereerd door:</strong> {{ $generated_by }}<br>
            <strong>Aantal overtredingen:</strong> {{ $overtredingen->count() }}<br>
            @if($force_re_export)
            <strong>Opmerking:</strong> Dit is een her-export (alle actieve overtredingen)
            @else
            <strong>Opmerking:</strong> Alleen niet eerder geëxporteerde overtredingen
            @endif
        </div>
    </div>

    @foreach($overtredingen as $overtreding)
    <div class="overtreding">
        <div class="overtreding-header">
            <h2 class="overtreding-title">Overtreding #{{ $overtreding->id }}</h2>
            <div class="overtreding-meta">
                Type: {{ $overtreding->overtredingType->naam ?? 'Onbekend' }} |
                Controleur: {{ $overtreding->controleRonde->user->name ?? 'Onbekend' }} |
                Datum: {{ $overtreding->geconstateerd_op?->format('d-m-Y H:i') ?? 'Onbekend' }}
            </div>
        </div>

        <div class="field-group">
            <span class="field-label">WAAR:</span>
            <span class="field-value">
                @if($overtreding->locatie_details)
                    Water: {{ $overtreding->locatie_details['water_naam'] ?? 'Onbekend' }},
                    Zone: {{ $overtreding->locatie_details['zone'] ?? 'Onbekend' }},
                    Coördinaten: {{ $overtreding->locatie_details['lat'] ?? '?' }}, {{ $overtreding->locatie_details['lon'] ?? '?' }}
                @else
                    Onbekend
                @endif
            </span>
        </div>

        <div class="field-group">
            <span class="field-label">WANNEER:</span>
            <span class="field-value">{{ $overtreding->geconstateerd_op?->format('d-m-Y H:i') ?? 'Onbekend' }}</span>
        </div>

        <div class="field-group">
            <span class="field-label">HOE:</span>
            <span class="field-value">{{ $overtreding->constatering_wijze ?? 'Onbekend' }}</span>
        </div>

        <div class="field-group">
            <span class="field-label">WAAROM:</span>
            <span class="field-value">{{ $overtreding->aanleiding ?? 'Onbekend' }}</span>
        </div>

        <div class="field-group">
            <span class="field-label">WAARMEE:</span>
            <span class="field-value">{{ $overtreding->middel ?? 'Onbekend' }}</span>
        </div>

        <div class="field-group">
            <span class="field-label">Vispasnummer:</span>
            <span class="field-value">{{ $overtreding->vispasnummer ?? 'Geen' }}</span>
        </div>

        <div class="field-group">
            <span class="field-label">Genomen maatregel:</span>
            <span class="field-value">{{ $overtreding->genomen_maatregel }}</span>
        </div>

        <div class="field-group">
            <span class="field-label">Vispas ingenomen:</span>
            <span class="field-value">{{ $overtreding->vispas_ingenomen ? 'Ja' : 'Nee' }}</span>
        </div>

        @if($overtreding->details)
        <div class="field-group">
            <span class="field-label">Details:</span>
            <span class="field-value">{!! nl2br(e($overtreding->details)) !!}</span>
        </div>
        @endif
    </div>
    @endforeach

    <div class="footer">
        Rapportage gegenereerd op {{ $generated_at }} door {{ $generated_by }}
    </div>
</body>
</html>