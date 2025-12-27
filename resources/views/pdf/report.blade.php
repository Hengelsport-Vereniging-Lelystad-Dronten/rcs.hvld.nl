<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Rapportage Statistieken</title>
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
        .kpi-box { display: inline-block; width: 30%; background: #f5f5f5; padding: 10px; margin-right: 2%; text-align: center; border: 1px solid #ddd; border-radius: 5px; }
        .kpi-value { font-size: 24px; font-weight: bold; display: block; margin-top: 5px; }
        .kpi-label { font-size: 12px; text-transform: uppercase; color: #666; }
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
        <img src="{{ public_path('images/logo.png') }}" class="logo" alt="Logo">
        <h1>Rapportage Statistieken</h1>
        <div class="meta">
            <strong>Gegenereerd op:</strong> {{ $generated_at }}<br>
            @if($filters['start_date'] || $filters['end_date'])
                <strong>Periode:</strong> {{ $filters['start_date'] ?? 'Begin' }} t/m {{ $filters['end_date'] ?? 'Heden' }}<br>
            @endif
            @if($filters['user_name'])
                <strong>Controleur:</strong> {{ $filters['user_name'] }}
            @endif
        </div>
    </div>

    <!-- KPI's -->
    <div style="margin-bottom: 30px;">
        <div class="kpi-box">
            <span class="kpi-label">Totaal Controles</span>
            <span class="kpi-value">{{ $totals['rounds'] }}</span>
        </div>
        <div class="kpi-box">
            <span class="kpi-label">Totaal Overtredingen</span>
            <span class="kpi-value">{{ $totals['violations'] }}</span>
        </div>
        <div class="kpi-box">
            <span class="kpi-label">Actieve Controleurs</span>
            <span class="kpi-value">{{ $totals['active_controllers'] }}</span>
        </div>
    </div>

    <!-- Top Wateren -->
    <div class="section-title">Overtredingen per Water (Top 10)</div>
    <table>
        <thead>
            <tr>
                <th>Water</th>
                <th width="100">Aantal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($byWater as $water)
            <tr>
                <td>{{ $water->name }}</td>
                <td>{{ $water->count }}</td>
            </tr>
            @empty
            <tr><td colspan="2">Geen data beschikbaar.</td></tr>
            @endforelse
        </tbody>
    </table>

    <!-- Top Types -->
    <div class="section-title">Top Overtreding Types</div>
    <table>
        <thead>
            <tr>
                <th>Type</th>
                <th width="100">Aantal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($byType as $type)
            <tr>
                <td>{{ $type->code }} - {{ $type->description }}</td>
                <td>{{ $type->count }}</td>
            </tr>
            @empty
            <tr><td colspan="2">Geen data beschikbaar.</td></tr>
            @endforelse
        </tbody>
    </table>

    <!-- Prestaties Controleurs -->
    <div class="section-title">Prestaties Controleurs</div>
    <table>
        <thead>
            <tr>
                <th>Controleur</th>
                <th>Rondes</th>
                <th>Overtredingen</th>
                <th>Gem. per Ronde</th>
            </tr>
        </thead>
        <tbody>
            @forelse($byController as $stat)
            <tr>
                <td>{{ $stat['name'] }}</td>
                <td>{{ $stat['rounds'] }}</td>
                <td>{{ $stat['violations'] }}</td>
                <td>{{ $stat['rounds'] > 0 ? number_format($stat['violations'] / $stat['rounds'], 2) : '0.00' }}</td>
            </tr>
            @empty
            <tr><td colspan="4">Geen data beschikbaar.</td></tr>
            @endforelse
        </tbody>
    </table>

    <!-- Recidive -->
    <div class="section-title">Mogelijke Recidivisten (Top 10)</div>
    <table>
        <thead>
            <tr>
                <th>Vispasnummer</th>
                <th>Aantal</th>
                <th>Laatste Overtreding</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recidivism as $recidivist)
            <tr>
                <td>{{ $recidivist['vispasnummer'] }}</td>
                <td>{{ $recidivist['count'] }}</td>
                <td>{{ $recidivist['last_violation_date'] }}</td>
            </tr>
            @empty
            <tr><td colspan="3">Geen recidivisten gevonden.</td></tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>