<script setup>
// ====================================================================
// IMPORTS
// Hier importeren we alle benodigde componenten en functionaliteit.
// ====================================================================
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
// Head: Voor het instellen van de paginatitel.
// useForm: Voor het beheren van formulierstatus (data, validatie, processing).
import { Head, useForm } from '@inertiajs/vue3';
// Standaard UI-componenten
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
// ref, computed: Voor het creëren van reactieve variabelen en afgeleide waarden in Vue.
import { ref, computed } from 'vue';
// axios: Voor het maken van HTTP-verzoeken (gebruikt voor Quick Add en Geolocation API).
import axios from 'axios';

// ====================================================================
// PROPS DEFINITIE
// Data die van de Laravel Controller via Inertia wordt doorgegeven.
// ====================================================================
const props = defineProps({
    // Lijst van alle beschikbare wateren (gebruikt in de dropdown).
    waters: Array, // Lijst van waters { id, naam }
});

// Zowel de lijst met waters als de formulier data moeten nu reactief zijn
const currentWaters = ref(props.waters);

// ====================================================================
// FORMULIER LOGICA (useForm)
// ====================================================================

// Formulier om de Ronde te Starten.
const form = useForm({
    // Standaard selecteer het eerste water in de lijst.
    water_id: currentWaters.value.length > 0 ? currentWaters.value[0].id : '', 
});

// ====================================================================
// GEOLOCATIE STATUSSEN EN CONSTANTEN
// ====================================================================

// Status van het opvragen van de locatie
const geoLoading = ref(false);
// Gebruikersvriendelijk statusbericht voor de Geolocatie-sectie
const geoStatusMessage = ref('Gebruik de knop of de dropdown.');
// Opslag voor eventuele foutmeldingen van de Geolocation API of de drempelcheck
const geoErrorMsg = ref(null);

// nearestWater: Bevat de data van het dichtstbijzijnde water { id, naam, distance_meters }
const nearestWater = ref(null); 

// --- CONSTANTEN voor Geolocation ---
// API-endpoint voor het opzoeken van het dichtstbijzijnde water (gedefinieerd in Laravel)
const NEAREST_WATER_API = '/api/water/nearest'; 
const USE_MOCK_API = false; // Vlag om mock data te gebruiken (momenteel uitgeschakeld)
// Maximale afstand (in meters) om een water als "nabij" te beschouwen.
const MAX_GPS_DISTANCE = 1000; 

// --- COMPUTED EIGENSCHAPPEN VOOR GEOLOCATIE KLASSEN ---
// Bepaalt de dynamische CSS-klassen op basis van de Geolocatie-status.
const geoStatusClass = computed(() => {
    if (geoErrorMsg.value) return 'bg-red-100 text-red-700'; // Fout
    if (geoLoading.value) return 'text-blue-600 font-semibold'; // Bezig
    if (nearestWater.value) return 'bg-green-100 text-green-700 font-semibold'; // Succesvol gevonden
    return 'text-gray-700'; // Standaard
});

// ====================================================================
// HELPER FUNCTIE: API CALL VOOR DICHTSTBIJZIJND WATER
// ====================================================================

/**
 * Roept de Laravel backend API aan om het dichtstbijzijnde water op te zoeken.
 * @param {number} lat - Breedtegraad.
 * @param {number} lng - Lengtegraad.
 * @returns {Promise<Object>} - Het gevonden waterobject (inclusief afstand).
 */
async function fetchNearestWater(lat, lng) {
    if (USE_MOCK_API) {
        // Mock implementatie (voor testdoeleinden)
        // ... (mock code weggelaten voor dit commentaar, maar staat in de file)
    }

    // ECHTE API-AANROEP (gebruikt standaard 'fetch' API)
    try {
        const response = await fetch(`${NEAREST_WATER_API}?lat=${lat}&lng=${lng}`, {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' }
        });

        const result = await response.json();

        if (!response.ok) {
            // Afhandeling van HTTP-statuscodes buiten 200-299
            const errorMessage = result.message || `Serverfout: ${response.status} (${response.statusText})`;
            throw new Error(errorMessage);
        }

        // Succesvolle respons (200 OK)
        return result;

    } catch (e) {
        // Afhandeling van netwerkfouten of parsingsfouten
        console.error('Fout bij API-aanroep:', e);
        throw e; 
    }
}

// ====================================================================
// HOOFDFUNCTIE: LOCATIE BEPALEN & WATER ZOEKEN
// ====================================================================

/**
 * Coördineert het opvragen van de GPS-locatie en het zoeken naar het dichtstbijzijnde water.
 */
function findNearestWater() {
    // Reset en zet laadstatus
    geoLoading.value = true;
    geoErrorMsg.value = null;
    nearestWater.value = null;
    geoStatusMessage.value = 'Locatie opvragen...';
    
    // Controleer of de browser Geolocation ondersteunt
    if (!navigator.geolocation) {
        geoErrorMsg.value = 'Geolocatie wordt niet ondersteund door deze browser.';
        geoStatusMessage.value = geoErrorMsg.value;
        geoLoading.value = false;
        return;
    }

    // Vraagt om de werkelijke locatie van de gebruiker (met hoge nauwkeurigheid)
    navigator.geolocation.getCurrentPosition(
        async (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            geoStatusMessage.value = `Locatie bepaald: Zoeken naar water...`;
            
            try {
                // Stap 1: Backend API aanroepen
                const result = await fetchNearestWater(lat, lng);

                // CONTROLE 1: Controleer op geldig ID in de respons
                if (result && result.id) {
                    const distance = parseFloat(result.distance_meters);

                    // CONTROLE 2: Drempelcheck (is het water niet te ver weg?)
                    if (distance > MAX_GPS_DISTANCE) {
                         throw new Error(`Water (${result.naam}) gevonden, maar te ver weg (${distance.toFixed(0)}m). Max. ${MAX_GPS_DISTANCE}m.`);
                    }

                    // Stap 2: State bijwerken bij succes
                    nearestWater.value = {
                        id: result.id,
                        naam: result.naam,
                        distance_meters: distance
                    };

                    geoStatusMessage.value = `Dichtstbijzijnde water (${result.naam}) geselecteerd op ${distance.toFixed(0)}m afstand.`;
                    
                    // Stap 3: Water toevoegen aan dropdown indien nodig (enkel als het water er nog niet in zit)
                    const existingWater = currentWaters.value.find(w => w.id === nearestWater.value.id);
                    
                    if (!existingWater) {
                        currentWaters.value.push({
                            id: nearestWater.value.id,
                            naam: nearestWater.value.naam + ` (Gevonden op ${distance.toFixed(0)}m)`
                        });
                        // Sorteer de lijst zodat de nieuwe entry op de juiste plek staat
                        currentWaters.value.sort((a, b) => a.naam.localeCompare(b.naam));
                    }
                    
                    // Stap 4: Selecteer het gevonden water in het formulier
                    form.water_id = nearestWater.value.id;

                } else {
                    // Geen geldig ID gevonden, ondanks 200 OK
                    throw new Error('Geen watergebied gevonden in de buurt (onvolledige API respons).');
                }

            } catch (error) {
                // Foutafhandeling van API of drempelcheck
                geoErrorMsg.value = error.message;
                geoStatusMessage.value = 'Fout opgetreden tijdens zoeken.';
                console.error('Fout bij zoeken water:', error);
            } finally {
                geoLoading.value = false;
            }
        },
        // Geolocation Error Callback (fouten op browser niveau)
        (error) => {
            let msg;
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    msg = "Locatietoegang geweigerd. Geef toestemming om te zoeken.";
                    break;
                case error.POSITION_UNAVAILABLE:
                    msg = "Locatie-informatie is niet beschikbaar.";
                    break;
                case error.TIMEOUT:
                    msg = "Locatieverzoek is verlopen (Time-out).";
                    break;
                default:
                    msg = "Onbekende fout bij het bepalen van de locatie.";
                    break;
            }
            geoErrorMsg.value = msg;
            geoStatusMessage.value = 'Fout opgetreden.';
            geoLoading.value = false;
            console.error('Geolocation error:', error);
        },
        // Geolocation Opties
        { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
    );
}

// ====================================================================
// QUICK ADD MODAL LOGICA (Snel Water Toevoegen)
// ====================================================================

// Status van de modal
const showQuickAddModal = ref(false);
// Formulier data voor de Quick Add
const quickAddForm = ref({
    naam: '',
    type: 'Water', // Standaardwaarde
    beheersgebied: '',
    processing: false,
    errors: {}
});

/**
 * Verstuurt het hoofdformulier om een nieuwe controle ronde te starten.
 */
const submitStartRonde = () => {
    // Maakt een POST-verzoek naar de 'controles.store' route.
    form.post(route('controles.store'), {
        preserveScroll: true,
    });
};

/**
 * Verstuurt het formulier om snel een nieuw water toe te voegen via de API.
 */
const submitQuickAdd = async () => {
    quickAddForm.value.processing = true;
    quickAddForm.value.errors = {};

    try {
        // Gebruikt axios (geen Inertia useForm) omdat dit een losse API-actie is
        const response = await axios.post(route('waters.store-quick'), quickAddForm.value);

        const newWater = response.data.water;
        
        if (newWater) {
            // Voeg het nieuwe water toe aan de reactieve lijst
            currentWaters.value.push(newWater);
            // Sorteer de lijst
            currentWaters.value.sort((a, b) => a.naam.localeCompare(b.naam)); 
            // Selecteer het zojuist toegevoegde water in het hoofdformulier
            form.water_id = newWater.id;
        }
        
        // Reset modal state
        showQuickAddModal.value = false;
        quickAddForm.value.naam = '';
        quickAddForm.value.type = 'Water';
        quickAddForm.value.beheersgebied = '';
        
    } catch (error) {
        // Afhandeling van validatiefouten (422) of onbekende fouten
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
    <!-- Stelt de paginatitel in -->
    <Head title="Start Controle Ronde" />

    <!-- Gebruikt de algemene geauthenticeerde lay-out -->
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Start Nieuwe Controle Ronde</h2>
        </template>

        <div class="py-12">
            <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submitStartRonde">
                        
                        <!-- SECTIE: Automatische Locatiebepaling via GPS -->
                        <div class="mb-8 border-b pb-4">
                            <h3 class="text-lg font-semibold text-gray-700 mb-4">Optie 1: Automatische Locatiebepaling</h3>
                            
                            <!-- Status en Laad Indicator -->
                            <!-- Dynamische achtergrondkleur en tekstkleur op basis van geoStatusClass -->
                            <div :class="geoStatusClass" class="text-center p-3 mb-4 rounded-lg text-sm transition-all duration-300 min-h-[40px] flex items-center justify-center">
                                {{ geoStatusMessage }}
                            </div>

                            <!-- Locatie Knop -->
                            <!-- Roept findNearestWater aan; is disabled tijdens het laden -->
                            <PrimaryButton @click.prevent="findNearestWater" :disabled="geoLoading"
                                class="w-full justify-center bg-blue-600 hover:bg-blue-700"
                                :class="{ 'opacity-50 cursor-not-allowed': geoLoading }">
                                <span v-if="geoLoading">Locatie bepalen...</span>
                                <span v-else>📍 Zoek Dichtstbijzijnde Water via GPS</span>
                            </PrimaryButton>

                            <!-- Foutmelding (indien aanwezig) -->
                            <div v-if="geoErrorMsg" class="mt-4 p-2 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm">
                                <p><strong>Fout:</strong> {{ geoErrorMsg }}</p>
                            </div>
                        </div>


                        <!-- SECTIE: Handmatige Selectie -->
                        <h3 class="text-lg font-semibold text-gray-700 mb-4">Optie 2: Handmatig Selecteren</h3>
                        
                        <div class="mb-4">
                            <InputLabel for="water" value="Selecteer Water" class="mb-2" />
                            <!-- De water_id wordt gesynchroniseerd met het formulier -->
                            <select
                                id="water"
                                v-model="form.water_id"
                                required
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            >
                                <!-- currentWaters bevat de oorspronkelijke lijst + eventueel het GPS-gevonden water -->
                                <option v-for="water in currentWaters" :key="water.id" :value="water.id">
                                    {{ water.naam }}
                                </option>
                                <option v-if="currentWaters.length === 0" value="" disabled>Geen wateren beschikbaar</option>
                            </select>

                            <InputError class="mt-2" :message="form.errors.water_id" />
                        </div>
                        
                        <!-- Link naar Quick Add Modal -->
                        <div class="mb-6 flex justify-end">
                            <button type="button" @click="showQuickAddModal = true" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                ➕ Water is niet beschikbaar? Voeg toe!
                            </button>
                        </div>

                        <!-- Hoofdknop om de ronde te starten -->
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
        <!-- Dit is een standaard, niet-Inertia-gestuurde modal -->
        <div v-if="showQuickAddModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
                <h3 class="text-lg font-bold mb-4">Snel Nieuw Water Toevoegen</h3>
                <form @submit.prevent="submitQuickAdd">
                    
                    <!-- Veld: Naam van het Water -->
                    <div class="mb-4">
                        <InputLabel for="new_naam" value="Naam van het Water" />
                        <TextInput id="new_naam" v-model="quickAddForm.naam" required type="text" class="mt-1 block w-full" />
                        <!-- Toont validatiefout voor dit veld -->
                        <InputError :message="quickAddForm.errors.naam ? quickAddForm.errors.naam[0] : ''" class="mt-2" />
                    </div>
                    
                    <!-- Veld: Type Water -->
                    <div class="mb-4">
                        <InputLabel for="new_type" value="Type (bijv. Vijver, Rivier)" />
                        <TextInput id="new_type" v-model="quickAddForm.type" type="text" class="mt-1 block w-full" />
                        <InputError :message="quickAddForm.errors.type ? quickAddForm.errors.type[0] : ''" class="mt-2" />
                    </div>
                    
                    <!-- Veld: Beheersgebied -->
                    <div class="mb-6">
                        <InputLabel for="new_gebied" value="Beheersgebied (Optioneel)" />
                        <TextInput id="new_gebied" v-model="quickAddForm.beheersgebied" type="text" class="mt-1 block w-full" />
                        <InputError :message="quickAddForm.errors.beheersgebied ? quickAddForm.errors.beheersgebied[0] : ''" class="mt-2" />
                    </div>

                    <div class="flex justify-end space-x-3">
                        <!-- Annuleren sluit de modal en reset de staat -->
                        <button type="button" @click="showQuickAddModal = false" class="px-4 py-2 bg-gray-200 rounded-md text-gray-800 hover:bg-gray-300">
                            Annuleren
                        </button>
                        <!-- Opslaan roept de submitQuickAdd functie aan -->
                        <PrimaryButton type="submit" :class="{ 'opacity-25': quickAddForm.processing }" :disabled="quickAddForm.processing">
                            Water Opslaan & Selecteren
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>