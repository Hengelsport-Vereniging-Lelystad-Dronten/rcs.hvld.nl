<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const props = defineProps({
    waters: {
        type: Array,
        default: () => []
    }
});

const mapContainer = ref(null);

onMounted(() => {
    // Functie om de kaart te initialiseren met Leaflet (OpenStreetMap)
    const initMap = () => {
        if (!mapContainer.value || typeof L === 'undefined') return;

        // Startpositie (bijv. midden van Nederland)
        // Leaflet gebruikt [lat, lng] array
        const map = L.map(mapContainer.value).setView([52.5000, 5.5653], 12);

        // OpenStreetMap Tiles toevoegen
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Loop door alle wateren en plaats markers
        props.waters.forEach(water => {
            const lat = parseFloat(water.latitude);
            const lng = parseFloat(water.longitude);

            if (!isNaN(lat) && !isNaN(lng)) {
                const marker = L.marker([lat, lng]).addTo(map);

                const content = `
                    <div style="padding: 4px;">
                        <h3 style="font-weight:bold; font-size: 1.1em; margin-bottom: 4px;">${water.naam}</h3>
                        <p style="font-size: 0.9em;">${water.beschrijving || ''}</p>
                    </div>
                `;
                
                marker.bindPopup(content);
            }
        });
    };

    // Leaflet CSS laden
    if (!document.getElementById('leaflet-css')) {
        const link = document.createElement('link');
        link.id = 'leaflet-css';
        link.rel = 'stylesheet';
        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(link);
    }

    // Leaflet JS laden en kaart initialiseren
    if (typeof L === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.async = true;
        script.onload = initMap;
        document.head.appendChild(script);
    } else {
        initMap();
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
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Locaties van Wateren</h3>
                    
                    <!-- De kaart container -->
                    <div ref="mapContainer" class="w-full h-[600px] bg-gray-100 rounded-lg border border-gray-300 flex items-center justify-center">
                        <span class="text-gray-500">Kaart wordt geladen...</span>
                    </div>

                    <div v-if="waters.length === 0" class="mt-4 text-sm text-gray-500 italic">
                        Er zijn geen wateren met coördinaten beschikbaar om te tonen.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>