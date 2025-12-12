<script setup>
// ====================================================================
// IMPORTS
// ====================================================================
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import LocationPickerMap from '@/Components/LocationPickerMap.vue'; // <-- Import map component
import { ref, computed } from 'vue';
import axios from 'axios';

// ====================================================================
// PROPS DEFINITIE
// ====================================================================
const props = defineProps({
    waters: Array,
});

const currentWaters = ref(props.waters);

// ====================================================================
// FORMULIER LOGICA (useForm)
// ====================================================================
const form = useForm({
    water_id: currentWaters.value.length > 0 ? currentWaters.value[0].id : '', 
});

// ====================================================================
// GEOLOCATIE STATUSSEN EN CONSTANTEN
// ====================================================================
const geoLoading = ref(false);
const geoStatusMessage = ref('Gebruik de knop of de dropdown.');
const geoErrorMsg = ref(null);
const nearestWater = ref(null); 
const NEAREST_WATER_API = '/api/water/nearest'; 
const USE_MOCK_API = false;
const MAX_GPS_DISTANCE = 1000; 

const geoStatusClass = computed(() => {
    if (geoErrorMsg.value) return 'bg-red-100 text-red-700';
    if (geoLoading.value) return 'text-blue-600 font-semibold';
    if (nearestWater.value) return 'bg-green-100 text-green-700 font-semibold';
    return 'text-gray-700';
});

// ====================================================================
// HELPER FUNCTIE: API CALL VOOR DICHTSTBIJZIJND WATER
// ====================================================================
async function fetchNearestWater(lat, lng) {
    if (USE_MOCK_API) { /* ... */ }
    try {
        const response = await fetch(`${NEAREST_WATER_API}?lat=${lat}&lng=${lng}`, {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' }
        });
        const result = await response.json();
        if (!response.ok) {
            const errorMessage = result.message || `Serverfout: ${response.status} (${response.statusText})`;
            throw new Error(errorMessage);
        }
        return result;
    } catch (e) {
        console.error('Fout bij API-aanroep:', e);
        throw e; 
    }
}

// ====================================================================
// HOOFDFUNCTIE: LOCATIE BEPALEN & WATER ZOEKEN
// ====================================================================
function findNearestWater() {
    geoLoading.value = true;
    geoErrorMsg.value = null;
    nearestWater.value = null;
    geoStatusMessage.value = 'Locatie opvragen...';
    
    if (!navigator.geolocation) {
        geoErrorMsg.value = 'Geolocatie wordt niet ondersteund door deze browser.';
        geoStatusMessage.value = geoErrorMsg.value;
        geoLoading.value = false;
        return;
    }

    navigator.geolocation.getCurrentPosition(
        async (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            geoStatusMessage.value = `Locatie bepaald: Zoeken naar water...`;
            
            try {
                const result = await fetchNearestWater(lat, lng);

                if (result && result.id) {
                    const distance = parseFloat(result.distance_meters);

                    if (distance > MAX_GPS_DISTANCE) {
                         throw new Error(`Water (${result.naam}) gevonden, maar te ver weg (${distance.toFixed(0)}m). Max. ${MAX_GPS_DISTANCE}m.`);
                    }

                    nearestWater.value = { id: result.id, naam: result.naam, distance_meters: distance };
                    geoStatusMessage.value = `Dichtstbijzijnde water (${result.naam}) geselecteerd op ${distance.toFixed(0)}m afstand.`;
                    
                    const existingWater = currentWaters.value.find(w => w.id === nearestWater.value.id);
                    if (!existingWater) {
                        currentWaters.value.push({
                            id: nearestWater.value.id,
                            naam: result.naam + ` (Gevonden op ${distance.toFixed(0)}m)`
                        });
                        currentWaters.value.sort((a, b) => a.naam.localeCompare(b.naam));
                    }
                    
                    form.water_id = nearestWater.value.id;

                } else {
                    throw new Error('Geen watergebied gevonden in de buurt (onvolledige API respons).');
                }
            } catch (error) {
                geoErrorMsg.value = error.message;
                geoStatusMessage.value = 'Fout opgetreden tijdens zoeken.';
                console.error('Fout bij zoeken water:', error);
            } finally {
                geoLoading.value = false;
            }
        },
        (error) => {
            let msg;
            switch (error.code) {
                case error.PERMISSION_DENIED: msg = "Locatietoegang geweigerd. Geef toestemming om te zoeken."; break;
                case error.POSITION_UNAVAILABLE: msg = "Locatie-informatie is niet beschikbaar."; break;
                case error.TIMEOUT: msg = "Locatieverzoek is verlopen (Time-out)."; break;
                default: msg = "Onbekende fout bij het bepalen van de locatie."; break;
            }
            geoErrorMsg.value = msg;
            geoStatusMessage.value = 'Fout opgetreden.';
            geoLoading.value = false;
            console.error('Geolocation error:', error);
        },
        { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
    );
}

// ====================================================================
// QUICK ADD MODAL LOGICA
// ====================================================================
const showQuickAddModal = ref(false);
const quickAddForm = ref({
    naam: '',
    type: 'Water',
    beheersgebied: '',
    latitude: 52.5261545,  // <-- Default Lelystad
    longitude: 5.4729717, // <-- Default Lelystad
    processing: false,
    errors: {}
});

// Functie om de locatie in het quickAddForm bij te werken
const updateQuickAddLocation = (location) => {
    if (location) {
        quickAddForm.value.latitude = location.lat;
        quickAddForm.value.longitude = location.lng;
    }
};

const resetQuickAddForm = () => {
    quickAddForm.value.naam = '';
    quickAddForm.value.type = 'Water';
    quickAddForm.value.beheersgebied = '';
    quickAddForm.value.latitude = 52.5261545;
    quickAddForm.value.longitude = 5.4729717;
    quickAddForm.value.errors = {};
}

const submitStartRonde = () => {
    form.post(route('controles.store'), {
        preserveScroll: true,
    });
};

const submitQuickAdd = async () => {
    quickAddForm.value.processing = true;
    quickAddForm.value.errors = {};

    try {
        const response = await axios.post(route('waters.store-quick'), quickAddForm.value);
        const newWater = response.data.water;
        
        if (newWater) {
            currentWaters.value.push(newWater);
            currentWaters.value.sort((a, b) => a.naam.localeCompare(b.naam)); 
            form.water_id = newWater.id;
        }
        
        showQuickAddModal.value = false;
        resetQuickAddForm();
        
    } catch (error) {
        if (error.response && error.response.status === 422) {
            quickAddForm.value.errors = error.response.data.errors;
        } else {
            quickAddForm.value.errors = { algemeen: ['Er is een onbekende fout opgetreden bij het opslaan.'] };
        }
    } finally {
        quickAddForm.value.processing = false;
    }
};
</script>

<template>
    <Head title="Start Controle Ronde" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Start Nieuwe Controle Ronde</h2>
        </template>

        <div class="py-12">
            <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submitStartRonde">
                        
                        <div class="mb-8 border-b pb-4">
                            <h3 class="text-lg font-semibold text-gray-700 mb-4">Optie 1: Automatische Locatiebepaling</h3>
                            <div :class="geoStatusClass" class="text-center p-3 mb-4 rounded-lg text-sm transition-all duration-300 min-h-[40px] flex items-center justify-center">
                                {{ geoStatusMessage }}
                            </div>
                            <PrimaryButton @click.prevent="findNearestWater" :disabled="geoLoading"
                                class="w-full justify-center bg-blue-600 hover:bg-blue-700"
                                :class="{ 'opacity-50 cursor-not-allowed': geoLoading }">
                                <span v-if="geoLoading">Locatie bepalen...</span>
                                <span v-else>📍 Zoek Dichtstbijzijnde Water via GPS</span>
                            </PrimaryButton>
                            <div v-if="geoErrorMsg" class="mt-4 p-2 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm">
                                <p><strong>Fout:</strong> {{ geoErrorMsg }}</p>
                            </div>
                        </div>

                        <h3 class="text-lg font-semibold text-gray-700 mb-4">Optie 2: Handmatig Selecteren</h3>
                        
                        <div class="mb-4">
                            <InputLabel for="water" value="Selecteer Water" class="mb-2" />
                            <select
                                id="water"
                                v-model="form.water_id"
                                required
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            >
                                <option v-for="water in currentWaters" :key="water.id" :value="water.id">
                                    {{ water.naam }}
                                </option>
                                <option v-if="currentWaters.length === 0" value="" disabled>Geen wateren beschikbaar</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.water_id" />
                        </div>
                        
                        <div class="mb-6 flex justify-end">
                            <button type="button" @click="showQuickAddModal = true" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                ➕ Water is niet beschikbaar? Voeg toe!
                            </button>
                        </div>

                        <div class="flex items-center justify-end">
                            <PrimaryButton :class="{ 'opacity-25': form.processing || !form.water_id }" :disabled="form.processing || !form.water_id">
                                Start Ronde Nu
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Quick Add Modal (Pop-up voor Snel Toevoegen) -->
        <div v-if="showQuickAddModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center z-50 overflow-y-auto p-4">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl">
                <h3 class="text-lg font-bold mb-4">Snel Nieuw Water Toevoegen</h3>
                <form @submit.prevent="submitQuickAdd" class="space-y-4">
                    
                    <div>
                        <InputLabel for="new_naam" value="Naam van het Water" />
                        <TextInput id="new_naam" v-model="quickAddForm.naam" required type="text" class="mt-1 block w-full" />
                        <InputError :message="quickAddForm.errors.naam ? quickAddForm.errors.naam[0] : ''" class="mt-2" />
                    </div>

                    <div class="pt-4">
                        <InputLabel value="Selecteer Locatie op de Kaart" />
                        <p class="text-sm text-gray-500 mb-2">
                            Klik op de kaart om een locatie te selecteren of sleep de marker naar de juiste plek.
                        </p>
                        <LocationPickerMap 
                            :latitude="quickAddForm.latitude"
                            :longitude="quickAddForm.longitude"
                            @update:location="updateQuickAddLocation"
                            class="h-64"
                        />
                         <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <div>
                                <InputLabel for="quick_latitude" value="Latitude (automatisch)" />
                                <TextInput
                                    id="quick_latitude"
                                    v-model="quickAddForm.latitude"
                                    type="text"
                                    class="mt-1 block w-full bg-gray-100"
                                    readonly
                                />
                                <InputError :message="quickAddForm.errors.latitude ? quickAddForm.errors.latitude[0] : ''" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="quick_longitude" value="Longitude (automatisch)" />
                                <TextInput
                                    id="quick_longitude"
                                    v-model="quickAddForm.longitude"
                                    type="text"
                                    class="mt-1 block w-full bg-gray-100"
                                    readonly
                                />
                                <InputError :message="quickAddForm.errors.longitude ? quickAddForm.errors.longitude[0] : ''" class="mt-2" />
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <InputLabel for="new_type" value="Type (bijv. Vijver, Rivier)" />
                        <TextInput id="new_type" v-model="quickAddForm.type" type="text" class="mt-1 block w-full" />
                        <InputError :message="quickAddForm.errors.type ? quickAddForm.errors.type[0] : ''" class="mt-2" />
                    </div>
                    
                    <div>
                        <InputLabel for="new_gebied" value="Beheersgebied (Optioneel)" />
                        <TextInput id="new_gebied" v-model="quickAddForm.beheersgebied" type="text" class="mt-1 block w-full" />
                        <InputError :message="quickAddForm.errors.beheersgebied ? quickAddForm.errors.beheersgebied[0] : ''" class="mt-2" />
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" @click="showQuickAddModal = false; resetQuickAddForm();" class="px-4 py-2 bg-gray-200 rounded-md text-gray-800 hover:bg-gray-300">
                            Annuleren
                        </button>
                        <PrimaryButton type="submit" :class="{ 'opacity-25': quickAddForm.processing }" :disabled="quickAddForm.processing">
                            Water Opslaan & Selecteren
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>