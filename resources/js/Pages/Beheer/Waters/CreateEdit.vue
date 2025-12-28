<script setup>
// ====================================================================
// IMPORTS & SETUP
// ====================================================================
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import WysiwygInput from '@/Components/WysiwygInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    water: {
        type: Object,
        default: null,
    },
});

// Bepaal of we in bewerk-modus zijn op basis van de aanwezigheid van een ID
const isEdit = !!(props.water && props.water.id);

// Initialiseer het formulier met Inertia useForm
const form = useForm({
    naam: props.water ? props.water.naam : '',
    beschrijving: props.water ? props.water.beschrijving : '',
    boundary: props.water ? props.water.boundary : null,
    center_lat: props.water ? props.water.center_lat : null,
    center_lng: props.water ? props.water.center_lng : null,
});

// Refs voor kaart elementen
const mapContainer = ref(null);
const hasPolygon = ref(!!props.water?.boundary);

// Leaflet variabelen (niet reactief nodig omdat Leaflet zijn eigen state beheert)
let map = null;
let polygonLayer = null;
let markers = [];
let tempPoints = [];
let tempLine = null;

// ====================================================================
// KAART CONFIGURATIE & ICONEN
// ====================================================================
const handleIcon = L.divIcon({
    className: 'bg-orange-500 rounded-full border-2 border-white shadow-sm cursor-move',
    iconSize: [12, 12],
    iconAnchor: [6, 6],
});

const firstPointIcon = L.divIcon({
    className: 'bg-red-600 rounded-full border-2 border-white shadow-sm cursor-pointer animate-pulse',
    iconSize: [14, 14],
    iconAnchor: [7, 7],
});

const tempPointIcon = L.divIcon({
    className: 'bg-blue-500 rounded-full border-2 border-white shadow-sm',
    iconSize: [10, 10],
    iconAnchor: [5, 5],
});

// ====================================================================
// KAART LOGICA
// ====================================================================
const initMap = () => {
    // Startpositie: Midden van Nederland of het bestaande centrum van het water
    const startLat = parseFloat(form.center_lat) || 52.1326;
    const startLng = parseFloat(form.center_lng) || 5.2913;
    const zoomLevel = isEdit && form.center_lat ? 14 : 7;

    map = L.map(mapContainer.value).setView([startLat, startLng], zoomLevel);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Laad bestaande grenzen (polygoon) indien aanwezig
    if (form.boundary) {
        try {
            const geoJson = typeof form.boundary === 'string' ? JSON.parse(form.boundary) : form.boundary;
            const layer = L.geoJSON(geoJson);
            const layers = layer.getLayers();
            
            if (layers.length > 0) {
                // Haal coördinaten op en maak de bewerkbare polygoon aan
                const latlngs = layers[0].getLatLngs()[0];
                createPolygon(latlngs);
                map.fitBounds(polygonLayer.getBounds());
            }
        } catch (e) {
            console.error("Error parsing boundary GeoJSON", e);
        }
    }

    // Click handler: Voeg punten toe als er nog geen polygoon is
    map.on('click', (e) => {
        if (hasPolygon.value) return; 
        addTempPoint(e.latlng);
    });
};

// Voegt een tijdelijk punt toe tijdens het tekenen
const addTempPoint = (latlng) => {
    tempPoints.push(latlng);
    
    const isFirst = tempPoints.length === 1;
    const icon = isFirst ? firstPointIcon : tempPointIcon;
    
    const marker = L.marker(latlng, { icon: icon }).addTo(map);
    markers.push(marker);

    // Als op het eerste punt wordt geklikt, sluit de polygoon
    if (isFirst) {
        marker.on('click', () => {
            if (tempPoints.length >= 3) {
                finishPolygon();
            }
        });
    }

    drawTempLines();
};

// Tekent stippellijnen tussen tijdelijke punten
const drawTempLines = () => {
    if (tempLine) map.removeLayer(tempLine);
    if (tempPoints.length > 1) {
        tempLine = L.polyline(tempPoints, { color: '#3b82f6', dashArray: '5, 10' }).addTo(map);
    }
};

// Rond het tekenproces af en converteert punten naar een polygoon
const finishPolygon = () => {
    createPolygon(tempPoints);
    // Opruimen tijdelijke teken-state
    tempPoints = [];
    if (tempLine) map.removeLayer(tempLine);
    tempLine = null;
};

// Maakt de definitieve, bewerkbare polygoon aan
const createPolygon = (latlngs) => {
    // Verwijder oude markers
    markers.forEach(m => map.removeLayer(m));
    markers = [];

    // Maak de polygoon laag
    polygonLayer = L.polygon(latlngs, { color: '#2563eb', weight: 2, fillColor: '#3b82f6', fillOpacity: 0.3 }).addTo(map);
    hasPolygon.value = true;

    // Maak sleepbare handgrepen op elk hoekpunt voor bewerking
    latlngs.forEach((latlng, index) => {
        const marker = L.marker(latlng, { icon: handleIcon, draggable: true }).addTo(map);
        
        marker.on('drag', (e) => {
            const newLatLngs = polygonLayer.getLatLngs()[0];
            newLatLngs[index] = e.latlng;
            polygonLayer.setLatLngs([newLatLngs]); // Update vorm
            updateForm();
        });

        markers.push(marker);
    });

    updateForm();
};

// Reset de tekening zodat de gebruiker opnieuw kan beginnen
const resetPolygon = () => {
    if (polygonLayer) map.removeLayer(polygonLayer);
    markers.forEach(m => map.removeLayer(m));
    if (tempLine) map.removeLayer(tempLine);
    
    polygonLayer = null;
    markers = [];
    tempPoints = [];
    tempLine = null;
    hasPolygon.value = false;
    
    form.boundary = null;
};

// Update de formulierdata met de huidige geometrie en centrum
const updateForm = () => {
    if (!polygonLayer) return;
    
    // Sla geometrie op als GeoJSON
    const geoJson = polygonLayer.toGeoJSON();
    form.boundary = JSON.stringify(geoJson.geometry);
    
    // Bereken en sla het centrum op (voor markers/zoom)
    const center = polygonLayer.getBounds().getCenter();
    form.center_lat = center.lat.toFixed(6);
    form.center_lng = center.lng.toFixed(6);
};

const submit = () => {
    if (isEdit) {
        form.put(route('beheer.waters.update', props.water.id));
    } else {
        form.post(route('beheer.waters.store'));
    }
};

onMounted(() => {
    initMap();
});
</script>

<template>
    <Head :title="isEdit ? 'Water Bewerken' : 'Nieuw Water'" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ isEdit ? 'Water Bewerken: ' + form.naam : 'Nieuw Water Toevoegen' }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Left Column: Details -->
                            <div class="space-y-6">
                                <div>
                                    <InputLabel for="naam" value="Naam van het Water" />
                                    <TextInput id="naam" type="text" class="mt-1 block w-full" v-model="form.naam" required autofocus />
                                    <InputError class="mt-2" :message="form.errors.naam" />
                                </div>

                                <div>
                                    <InputLabel for="beschrijving" value="Beschrijving" />
                                    <!-- Gebruik van de nieuwe WYSIWYG editor -->
                                    <WysiwygInput id="beschrijving" class="mt-1 block w-full" v-model="form.beschrijving" />
                                    <InputError class="mt-2" :message="form.errors.beschrijving" />
                                </div>

                                <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg border border-gray-100">
                                    <div class="col-span-2 text-sm font-medium text-gray-700 mb-1">Automatische Locatiegegevens</div>
                                    <div>
                                        <InputLabel for="center_lat" value="Lat" class="text-xs" />
                                        <TextInput id="center_lat" type="text" class="mt-1 block w-full bg-gray-100 text-sm" v-model="form.center_lat" readonly />
                                    </div>
                                    <div>
                                        <InputLabel for="center_lng" value="Lng" class="text-xs" />
                                        <TextInput id="center_lng" type="text" class="mt-1 block w-full bg-gray-100 text-sm" v-model="form.center_lng" readonly />
                                    </div>
                                    <p class="col-span-2 text-xs text-gray-500 italic">
                                        Deze coördinaten worden berekend op basis van het midden van het getekende vlak.
                                    </p>
                                </div>
                            </div>

                            <!-- Right Column: Map -->
                            <div>
                                <div class="flex justify-between items-end mb-2">
                                    <InputLabel value="Intekenen op Kaart" />
                                    <button v-if="hasPolygon" type="button" @click="resetPolygon" class="text-xs text-red-600 hover:text-red-800 underline">
                                        Reset Tekening
                                    </button>
                                </div>
                                
                                <div class="text-sm text-gray-600 mb-3 bg-blue-50 p-3 rounded border border-blue-100">
                                    <ul class="list-disc pl-4 space-y-1">
                                        <li v-if="!hasPolygon">Klik op de kaart om hoekpunten te plaatsen.</li>
                                        <li v-if="!hasPolygon">Klik op het <strong class="text-red-600">rode startpunt</strong> om het vlak te sluiten.</li>
                                        <li v-if="hasPolygon">Sleep de <strong class="text-orange-500">oranje punten</strong> om de vorm aan te passen.</li>
                                    </ul>
                                </div>

                                <div ref="mapContainer" class="w-full h-96 rounded-lg border border-gray-300 shadow-inner z-0"></div>
                                <InputError class="mt-2" :message="form.errors.boundary" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-8 pt-4 border-t border-gray-100">
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                {{ isEdit ? 'Wijzigingen Opslaan' : 'Water Aanmaken' }}
                            </PrimaryButton>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>