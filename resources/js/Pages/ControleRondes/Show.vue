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
// Standaard UI-componenten
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

// ====================================================================
// PROPS DEFINITIE
// Data die van de Laravel Controller via Inertia wordt doorgegeven.
// ====================================================================
const props = defineProps({
    // De hoofddata van de ronde, inclusief geneste relaties (user, water, overtredingen).
    ronde: Object, 
    // Lijst van alle mogelijke overtredingstypes (bevat de default_strafmaat_id).
    overtredingTypes: Array, 
    // NIEUW: De volledige lijst van beschikbare Strafmaten (ID en Omschrijving).
    strafmaten: Array,
});

// ====================================================================
// HULP FUNCTIES
// ====================================================================

/**
 * Zoekt de omschrijving van de standaardstrafmaat op basis van de geselecteerde overtreding type ID.
 * Dit is de kernlogica om de dynamische koppeling te maken.
 * @param {number | string} typeId - De ID van het geselecteerde overtredingstype.
 * @returns {string} De omschrijving van de standaardstrafmaat.
 */
const lookupDefaultMaatregelOmschrijving = (typeId) => {
    // 1. Zoek het geselecteerde overtredingstype om de Foreign Key (default_strafmaat_id) te vinden.
    const selectedType = props.overtredingTypes.find(type => type.id === typeId);

    if (!selectedType) {
        // Als het type onbekend is, of als de ID 00 ('Geen overtreding') is, gebruik 'Niet van toepassing'.
        return 'Niet van toepassing';
    }

    // 2. Gebruik de Foreign Key om de volledige strafmaat omschrijving op te zoeken.
    const defaultStrafmaatId = selectedType.default_strafmaat_id;
    
    // Zoek de omschrijving in de volledige lijst van strafmaten (props.strafmaten).
    const defaultStrafmaat = props.strafmaten.find(maatregel => maatregel.id === defaultStrafmaatId);

    // 3. Geef de omschrijving terug, of een fallback als er geen default is ingesteld.
    return defaultStrafmaat ? defaultStrafmaat.omschrijving : 'Handmatig Invoeren';
};

// ====================================================================
// FORMULIER LOGICA (useForm)
// ====================================================================

// Bepaal de initiele overtredingstype ID (gebruik de eerste in de lijst als default)
const initialTypeId = props.overtredingTypes.length > 0 ? props.overtredingTypes[0].id : '';

// 1. Formulier voor het vastleggen van een NIEUWE Overtreding
const overtredingForm = useForm({
    controle_ronde_id: props.ronde.id,
    overtreding_type_id: initialTypeId,
    vispasnummer: '',
    // Gebruik de nieuwe lookup functie om de initiële standaardmaatregel te bepalen.
    genomen_maatregel: lookupDefaultMaatregelOmschrijving(initialTypeId), 
    details: '',
});

// 2. Formulier voor het AFSLUITEN van de ronde
const afrondForm = useForm({
    ronde_id: props.ronde.id,
    opmerkingen: props.ronde.opmerkingen || '', 
});

// ====================================================================
// STATE MANAGEMENT (ref)
// ====================================================================

// Houdt bij of de ronde nog 'Actief' is, stuurt de weergave van formulieren aan.
const isActief = ref(props.ronde.status === 'Actief');
// Houdt de laadstatus bij tijdens het annuleren/verwijderen.
const isDeleting = ref(false);

// ====================================================================
// DYNAMISCHE LOGICA (WATCHER)
// ====================================================================

/**
 * Watcher: Kijkt naar veranderingen in het geselecteerde overtredingstype ID.
 * Bij een wijziging wordt automatisch de 'genomen_maatregel' bijgewerkt met
 * de voorgestelde standaardmaatregel van dat type, opgezocht in de 'strafmaten' lijst.
 */
watch(() => overtredingForm.overtreding_type_id, (newTypeId) => {
    // Roep de lookup functie aan om de juiste omschrijving op te halen
    const defaultOmschrijving = lookupDefaultMaatregelOmschrijving(newTypeId);
    
    // Update de genomen maatregel in het formulier.
    overtredingForm.genomen_maatregel = defaultOmschrijving;
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
        preserveScroll: true,
        onSuccess: () => {
            // Herlaad de 'ronde' prop om de overtredingenlijst bij te werken.
            router.reload({ only: ['ronde'] });
            // Reset de invoervelden.
            overtredingForm.reset('vispasnummer', 'details');
        },
    });
};

/**
 * Verstuurt het formulier om de ronde definitief af te sluiten (status wijzigen).
 */
const sluitRondeAf = () => {
    // Maakt een PUT-verzoek naar de custom 'controles.afronden' route.
    afrondForm.put(route('controles.afronden', afrondForm.ronde_id), {
        onSuccess: () => {
            // Werk de lokale state bij.
            isActief.value = false;
        },
    });
};

/**
 * Annuleert en verwijdert de controle ronde (inclusief alle overtredingen).
 */
const annuleerRonde = () => {
    // Vraag om bevestiging (gebruik in productie een custom modal i.p.v. confirm()).
    if (confirm('Weet je zeker dat je deze ronde wilt annuleren? Alle vastgelegde overtredingen gaan verloren!')) {
        isDeleting.value = true;
        // Maakt een DELETE-verzoek naar de 'controles.destroy' route.
        router.delete(route('controles.destroy', props.ronde.id), {
            onFinish: () => {
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
                
                <!-- Sectie: Ronde Details -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Ronde Details</h3>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                        <dt class="font-bold">Water:</dt> <dd>{{ ronde.water.naam }}</dd>
                        <dt class="font-bold">Controller:</dt> <dd>{{ ronde.user.name }}</dd>
                        <dt class="font-bold">Start Tijd:</dt> <dd>{{ new Date(ronde.start_tijd).toLocaleString('nl-NL') }}</dd>
                        <dt class="font-bold">Eind Tijd:</dt> <dd>{{ ronde.eind_tijd ? new Date(ronde.eind_tijd).toLocaleString('nl-NL') : 'N.V.T.' }}</dd>
                    </dl>
                </div>

                <!-- Sectie: Geregistreerde Overtredingen Lijst -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Geregistreerde Overtredingen ({{ ronde.overtredingen.length }})</h3>
                    
                    <!-- Bericht indien de lijst leeg is -->
                    <div v-if="ronde.overtredingen.length === 0" class="text-gray-500">
                        Nog geen overtredingen vastgelegd in deze ronde.
                    </div>
                    
                    <!-- Lijst met overtredingen -->
                    <ul v-else class="space-y-4">
                        <li v-for="overtreding in ronde.overtredingen" :key="overtreding.id" class="p-4 border border-gray-200 rounded-md bg-red-50">
                            <p class="font-bold text-red-800">Code: {{ overtreding.overtreding_type.code }} - {{ overtreding.overtreding_type.omschrijving }}</p>
                            <p class="text-sm mt-1">Maatregel: {{ overtreding.genomen_maatregel }}</p>
                            <p v-if="overtreding.vispasnummer" class="text-sm">Vispasnr: {{ overtreding.vispasnummer }}</p>
                            <p v-if="overtreding.details" class="text-sm italic text-gray-600">Details: {{ overtreding.details }}</p>
                        </li>
                    </ul>
                </div>


                <!-- Formulier Container: Alleen zichtbaar als de ronde isActief -->
                <div v-if="isActief" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                    <!-- Paneel 1: Nieuwe Overtreding Registreren -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Nieuwe Overtreding Registreren</h3>
                        <form @submit.prevent="submitOvertreding">
                            
                            <!-- Veld: Overtreding Type -->
                            <div class="mb-4">
                                <InputLabel for="type" value="Overtreding Type" />
                                <select
                                    id="type"
                                    v-model="overtredingForm.overtreding_type_id"
                                    required
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                    <!-- Opties uit de database prop -->
                                    <option v-for="type in overtredingTypes" :key="type.id" :value="type.id">
                                        {{ type.code }} - {{ type.omschrijving }}
                                    </option>
                                </select>
                                <InputError :message="overtredingForm.errors.overtreding_type_id" class="mt-2" />
                            </div>

                            <!-- Veld: Vispasnummer -->
                            <div class="mb-4">
                                <InputLabel for="vispasnummer" value="Vispasnummer (Optioneel)" />
                                <TextInput
                                    id="vispasnummer"
                                    v-model="overtredingForm.vispasnummer"
                                    type="text"
                                    class="mt-1 block w-full"
                                    autocomplete="off"
                                />
                                <InputError :message="overtredingForm.errors.vispasnummer" class="mt-2" />
                            </div>

                            <!-- Veld: Genomen Maatregel (Nu Dynamisch Gevuld uit de Database Lijst) -->
                            <div class="mb-4">
                                <!-- Toont de voorgestelde standaardmaatregel van het geselecteerde type -->
                                <InputLabel for="maatregel" :value="'Voorgestelde Maatregel (Standaard: ' + overtredingForm.genomen_maatregel + ')'" />
                                
                                <select
                                    id="maatregel"
                                    v-model="overtredingForm.genomen_maatregel"
                                    required
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                    <!-- DYNAMISCHE OPTIES uit de nieuwe 'strafmaten' prop -->
                                    <option v-for="maatregel in strafmaten" :key="maatregel.id" :value="maatregel.omschrijving">
                                        {{ maatregel.omschrijving }}
                                    </option>
                                    <option value="Anders">Anders, zie details</option>
                                </select>
                                <InputError :message="overtredingForm.errors.genomen_maatregel" class="mt-2" />
                            </div>

                            <!-- Veld: Details / Opmerkingen Overtreding -->
                            <div class="mb-4">
                                <InputLabel for="details" value="Details / Opmerkingen Overtreding" />
                                <textarea
                                    id="details"
                                    v-model="overtredingForm.details"
                                    rows="3"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                ></textarea>
                                <InputError :message="overtredingForm.errors.details" class="mt-2" />
                            </div>

                            <!-- Knop: Overtreding Vastleggen -->
                            <PrimaryButton :class="{ 'opacity-25': overtredingForm.processing }" :disabled="overtredingForm.processing">
                                Overtreding Vastleggen
                            </PrimaryButton>
                        </form>
                    </div>

                    <!-- Paneel 2: Controle Ronde Afronden en Annuleren -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Controle Ronde Afronden</h3>
                        <p class="text-sm text-gray-600 mb-4">Sluit de ronde af om de gegevens definitief op te slaan en te archiveren.</p>
                        
                        <form @submit.prevent="sluitRondeAf">
                            <!-- Veld: Algemene Opmerkingen Ronde -->
                            <div class="mb-4">
                                <InputLabel for="opmerkingen_ronde" value="Algemene Opmerkingen Ronde" />
                                <textarea
                                    id="opmerkingen_ronde"
                                    v-model="afrondForm.opmerkingen"
                                    rows="4"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    :disabled="afrondForm.processing"
                                ></textarea>
                                <InputError :message="afrondForm.errors.opmerkingen" class="mt-2" />
                            </div>
                            
                            <!-- Knop: Ronde Definitief Afronden -->
                            <PrimaryButton 
                                type="submit" 
                                :class="{ 'opacity-25': afrondForm.processing }" 
                                :disabled="afrondForm.processing || !isActief" 
                                class="bg-red-600 hover:bg-red-700"
                            >
                                Ronde Definitief Afronden
                            </PrimaryButton>
                        </form>

                        <!-- Sectie: Ronde Annuleren -->
                        <div class="mt-6 pt-4 border-t border-gray-200">
                            <h4 class="font-medium text-gray-700 mb-2">Ronde Annuleren</h4>
                            <p class="text-xs text-gray-500 mb-3">Hiermee wordt de ronde permanent verwijderd, inclusief alle geregistreerde overtredingen.</p>
                            
                            <!-- Knop: Annuleer Ronde -->
                            <button
                                @click="annuleerRonde"
                                :disabled="isDeleting || !isActief"
                                type="button"
                                :class="{ 'opacity-25': isDeleting }"
                                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                {{ isDeleting ? 'Bezig met annuleren...' : 'Annuleer Ronde' }}
                            </button>
                        </div>
                    </div>

                </div>
                
                <!-- Sectie: Weergave als de ronde AFGEROND is -->
                <div v-else class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-lg">
                    <p class="font-bold">Ronde Afgerond</p>
                    <p class="text-sm">Deze ronde is definitief afgesloten en kan niet meer worden bewerkt of nieuwe overtredingen aan worden toegevoegd.</p>
                    <p v-if="ronde.opmerkingen" class="mt-2 text-sm italic">Algemene Opmerkingen: {{ ronde.opmerkingen }}</p>
                </div>
                
            </div>
        </div>
    </AuthenticatedLayout>
</template>