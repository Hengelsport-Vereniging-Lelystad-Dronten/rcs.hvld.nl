<script setup>
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const props = defineProps({
    waters: {
        type: Array,
        default: () => [],
    },
});

const mapContainer = ref(null);
let map = null;
const isLegendOpen = ref(true);

const locateUser = () => {
    if (!navigator.geolocation) {
        alert("Geolocatie wordt niet ondersteund door uw browser.");
        return;
    }

    navigator.geolocation.getCurrentPosition((position) => {
        const { latitude, longitude } = position.coords;
        if (map) {
            map.setView([latitude, longitude], 14);
            L.circleMarker([latitude, longitude], {
                radius: 8,
                fillColor: "#2563eb",
                color: "#ffffff",
                weight: 2,
                fillOpacity: 0.8
            }).addTo(map).bindPopup("U bent hier").openPopup();
        }
    }, (error) => {
        console.error(error);
        alert("Kon locatie niet ophalen. Controleer uw GPS-instellingen.");
    });
};

onMounted(() => {
    if (window.innerWidth < 640) {
        isLegendOpen.value = false;
    }

    // Startpositie: Midden van Nederland (of aangepast aan data)
    map = L.map(mapContainer.value).setView([52.1326, 5.2913], 9);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const bounds = L.latLngBounds();
    let hasLayers = false;

    props.waters.forEach(water => {
        // 1. Teken het water zelf
        if (water.boundary) {
            try {
                const geoJson = typeof water.boundary === 'string' ? JSON.parse(water.boundary) : water.boundary;
                
                // Stijl bepalen op basis van status
                const color = water.is_verboden ? '#dc2626' : '#2563eb'; // Rood-600 of Blauw-600
                const fillColor = water.is_verboden ? '#ef4444' : '#3b82f6'; // Rood-500 of Blauw-500
                
                const layer = L.geoJSON(geoJson, {
                    style: {
                        color: color,
                        weight: 2,
                        fillColor: fillColor,
                        fillOpacity: 0.3
                    }
                }).addTo(map);

                // Popup samenstellen
                const popupContent = `
                    <div class="min-w-[200px] font-sans">
                        <h3 class="font-bold text-lg mb-1 text-gray-900">${water.naam}</h3>
                        <div class="text-sm text-gray-600 mb-3 max-h-32 overflow-y-auto prose prose-sm">
                            ${water.beschrijving || 'Geen beschrijving beschikbaar.'}
                        </div>
                        <div class="flex items-center pt-2 border-t border-gray-100">
                            ${water.is_verboden 
                                ? '<span class="text-red-600 font-bold flex items-center text-sm">🚫 Verboden te vissen</span>' 
                                : '<span class="text-blue-600 font-bold flex items-center text-sm">🎣 Vissen toegestaan</span>'
                            }
                        </div>
                    </div>
                `;
                layer.bindPopup(popupContent);

                if (layer.getBounds) {
                    bounds.extend(layer.getBounds());
                    hasLayers = true;
                }
            } catch (e) {
                console.error(`Fout bij parsen boundary voor water ${water.id}`, e);
            }
        }

        // 2. Teken nachtviszones (als overlay)
        if (water.nachtviszones && water.nachtviszones.length > 0) {
            water.nachtviszones.forEach(zone => {
                try {
                    // Check of we de boundary direct hebben of via een property
                    const rawBoundary = zone.boundary || zone; 
                    const zoneGeoJson = typeof rawBoundary === 'string' ? JSON.parse(rawBoundary) : rawBoundary;

                    const zoneLayer = L.geoJSON(zoneGeoJson, {
                        style: {
                            color: '#10b981', // Emerald-500
                            weight: 2,
                            fillColor: '#34d399', // Emerald-400
                            fillOpacity: 0.4,
                            dashArray: '5, 5'
                        }
                    }).addTo(map);

                    zoneLayer.bindPopup(`
                        <div class="font-sans p-1">
                            <h3 class="font-bold text-sm text-green-700 flex items-center">
                                🌙 Nachtviszone
                            </h3>
                            <p class="text-xs text-gray-600 mt-1">
                                In dit gemarkeerde gebied is nachtvissen toegestaan, mits in bezit van de juiste documenten.
                            </p>
                            <p class="text-xs text-gray-400 mt-2 italic">
                                Behoort bij: ${water.naam}
                            </p>
                        </div>
                    `);
                    
                    // Zorg dat zones bovenop water liggen
                    zoneLayer.bringToFront();

                } catch (e) {
                    console.error(`Fout bij parsen nachtviszone voor water ${water.id}`, e);
                }
            });
        }
    });

    // Zoom de kaart zodat alles zichtbaar is
    if (hasLayers) {
        map.fitBounds(bounds, { padding: [50, 50] });
    }
});
</script>

<template>
    <Head title="Visplanner - Publieke Waterkaart" />

    <div class="flex flex-col h-screen bg-gray-100 overflow-hidden">
        <!-- Header -->
        <header class="bg-white shadow-sm z-20 relative">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center">
                    <ApplicationLogo class="block h-9 w-auto fill-current text-gray-800 mr-3" />
                    <div>
                        <h1 class="text-lg font-bold text-gray-900 leading-tight">HVLD Visplanner</h1>
                        <p class="text-xs text-gray-500 hidden sm:block">HVLD waterkaart & regelgeving</p>
                    </div>
                </div>
                
                <div class="text-xs text-gray-500 text-right">
                
                    <button @click="locateUser" class="bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 px-3 py-1.5 rounded-md text-xs font-semibold flex items-center transition shadow-sm">
                        <span class="mr-1 text-base">📍</span>
                        <span class="hidden sm:inline">Mijn Locatie</span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Map Container -->
        <main class="flex-grow relative z-0">
            <div ref="mapContainer" class="absolute inset-0 w-full h-full bg-gray-200"></div>

            <!-- Legenda Overlay -->
            <div class="absolute top-4 right-4 bg-white/95 backdrop-blur-sm p-4 rounded-lg shadow-lg z-[1000] max-w-[200px] border border-gray-200 text-sm">
                <div class="flex justify-between items-center cursor-pointer" @click="isLegendOpen = !isLegendOpen">
                    <h3 class="font-bold text-gray-800">Legenda</h3>
                    <span class="text-gray-500 text-xs ml-2">{{ isLegendOpen ? '▼' : '▲' }}</span>
                </div>
                <div v-show="isLegendOpen">
                <ul class="space-y-2 mt-2 pt-2 border-t border-gray-100">
                    <li class="flex items-center">
                        <span class="w-4 h-4 bg-blue-500/40 border-2 border-blue-600 rounded-sm mr-2"></span>
                        <span class="text-gray-700 text-xs">Viswater</span>
                    </li>
                    <li class="flex items-center">
                        <span class="w-4 h-4 bg-red-500/40 border-2 border-red-600 rounded-sm mr-2"></span>
                        <span class="text-gray-700 text-xs">Verboden Water</span>
                    </li>
                    <li class="flex items-center">
                        <span class="w-4 h-4 bg-green-400/40 border-2 border-green-500 border-dashed rounded-sm mr-2"></span>
                        <span class="text-gray-700 text-xs">Nachtviszone</span>
                    </li>
                </ul>
                <div class="mt-3 pt-2 border-t border-gray-100 text-[10px] text-gray-400 leading-tight">
                    Klik op een gebied voor details en regels.
                </div>
                </div>
            </div>

            <!-- Footer Disclaimer -->
            <footer class="absolute bottom-0 left-0 right-0 bg-white/90 backdrop-blur-sm border-t border-gray-200 p-2 text-center text-[10px] text-gray-500 z-[2000]">
                <p>
                    Deze kaart is een lokale informatiekaart van Hengelsportvereniging Lelystad-Dronten (HVLD).
                    De weergegeven wateren, zones en regels zijn bedoeld ter verduidelijking van de lokale
                    handhaving. Aan deze kaart kunnen geen rechten worden ontleend.
                </p>
            </footer>
        </main>
    </div>
</template>