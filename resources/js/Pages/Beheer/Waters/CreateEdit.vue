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
import { onMounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    water: {
        type: Object,
        default: null,
    },
    overtredingTypes: {
        type: Array,
        default: () => [],
    },
});

// Bepaal of we in bewerk-modus zijn op basis van de aanwezigheid van een ID
const isEdit = !!(props.water && props.water.id);

// Initialiseer het formulier met Inertia useForm
const form = useForm({
    naam: props.water ? props.water.naam : '',
    beheersgebied: props.water ? props.water.beheersgebied : 'HVLD',
    beschrijving: props.water ? props.water.beschrijving : '',
    boundary: props.water ? props.water.boundary : null,
    center_lat: props.water ? props.water.center_lat : null,
    center_lng: props.water ? props.water.center_lng : null,
    is_verboden: props.water ? !!props.water.is_verboden : false,
    default_overtreding_type_id: props.water ? props.water.default_overtreding_type_id : null,
    nachtviszones: props.water && props.water.nachtviszones ? props.water.nachtviszones.map(z => z.boundary) : [],
});

// Refs voor kaart elementen
const mapContainer = ref(null);
const hasPolygon = ref(!!props.water?.boundary);
const drawingMode = ref('water'); // 'water', 'hole', 'zone'

// Leaflet variabelen (niet reactief nodig omdat Leaflet zijn eigen state beheert)
let map = null;
let drawnItems = null; // FeatureGroup voor alle polygonen
let zoneItems = null; // FeatureGroup voor nachtviszones
let tempMarkers = []; // Markers tijdens het tekenen
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
    iconSize: [20, 20],
    iconAnchor: [10, 10],
});

const tempPointIcon = L.divIcon({
    className: 'bg-blue-500 rounded-full border-2 border-white shadow-sm',
    iconSize: [10, 10],
    iconAnchor: [5, 5],
});

// Helper voor kleuren op basis van status en beheersgebied
const getPolygonStyle = () => {
    if (form.is_verboden) {
        return { color: '#dc2626', fillColor: '#ef4444' }; // Rood (Verboden)
    }
    if (form.beheersgebied === 'SVU') {
        return { color: '#ea580c', fillColor: '#f97316' }; // Oranje (SVU)
    }
    return { color: '#2563eb', fillColor: '#3b82f6' }; // Blauw (HVLD)
};

// ====================================================================
// KAART LOGICA
// ====================================================================
const initMap = () => {
    // Startpositie: Midden van Nederland of het bestaande centrum van het water
    const startLat = parseFloat(form.center_lat) || 52.1326;
    const startLng = parseFloat(form.center_lng) || 5.2913;
    const zoomLevel = isEdit && form.center_lat ? 14 : 9;

    map = L.map(mapContainer.value).setView([startLat, startLng], zoomLevel);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // FeatureGroup voor opgeslagen polygonen
    drawnItems = new L.FeatureGroup().addTo(map);
    zoneItems = new L.FeatureGroup().addTo(map);

    // Laad bestaande grenzen (polygoon) indien aanwezig
    if (form.boundary) {
        try {
            const geoJson = typeof form.boundary === 'string' ? JSON.parse(form.boundary) : form.boundary;
            const layer = L.geoJSON(geoJson);
            
            layer.eachLayer((l) => {
                if (l instanceof L.Polygon) {
                    addEditablePolygon(l.getLatLngs(), true);
                }
            });

            if (drawnItems.getLayers().length > 0) {
                map.fitBounds(drawnItems.getBounds());
            }
        } catch (e) {
            console.error("Error parsing boundary GeoJSON", e);
        }
    }

    // Laad bestaande nachtviszones
    if (form.nachtviszones && form.nachtviszones.length > 0) {
        form.nachtviszones.forEach(zoneJson => {
            try {
                const geoJson = typeof zoneJson === 'string' ? JSON.parse(zoneJson) : zoneJson;
                const layer = L.geoJSON(geoJson);
                layer.eachLayer((l) => {
                    if (l instanceof L.Polygon) {
                        addEditableZone(l.getLatLngs(), true);
                    }
                });
            } catch (e) { console.error("Error parsing zone GeoJSON", e); }
        });
    }

    // Click handler: Voeg punten toe als er nog geen polygoon is
    map.on('click', (e) => {
        addTempPoint(e.latlng);
    });
};

// Voegt een tijdelijk punt toe tijdens het tekenen
const addTempPoint = (latlng) => {
    tempPoints.push(latlng);
    
    const isFirst = tempPoints.length === 1;
    const icon = isFirst ? firstPointIcon : tempPointIcon;
    
    const marker = L.marker(latlng, { icon: icon, zIndexOffset: 1000 }).addTo(map);
    tempMarkers.push(marker);

    // Voorkom dat klikken op de marker doorgaat naar de kaart of onderliggende polygonen en sluit indien startpunt
    marker.on('click', (e) => {
        L.DomEvent.stopPropagation(e.originalEvent || e);
        if (isFirst && tempPoints.length >= 3) {
            finishPolygon();
        }
    });

    if (isFirst) {
        marker.bindTooltip("Klik om te sluiten", { direction: 'top', offset: [0, -10] });
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
    if (drawingMode.value === 'hole') {
        // Gat toevoegen aan bestaand polygon
        let parentLayer = null;
        const holeLatlngs = [...tempPoints];

        drawnItems.eachLayer(layer => {
            // Haal outer ring op
            let latlngs = layer.getLatLngs();
            // Normaliseer naar array van rings als het een simpele polygon is
            if (latlngs.length > 0 && latlngs[0] instanceof L.LatLng) {
                latlngs = [latlngs];
            }
            
            // Check of eerste punt van gat in outer ring valt
            if (isPointInPolygon(holeLatlngs[0], latlngs[0])) {
                parentLayer = layer;
            }
        });

        if (parentLayer) {
            let latlngs = parentLayer.getLatLngs();
            if (latlngs.length > 0 && latlngs[0] instanceof L.LatLng) {
                latlngs = [latlngs];
            }
            latlngs.push(holeLatlngs);
            parentLayer.setLatLngs(latlngs);
            rebuildHandles(parentLayer);
            updateForm();
        } else {
            alert("Het getekende gat valt niet binnen een bestaand watervlak. Teken het gat volledig binnen een bestaand vlak.");
        }
    } else if (drawingMode.value === 'zone') {
        addEditableZone(tempPoints);
    } else {
        addEditablePolygon(tempPoints);
    }
    
    // Opruimen tijdelijke teken-state
    tempMarkers.forEach(m => map.removeLayer(m));
    tempMarkers = [];
    tempPoints = [];
    if (tempLine) map.removeLayer(tempLine);
    tempLine = null;
};

// Voegt een bewerkbare polygoon toe aan de kaart (en drawnItems)
const addEditablePolygon = (latlngs, skipUpdate = false) => {
    const { color, fillColor } = getPolygonStyle();
    const layer = L.polygon(latlngs, { color: color, weight: 2, fillColor: fillColor, fillOpacity: 0.3 }).addTo(drawnItems);
    
    // Voeg popup toe om vlak te verwijderen
    const popupContent = document.createElement('div');
    const btn = document.createElement('button');
    btn.innerText = 'Verwijder dit vlak';
    btn.className = 'text-red-600 font-bold hover:underline text-sm';
    btn.type = 'button';
    btn.onclick = () => {
        removeEditablePolygon(layer);
        map.closePopup();
    };
    popupContent.appendChild(btn);

    layer.on('click', (e) => {
        if (drawingMode.value === 'hole') {
            addTempPoint(e.latlng);
        } else {
            L.popup().setLatLng(e.latlng).setContent(popupContent).openOn(map);
        }
    });

    rebuildHandles(layer);
    if (!skipUpdate) {
        updateForm();
    }
};

// Voegt een bewerkbare nachtviszone toe
const addEditableZone = (latlngs, skipUpdate = false) => {
    const color = '#10b981'; // Green-500
    const fillColor = '#34d399'; // Green-400
    const layer = L.polygon(latlngs, { color: color, weight: 2, fillColor: fillColor, fillOpacity: 0.5, dashArray: '5, 5' }).addTo(zoneItems);
    
    // Voeg popup toe om vlak te verwijderen
    const popupContent = document.createElement('div');
    const btn = document.createElement('button');
    btn.innerText = 'Verwijder deze nachtviszone';
    btn.className = 'text-red-600 font-bold hover:underline text-sm';
    btn.type = 'button';
    btn.onclick = () => {
        removeEditableZone(layer);
        map.closePopup();
    };
    popupContent.appendChild(btn);

    layer.on('click', (e) => {
        // Als we aan het tekenen zijn, negeer clicks op zones (zodat we punten kunnen zetten)
        if (tempPoints.length > 0) return;
        
        L.DomEvent.stopPropagation(e);
        L.popup().setLatLng(e.latlng).setContent(popupContent).openOn(map);
    });

    rebuildHandles(layer);
    if (!skipUpdate) {
        updateForm();
    }
};

// Herbouwt de sleepbare handles voor een polygon (nodig na wijziging geometrie/gaten)
const rebuildHandles = (layer) => {
    // Verwijder oude handles
    if (layer._handles) {
        layer._handles.forEach(h => map.removeLayer(h));
    }
    layer._handles = [];

    let rings = layer.getLatLngs();
    // Normaliseer naar array van rings
    if (rings.length > 0 && rings[0] instanceof L.LatLng) {
        rings = [rings];
    }

    rings.forEach((ring, ringIndex) => {
        ring.forEach((latlng, pointIndex) => {
            const marker = L.marker(latlng, { icon: handleIcon, draggable: true }).addTo(map);
            
            marker.on('drag', (e) => {
                let currentRings = layer.getLatLngs();
                if (currentRings.length > 0 && currentRings[0] instanceof L.LatLng) {
                    currentRings = [currentRings];
                }
                currentRings[ringIndex][pointIndex] = e.latlng;
                layer.setLatLngs(currentRings);
                updateForm();
            });

            layer._handles.push(marker);
        });
    });
};

// Helper: Ray-casting algoritme om te checken of punt in polygon ligt
const isPointInPolygon = (point, vs) => {
    // point = {lat, lng}, vs = [{lat, lng}, ...]
    let x = point.lat, y = point.lng;
    let inside = false;
    for (let i = 0, j = vs.length - 1; i < vs.length; j = i++) {
        let xi = vs[i].lat, yi = vs[i].lng;
        let xj = vs[j].lat, yj = vs[j].lng;
        let intersect = ((yi > y) != (yj > y))
            && (x < (xj - xi) * (y - yi) / (yj - yi) + xi);
        if (intersect) inside = !inside;
    }
    return inside;
};

// Verwijder een specifiek vlak
const removeEditablePolygon = (layer) => {
    if (layer._handles) {
        layer._handles.forEach(h => map.removeLayer(h));
    }
    drawnItems.removeLayer(layer);
    updateForm();
};

// Verwijder een zone
const removeEditableZone = (layer) => {
    if (layer._handles) {
        layer._handles.forEach(h => map.removeLayer(h));
    }
    zoneItems.removeLayer(layer);
    updateForm();
};

// Watcher: Pas de kleur van de polygoon direct aan als de checkbox verandert
watch([() => form.is_verboden, () => form.beheersgebied], () => {
    if (drawnItems) {
        const { color, fillColor } = getPolygonStyle();
        drawnItems.eachLayer(layer => {
            layer.setStyle({ color, fillColor });
        });
    }
});

// Reset de volledige tekening
const resetMap = () => {
    map.closePopup();
    drawnItems.eachLayer(layer => removeEditablePolygon(layer));
    zoneItems.eachLayer(layer => removeEditableZone(layer));
    
    tempMarkers.forEach(m => map.removeLayer(m));
    if (tempLine) map.removeLayer(tempLine);
    
    tempMarkers = [];
    tempPoints = [];
    tempLine = null;
    
    updateForm();
};

// Update de formulierdata met de huidige geometrie en centrum
const updateForm = () => {
    const layers = drawnItems.getLayers();
    hasPolygon.value = layers.length > 0;

    if (layers.length === 0) {
        form.boundary = null;
        return;
    }
    
    // Sla geometrie op als GeoJSON (Polygon of MultiPolygon)
    const geoJson = drawnItems.toGeoJSON();
    // drawnItems is een FeatureGroup, toGeoJSON geeft een FeatureCollection.
    // We willen de geometrie van de features samenvoegen.
    if (layers.length === 1) {
        form.boundary = JSON.stringify(geoJson.features[0].geometry);
    } else {
        const coordinates = geoJson.features.map(f => f.geometry.coordinates);
        form.boundary = JSON.stringify({
            type: 'MultiPolygon',
            coordinates: coordinates
        });
    }
    
    // Bereken en sla het centrum op (voor markers/zoom)
    const center = drawnItems.getBounds().getCenter();
    form.center_lat = center.lat.toFixed(6);
    form.center_lng = center.lng.toFixed(6);

    // Update zones
    const zoneLayers = zoneItems.getLayers();
    form.nachtviszones = zoneLayers.map(layer => {
        const geoJson = layer.toGeoJSON();
        return JSON.stringify(geoJson.geometry);
    });
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
                                    <InputLabel for="beheersgebied" value="Beheersgebied" />
                                    <select id="beheersgebied" v-model="form.beheersgebied" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                        <option value="HVLD">HVLD</option>
                                        <option value="SVU">SportvisUnie (SVU)</option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.beheersgebied" />
                                </div>

                                <div>
                                    <InputLabel for="beschrijving" value="Beschrijving" />
                                    <!-- Gebruik van de nieuwe WYSIWYG editor -->
                                    <WysiwygInput id="beschrijving" class="mt-1 block w-full" v-model="form.beschrijving" />
                                    <InputError class="mt-2" :message="form.errors.beschrijving" />
                                </div>

                                <!-- Verboden Water Instellingen -->
                                <div class="p-4 bg-red-50 border border-red-100 rounded-lg">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" v-model="form.is_verboden" class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500">
                                        <span class="ml-2 text-sm font-bold text-red-700">Dit is een Verboden Water</span>
                                    </label>
                                    <p class="text-xs text-red-600 mt-1 ml-6">
                                        Verboden wateren worden rood gemarkeerd op de kaart en zijn bedoeld voor handhaving bij illegale visserij.
                                    </p>

                                    <div v-if="form.is_verboden" class="mt-4 ml-6">
                                        <InputLabel for="default_overtreding_type_id" value="Standaard Overtreding" class="text-red-800 text-xs" />
                                        <select id="default_overtreding_type_id" v-model="form.default_overtreding_type_id" class="mt-1 block w-full border-red-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm text-sm">
                                            <option :value="null">-- Geen standaard --</option>
                                            <option v-for="type in overtredingTypes" :key="type.id" :value="type.id">
                                                {{ type.code }} - {{ type.omschrijving }}
                                            </option>
                                        </select>
                                        <InputError class="mt-2" :message="form.errors.default_overtreding_type_id" />
                                    </div>
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
                                    <InputLabel value="Intekenen op Kaart" class="mb-0" />
                                    <div class="flex space-x-4">
                                        <select v-model="drawingMode" class="text-xs border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm py-1 pl-2 pr-8">
                                            <option value="water">💧 Watergrens</option>
                                            <option value="hole">✂️ Gat in Water</option>
                                            <option value="zone">🌙 Nachtviszone</option>
                                        </select>
                                        
                                        <div class="flex items-center space-x-3 text-xs">
                                            <span class="flex items-center">
                                                <span class="w-3 h-3 bg-blue-500 inline-block mr-1 rounded-sm"></span> HVLD
                                            </span>
                                            <span class="flex items-center">
                                                <span class="w-3 h-3 bg-orange-500 inline-block mr-1 rounded-sm"></span> SVU
                                            </span>
                                            <span class="flex items-center">
                                                <span class="w-3 h-3 bg-red-500 inline-block mr-1 rounded-sm"></span> Verboden
                                            </span>
                                            <span class="flex items-center">
                                                <span class="w-3 h-3 bg-green-500 inline-block mr-1 rounded-sm"></span> Zone
                                            </span>
                                        </div>

                                        <button v-if="hasPolygon" type="button" @click="resetMap" class="text-xs text-red-600 hover:text-red-800 underline">
                                            Alles Wissen
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="text-sm text-gray-600 mb-3 bg-blue-50 p-3 rounded border border-blue-100">
                                    <ul class="list-disc pl-4 space-y-1 text-xs">
                                        <li v-if="drawingMode === 'water'">Klik op de kaart om hoekpunten te plaatsen voor een <strong>nieuw watervlak</strong>.</li>
                                        <li v-if="drawingMode === 'hole'">Klik op de kaart om hoekpunten te plaatsen voor een <strong>gat</strong> (binnen een bestaand vlak).</li>
                                        <li v-if="drawingMode === 'zone'">Klik op de kaart om een <strong>nachtviszone</strong> (groen) te tekenen.</li>
                                        <li>Klik op het <strong class="text-red-600">rode startpunt</strong> om de vorm te sluiten.</li>
                                        <li v-if="hasPolygon">Sleep de <strong class="text-orange-500">oranje punten</strong> om de vorm aan te passen.</li>
                                        <li v-if="hasPolygon">Klik op een vlak om deze te verwijderen.</li>
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