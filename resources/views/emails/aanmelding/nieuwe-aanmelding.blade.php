@component('mail::message')
# Nieuwe Aanmelding Sportvisserijcontroleur

Er is een nieuwe aanmelding binnengekomen via het webformulier.

## Persoonsgegevens
**Naam:** {{ $data['voornaam'] }} {{ $data['achternaam'] }}  
**Geboortedatum:** {{ \Carbon\Carbon::parse($data['geboortedatum'])->format('d-m-Y') }}  
**Adres:** {{ $data['adres'] }}, {{ $data['postcode'] }} {{ $data['woonplaats'] }}  
**Telefoon:** {{ $data['telefoonnummer'] }}  
**E-mail:** {{ $data['email'] }}  
**Lidnummer:** {{ $data['lidnummer'] ?? 'Niet opgegeven' }}

## Motivatie & Ervaring
**Motivatie:**  
{{ $data['motivatie_tekst'] }}

@if(!empty($data['motivatie_keuzes']))
**Spreekt aan in de rol:**
@foreach($data['motivatie_keuzes'] as $keuze)
- {{ $keuze }}
@endforeach
@endif

**Ervaring:**  
{{ $data['ervaring_tekst'] ?? 'Geen ervaring opgegeven' }}

## Verklaringen
- [x] Gaat akkoord met lidmaatschapsvoorwaarden
- [x] Gaat akkoord met VOG aanvraag

@component('mail::button', ['url' => config('app.url')])
Naar RCS Dashboard
@endcomponent

Met vriendelijke groet,<br>
{{ config('app.name') }}
@endcomponent