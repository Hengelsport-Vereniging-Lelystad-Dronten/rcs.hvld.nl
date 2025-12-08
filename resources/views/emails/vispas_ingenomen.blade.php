<!DOCTYPE html>
<html>
<head>
    <title>Vispas Ingenomen</title>
</head>
<body>
    <h1>Vispas Ingenomen</h1>
    <p>Er is een vispas ingenomen.</p>
    <p>Details:</p>
    <ul>
        <li>Datum: {{ $overtreding->created_at->format('d-m-Y H:i') }}</li>
        <li>Controleur: {{ $overtreding->controleRonde->user->name }}</li>
        <li>Overtreding: {{ $overtreding->overtredingType->omschrijving }}</li>
        <li>Vispasnummer: {{ $overtreding->vispasnummer }}</li>
    </ul>
    <p>
        <a href="{{ route('controles.show', $overtreding->controleRonde->id) }}">Bekijk de volledige controle</a>
    </p>
</body>
</html>
