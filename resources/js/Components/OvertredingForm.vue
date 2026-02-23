<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

// ====================================================================
// PROPS & EMITS
// ====================================================================
const props = defineProps({
    ronde: Object,
    overtredingTypes: Array,
    strafmaten: Array,
    constateringWijzes: Array,
});

const emit = defineEmits(['success']);

const aanleidingOpties = [
    { value: 'melding_van_derde', label: 'Melding van derde', hint: 'Iemand heeft de overtreding doorgegeven.' },
    { value: 'opvallend_gedrag', label: 'Opvallend gedrag', hint: 'Visser gedraagt zich verdacht of herhaaldelijk fout.' },
    { value: 'routinecontrole', label: 'Routinecontrole', hint: 'Onderdeel van standaard toezicht.' },
    { value: 'signalen_van_gevaar', label: 'Signalen van gevaar', hint: 'Bijvoorbeeld recidive of verhoogd risico.' },
    { value: 'overig', label: 'Overig / Specifiek', hint: 'Gebruik toelichting voor uitzonderingen.' },
];

const middelOpties = [
    { value: 'boot_vaartuig', label: 'Boot / Vaartuig', hint: 'Type en nummer indien bekend.' },
    { value: 'hengel_visuitrusting', label: 'Hengel / Visuitrusting', hint: 'Gereedschap dat gebruikt werd.' },
    { value: 'voertuig', label: 'Voertuig', hint: 'Bij vervoer van illegale vangst.' },
    { value: 'net_val_of_hulpmiddel', label: 'Net / Val / Overig hulpmiddel', hint: 'Bij verboden vangstmethodes.' },
    { value: 'overig', label: 'Overig / Toelichting', hint: 'Gebruik toelichting voor unieke gevallen.' },
];

// ====================================================================
// STATE & FORMULIER
// ====================================================================
const isRecidiveCheckLoading = ref(false);
const recidiveStatus = ref('');
const isRecidivist = ref(false);
const recidiveCount = ref(0);
const suggestedMaatregel = ref('');
const aanleidingType = ref('');
const aanleidingToelichting = ref('');
const middelType = ref('');
const middelToelichting = ref('');

const getLocalDateTime = () => {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    return now.toISOString().slice(0, 16);
};

const initialTypeId = props.ronde.water?.default_overtreding_type_id || (props.overtredingTypes.length > 0 ? props.overtredingTypes[0].id : '');

const form = useForm({
    controle_ronde_id: props.ronde.id,

    // --- 7 W's Implementatie ---
    // WAT: Type overtreding
    overtreding_type_id: initialTypeId,
    // WANNEER: Datum en tijdstip van constatering
    geconstateerd_op: getLocalDateTime(),
    // WAAR: Locatie details (als JSON)
    locatie_details: JSON.stringify({
        type: 'water',
        id: props.ronde.water.id,
        naam: props.ronde.water.naam,
    }),
    // HOE: Wijze van constatering
    constatering_wijze: props.constateringWijzes.length > 0 ? props.constateringWijzes[0] : 'visueel',
    // WAAROM: Aanleiding of context
    aanleiding: '',
    // WAARMEE: Middel, voertuig of object
    middel: '',

    // --- Overige velden ---
    vispasnummer: '',
    vispas_ingenomen: false,
    details: '',
});

const geselecteerdeAanleiding = () => aanleidingOpties.find(optie => optie.value === aanleidingType.value);
const geselecteerdeMiddel = () => middelOpties.find(optie => optie.value === middelType.value);

const buildStructuredValue = (selectedValue, selectedLabel, freeText) => {
    if (!selectedValue) return '';
    const trimmedFreeText = freeText.trim();
    if (selectedValue === 'overig') return trimmedFreeText ? `Overig: ${trimmedFreeText}` : 'Overig';
    return trimmedFreeText ? `${selectedLabel}: ${trimmedFreeText}` : selectedLabel;
};

const validateStructuredFields = () => {
    let isValid = true;
    form.clearErrors('aanleiding', 'middel');

    if (aanleidingType.value === 'overig' && !aanleidingToelichting.value.trim()) {
        form.setError('aanleiding', 'Toelichting is verplicht bij "Overig / Specifiek".');
        isValid = false;
    }

    if (middelType.value === 'overig' && !middelToelichting.value.trim()) {
        form.setError('middel', 'Toelichting is verplicht bij "Overig / Toelichting".');
        isValid = false;
    }

    return isValid;
};

const resetStructuredFields = () => {
    aanleidingType.value = '';
    aanleidingToelichting.value = '';
    middelType.value = '';
    middelToelichting.value = '';
    form.aanleiding = '';
    form.middel = '';
};

// ====================================================================
// RECIDIVE LOGICA & WATCHERS
// ====================================================================
const lookupProposedMaatregel = (typeId, isRecidivistFlag) => {
    const selectedType = props.overtredingTypes.find(type => type.id === typeId);
    if (!selectedType) return 'Handmatig Invoeren';
    if (isRecidivistFlag && selectedType.recidive_strafmaat) return selectedType.recidive_strafmaat.omschrijving;
    if (selectedType.default_strafmaat) return selectedType.default_strafmaat.omschrijving;
    return 'Handmatig Invoeren';
};

const checkRecidive = async (vispasnummer, overtredingTypeId) => {
    if (!vispasnummer || vispasnummer.length < 6) {
        isRecidivist.value = false;
        recidiveStatus.value = '';
        recidiveCount.value = 0;
        suggestedMaatregel.value = lookupProposedMaatregel(overtredingTypeId, false);
        return;
    }

    isRecidiveCheckLoading.value = true;
    recidiveStatus.value = 'Controleren...';

    try {
        const response = await fetch(route('api.recidive-check'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ vispasnummer, overtreding_type_id: overtredingTypeId })
        });
        const data = await response.json();

        isRecidivist.value = data.is_recidivist;
        recidiveCount.value = data.historie_count || 0;
        recidiveStatus.value = data.is_recidivist ? `RECIDIVIST! (${recidiveCount.value}e keer)` : 'Geen bekende recidive.';
        
        const geadviseerdeStrafmaat = props.strafmaten.find(s => s.id === data.geadviseerde_strafmaat_id);
        suggestedMaatregel.value = geadviseerdeStrafmaat ? geadviseerdeStrafmaat.omschrijving : lookupProposedMaatregel(overtredingTypeId, data.is_recidivist);

    } catch (error) {
        console.error("Fout tijdens recidive check:", error);
        recidiveStatus.value = 'Fout bij controleren.';
    } finally {
        isRecidiveCheckLoading.value = false;
    }
};

const debouncedCheckRecidive = debounce((vispasnummer, typeId) => checkRecidive(vispasnummer, typeId), 500);

watch(() => form.overtreding_type_id, (newTypeId) => {
    suggestedMaatregel.value = lookupProposedMaatregel(newTypeId, isRecidivist.value);
    if (form.vispasnummer) {
        debouncedCheckRecidive(form.vispasnummer, newTypeId);
    }
});

watch(() => form.vispasnummer, (newVispasnummer) => {
    debouncedCheckRecidive(newVispasnummer, form.overtreding_type_id);
});

// Initial suggested measure
suggestedMaatregel.value = lookupProposedMaatregel(initialTypeId, false);

// ====================================================================
// ACTIES
// ====================================================================
const submitOvertreding = () => {
    if (!validateStructuredFields()) {
        return;
    }

    form.aanleiding = buildStructuredValue(
        aanleidingType.value,
        geselecteerdeAanleiding()?.label || '',
        aanleidingToelichting.value
    );
    form.middel = buildStructuredValue(
        middelType.value,
        geselecteerdeMiddel()?.label || '',
        middelToelichting.value
    );

    form.post(route('overtredingen.store'), {
        preserveScroll: true,
        onSuccess: () => {
            emit('success');
            form.reset('vispasnummer', 'details', 'vispas_ingenomen', 'aanleiding', 'middel');
            resetStructuredFields();
            form.geconstateerd_op = getLocalDateTime(); // Reset time to now
            isRecidivist.value = false;
            recidiveStatus.value = '';
            recidiveCount.value = 0;
            suggestedMaatregel.value = lookupProposedMaatregel(form.overtreding_type_id, false);
        },
    });
};
</script>

<template>
    <div class="p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-6">Nieuwe Overtreding Registreren</h3>
        
        <form @submit.prevent="submitOvertreding" class="space-y-4">

            <!-- WAT: Overtreding Type -->
            <div>
                <InputLabel for="type" value="Wat is de overtreding?" />
                <select id="type" v-model="form.overtreding_type_id" required class="mt-1 block w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm">
                    <option v-for="type in overtredingTypes" :key="type.id" :value="type.id">
                        {{ type.code }} - {{ type.omschrijving }}
                    </option>
                </select>
                <InputError :message="form.errors.overtreding_type_id" class="mt-2" />
            </div>

            <!-- WANNEER: Datum en Tijdstip -->
            <div>
                <InputLabel for="geconstateerd_op" value="Wanneer is het geconstateerd?" />
                <TextInput id="geconstateerd_op" type="datetime-local" v-model="form.geconstateerd_op" required class="mt-1 block w-full" />
                <InputError :message="form.errors.geconstateerd_op" class="mt-2" />
            </div>

            <!-- HOE: Wijze van constatering -->
            <div>
                <InputLabel for="constatering_wijze" value="Hoe is het geconstateerd?" />
                <select id="constatering_wijze" v-model="form.constatering_wijze" required class="mt-1 block w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm">
                    <option v-for="wijze in constateringWijzes" :key="wijze" :value="wijze">{{ wijze.charAt(0).toUpperCase() + wijze.slice(1) }}</option>
                </select>
                <InputError :message="form.errors.constatering_wijze" class="mt-2" />
            </div>

            <!-- Veld: Vispasnummer MET Recidive Status -->
            <div>
                <InputLabel for="vispasnummer" value="Vispasnummer (voor recidive-check)" />
                <TextInput id="vispasnummer" v-model="form.vispasnummer" type="text" class="mt-1 block w-full" autocomplete="off" placeholder="Voer vispasnummer in..." />
                <InputError :message="form.errors.vispasnummer" class="mt-2" />
                <div class="mt-2 text-sm" :class="{ 'text-blue-500': isRecidiveCheckLoading, 'text-red-600 font-bold': isRecidivist, 'text-green-600': recidiveStatus && !isRecidivist && !isRecidiveCheckLoading }">
                    <span v-if="isRecidiveCheckLoading">Controleren...</span>
                    <span v-else>{{ recidiveStatus || 'Typ een vispasnummer om te controleren.' }}</span>
                </div>
            </div>

            <!-- WAAROM: Aanleiding -->
            <div>
                <InputLabel for="aanleiding_type" value="Waarom? (Aanleiding / Reden)" />
                <select
                    id="aanleiding_type"
                    v-model="aanleidingType"
                    class="mt-1 block w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm"
                >
                    <option value="">Kies aanleiding (optioneel)</option>
                    <option v-for="optie in aanleidingOpties" :key="optie.value" :value="optie.value">
                        {{ optie.label }}
                    </option>
                </select>
                <p v-if="geselecteerdeAanleiding()" class="mt-1 text-xs text-gray-500">
                    Hint: {{ geselecteerdeAanleiding().hint }}
                </p>
                <TextInput
                    v-if="aanleidingType === 'overig'"
                    id="aanleiding_toelichting"
                    v-model="aanleidingToelichting"
                    type="text"
                    class="mt-2 block w-full"
                    placeholder="Toelichting verplicht bij overig"
                />
                <TextInput
                    v-else-if="aanleidingType"
                    id="aanleiding_toelichting_optional"
                    v-model="aanleidingToelichting"
                    type="text"
                    class="mt-2 block w-full"
                    placeholder="Optionele aanvulling"
                />
                <InputError :message="form.errors.aanleiding" class="mt-2" />
            </div>

            <!-- WAARMEE: Middel -->
            <div>
                <InputLabel for="middel_type" value="Waarmee? (Object / Middel)" />
                <select
                    id="middel_type"
                    v-model="middelType"
                    class="mt-1 block w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm"
                >
                    <option value="">Kies object/middel (optioneel)</option>
                    <option v-for="optie in middelOpties" :key="optie.value" :value="optie.value">
                        {{ optie.label }}
                    </option>
                </select>
                <p v-if="geselecteerdeMiddel()" class="mt-1 text-xs text-gray-500">
                    Hint: {{ geselecteerdeMiddel().hint }}
                </p>
                <TextInput
                    v-if="middelType === 'overig'"
                    id="middel_toelichting"
                    v-model="middelToelichting"
                    type="text"
                    class="mt-2 block w-full"
                    placeholder="Toelichting verplicht bij overig"
                />
                <TextInput
                    v-else-if="middelType"
                    id="middel_toelichting_optional"
                    v-model="middelToelichting"
                    type="text"
                    class="mt-2 block w-full"
                    placeholder="Optionele aanvulling (bv. type, nummer)"
                />
                <InputError :message="form.errors.middel" class="mt-2" />
            </div>

            <!-- ESCALATIE WAARSCHUWINGSBOX (als recidivist) -->
            <div v-if="isRecidivist" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md relative shadow-sm" role="alert">
                <p class="font-bold">⚠️ RECIDIVE GEVAAR - ESCALATIE GEADVISEERD!</p>
                <p class="font-semibold text-sm mt-2">Geadviseerde Escalatie: <span class="text-red-800 font-medium">{{ suggestedMaatregel }}</span></p>
            </div>

            <!-- Veld: Checkbox voor 'Pas ingenomen' -->
            <div v-if="suggestedMaatregel.includes('Inname') || suggestedMaatregel.includes('Ontbinding') || suggestedMaatregel.includes('Politie') || suggestedMaatregel.includes('Justitie')" class="bg-yellow-50 border border-yellow-300 p-3 rounded-md">
                <label for="vispas_ingenomen" class="flex items-center">
                    <input id="vispas_ingenomen" type="checkbox" v-model="form.vispas_ingenomen" class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500" />
                    <span class="ml-2 text-sm font-medium text-gray-800">VISpas daadwerkelijk ingenomen</span>
                </label>
            </div>

            <!-- Veld: Details / Opmerkingen Overtreding -->
            <div class="pt-2">
                <InputLabel for="details" value="Aanvullende Details (vrije tekst)" />
                <textarea id="details" v-model="form.details" rows="3" class="mt-1 block w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm" placeholder="Aanvullende informatie die niet in de andere velden past."></textarea>
                <InputError :message="form.errors.details" class="mt-2" />
            </div>

            <!-- Knop: Overtreding Vastleggen -->
            <div class="pt-4">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing || isRecidiveCheckLoading" class="w-full justify-center bg-red-600 hover:bg-red-700 active:bg-red-800">
                    <span v-if="form.processing">Vastleggen...</span>
                    <span v-else>Overtreding Vastleggen</span>
                </PrimaryButton>
            </div>

            <!-- Verborgen velden die wel meegestuurd moeten worden -->
            <input type="hidden" v-model="form.locatie_details">
        </form>
    </div>
</template>
