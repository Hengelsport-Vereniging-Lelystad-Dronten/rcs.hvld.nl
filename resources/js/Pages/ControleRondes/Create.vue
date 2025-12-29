<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { onMounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    waters: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    water_id: '',
    start_tijd: new Date().toISOString().slice(0, 16), // Huidige tijd als default
});

const mapContainer = ref(null);
let map = null;
const waterLayers = {}; // Opslag voor map layers per water ID

onMounted(() => {
    // Initialiseer kaart (Centrum NL)
    map = L.map(mapContainer.value).setView([52.1326, 5.2913], 9);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const bounds = L.latLngBounds();
    let hasLayers = false;

    // Loop door alle wateren en teken ze op de kaart
    props.waters.forEach(water => {
        let layer = null;

        // Optie 1: Teken Polygoon als boundary bestaat
        if (water.boundary) {
            try {
                const geoJson = typeof water.boundary === 'string' ? JSON.parse(water.boundary) : water.boundary;
                layer = L.geoJSON(geoJson, {
                    style: {
                        color: water.is_verboden ? '#dc2626' : '#3b82f6', // Rood indien verboden, anders blauw
                        fillColor: water.is_verboden ? '#ef4444' : '#3b82f6',
                        weight: 2,
                        fillOpacity: 0.2
                    }
                });
            } catch (e) {
                console.error(`Fout bij parsen boundary voor water ${water.id}`, e);
            }
        } 
        // Optie 2: Fallback naar marker als er alleen een center punt is
        else if (water.latitude && water.longitude) {
            layer = L.marker([water.latitude, water.longitude]);
        }

        if (layer) {
            layer.addTo(map);
            layer.bindPopup(`<b>${water.naam}</b><br>Klik om te selecteren`);

            // Klik event: Selecteer dit water in het formulier
            layer.on('click', () => {
                form.water_id = water.id;
            });

            // Sla layer op voor latere referentie (styling updates)
            waterLayers[water.id] = layer;

            // Update bounds zodat alle wateren zichtbaar zijn
            if (layer.getBounds) {
                bounds.extend(layer.getBounds());
            } else if (layer.getLatLng) {
                bounds.extend(layer.getLatLng());
            }
            hasLayers = true;
        }
    });

    if (hasLayers) {
        map.fitBounds(bounds, { padding: [50, 50] });
    }
});

// Watcher: Update kaartstijl wanneer selectie verandert (via dropdown OF kaartklik)
watch(() => form.water_id, (newId) => {
    Object.keys(waterLayers).forEach(id => {
        const layer = waterLayers[id];
        const isSelected = id == newId;
        const water = props.waters.find(w => w.id == id);
        const isVerboden = water ? !!water.is_verboden : false;

        if (layer instanceof L.Path) { // Is Polygoon
            layer.setStyle({
                // Selectie = Oranje, Verboden = Rood, Standaard = Blauw
                color: isSelected ? '#f97316' : (isVerboden ? '#dc2626' : '#3b82f6'),
                fillColor: isSelected ? '#fb923c' : (isVerboden ? '#ef4444' : '#3b82f6'),
                fillOpacity: isSelected ? 0.5 : 0.2,
                weight: isSelected ? 3 : 2
            });
            if (isSelected) layer.bringToFront();
        } else if (layer instanceof L.Marker) { // Is Marker
            if (isSelected) layer.openPopup();
        }

        // Zoom naar selectie
        if (isSelected) {
            if (layer.getBounds) map.fitBounds(layer.getBounds());
            else if (layer.getLatLng) map.setView(layer.getLatLng(), 14);
        }
    });
});

const submit = () => {
    form.post(route('controles.store'));
};
</script>

<template>
    <Head title="Nieuwe Controle Ronde" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nieuwe Controle Ronde Starten</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Linker Kolom: Formulier -->
                        <div class="space-y-6">
                            <div>
                                <InputLabel for="water_id" value="Selecteer Water" />
                                <p class="text-xs text-gray-500 mb-2">Kies uit de lijst of klik op de kaart.</p>
                                <select id="water_id" v-model="form.water_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="" disabled>-- Kies een water --</option>
                                    <option v-for="water in waters" :key="water.id" :value="water.id">{{ water.naam }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.water_id" />
                            </div>

                            <div class="pt-4">
                                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" class="w-full justify-center">
                                    Start Controle
                                </PrimaryButton>
                            </div>
                        </div>

                        <!-- Rechter Kolom: Kaart -->
                        <div class="h-96 rounded-lg border border-gray-300 shadow-inner overflow-hidden relative z-0">
                            <div ref="mapContainer" class="h-full w-full"></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>