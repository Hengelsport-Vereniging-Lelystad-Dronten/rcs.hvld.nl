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
const isCameraOpen = ref(false);
const isScanLoading = ref(false);
const scanStatus = ref('');
const scanError = ref('');
const videoRef = ref(null);
const canvasRef = ref(null);
const cameraStream = ref(null);
const photoPreviewUrl = ref('');
const uploadedVispasUrl = ref('');
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
    vispas_foto_path: '',
    vispas_scan_confidence: null,
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

const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const stopCamera = () => {
    if (cameraStream.value) {
        cameraStream.value.getTracks().forEach(track => track.stop());
        cameraStream.value = null;
    }
    isCameraOpen.value = false;
};

const openCamera = async () => {
    scanError.value = '';

    if (!navigator.mediaDevices?.getUserMedia) {
        scanError.value = 'Camera is niet beschikbaar op dit apparaat. Gebruik upload als alternatief.';
        return;
    }

    try {
        cameraStream.value = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: { ideal: 'environment' },
                width: { ideal: 1600 },
                height: { ideal: 1000 },
            },
            audio: false,
        });
        isCameraOpen.value = true;

        requestAnimationFrame(() => {
            if (videoRef.value) {
                videoRef.value.srcObject = cameraStream.value;
            }
        });
    } catch (error) {
        console.error('Camera openen mislukt:', error);
        scanError.value = 'Camera openen is niet gelukt. Controleer cameratoegang of gebruik upload.';
    }
};

const capturePhoto = () => {
    const video = videoRef.value;
    const canvas = canvasRef.value;

    if (!video || !canvas) return;

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const context = canvas.getContext('2d');
    context.drawImage(video, 0, 0, canvas.width, canvas.height);

    canvas.toBlob((blob) => {
        if (!blob) {
            scanError.value = 'Foto maken is niet gelukt.';
            return;
        }

        const file = new File([blob], `vispas-${Date.now()}.jpg`, { type: 'image/jpeg' });
        stopCamera();
        scanVispasFile(file);
    }, 'image/jpeg', 0.9);
};

const handlePhotoInput = (event) => {
    const file = event.target.files?.[0];
    if (file) {
        scanVispasFile(file);
    }
    event.target.value = '';
};

const clearVispasPhoto = () => {
    form.vispas_foto_path = '';
    form.vispas_scan_confidence = null;
    uploadedVispasUrl.value = '';
    photoPreviewUrl.value = '';
    scanStatus.value = '';
    scanError.value = '';
};

const scanVispasFile = async (file) => {
    scanError.value = '';
    scanStatus.value = 'Foto uploaden en VISpasnummer uitlezen...';
    isScanLoading.value = true;

    if (photoPreviewUrl.value) {
        URL.revokeObjectURL(photoPreviewUrl.value);
    }
    photoPreviewUrl.value = URL.createObjectURL(file);

    const payload = new FormData();
    payload.append('controle_ronde_id', props.ronde.id);
    payload.append('foto', file);

    try {
        const response = await fetch('/vispas/scan', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
            body: payload,
            credentials: 'same-origin',
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'VISpas scan is mislukt.');
        }

        form.vispas_foto_path = data.path || '';
        form.vispas_scan_confidence = data.confidence ?? null;
        uploadedVispasUrl.value = data.url || '';

        if (data.vispas_nummer) {
            form.vispasnummer = data.vispas_nummer;
            scanStatus.value = `VISpasnummer ingevuld (${data.confidence}% zekerheid).`;
        } else {
            scanStatus.value = data.message || 'Foto opgeslagen. Controleer en vul het VISpasnummer handmatig in.';
        }
    } catch (error) {
        console.error('VISpas scan fout:', error);
        scanError.value = error.message || 'VISpas scan is mislukt.';
        scanStatus.value = '';
    } finally {
        isScanLoading.value = false;
    }
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
            form.reset('vispasnummer', 'vispas_foto_path', 'vispas_scan_confidence', 'details', 'vispas_ingenomen', 'aanleiding', 'middel');
            resetStructuredFields();
            clearVispasPhoto();
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
        <h3 class="text-xl font-bold text-gray-900 mb-6">Nieuwe registratie</h3>
        
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

                <div class="mt-3 rounded-md border border-gray-200 bg-gray-50 p-3">
                    <div class="flex flex-col sm:flex-row gap-2">
                        <button
                            type="button"
                            @click="openCamera"
                            :disabled="isScanLoading"
                            class="inline-flex justify-center items-center px-3 py-2 bg-gray-900 text-white text-sm font-semibold rounded-md hover:bg-gray-800 disabled:opacity-50"
                        >
                            Foto maken
                        </button>
                        <label class="inline-flex justify-center items-center px-3 py-2 bg-white text-gray-800 text-sm font-semibold rounded-md border border-gray-300 hover:bg-gray-100 cursor-pointer">
                            Foto uploaden
                            <input
                                type="file"
                                accept="image/*"
                                capture="environment"
                                class="sr-only"
                                :disabled="isScanLoading"
                                @change="handlePhotoInput"
                            />
                        </label>
                        <button
                            v-if="form.vispas_foto_path"
                            type="button"
                            @click="clearVispasPhoto"
                            :disabled="isScanLoading"
                            class="inline-flex justify-center items-center px-3 py-2 bg-white text-gray-700 text-sm font-semibold rounded-md border border-gray-300 hover:bg-gray-100 disabled:opacity-50"
                        >
                            Foto verwijderen
                        </button>
                    </div>

                    <div v-if="photoPreviewUrl || uploadedVispasUrl" class="mt-3 flex items-start gap-3">
                        <img
                            :src="uploadedVispasUrl || photoPreviewUrl"
                            alt="Gescande VISpas"
                            class="h-24 w-36 rounded border border-gray-300 object-cover"
                        />
                        <div class="text-sm">
                            <p v-if="form.vispas_foto_path" class="font-medium text-gray-800">Foto opgeslagen bij deze registratie.</p>
                            <p v-if="form.vispas_scan_confidence !== null" class="text-gray-600">Scanzekerheid: {{ form.vispas_scan_confidence }}%</p>
                        </div>
                    </div>

                    <p v-if="scanStatus" class="mt-2 text-sm text-blue-700">{{ scanStatus }}</p>
                    <p v-if="scanError" class="mt-2 text-sm text-red-600">{{ scanError }}</p>
                    <InputError :message="form.errors.vispas_foto_path" class="mt-2" />
                </div>

                <div class="mt-2 text-sm" :class="{ 'text-blue-500': isRecidiveCheckLoading, 'text-red-600 font-bold': isRecidivist, 'text-green-600': recidiveStatus && !isRecidivist && !isRecidiveCheckLoading }">
                    <span v-if="isRecidiveCheckLoading">Controleren...</span>
                    <span v-else>{{ recidiveStatus || 'Typ een vispasnummer om te controleren.' }}</span>
                </div>
            </div>

            <div v-if="isCameraOpen" class="fixed inset-0 z-50 bg-black/90 p-4 flex flex-col">
                <div class="mx-auto flex w-full max-w-lg flex-1 flex-col">
                    <div class="mb-3 flex items-center justify-between text-white">
                        <h4 class="text-base font-semibold">Positioneer de VISpas binnen het kader</h4>
                        <button type="button" @click="stopCamera" class="rounded-md px-3 py-2 text-sm font-semibold bg-white/10 hover:bg-white/20">
                            Sluiten
                        </button>
                    </div>

                    <div class="relative flex-1 overflow-hidden rounded-md bg-black">
                        <video ref="videoRef" autoplay playsinline muted class="h-full w-full object-cover"></video>
                        <div class="pointer-events-none absolute inset-0 flex items-center justify-center p-5">
                            <div class="aspect-[1.58/1] w-full max-w-md rounded-md border-4 border-white shadow-[0_0_0_9999px_rgba(0,0,0,0.35)]"></div>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="capturePhoto"
                        class="mt-4 w-full rounded-md bg-white px-4 py-3 text-sm font-bold text-gray-900 hover:bg-gray-100"
                    >
                        Foto gebruiken
                    </button>
                </div>
            </div>

            <canvas ref="canvasRef" class="hidden"></canvas>

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
                    <span v-else>Visser gecontroleerd</span>
                </PrimaryButton>
            </div>

            <!-- Verborgen velden die wel meegestuurd moeten worden -->
            <input type="hidden" v-model="form.locatie_details">
        </form>
    </div>
</template>
