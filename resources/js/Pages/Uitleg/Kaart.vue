<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    waters: Array,
});

const mapContainer = ref(null);
const showNachtviszones = ref(true);
let map = null;
let zoneLayerGroup = null;

const initMap = () => {
    // Default center (Netherlands)
    map = L.map(mapContainer.value).setView([52.1326, 5.2913], 8);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Layer group for zones, to easily toggle them
    zoneLayerGroup = L.featureGroup().addTo(map);

    const bounds = L.latLngBounds([]);

    props.waters.forEach(water => {
        if (water.boundary) {
            try {
                const geoJson = typeof water.boundary === 'string' ? JSON.parse(water.boundary) : water.boundary;
                
                // Water styling
                let color = '#2563eb'; // Blauw (HVLD)
                let fillColor = '#3b82f6';

                if (water.is_verboden) { color = '#dc2626'; fillColor = '#ef4444'; }
                else if (water.beheersgebied === 'SVU') { color = '#ea580c'; fillColor = '#f97316'; }

                const defaultStyle = {
                    color: color,
                    weight: 2,
                    fillColor: fillColor,
                    fillOpacity: 0.3
                };

                const waterLayer = L.geoJSON(geoJson, {
                    style: defaultStyle
                }).addTo(map);

                // Popup for water
                waterLayer.bindPopup(`
                    <strong>${water.naam}</strong><br>
                    ${water.is_verboden ? '<span class="text-red-600 font-bold">Verboden Water</span>' : 'Viswater'}<br>
                    ${water.beheersgebied ? 
                        `<span class="text-xs font-bold px-1 rounded ${water.beheersgebied === 'SVU' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800'}">${water.beheersgebied}</span>` 
                        : ''}
                `);

                // Highlight bij openen popup
                waterLayer.on('popupopen', () => {
                    waterLayer.setStyle({
                        weight: 5,
                        fillOpacity: 0.5
                    });
                });

                waterLayer.on('popupclose', () => {
                    waterLayer.setStyle(defaultStyle);
                });

                bounds.extend(waterLayer.getBounds());

                // Nachtviszones
                if (water.nachtviszones && water.nachtviszones.length > 0) {
                    water.nachtviszones.forEach(zone => {
                        try {
                            const zoneGeoJson = typeof zone.boundary === 'string' ? JSON.parse(zone.boundary) : zone.boundary;
                            
                            const zoneDefaultStyle = {
                                color: '#10b981', // Green-500
                                weight: 2,
                                fillColor: '#34d399', // Green-400
                                fillOpacity: 0.5,
                                dashArray: '5, 5'
                            };

                            const zoneLayer = L.geoJSON(zoneGeoJson, {
                                style: zoneDefaultStyle
                            }).addTo(zoneLayerGroup);

                            zoneLayer.bindPopup(`
                                <strong>Nachtviszone</strong><br>
                                <span class="text-xs">Onderdeel van: ${water.naam}</span>
                            `);

                            // Highlight bij openen popup
                            zoneLayer.on('popupopen', () => {
                                zoneLayer.setStyle({
                                    weight: 5,
                                    fillOpacity: 0.8,
                                    dashArray: '' // Solid border bij selectie
                                });
                            });

                            zoneLayer.on('popupclose', () => {
                                zoneLayer.setStyle(zoneDefaultStyle);
                            });
                        } catch (e) {
                            console.error("Error parsing zone GeoJSON", e);
                        }
                    });
                }

            } catch (e) {
                console.error("Error parsing water boundary", e);
            }
        }
    });

    if (bounds.isValid()) {
        map.fitBounds(bounds);
    }
};

onMounted(() => {
    initMap();
});

watch(showNachtviszones, (isVisible) => {
    if (!map || !zoneLayerGroup) return;

    if (isVisible) {
        if (!map.hasLayer(zoneLayerGroup)) {
            map.addLayer(zoneLayerGroup);
        }
    } else {
        map.removeLayer(zoneLayerGroup);
    }
});
</script>

<template>
    <Head title="Waterkaart" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Waterkaart & Nachtviszones</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="mb-4 flex flex-wrap gap-x-6 gap-y-2 text-sm items-center">
                        <div class="flex items-center">
                            <span class="w-4 h-4 bg-blue-500 opacity-50 mr-2 border border-blue-600"></span>
                            <span>HVLD Viswater</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-4 h-4 bg-orange-500 opacity-50 mr-2 border border-orange-600"></span>
                            <span>SVU Viswater</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-4 h-4 bg-red-500 opacity-50 mr-2 border border-red-600"></span>
                            <span>Verboden Water</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-4 h-4 bg-green-400 opacity-50 mr-2 border border-green-500 border-dashed"></span>
                            <span>Nachtviszone</span>
                        </div>
                        <div class="flex items-center ml-auto">
                            <input type="checkbox" id="toggle-zones" v-model="showNachtviszones" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                            <label for="toggle-zones" class="ml-2 font-medium cursor-pointer">Toon Nachtviszones</label>
                        </div>
                    </div>
                    <div ref="mapContainer" class="w-full h-[600px] rounded-lg border border-gray-300 z-0"></div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>