<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import LocationPickerMap from '@/Components/LocationPickerMap.vue';

defineProps({
    categories: {
        type: Object,
        required: true,
    },
});

const currentStep = ref(1);
const totalSteps = 5;

const formData = useForm({
    categorie: '',
    beschrijving: '',
    melding_datum_tijd: new Date().toISOString().slice(0, 16),
    aantal_vissen: null,
    ernst_situatie: '',
    locatie_adres: '',
    locatie_details: null,
    fotos: [],
    melder_naam: '',
    melder_email: '',
    melder_telefoon: '',
    melder_anoniem: false,
    categorie_scope_geldig: false,
    captcha_token: '',
});

const selectedFiles = ref([]);
const mapLatitude = ref(52.5261545);
const mapLongitude = ref(5.4729717);
const fileInputKey = ref(0); // Force re-render of file input

// Get today's date at current time (max date for incident)
const maxDateTime = new Date().toISOString().slice(0, 16);

const handleFileSelect = (event) => {
    const files = event.target.files;
    if (files) {
        // Limit to 5 files
        const newFiles = Array.from(files).slice(0, 5 - selectedFiles.value.length);
        selectedFiles.value = [...selectedFiles.value, ...newFiles];
    }
};

const removeFile = (index) => {
    selectedFiles.value.splice(index, 1);
    // Reset file input
    fileInputKey.value++;
};

const handleMapUpdate = (location) => {
    if (location) {
        mapLatitude.value = location.lat;
        mapLongitude.value = location.lng;
        formData.locatie_details = {
            latitude: location.lat,
            longitude: location.lng,
            address: location.address || formData.locatie_adres,
        };
    }
};

const nextStep = () => {
    if (validateCurrentStep()) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const validateCurrentStep = () => {
    switch (currentStep.value) {
        case 1:
            return formData.categorie && formData.categorie_scope_geldig;
        case 2:
            return formData.beschrijving && 
                   formData.beschrijving.length >= 20 && 
                   formData.melding_datum_tijd;
        case 3:
            return true; // Locatie is optional
        case 4:
            return true; // Photos are optional
        case 5:
            return validateStep5();
        default:
            return true;
    }
};

const validateStep5 = () => {
    if (formData.melder_anoniem) {
        return true;
    }
    // Als niet anoniem, check if at least name of email of telefoon is provided
    return formData.melder_naam || formData.melder_email || formData.melder_telefoon;
};

const updateMelderInfo = () => {
    if (formData.melder_naam || formData.melder_email || formData.melder_telefoon) {
        formData.melder_anoniem = false;
    }
};

const toggleAnoniem = () => {
    if (formData.melder_anoniem) {
        formData.melder_naam = null;
        formData.melder_email = null;
        formData.melder_telefoon = null;
    }
};

const submit = async () => {
    // Houd geselecteerde bestanden vast voor upload
    formData.fotos = selectedFiles.value.length > 0 ? selectedFiles.value : null;

    // Convert null values to empty values
    if (!formData.aantal_vissen) {
        formData.aantal_vissen = null;
    }

    // Ensure melder fields are properly set (convert empty strings to null)
    if (formData.melder_naam === '') formData.melder_naam = null;
    if (formData.melder_email === '') formData.melder_email = null;
    if (formData.melder_telefoon === '') formData.melder_telefoon = null;
    if (formData.locatie_adres === '') formData.locatie_adres = null;

    // Als contactgegevens zijn ingevuld, forceer anoniem uit
    if (formData.melder_naam || formData.melder_email || formData.melder_telefoon) {
        formData.melder_anoniem = false;
    }

    // Convert locatie_details object to JSON string if het nodig is
    if (formData.locatie_details && typeof formData.locatie_details === 'object') {
        formData.locatie_details = JSON.stringify(formData.locatie_details);
    } else if (!formData.locatie_details) {
        formData.locatie_details = null;
    }

    formData.post(route('api.overlast-meldingen.store'), {
        onSuccess: () => {
            // Inertia::location() handelt de redirect automatisch af
        },
        onError: () => {
            currentStep.value = 5;
        },
        forceFormData: true,
    });
};

const getCategoryLabel = (category) => {
    const labels = {
        'vissterfte': 'Vissterfte',
        'onjuist_gedrag_vissers': 'Onjuist gedrag vissers',
        'dierenmishandeling': 'Dierenmishandeling (vis-gerelateerd)',
        'illegale_visserij': 'Illegale visserij',
        'vervuiling': 'Vervuiling met impact op vissen',
        'overig': 'Overig (binnen scope)',
    };
    return labels[category] || category;
};
</script>

<template>
    <Head title="Meld overlast rond vissen" />

    <div class="min-h-screen bg-gradient-to-b from-blue-50 to-gray-100 py-6 px-4 sm:py-12">
        <div class="max-w-2xl mx-auto">
            
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-gray-900 mb-2">🎣 Meld overlast rond vissen</h1>
                <p class="text-lg text-gray-700">
                    Help ons sportvisserij en dierenwelzijn te beschermen
                </p>
            </div>

            <!-- Scope Warning -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded">
                <p class="text-sm text-blue-900">
                    <strong>⚠️ Dit formulier is uitsluitend bedoeld voor:</strong> Sportvisserij, gedrag van vissers, vissterfte en dierenwelzijn rondom vissen. 
                    Andere vormen van overlast (geluid, parkeren, etc.) worden niet in behandeling genomen.
                </p>
            </div>

            <!-- Progress Bar -->
            <div class="mb-8">
                <div class="flex justify-between mb-2">
                    <span class="text-sm font-medium text-gray-600">Stap {{ currentStep }} van {{ totalSteps }}</span>
                    <span class="text-sm font-medium text-gray-600">{{ Math.round((currentStep / totalSteps) * 100) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div 
                        class="bg-blue-600 h-3 rounded-full transition-all duration-300"
                        :style="{ width: ((currentStep / totalSteps) * 100) + '%' }"
                    ></div>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="bg-white rounded-lg shadow-lg p-8 space-y-6">

                <!-- STAP 1: CATEGORIE -->
                <div v-if="currentStep === 1">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Stap 1: Type Melding</h2>
                    
                    <div class="space-y-3 mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            Wat is het type melding? <span class="text-red-500">*</span>
                        </label>
                        <div class="space-y-2">
                            <label v-for="category in categories" :key="category" class="flex items-center p-3 border-2 rounded-lg cursor-pointer transition" :class="{ 'border-blue-500 bg-blue-50': formData.categorie === category, 'border-gray-300 hover:border-gray-400': formData.categorie !== category }">
                                <input 
                                    v-model="formData.categorie" 
                                    type="radio" 
                                    :value="category"
                                    class="w-4 h-4 text-blue-600"
                                >
                                <span class="ml-3 text-gray-700">{{ getCategoryLabel(category) }}</span>
                            </label>
                        </div>
                        <div v-if="formData.errors.categorie" class="text-red-500 text-sm mt-2">{{ formData.errors.categorie }}</div>
                    </div>

                    <!-- Scope Confirmation -->
                    <div class="bg-gray-50 p-4 rounded-lg mb-6">
                        <label class="flex items-start space-x-3">
                            <input 
                                v-model="formData.categorie_scope_geldig" 
                                type="checkbox"
                                class="mt-1 w-4 h-4 text-blue-600 rounded border-gray-300"
                            >
                            <span class="text-sm text-gray-700">
                                Ik bevestig dat deze melding betrekking heeft op <strong>sportvisserij of dierenwelzijn rondom vissen</strong> <span class="text-red-500">*</span>
                            </span>
                        </label>
                        <div v-if="formData.errors.categorie_scope_geldig" class="text-red-500 text-sm mt-2">{{ formData.errors.categorie_scope_geldig }}</div>
                    </div>
                </div>

                <!-- STAP 2: MELDING DETAILS -->
                <div v-if="currentStep === 2">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Stap 2: Details van de Melding</h2>
                    
                    <div class="space-y-4">
                        <!-- Beschrijving -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Beschrijving <span class="text-red-500">*</span>
                                <span class="text-gray-500 text-xs">(minimaal 20 karakters)</span>
                            </label>
                            <textarea 
                                v-model="formData.beschrijving"
                                rows="5"
                                class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Beschrijf gedetailleerd wat u heeft waargenomen..."
                                required
                            ></textarea>
                            <div class="mt-1 text-xs text-gray-500">{{ formData.beschrijving.length }} / 2000 karakters</div>
                            <div v-if="formData.errors.beschrijving" class="text-red-500 text-sm mt-1">{{ formData.errors.beschrijving }}</div>
                        </div>

                        <!-- Datum/Tijd -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Moment van het incident <span class="text-red-500">*</span>
                            </label>
                            <input 
                                v-model="formData.melding_datum_tijd"
                                type="datetime-local"
                                :max="maxDateTime"
                                class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-blue-500"
                                required
                            >
                            <div v-if="formData.errors.melding_datum_tijd" class="text-red-500 text-sm mt-1">{{ formData.errors.melding_datum_tijd }}</div>
                        </div>

                        <!-- Aantal Vissen -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Aantal betrokken vissen (optioneel)
                            </label>
                            <input 
                                v-model.number="formData.aantal_vissen"
                                type="number"
                                min="1"
                                max="10000"
                                class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-blue-500"
                                placeholder="bijv. 5, 10, 50"
                            >
                            <div v-if="formData.errors.aantal_vissen" class="text-red-500 text-sm mt-1">{{ formData.errors.aantal_vissen }}</div>
                        </div>

                        <!-- Ernst Situatie -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Ernst van de situatie (optioneel)
                            </label>
                            <select 
                                v-model="formData.ernst_situatie"
                                class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">-- Selecteer --</option>
                                <option value="laag">Laag</option>
                                <option value="midden">Midden</option>
                                <option value="hoog">Hoog</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- STAP 3: LOCATIE -->
                <div v-if="currentStep === 3">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Stap 3: Locatie van het Incident</h2>
                    
                    <div class="space-y-4">
                        <!-- Adres -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Adres of nabij adres (optioneel)
                            </label>
                            <input 
                                v-model="formData.locatie_adres"
                                type="text"
                                class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-blue-500"
                                placeholder="bijv. Polder 123, Lelystad"
                            >
                            <div v-if="formData.errors.locatie_adres" class="text-red-500 text-sm mt-1">{{ formData.errors.locatie_adres }}</div>
                        </div>

                        <!-- Map -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Locatie op kaart (optioneel - klik op kaart)
                            </label>
                            <LocationPickerMap 
                                :latitude="mapLatitude"
                                :longitude="mapLongitude"
                                @update:location="handleMapUpdate"
                            />
                            <div v-if="formData.locatie_details" class="mt-2 p-3 bg-green-50 border border-green-200 rounded text-sm text-green-800">
                                ✓ Locatie gekozen: {{ formData.locatie_details.latitude.toFixed(4) }}, {{ formData.locatie_details.longitude.toFixed(4) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STAP 4: BIJLAGEN -->
                <div v-if="currentStep === 4">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Stap 4: Foto's / Bewijs (Optioneel)</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                Upload foto's of ander bewijs (max 5 bestanden)
                            </label>
                            
                            <!-- File Input -->
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-blue-500 transition">
                                <input 
                                    :key="fileInputKey"
                                    @change="handleFileSelect"
                                    type="file"
                                    multiple
                                    accept="image/*,video/*,.pdf"
                                    class="hidden"
                                    id="file-upload"
                                >
                                <label for="file-upload" class="cursor-pointer">
                                    <div class="text-4xl mb-2">📸</div>
                                    <p class="text-sm text-gray-600">Klik om bestanden te selecteren</p>
                                    <p class="text-xs text-gray-500 mt-1">Ondersteunde formaten: afbeeldingen, video, PDF</p>
                                </label>
                            </div>

                            <!-- Selected Files -->
                            <div v-if="selectedFiles.length > 0" class="mt-4">
                                <p class="text-sm font-medium text-gray-700 mb-2">Geselecteerde bestanden:</p>
                                <ul class="space-y-2">
                                    <li v-for="(file, index) in selectedFiles" :key="index" class="flex items-center justify-between p-2 bg-gray-50 rounded border border-gray-200">
                                        <span class="text-sm text-gray-700">{{ file.name }}</span>
                                        <button 
                                            @click="removeFile(index)"
                                            type="button"
                                            class="text-red-500 hover:text-red-700 text-xs font-medium"
                                        >
                                            ✕
                                        </button>
                                    </li>
                                </ul>
                                <p class="text-xs text-gray-500 mt-2">{{ selectedFiles.length }} / 5 bestanden</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STAP 5: MELDER GEGEVENS -->
                <div v-if="currentStep === 5">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Stap 5: Contactgegevens Melder</h2>
                    
                    <div class="space-y-4">
                        <!-- Anoniem Toggle -->
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <label class="flex items-center space-x-3">
                                <input 
                                    v-model="formData.melder_anoniem"
                                    type="checkbox"
                                    class="w-4 h-4 text-blue-600 rounded border-gray-300"
                                    @change="toggleAnoniem"
                                >
                                <span class="text-sm font-medium text-gray-700">Ik wil anoniem blijven</span>
                            </label>
                            <p class="text-xs text-gray-600 mt-2">Wenn u anoniem blijft kunnen we geen vervolgvragen stellen.</p>
                        </div>

                        <!-- Contact Info (if not anonymous) -->
                        <div v-if="!formData.melder_anoniem" class="space-y-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <p class="text-sm text-blue-900">Vul zeker je naam en/of contact gegevens in zodat we je kunnen bereiken:</p>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Naam (optioneel)
                                </label>
                                <input 
                                    v-model="formData.melder_naam"
                                    @input="updateMelderInfo"
                                    type="text"
                                    class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Voornaam Achternaam"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    E-mail (optioneel)
                                </label>
                                <input 
                                    v-model="formData.melder_email"
                                    @input="updateMelderInfo"
                                    type="email"
                                    class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="e@mail.nl"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Telefoonnummer (optioneel)
                                </label>
                                <input 
                                    v-model="formData.melder_telefoon"
                                    @input="updateMelderInfo"
                                    type="tel"
                                    class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="+31 6 12345678"
                                >
                            </div>
                        </div>

                        <!-- Summary -->
                        <div class="bg-amber-50 p-4 rounded-lg border border-amber-200">
                            <h3 class="font-semibold text-amber-900 mb-3">Samenvatting van uw melding:</h3>
                            <ul class="text-sm text-amber-800 space-y-2">
                                <li><strong>Type:</strong> {{ getCategoryLabel(formData.categorie) }}</li>
                                <li><strong>Datum/Tijd:</strong> {{ new Date(formData.melding_datum_tijd).toLocaleString('nl-NL') }}</li>
                                <li v-if="formData.locatie_adres"><strong>Locatie:</strong> {{ formData.locatie_adres }}</li>
                                <li v-if="selectedFiles.length > 0"><strong>Bijlagen:</strong> {{ selectedFiles.length }} bestand(en)</li>
                                <li><strong>Status:</strong> Uw melding zal worden gereviewd alvorens deze in behandeling wordt genomen.</li>
                            </ul>
                        </div>

                        <!-- Error Display -->
                        <div v-if="Object.keys(formData.errors).length > 0" class="bg-red-50 p-4 rounded-lg border border-red-200">
                            <p class="text-sm font-medium text-red-800 mb-2">Er zijn fouten in het formulier:</p>
                            <ul class="text-sm text-red-700 space-y-1">
                                <li v-for="(error, field) in formData.errors" :key="field">• {{ error }}</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="flex justify-between pt-6 border-t">
                    <button
                        v-if="currentStep > 1"
                        @click="prevStep"
                        type="button"
                        class="px-6 py-2 border-2 border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition"
                    >
                        ← Vorige
                    </button>
                    <div v-else></div>

                    <button
                        v-if="currentStep < totalSteps"
                        @click="nextStep"
                        type="button"
                        class="px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition"
                    >
                        Volgende →
                    </button>

                    <button
                        v-if="currentStep === totalSteps"
                        @click="submit"
                        :disabled="formData.processing"
                        type="submit"
                        class="px-6 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition disabled:opacity-50"
                    >
                        {{ formData.processing ? 'Verzenden...' : '✓ Melding Verzenden' }}
                    </button>
                </div>
            </form>

            <!-- Privacy Notice -->
            <div class="mt-8 p-4 bg-gray-50 rounded-lg border border-gray-200 text-sm text-gray-600">
                <p><strong>Privacy:</strong> Uw gegevens worden alleen gebruikt voor het verwerken van deze melding en zullen niet aan derden worden verstrekt.</p>
            </div>

        </div>
    </div>
</template>
