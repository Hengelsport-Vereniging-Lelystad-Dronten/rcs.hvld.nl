<script setup>
// ====================================================================
// IMPORTS
// Hier importeren we alle benodigde componenten en functionaliteit.
// ====================================================================
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
// Head: Voor het instellen van de paginatitel.
// useForm: Voor het beheren van formulierstatus (data, validatie, processing).
// router: Voor navigatie en het herladen van props (bijvoorbeeld na een succesvolle actie).
import { Head, useForm, router } from '@inertiajs/vue3';
// ref EN watch: Voor het creëren van reactieve variabelen en het volgen van wijzigingen in Vue.
import { ref, watch } from 'vue';
// Debounce utiliteit voor de API-call op het vispasnummer. Dit voorkomt overbodige calls tijdens het typen.
import { debounce } from 'lodash';
// Standaard UI-componenten
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

// De AdviesOverzicht component is verwijderd, aangezien de gevraagde functionaliteit
// de formulier-specifieke recidive waarschuwing is (zie foto).

// ====================================================================
// PROPS DEFINITIE
// Data die van de Laravel Controller via Inertia wordt doorgegeven.
// ====================================================================
const props = defineProps({
    // De hoofddata van de ronde, inclusief geneste relaties (user, water, overtredingen).
    ronde: Object,
    // Lijst van alle mogelijke overtredingstypes (bevat de default_strafmaat_id en recidive_strafmaat_id).
    overtredingTypes: Array,
    // De volledige lijst van beschikbare Strafmaten (ID en Omschrijving).
    strafmaten: Array,
});

// ====================================================================
// STATE MANAGEMENT (ref) VOOR RECIDIVE CHECK & UI
// Dit is de recidive check die bij de formulierinvoer hoort.
// ====================================================================

// Geeft aan of de API-check op recidive nog bezig is.
const isRecidiveCheckLoading = ref(false);
// De tekstuele status van de recidive check.
const recidiveStatus = ref('');
// De boolean waarde die aangeeft of de visser recidivist is voor dit type overtreding.
const isRecidivist = ref(false);
// Slaat het aantal eerdere overtredingen op.
const recidiveCount = ref(0);
// NIEUW: ID van de maatregel die AUTOMATISCH is geadviseerd door de API (geëscaleerde maatregel).
const geadviseerdeStrafmaatId = ref(null);
// Houdt bij of de ronde nog 'Actief' is, stuurt de weergave van formulieren aan.
const isActief = ref(props.ronde.status === 'Actief');
// Houdt de laadstatus bij tijdens het annuleren/verwijderen.
const isDeleting = ref(false);

// De omschrijving van de maatregel die AUTOMATISCH is voorgesteld.
const suggestedMaatregel = ref('');

// ====================================================================
// HULP FUNCTIES
// ====================================================================

/**
 * Zoekt de omschrijving van de voorgestelde strafmaat op basis van het overtredingstype
 * en de recidive status, of de ID die direct door de API is doorgegeven.
 * @param {number | string} typeId - De ID van het geselecteerde overtredingstype.
 * @param {boolean} isRecidivistFlag - Vlag die aangeeft of de visser recidivist is.
 * @param {number | null} advisedId - OPTIONEEL: De ID van de maatregel die de API heeft geadviseerd (prioriteit).
 * @returns {string} De omschrijving van de voorgestelde strafmaat (default, recidive, of API advies).
 */
const lookupProposedMaatregel = (typeId, isRecidivistFlag, advisedId = null) => {
    // 1. Prioriteit: Gebruik de ID die direct door de API is geadviseerd, als deze aanwezig is bij recidive.
    if (isRecidivistFlag && advisedId) {
        const advisedStrafmaat = props.strafmaten.find(maatregel => maatregel.id === advisedId);
        // Als de API-geadviseerde ID een geldige maatregel oplevert, gebruik die direct.
        if (advisedStrafmaat) {
            return advisedStrafmaat.omschrijving;
        }
    }
    
    // 2. Fallback: Gebruik de lokale logica (default of recidive_strafmaat_id uit de props).
    const selectedType = props.overtredingTypes.find(type => type.id === typeId);

    if (!selectedType || selectedType.code === '00') {
        // ID 00 ('Geen overtreding') of onbekend type.
        return 'Niet van toepassing';
    }

    let strafmaatId;

    // Bepaal welke ID te gebruiken: recidive (uit props) of default.
    // Dit wordt alleen bereikt als het API advies (stap 1) niet werkte.
    if (isRecidivistFlag && selectedType.recidive_strafmaat_id) {
        strafmaatId = selectedType.recidive_strafmaat_id;
    } else {
        strafmaatId = selectedType.default_strafmaat_id;
    }

    // Zoek de omschrijving in de volledige lijst van strafmaten.
    const strafmaat = props.strafmaten.find(maatregel => maatregel.id === strafmaatId);

    // 4. Geef de omschrijving terug, of een fallback.
    return strafmaat ? strafmaat.omschrijving : 'Handmatig Invoeren';
};

/**
 * Update de voorgestelde maatregel (suggestedMaatregel) en zet deze ook als
 * de actuele waarde in het formulier (overtredingForm.genomen_maatregel).
 * @param {boolean | null} recidiveFlag - Optioneel, forceer een recidive vlag. Gebruikt de state als null.
 * @param {number | null} advisedId - De geadviseerde strafmaat ID van de API.
 */
const updateProposedMeasure = (recidiveFlag = isRecidivist.value, advisedId = geadviseerdeStrafmaatId.value) => {
    const newMeasure = lookupProposedMaatregel(
        overtredingForm.overtreding_type_id,
        recidiveFlag,
        advisedId // Geef de geëscaleerde ID door
    );
    // 1. Update de voorgestelde tekst voor het label en de waarschuwingsbox.
    suggestedMaatregel.value = newMeasure;

    // 2. Update de formulierwaarde (deze wordt direct geselecteerd in de dropdown).
    overtredingForm.genomen_maatregel = newMeasure;
};

/**
 * Voert de API-check uit om te bepalen of de visser recidivist is.
 * Dit is de check die hoort bij het vastleggen van een overtreding.
 * @param {string} vispasnummer - Het ingevoerde vispasnummer.
 * @param {number} overtredingTypeId - De ID van het geselecteerde overtredingstype.
 */
const checkRecidive = async (vispasnummer, overtredingTypeId) => {
    // Voorkom onnodige checks als het nummer te kort is.
    if (!vispasnummer || vispasnummer.length < 6) {
        isRecidivist.value = false;
        recidiveStatus.value = '';
        recidiveCount.value = 0;
        geadviseerdeStrafmaatId.value = null; // Reset ID
        updateProposedMeasure(false, null); // Forceer default maatregel
        return;
    }

    isRecidiveCheckLoading.value = true;
    recidiveStatus.value = 'Controleren...';

    // Haal de CSRF token op voor de handmatige POST-request
    // (Aangenomen dat 'route' helper en CSRF-token op de juiste manier beschikbaar zijn in de omgeving)
    const csrfToken = document.querySelector('meta[name="csrf-csrfToken"]')
        ? document.querySelector('meta[name="csrf-csrfToken"]').getAttribute('content')
        : '';
    
    try {
        const response = await fetch(route('api.recidive-check'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                // De CSRF-token is essentieel voor een succesvolle POST in Laravel!
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                vispasnummer: vispasnummer,
                overtreding_type_id: overtredingTypeId
            })
        });

        if (!response.ok) {
            // Als de API niet 200/OK teruggeeft, gooien we een foutmelding.
            throw new Error(`API-fout bij recidive check: ${response.status}`);
        }

        const data = await response.json();

        // Werk de lokale state bij op basis van de API-response
        isRecidivist.value = data.is_recidivist;
        // FIX: Gebruik 'historie_count' i.p.v. 'aantal' om de telling correct uit te lezen.
        recidiveCount.value = data.historie_count || 0; 
        // FIX: Sla de API geadviseerde ID op, deze heeft prioriteit voor de maatregel.
        geadviseerdeStrafmaatId.value = data.geadviseerde_strafmaat_id || null;
        
        recidiveStatus.value = data.is_recidivist
            ? `RECIDIVIST! (${recidiveCount.value}e keer)`
            : 'Geen bekende recidive.';

    } catch (error) {
        console.error("Fout tijdens recidive check:", error);
        isRecidivist.value = false;
        recidiveStatus.value = 'Fout bij controleren.';
        recidiveCount.value = 0;
        geadviseerdeStrafmaatId.value = null; // Reset ID bij fout
    } finally {
        isRecidiveCheckLoading.value = false;
        // Zorg ervoor dat de voorgestelde maatregel direct wordt bijgewerkt
        updateProposedMeasure();
    }
};

// ====================================================================
// FORMULIER LOGICA (useForm)
// ====================================================================

// Bepaal de initiele overtredingstype ID (altijd de eerste in de lijst, of leeg)
const initialTypeId = props.overtredingTypes.length > 0 ? props.overtredingTypes[0].id : '';
// Bepaal de initiele maatregel omschrijving op basis van het start-type
const initialMaatregel = lookupProposedMaatregel(initialTypeId, false, null);

// Initialiseer de suggestedMaatregel direct bij opstarten
suggestedMaatregel.value = initialMaatregel;

// 1. Formulier voor het vastleggen van een NIEUWE Overtreding
const overtredingForm = useForm({
    controle_ronde_id: props.ronde.id,
    overtreding_type_id: initialTypeId,
    vispasnummer: '',
    // De formulierwaarde wordt direct op de voorgestelde waarde gezet.
    genomen_maatregel: initialMaatregel,
    details: '',
});

// 2. Formulier voor het AFSLUITEN van de ronde
const afrondForm = useForm({
    ronde_id: props.ronde.id,
    opmerkingen: props.ronde.opmerkingen || '',
});

// ====================================================================
// DYNAMISCHE LOGICA (WATCHERS)
// ====================================================================

// Watcher 1: Kijkt naar veranderingen in het geselecteerde overtredingstype ID.
watch(() => overtredingForm.overtreding_type_id, (newTypeId) => {
    // 1. Werk de voorgestelde maatregel bij op basis van de HUIDIGE recidive status.
    updateProposedMeasure();

    // 2. Als er al een vispasnummer is, voer de recidive check opnieuw uit voor dit nieuwe type (met debounce).
    if (overtredingForm.vispasnummer) {
        debouncedCheckRecidive(overtredingForm.vispasnummer, newTypeId);
    }
});

// Watcher 2: Kijkt naar veranderingen in het Vispasnummer (met debounce voor prestaties).
// We gebruiken debounce om de API-call pas uit te voeren nadat de gebruiker 500ms gestopt is met typen.
const debouncedCheckRecidive = debounce((newVispasnummer, typeId) => {
    checkRecidive(newVispasnummer, typeId);
}, 500);

watch(() => overtredingForm.vispasnummer, (newVispasnummer) => {
    // Start de gedebouncete check
    debouncedCheckRecidive(newVispasnummer, overtredingForm.overtreding_type_id);

    // Reset de recidive status en maatregel direct als het veld leeg is of te kort.
    if (!newVispasnummer || newVispasnummer.length < 6) {
        isRecidivist.value = false;
        recidiveStatus.value = '';
        recidiveCount.value = 0; 
        geadviseerdeStrafmaatId.value = null; // NIEUW: Reset ID
        updateProposedMeasure(false, null); // Forceer de default maatregel
    }
});

// ====================================================================
// ACTIES / FUNCTIES
// ====================================================================

/**
 * Verstuurt het formulier om een nieuwe overtreding op te slaan.
 */
const submitOvertreding = () => {
    // Maakt een POST-verzoek naar de 'overtredingen.store' route.
    overtredingForm.post(route('overtredingen.store'), {
        preserveScroll: true, // Zorgt dat de scrollpositie behouden blijft.
        onSuccess: () => {
            // Herlaad de 'ronde' prop om de overtredingenlijst bij te werken (via Inertia).
            router.reload({ only: ['ronde'] });
            
            // Reset de invoervelden van het formulier (behalve de ronde-ID en het type).
            overtredingForm.reset('vispasnummer', 'details');
            
            // Reset de status van de recidive check naar de initiële staat.
            isRecidivist.value = false;
            recidiveStatus.value = '';
            recidiveCount.value = 0;
            geadviseerdeStrafmaatId.value = null; // Reset ID
            
            // Werk de maatregel bij naar de default van het geselecteerde type (voor de volgende overtreding).
            updateProposedMeasure(false);
        },
    });
};

/**
 * Verstuurt het formulier om de ronde definitief af te sluiten (status wijzigen naar Afgerond).
 */
const sluitRondeAf = () => {
    // Maakt een PUT-verzoek naar de custom 'controles.afronden' route.
    afrondForm.put(route('controles.afronden', afrondForm.ronde_id), {
        onSuccess: () => {
            // Werk de lokale state bij, waardoor de formulieren verdwijnen en de afrondingstekst verschijnt.
            isActief.value = false;
        },
    });
};

/**
 * Annuleert en verwijdert de controle ronde (inclusief alle overtredingen).
 */
const annuleerRonde = () => {
    // Vraag om bevestiging.
    if (confirm('Weet je zeker dat je deze ronde wilt annuleren? Alle vastgelegde overtredingen gaan verloren!')) {
        isDeleting.value = true;
        // Maakt een DELETE-verzoek naar de 'controles.destroy' route.
        router.delete(route('controles.destroy', props.ronde.id), {
            onFinish: () => {
                // isDeleting wordt ook gereset na een succesvolle/mislukte omleiding.
                isDeleting.value = false;
            },
        });
    }
};
</script>

<template>
    <!-- Stelt de paginatitel in (getoond in de browser tab) -->
    <Head :title="'Ronde: ' + ronde.water.naam" />

    <!-- Gebruikt de algemene geauthenticeerde lay-out -->
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Controle Ronde: {{ ronde.water.naam }}
                <!-- Status Badge -->
                <span :class="['ml-3 px-3 py-1 text-sm font-bold rounded-full', isActief ? 'bg-green-600 text-white' : 'bg-blue-600 text-white']">
                    {{ isActief ? 'ACTIEF' : 'AFGEROND' }}
                </span>
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Sectie 1: Ronde Details -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-8 border-l-4 border-indigo-500">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Ronde Overzicht</h3>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                        <dt class="font-semibold text-gray-700">Water:</dt> <dd>{{ ronde.water.naam }}</dd>
                        <dt class="font-semibold text-gray-700">Controller:</dt> <dd>{{ ronde.user.name }}</dd>
                        <dt class="font-semibold text-gray-700">Start Tijd:</dt> <dd>{{ new Date(ronde.start_tijd).toLocaleString('nl-NL') }}</dd>
                        <dt class="font-semibold text-gray-700">Eind Tijd:</dt> <dd>{{ ronde.eind_tijd ? new Date(ronde.eind_tijd).toLocaleString('nl-NL') : 'N.V.T.' }}</dd>
                    </dl>
                </div>
                
                <!-- Sectie 2 (Oude AdviesOverzicht): Deze sectie is verwijderd conform uw wens. -->


                <!-- Sectie 3: Geregistreerde Overtredingen Lijst -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Geregistreerde Overtredingen ({{ ronde.overtredingen.length }})</h3>
                    
                    <!-- Bericht indien de lijst leeg is -->
                    <div v-if="ronde.overtredingen.length === 0" class="text-gray-500 italic p-4 border border-gray-100 rounded-md">
                        Nog geen overtredingen vastgelegd in deze ronde.
                    </div>
                    
                    <!-- Lijst met overtredingen -->
                    <ul v-else class="space-y-4">
                        <li v-for="overtreding in ronde.overtredingen" :key="overtreding.id" class="p-4 border border-red-200 rounded-lg bg-red-50 hover:bg-red-100 transition duration-150">
                            <p class="font-bold text-red-800">
                                <span class="text-sm mr-2 text-red-600">Overtreding:</span>
                                {{ overtreding.overtreding_type.code }} - {{ overtreding.overtreding_type.omschrijving }}
                            </p>
                            <p class="text-sm mt-1 text-gray-700">
                                <span class="font-semibold">Maatregel:</span> {{ overtreding.genomen_maatregel }}
                            </p>
                            <p v-if="overtreding.vispasnummer" class="text-sm text-gray-700">
                                <span class="font-semibold">Vispasnr:</span> {{ overtreding.vispasnummer }}
                            </p>
                            <p v-if="overtreding.details" class="text-xs italic text-gray-600 mt-1">
                                <span class="font-semibold">Details:</span> {{ overtreding.details }}
                            </p>
                        </li>
                    </ul>
                </div>


                <!-- Sectie 4: Formulier Container: Alleen zichtbaar als de ronde isActief -->
                <div v-if="isActief" class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <!-- Paneel 1: Nieuwe Overtreding Registreren -->
                    <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg p-6 border-t-4 border-red-500">
                        <h3 class="text-xl font-bold text-gray-900 mb-6">Nieuwe Overtreding Registreren</h3>
                        
                        <!-- DEZE FORM ZORGT VOOR DE Inertia POST (geen full page reload) -->
                        <form @submit.prevent="submitOvertreding">
                            
                            <!-- Veld: Overtreding Type -->
                            <div class="mb-4">
                                <InputLabel for="type" value="Overtreding Type" />
                                <select
                                    id="type"
                                    v-model="overtredingForm.overtreding_type_id"
                                    required
                                    class="mt-1 block w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm"
                                >
                                    <!-- Opties uit de database prop -->
                                    <option v-for="type in overtredingTypes" :key="type.id" :value="type.id">
                                        {{ type.code }} - {{ type.omschrijving }}
                                    </option>
                                </select>
                                <InputError :message="overtredingForm.errors.overtreding_type_id" class="mt-2" />
                            </div>

                            <!-- Veld: Vispasnummer MET Recidive Status -->
                            <div class="mb-4">
                                <InputLabel for="vispasnummer" value="Vispasnummer (Optioneel)" />
                                <TextInput
                                    id="vispasnummer"
                                    v-model="overtredingForm.vispasnummer"
                                    type="text"
                                    class="mt-1 block w-full"
                                    autocomplete="off"
                                    placeholder="Voer vispasnummer in..."
                                />
                                <InputError :message="overtredingForm.errors.vispasnummer" class="mt-2" />
                                
                                <!-- Weergave van de Recidive Status onder het veld -->
                                <div class="mt-2 text-sm" :class="{
                                    'text-gray-500': !recidiveStatus,
                                    'text-blue-500': isRecidiveCheckLoading,
                                    'text-red-600 font-bold': isRecidivist,
                                    'text-green-600': recidiveStatus && !isRecidivist && !isRecidiveCheckLoading
                                }">
                                    <span v-if="isRecidiveCheckLoading">
                                        <!-- Loading spinner (Tailwind utility) -->
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ recidiveStatus }}
                                    </span>
                                    <span v-else>{{ recidiveStatus || 'Typ een vispasnummer om op recidive te controleren.' }}</span>
                                </div>
                            </div>
                            
                            <!-- ESCALATIE WAARSCHUWINGSBOX (als recidivist) -->
                            <div v-if="isRecidivist" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md relative mb-4 shadow-sm" role="alert">
                                <p class="font-bold">⚠️ RECIDIVE GEVAAR - ESCALATIE GEADVISEERD!</p>
                                <p class="text-sm mt-1">
                                    <!-- De lookback periode is aangepast naar 24 maanden, volgens de API output -->
                                    Visser had {{ recidiveCount }} overtreding(en) in de laatste 24 maanden.
                                    Escalatie van WA naar HG geadviseerd.
                                </p>
                                <p class="font-semibold text-sm mt-2">Geadviseerde Escalatie:</p>
                                <!-- We tonen de voorgestelde maatregel als het advies -->
                                <div class="text-sm pl-2 mt-1 whitespace-pre-line text-red-800 font-medium">- {{ suggestedMaatregel }}</div>
                            </div>


                            <!-- Veld: Genomen Maatregel (Aangepast Label) -->
                            <div class="mb-4">
                                <!-- AANGEPAST LABEL: Toont de voorgestelde maatregel in het label -->
                                <InputLabel for="maatregel">
                                    Gekozen Maatregel
                                    <span class="text-xs text-gray-500 ml-2">
                                        (Voorgesteld: <span :class="{'font-bold text-red-600': isRecidivist, 'font-medium text-green-600': !isRecidivist && suggestedMaatregel !== 'Niet van toepassing'}">{{ suggestedMaatregel }}</span>)
                                    </span>
                                </InputLabel>
                                
                                <select
                                    id="maatregel"
                                    v-model="overtredingForm.genomen_maatregel"
                                    required
                                    class="mt-1 block w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm"
                                >
                                    <!-- DYNAMISCHE OPTIES uit de 'strafmaten' prop. De v-model selecteert de suggestedMaatregel. -->
                                    <option v-for="maatregel in strafmaten" :key="maatregel.id" :value="maatregel.omschrijving">
                                        {{ maatregel.omschrijving }}
                                    </option>
                                    <option value="Anders">Anders (Handmatig invullen in Details)</option>
                                </select>
                                <InputError :message="overtredingForm.errors.genomen_maatregel" class="mt-2" />
                            </div>

                            <!-- Veld: Details / Opmerkingen Overtreding -->
                            <div class="mb-6">
                                <InputLabel for="details" value="Details / Opmerkingen Overtreding" />
                                <textarea
                                    id="details"
                                    v-model="overtredingForm.details"
                                    rows="3"
                                    class="mt-1 block w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm"
                                    placeholder="Bijvoorbeeld: Exacte locatie, toelichting op de maatregel, of aanvullende gegevens."
                                ></textarea>
                                <InputError :message="overtredingForm.errors.details" class="mt-2" />
                            </div>

                            <!-- Knop: Overtreding Vastleggen -->
                            <PrimaryButton 
                                :class="{ 'opacity-25': overtredingForm.processing }" 
                                :disabled="overtredingForm.processing || isRecidiveCheckLoading || overtredingForm.overtreding_type_id === initialTypeId && overtredingTypes[0].code === '00'"
                                class="w-full justify-center bg-red-600 hover:bg-red-700 active:bg-red-800"
                            >
                                <span v-if="overtredingForm.processing">Vastleggen...</span>
                                <span v-else>Overtreding Vastleggen</span>
                            </PrimaryButton>
                        </form>
                    </div>

                    <!-- Paneel 2: Controle Ronde Afronden en Annuleren -->
                    <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg p-6 border-t-4 border-green-500">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Controle Ronde Afronden</h3>
                        <p class="text-sm text-gray-600 mb-6">Sluit de ronde af om de gegevens definitief op te slaan, te archiveren en naar de rapportage te sturen.</p>
                        
                        <form @submit.prevent="sluitRondeAf">
                            <!-- Veld: Algemene Opmerkingen Ronde -->
                            <div class="mb-6">
                                <InputLabel for="opmerkingen_ronde" value="Algemene Opmerkingen Ronde (Optioneel)" />
                                <textarea
                                    id="opmerkingen_ronde"
                                    v-model="afrondForm.opmerkingen"
                                    rows="4"
                                    class="mt-1 block w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm"
                                    :disabled="afrondForm.processing || !isActief"
                                    placeholder="Eventuele opmerkingen over de gehele ronde, waarnemingen, etc."
                                ></textarea>
                                <InputError :message="afrondForm.errors.opmerkingen" class="mt-2" />
                            </div>
                            
                            <!-- Knop: Ronde Definitief Afronden -->
                            <PrimaryButton 
                                type="submit" 
                                :class="{ 'opacity-25': afrondForm.processing }" 
                                :disabled="afrondForm.processing || !isActief" 
                                class="w-full justify-center bg-green-600 hover:bg-green-700 active:bg-green-800"
                            >
                                <span v-if="afrondForm.processing">Bezig met afronden...</span>
                                <span v-else>Ronde Definitief Afronden</span>
                            </PrimaryButton>
                        </form>

                        <!-- Sectie: Ronde Annuleren -->
                        <div class="mt-8 pt-4 border-t border-gray-200">
                            <h4 class="font-medium text-gray-700 mb-2">Ronde Permanent Verwijderen</h4>
                            <p class="text-xs text-red-500 mb-3">
                                <span class="font-bold">Waarschuwing:</span> Hiermee wordt de ronde permanent verwijderd, inclusief alle geregistreerde overtredingen. Dit kan niet ongedaan gemaakt worden.
                            </p>
                            
                            <!-- Knop: Annuleer Ronde -->
                            <button
                                @click="annuleerRonde"
                                :disabled="isDeleting || !isActief"
                                type="button"
                                :class="{ 'opacity-25': isDeleting || !isActief }"
                                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:bg-gray-400"
                            >
                                {{ isDeleting ? 'Bezig met annuleren...' : 'Annuleer Ronde' }}
                            </button>
                        </div>
                    </div>

                </div>
                
                <!-- Sectie 5: Weergave als de ronde AFGEROND is -->
                <div v-else class="bg-blue-50 border border-blue-200 text-blue-800 p-6 rounded-lg shadow-md mt-8">
                    <p class="font-bold text-lg">✅ Ronde Afgerond</p>
                    <p class="text-sm mt-1">Deze ronde is definitief afgesloten en gearchiveerd. Er kunnen geen nieuwe overtredingen meer aan worden toegevoegd.</p>
                    <p v-if="ronde.opmerkingen" class="mt-3 text-sm italic border-t border-blue-200 pt-2">Algemene Opmerkingen: {{ ronde.opmerkingen }}</p>
                </div>
                
            </div>
        </div>
    </AuthenticatedLayout>
</template>