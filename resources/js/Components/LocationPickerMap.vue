<template>
    <div>
        <div id="map" style="height: 400px;"></div>
    </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';

// Fix for broken marker icons
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

L.Icon.Default.mergeOptions({
  iconRetinaUrl: markerIcon2x,
  iconUrl: markerIcon,
  shadowUrl: markerShadow,
});


const props = defineProps({
    latitude: {
        type: Number,
        default: 52.5261545, // Default to Lelystad
    },
    longitude: {
        type: Number,
        default: 5.4729717, // Default to Lelystad
    },
});

const emit = defineEmits(['update:location']);

let map = null;
let marker = null;
const defaultCenter = [52.5261545, 5.4729717]; // Lelystad

onMounted(() => {
    const initialLat = props.latitude ?? defaultCenter[0];
    const initialLng = props.longitude ?? defaultCenter[1];

    map = L.map('map').setView([initialLat, initialLng], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map);

    marker = L.marker([initialLat, initialLng], {
        draggable: true,
    }).addTo(map);

    map.on('click', (e) => {
        if (e.latlng) {
            const { lat, lng } = e.latlng;
            marker.setLatLng([lat, lng]);
            emit('update:location', { lat, lng });
        }
    });

    marker.on('dragend', (e) => {
        const latlng = e.target.getLatLng();
        if (latlng) {
            const { lat, lng } = latlng;
            emit('update:location', { lat, lng });
        }
    });
});

watch(() => [props.latitude, props.longitude], ([newLat, newLng]) => {
    if (map && marker) {
        const lat = newLat ?? defaultCenter[0];
        const lng = newLng ?? defaultCenter[1];
        map.setView([lat, lng], 13);
        marker.setLatLng([lat, lng]);
    }
});
</script>
