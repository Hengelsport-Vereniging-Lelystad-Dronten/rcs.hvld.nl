<script setup>
// ====================================================================
// IMPORTS
// ====================================================================
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

// ====================================================================
// PROPS DEFINITIE
// ====================================================================
const props = defineProps({
    ronde: Object,
    overtredingTypes: Array,
    strafmaten: Array,
});

// ====================================================================
// STATE MANAGEMENT (ref)
// ====================================================================
const isRecidiveCheckLoading = ref(false);
const recidiveStatus = ref('');
const isRecidivist = ref(false);
const recidiveCount = ref(0);
const isActief = ref(props.ronde.status === 'Actief');
const isDeleting = ref(false);
const suggestedMaatregel = ref('');

// ====================================================================
// HULP FUNCTIES
// ====================================================================
const lookupProposedMaatregel = (typeId, isRecidivistFlag) => {
    const selectedType = props.overtredingTypes.find(type => type.id === typeId);

    if (!selectedType || selectedType.code === '00') {
        return 'Niet van toepassing';
    }

    if (isRecidivistFlag && selectedType.recidive_strafmaat) {
        return selectedType.recidive_strafmaat.omschrijving;
    } 
    
    if (selectedType.default_strafmaat) {
        return selectedType.default_strafmaat.omschrijving;
    }

    return 'Handmatig Invoeren';
};

const updateProposedMeasure = (recidiveFlag = isRecidivist.value) => {
    const newMeasure = lookupProposedMaatregel(
        overtredingForm.overtreding_type_id,
        recidiveFlag
    );
    suggestedMaatregel.value = newMeasure;
    overtredingForm.genomen_maatregel = newMeasure;
};

const checkRecidive = async (vispasnummer, overtredingTypeId) => {
    if (!vispasnummer || vispasnummer.length < 6) {
        isRecidivist.value = false;
        recidiveStatus.value = '';
        recidiveCount.value = 0;
        updateProposedMeasure(false);
        return;
    }

    isRecidiveCheckLoading.value = true;
    recidiveStatus.value = 'Controleren...';

    const csrfToken = document.querySelector('meta[name="csrf-csrfToken"]')
        ? document.querySelector('meta[name="csrf-csrfToken"]').getAttribute('content')
        : '';
    
    try {
        const response = await fetch(route('api.recidive-check'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                vispasnummer: vispasnummer,
                overtreding_type_id: overtredingTypeId
            })
        });

        if (!response.ok) {
            throw new Error(`API-fout bij recidive check: ${response.status}`);
        }

        const data = await response.json();

        isRecidivist.value = data.is_recidivist;
        recidiveCount.value = data.historie_count || 0;
        
        recidiveStatus.value = data.is_recidivist
            ? `RECIDIVIST! (${recidiveCount.value}e keer)`
            : 'Geen bekende recidive.';

        // --- FIX START ---
        // Als de API een geadviseerde strafmaat ID teruggeeft, gebruik die dan direct.
        if (data.geadviseerde_strafmaat_id) {
            const geadviseerdeStrafmaat = props.strafmaten.find(s => s.id === data.geadviseerde_strafmaat_id);
            if (geadviseerdeStrafmaat) {
                // Update direct de `genomen_maatregel` van het formulier
                overtredingForm.genomen_maatregel = geadviseerdeStrafmaat.omschrijving;
                // Update ook de `suggestedMaatregel` voor de UI-feedback
                suggestedMaatregel.value = geadviseerdeStrafmaat.omschrijving;
            }
        }
        // --- FIX END ---

    } catch (error) {
        console.error("Fout tijdens recidive check:", error);
        isRecidivist.value = false;
        recidiveStatus.value = 'Fout bij controleren.';
        recidiveCount.value = 0;
    } finally {
        isRecidiveCheckLoading.value = false;
    }
};

// ====================================================================
// FORMULIER LOGICA (useForm)
// ====================================================================
const initialTypeId = props.overtredingTypes.length > 0 ? props.overtredingTypes[0].id : '';

// Find the initial strafmaat based on lookupProposedMaatregel and then get its ID
const initialMaatregelOmschrijving = lookupProposedMaatregel(initialTypeId, false);
const initialStrafmaat = props.strafmaten.find(s => s.omschrijving === initialMaatregelOmschrijving);
const initialStrafmaatId = initialStrafmaat ? initialStrafmaat.id : '';

suggestedMaatregel.value = initialMaatregelOmschrijving;

const overtredingForm = useForm({

    controle_ronde_id: props.ronde.id,

    overtreding_type_id: initialTypeId,

    vispasnummer: '',

    strafmaat_id: initialStrafmaatId, // Gebruik ID voor opslag

    genomen_maatregel: initialMaatregelOmschrijving, // Gebruik omschrijving voor display en potentieel fallback

    vispas_ingenomen: false,

    details: '',

});



const getLocalDateTime = () => {

    const now = new Date();

    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());

    return now.toISOString().slice(0, 16);

};



const afrondForm = useForm({

    ronde_id: props.ronde.id,

    opmerkingen: props.ronde.opmerkingen || '',

    eind_tijd: getLocalDateTime(),

});



// ====================================================================

// DYNAMISCHE LOGICA (WATCHERS)

// ====================================================================

watch(() => overtredingForm.overtreding_type_id, (newTypeId) => {

    // Determine the proposed measure based on the new type, but don't set the ID directly here yet.

    const newProposedOmschrijving = lookupProposedMaatregel(newTypeId, isRecidivist.value);

    suggestedMaatregel.value = newProposedOmschrijving;

    

    // Find the corresponding strafmaat ID if it exists and update the form

    const newProposedStrafmaat = props.strafmaten.find(s => s.omschrijving === newProposedOmschrijving);

    overtredingForm.strafmaat_id = newProposedStrafmaat ? newProposedStrafmaat.id : '';

    overtredingForm.genomen_maatregel = newProposedOmschrijving; // Keep this for now for the form display



    if (overtredingForm.vispasnummer) {

        debouncedCheckRecidive(overtredingForm.vispasnummer, newTypeId);

    }

});



const debouncedCheckRecidive = debounce((newVispasnummer, typeId) => {

    checkRecidive(newVispasnummer, typeId);

}, 500);



watch(() => overtredingForm.vispasnummer, (newVispasnummer) => {

    debouncedCheckRecidive(newVispasnummer, overtredingForm.overtreding_type_id);



    if (!newVispasnummer || newVispasnummer.length < 6) {

        isRecidivist.value = false;

        recidiveStatus.value = '';

        recidiveCount.value = 0; 

        

        // Reset to initial proposed measure when vispasnummer is cleared

        const newProposedOmschrijving = lookupProposedMaatregel(overtredingForm.overtreding_type_id, false);

        const newProposedStrafmaat = props.strafmaten.find(s => s.omschrijving === newProposedOmschrijving);

        overtredingForm.strafmaat_id = newProposedStrafmaat ? newProposedStrafmaat.id : '';

        suggestedMaatregel.value = newProposedOmschrijving;

        overtredingForm.genomen_maatregel = newProposedOmschrijving;

    }

});



// ====================================================================

// ACTIES / FUNCTIES

// ====================================================================

const submitOvertreding = () => {

    // Ensure the `genomen_maatregel` (omschrijving) matches the selected ID for consistency,

    // although the backend should primarily use `strafmaat_id`.

    const selectedStrafmaat = props.strafmaten.find(s => s.id === overtredingForm.strafmaat_id);

    overtredingForm.genomen_maatregel = selectedStrafmaat ? selectedStrafmaat.omschrijving : 'Niet Gevonden';



    overtredingForm.post(route('overtredingen.store'), {

        preserveScroll: true,

        onSuccess: () => {

            router.reload({ only: ['ronde'] });

            overtredingForm.reset('vispasnummer', 'details', 'vispas_ingenomen');

            isRecidivist.value = false;

            recidiveStatus.value = '';

            recidiveCount.value = 0;

            

            // Reset to initial proposed measure after successful submission

            const newProposedOmschrijving = lookupProposedMaatregel(overtredingForm.overtreding_type_id, false);

            const newProposedStrafmaat = props.strafmaten.find(s => s.omschrijving === newProposedOmschrijving);

            overtredingForm.strafmaat_id = newProposedStrafmaat ? newProposedStrafmaat.id : '';

            suggestedMaatregel.value = newProposedOmschrijving;

            overtredingForm.genomen_maatregel = newProposedOmschrijving;

        },

    });

};



const sluitRondeAf = () => {

    afrondForm.put(route('controles.afronden', afrondForm.ronde_id), {

        onSuccess: () => {

            isActief.value = false;

        },

    });

};



const annuleerRonde = () => {

    if (confirm('Weet je zeker dat je deze ronde wilt annuleren? Alle vastgelegde overtredingen gaan verloren!')) {

        isDeleting.value = true;

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

                            <p v-if="overtreding.vispas_ingenomen" class="text-sm text-red-700 font-bold">

                                <span class="font-semibold">Status:</span> VISpas Ingenomen

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

                                    Visser had {{ recidiveCount }} overtreding(en) in de laatste 12 maanden.

                                </p>

                                <p class="font-semibold text-sm mt-2">Geadviseerde Escalatie:</p>

                                <!-- We tonen de voorgestelde maatregel als het advies -->

                                <div class="text-sm pl-2 mt-1 whitespace-pre-line text-red-800 font-medium">- {{ suggestedMaatregel }}</div>

                            </div>



                            <!-- Veld: Checkbox voor 'Pas ingenomen' -->

                            <!-- Dit veld verschijnt als de voorgestelde maatregel de term 'Inname' of 'Ontbinding' bevat. -->

                            <div v-if="suggestedMaatregel.includes('Inname') || suggestedMaatregel.includes('Ontbinding') || suggestedMaatregel.includes('Politie') || suggestedMaatregel.includes('Justitie')" class="mb-4 bg-yellow-50 border border-yellow-300 p-3 rounded-md">                                <label for="vispas_ingenomen" class="flex items-center">

                                    <input

                                        id="vispas_ingenomen"

                                        type="checkbox"

                                        v-model="overtredingForm.vispas_ingenomen"

                                        class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500"

                                    />

                                    <span class="ml-2 text-sm font-medium text-gray-800">VISpas daadwerkelijk ingenomen</span>

                                </label>

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

                            <!-- Veld: Eindtijd -->

                            <div class="mb-4">

                                <InputLabel for="eind_tijd" value="Eindtijd" />

                                <TextInput

                                    id="eind_tijd"

                                    type="datetime-local"

                                    class="mt-1 block w-full"

                                    v-model="afrondForm.eind_tijd"

                                    required

                                />

                                <InputError class="mt-2" :message="afrondForm.errors.eind_tijd" />

                            </div>



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

                        <!-- Alleen zichtbaar indien nog geen overtredingen zijn geregistreerd -->

                        <div v-if="ronde.overtredingen.length === 0" class="mt-8 pt-4 border-t border-gray-200">

                            <h4 class="font-medium text-gray-700 mb-2">Ronde Permanent Verwijderen</h4>

.                            <p class="text-xs text-red-500 mb-3">

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
