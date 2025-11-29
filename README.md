
# Visserij-Controle Applicatie (HVLD)

<p align="center"><img src="public/images/logo.png" width="400" alt="HVLD Logo"></p>

## Overzicht

De HVLD Visserij-Controle Applicatie is een moderne beheerinterface voor hengelsportverenigingen en vrijwillige sportviscontroleurs. De applicatie is ontwikkeld met Laravel, Inertia.js, Vue.js en Tailwind CSS en biedt een consistente, juridisch correcte en eenvoudig te beheren basis voor het registreren van overtredingen, maatregelen en administratieve afhandelingen.

## Doel van de Applicatie

-   Stroomlijnen van maatregelen rondom controles.
    
-   Één uniforme bron voor strafmaten, categorieën en overtredingstypes.
    
-   Stabiliteit en traceerbaarheid in administratieve processen.
    
-   Minimaliseren van fouten via order_id-sortering en standaardkoppelingen.
    

## Kernfunctionaliteiten

### Sanctie- en Registratiesysteem

#### Interne Verenigingsmaatregelen

Maatregelen die het bestuur daadwerkelijk mag opleggen, zoals WA, TS en VPA.

#### Operationele Registraties

Registraties zonder juridische sanctie, zoals MWB, OKE en ADM.

#### Officiële Meldingen aan BOA/Politie

Acties die leiden tot formele opvolging door bevoegde instanties, zoals MJ, BOA en POL.

### Strafmaten Beheer

-   CRUD-functionaliteit.
    
-   Gesorteerd op order_id.
    
-   Kolomsortering op Volgorde, Code en Omschrijving.
    

### Overtredingstypes Beheer

-   CRUD-functionaliteit.
    
-   Koppelbare default_strafmaat_id.
    

## Gebruikte Technologieën

-   Backend: Laravel
    
-   Frontend: Vue.js
    
-   SPA-adapter: Inertia.js
    
-   Styling: Tailwind CSS
    
-   Database: MySQL/MariaDB/PostgreSQL
    

## Belangrijke Projectbestanden

-   Migratie: order_id kolom voor strafmaten.
    
-   Seeders: basisdata en sortering.
    
-   Frontend component: klikbare sortering.
    

## Installatie en Opstarten

1.  Clone repository en installeer dependencies.
    
2.  Configureer .env en genereer app key.
    
3.  Voer migraties en seeders uit.
    
4.  Start npm dev en Laravel server.
    

## Security Richtlijnen

Zie SECURITY.md voor kwetsbaarheden, verantwoordelijk melden en beveiligingsprocessen.

## Code of Conduct

Zie CODE_OF_CONDUCT.md voor richtlijnen over gedrag en samenwerking.

## Bijdragen

Zie CONTRIBUTING.md voor pull requests, commitregels en QA-richtlijnen.

## Licentie

(Vul aan indien gewenst)

## Contact

Open een issue of dien een pull request in voor vragen of suggesties.
