<!DOCTYPE html>
<html>
<head>
    <title>Vispas Ingenomen</title>
    <!--
        resources/views/emails/vispas_ingenomen.blade.php

        E-mail template die wordt gebruikt wanneer een vispas door een controleur
        is ingenomen. Deze template ontvangt een `$overtreding` model en toont
        datum, controleur, type overtreding en vispasnummer.
    -->
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
