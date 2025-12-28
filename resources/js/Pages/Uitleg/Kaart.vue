<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    waters: {
        type: Array,
        default: () => [],
    },
});

const mapContainer = ref(null);
let map = null;

onMounted(() => {
    // Initialize map centered on Netherlands
    map = L.map(mapContainer.value).setView([52.1326, 5.2913], 8);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const bounds = L.latLngBounds();
    let hasLayers = false;

    props.waters.forEach(water => {
        if (water.boundary) {
            try {
                const geoJson = typeof water.boundary === 'string' ? JSON.parse(water.boundary) : water.boundary;
                const layer = L.geoJSON(geoJson, {
                    style: {
                        color: '#2563eb',
                        weight: 2,
                        fillOpacity: 0.4
                    }
                }).addTo(map);

                layer.bindPopup(`
                    <div class="font-sans">
                        <h3 class="font-bold text-lg mb-1">${water.naam}</h3>
                        <div class="text-sm text-gray-600">${water.beschrijving || 'Geen beschrijving.'}</div>
                    </div>
                `);

                bounds.extend(layer.getBounds());
                hasLayers = true;
            } catch (e) {
                console.error(`Invalid boundary for water ${water.id}`, e);
            }
        } else if (water.latitude && water.longitude) {
            // Fallback for waters without polygon
            const marker = L.marker([water.latitude, water.longitude]).addTo(map);
            marker.bindPopup(`<b>${water.naam}</b>`);
            bounds.extend(marker.getLatLng());
            hasLayers = true;
        }
    });

    if (hasLayers) {
        map.fitBounds(bounds, { padding: [50, 50] });
    }
});
</script>

<template>
    <Head title="Waterkaart" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Waterkaart</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <p class="mb-4 text-gray-600">
                            Hieronder ziet u een overzicht van alle geregistreerde wateren en hun grenzen.
                            Klik op een gebied voor meer informatie.
                        </p>
                        
                        <div ref="mapContainer" class="w-full h-[600px] rounded-lg border border-gray-300 shadow-inner z-0"></div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>