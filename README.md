# **HVLD Visserij-Controle Applicatie**

<p align="center"><img src="public/images/logo.png" width="200" alt="HVLD Logo"></p>

## 1. Overzicht en Doelstelling

Het **Ronde Controle Systeem (RCS)** is een moderne, webgebaseerde applicatie, specifiek ontworpen ter ondersteuning van vrijwillige sportviscontroleurs en bestuursleden van hengelsportverenigingen (zoals HVLD).
Het hoofddoel is het bieden van een **uniform, efficiënt en traceerbaar registratiesysteem** voor alle controle-activiteiten in het veld. De applicatie stroomlijnt het proces van de start van een controleronde tot de administratieve afhandeling van een overtreding.

## 2. Kernfunctionaliteiten

* **Controle Rondes:** Realtime vastlegging van controleactiviteiten, inclusief start-/eindtijd, controller en gecontroleerd water.  
* **Overtredingsregistratie:** Gestandaardiseerde vastlegging van overtredingen met koppeling naar:  
  * Overtredingstype (Code, Omschrijving).  
  * Geadviseerde sanctie (via recidive-check).  
  * Genomen Maatregel (Sanctie).  
* **Recidive-Check:** Automatische controle van het Vispasnummer tegen eerdere overtredingen om de correcte, geëscaleerde strafmaat te adviseren.  
* **Maatregelenbeheer:** Ondersteuning van zowel interne verenigingsmaatregelen (WA, VPA) als operationele registraties (OKE, ADM) en officiële meldingen (BOA, POL).
De applicatie is rijk aan features die zijn ontworpen voor gebruiksgemak in het veld en krachtig beheer op kantoor.

### Voor de Controleur (in het veld)
*   **Dashboard:** Een persoonlijk dashboard met belangrijke statistieken zoals het totaal aantal actieve rondes en recente overtredingen.
*   **Start Controleronde:**
    *   **GPS-gebaseerde selectie:** Vind en selecteer automatisch het dichtstbijzijnde water met één druk op de knop.
    *   **Handmatige selectie:** Kies een water uit een doorzoekbare lijst.
    *   **Quick Add:** Voeg direct een nieuw water toe zonder de pagina te verlaten.
*   **Overtredingsregistratie:**
    *   **Real-time Recidive Check:** Voer een VISpasnummer in en controleer direct op eerdere overtredingen.
    *   **Dynamisch Sanctie-advies:** Het systeem adviseert automatisch de juiste maatregel (standaard of geëscaleerd bij recidive).
    *   **Gestructureerde vastlegging:** Leg type overtreding, genomen maatregel, details en eventuele inname van de VISpas gestructureerd vast.
*   **Ronde Afronden:** Sluit een ronde af met eventuele algemene opmerkingen, waarna deze wordt gearchiveerd.

### Voor de Beheerder
*   **Centraal Dashboard:** Overzicht van alle activiteiten binnen de vereniging, inclusief KPI's zoals 'Top Overtreding' en 'Meest Gecontroleerde Locatie'.
*   **Stamgegevensbeheer:** Volledige CRUD-functionaliteit voor:
    *   **Wateren:** Beheer van alle controlelocaties, inclusief geografische data.
    *   **Overtredingstypes:** Definiëren van overtredingen en de daaraan gekoppelde standaard- en recidive-strafmaten.
    *   **Strafmaten:** Beheren van de lijst met mogelijke sancties, inclusief een sorteervolgorde.
*   **Gebruikersbeheer:** (Via Laravel backend) Toewijzen van rollen aan gebruikers.

## 3. Technische Architectuur

RCS is gebouwd als een **moderne monolithische applicatie** met een server-side framework dat data direct aan een reactieve frontend levert. Dit zorgt voor een snelle, Single-Page Application (SPA) ervaring zonder de complexiteit van een volledig losgekoppelde API.

| Component | Technologie | Rol |
| :---- | :---- | :---- |
| **Backend** | **Laravel (PHP)** | Verantwoordelijk voor business logica, routing, database-interactie (Eloquent), autorisatie en het aanleveren van data aan de frontend. |
| **Frontend** | **Vue.js 3 (Composition API)** | Bouwt de interactieve en reactieve gebruikersinterface met herbruikbare componenten. |
| **Connector** | **Inertia.js** | De "lijm" die Laravel en Vue.js verbindt. Het stelt Laravel in staat om direct Vue-componenten te renderen en props mee te geven. |
| **Styling** | **Tailwind CSS** | Utility-first CSS framework voor snelle en responsieve styling. |
| **Database** | MySQL / MariaDB / PostgreSQL | Permanente opslag van alle applicatiegegevens. |

## 4. Rollen en Autorisatie

De applicatie kent een eenvoudig maar effectief rollensysteem:

### **A. Strafmaten (Sancties)**
*   **Controleur:** De standaardgebruiker. Kan controlerondes starten, overtredingen vastleggen en zijn/haar eigen rondes inzien.
*   **Beheerder:** Heeft alle rechten van een controleur, plus toegang tot het 'Beheer' gedeelte om stamgegevens (wateren, overtredingstypes, strafmaten) te beheren.

De lijst van alle mogelijke maatregelen die opgelegd of geregistreerd kunnen worden.
## 5. Installatie en Opstarten

Volg deze stappen om de applicatie lokaal te installeren en op te starten:

1.  **Repository Klonen:**
    ```bash
    git clone [repository-url]
    cd rcs.hvld.nl
    ```

2.  **Dependencies Installeren:**
    ```bash
    composer install
    npm install
    ```

3.  **Configuratie:**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
    Configureer vervolgens de database-instellingen (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) in het `.env` bestand.

4.  **Database Initialisatie:**
    ```bash
    php artisan migrate --seed
    ```
    Dit commando voert alle databasemigraties uit en vult de tabellen met essentiële startgegevens (zoals standaard overtredingstypes en strafmaten) via de seeders.

5.  **Applicatie Starten:**
    Open twee terminalvensters:

    *   In terminal 1, start de Vite development server voor de frontend:
        ```bash
        npm run dev
        ```
    *   In terminal 2, start de Laravel ontwikkelserver:
        ```bash
        php artisan serve
        ```

De applicatie is nu lokaal bereikbaar, meestal op `http://127.0.0.1:8000`.

## 6. Kern Data Definities (Seeders)

De meegeleverde seeders zijn cruciaal voor de werking van het advies- en registratiesysteem.

### `strafmaten` (via `StrafmaatSeeder`)
Definieert alle mogelijke maatregelen en hun sorteervolgorde (`order_id`).

| ID | Code | Omschrijving | Categorie | Order |
| :---- | :---- | :---- | :---- | :---- |
| **1** | **WA** | Waarschuwing (mondeling/officieel) | Interne Maatregel | 1 |
| **2** | **HG** | Herstelgesprek verplicht met bestuur | Interne Maatregel | 2 |
| **3** | **VPA-ZT** | Zeer Tijdelijke Inname VISpas (veldactie, 1 week) | Interne Maatregel | 3 |
| **4** | **VPA-T** | Tijdelijke Inname VISpas (bijv. 3-12 maanden) | Interne Maatregel | 4 |
| **10** | **PV** | Proces-verbaal opgemaakt door BOA/Politie | Wettelijke Sanctie | 10 |
| **11** | **JUS** | Melding aan Justitie (strafbare feiten) | Wettelijke Sanctie | 11 |


*(Noot: Codes OKE, MA, MWB zijn ook opgenomen maar zijn Operationele Acties, geen daadwerkelijke straffen.)*

### **4.1.2. overtreding_types (via OvertredingTypeSeeder)**
De catalogus van overtredingen, met direct gekoppelde adviezen voor strafmaten. Dit is de kern van de geautomatiseerde sanctie-advisering.

Dit is de catalogus van overtredingen, met de direct gekoppelde adviezen voor strafmaten.


| Code | Omschrijving | Default Strafmaat (ID) | Recidive Strafmaat (ID) |
| :---- | :---- | :---- | :---- |
| **10** | Géén schriftelijke toestemming | **1 (WA)** | **10 (PV)** |
| **15** | Vissen zonder nachtvistoestemming | **1 (WA)** | **4 (VPA-T)** |
| **20** | Vissen met meer hengels dan toegestaan | **1 (WA)** | **4 (VPA-T)** |
| **35** | Weigering medewerking verlenen | **10 (PV)** | **10 (PV)** |
| **40** | In bezit/meenemen teveel/niet toegestane vis | **3 (VPA-ZT)** | **10 (PV)** |
| **50** | Vissen met levend aas | **4 (VPA-T)** | **10 (PV)** |

*(Noot: De default_strafmaat_id en recidive_strafmaat_id verwijzen direct naar de ID's uit de strafmaten tabel. Dit is de kern van de geautomatiseerde sanctie-advisering.)*

### **4.1.3. waters (via WaterSeeder)**

Deze tabel bevat de geregistreerde viswateren met hun geografische en regelgevende context.

* **Kerngegevens:** naam, type, beheersgebied, beschrijving (met specifieke regels zoals nachtvisverboden), latitude, longitude.  
* **Doel:** Bieden van een gestandaardiseerde lijst van wateren voor de start van een Controle Ronde. De GPS-coördinaten ondersteunen kaartweergave en geofencing functionaliteit in de toekomst.

## **5. Security & Operationele Richtlijnen**

* **Rolgebaseerde Toegang:** Toegang tot beheerinterfaces (Strafmaten, Overtredingstypes) moet strikt beperkt zijn tot geautoriseerde bestuursleden/admins.  
* **Recidive Data:** De Recidive-check API moet robuust zijn en alleen de noodzakelijke metadata (historie_count, geadviseerde_strafmaat_id) teruggeven. De privacy van de visser moet gewaarborgd blijven.  
* **Data Integriteit:** Door het gebruik van order_id en standaard default_strafmaat_id koppelingen, wordt de administratieve foutmarge geminimaliseerd. Dit waarborgt juridische consistentie.

## **6. Bijdragen en Code of Conduct**

Zie de afzonderlijke bestanden:

* [SECURITY.md](SECURITY.md) voor onze Responsible Disclosure Statement and Security Policy
* [CONTRIBUTING.md](CONTRIBUTING.md) voor richtlijnen bij het aanleveren van Pull Requests.  
* [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) voor de gedragsregels.


## 7. Licentie

Dit project valt onder de **MIT License**. Dit is een permissieve open-source licentie die de Hengelsportvereniging Lelystad-Dronten (HVLD) in staat stelt de code vrijelijk te gebruiken, aan te passen en te onderhouden, wat cruciaal is voor de langetermijncontinuïteit van dit project. Zie het [LICENSE.md](LICENSE.md) bestand voor de volledige tekst.
