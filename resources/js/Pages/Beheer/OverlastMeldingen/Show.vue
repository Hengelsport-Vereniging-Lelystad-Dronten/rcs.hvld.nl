<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    melding: Object,
});

const selectedStatus = ref(props.melding.status || 'nieuw');
const interneNotities = ref('');
const afgewezenReden = ref('');
const updateMessage = ref('');
const updateError = ref('');
const updatingStatus = ref(false);

const statusTexts = {
    nieuw: 'Nieuw',
    in_behandeling: 'In behandeling',
    verwerkt: 'Verwerkt',
    afgewezen: 'Afgewezen',
};

const gemaakteDoor = computed(() => {
    return props.melding.verwerktDoor?.name ?? 'Onbekend';
});

const formatDate = (value) => (value ? new Date(value).toLocaleString('nl-NL') : '-');

const updateStatus = async () => {
    updateError.value = '';
    updateMessage.value = '';

    if (selectedStatus.value === 'afgewezen' && !afgewezenReden.value.trim()) {
        updateError.value = 'Geef een reden op voor afwijzing.';
        return;
    }

    updatingStatus.value = true;

    try {
        const response = await window.axios.patch(route('beheer.overlast-meldingen.update-status', props.melding.id), {
            status: selectedStatus.value,
            interne_notities: interneNotities.value || null,
            afgewezen_reden: selectedStatus.value === 'afgewezen' ? afgewezenReden.value : null,
        });

        if (response.data.success) {
            // Refresh melding properties lokaal
            props.melding.status = response.data.melding.status;
            props.melding.verwerkt_door = response.data.melding.verwerktDoor;
            props.melding.verwerkt_op = response.data.melding.verwerkt_op;
            updateMessage.value = 'Status succesvol bijgewerkt.';
            interneNotities.value = '';
            afgewezenReden.value = '';
        } else {
            updateError.value = response.data.message || 'Fout bij bijwerken status.';
        }
    } catch (e) {
        updateError.value = e.response?.data?.message ?? 'Fout bij bijwerken status.';
    } finally {
        updatingStatus.value = false;
    }
};
</script>

<template>
    <Head title="Overlastmelding detail" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Overlastmelding #{{ melding.id }}
                <span class="text-sm text-gray-500">({{ melding.categorie || 'Onbekend' }})</span>
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                    <div class="mb-6 flex justify-between items-start gap-2">
                        <div>
                            <p class="text-sm text-gray-500">ID: {{ melding.id }}</p>
                            <p class="text-sm text-gray-500">Ingediend: {{ formatDate(melding.created_at) }}</p>
                            <p class="text-sm text-gray-500">Bijgewerkt: {{ formatDate(melding.updated_at) }}</p>
                        </div>
                        <Link :href="route('beheer.overlast-meldingen.index')" class="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Terug naar overzicht</Link>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <p class="font-semibold">Naam Melder</p>
                            <p>{{ melding.naam_aanmelder ?? 'Niet opgegeven' }}</p>
                        </div>
                        <div>
                            <p class="font-semibold">E-mail Melder</p>
                            <p>{{ melding.email_aanmelder ?? 'Niet opgegeven' }}</p>
                        </div>
                        <div>
                            <p class="font-semibold">Telefoon</p>
                            <p>{{ melding.telefoon_aanmelder ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="font-semibold">Status</p>
                            <p>{{ statusTexts[melding.status] ?? melding.status }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="font-semibold">Categorie</p>
                            <p>{{ melding.categorie ?? '-' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="font-semibold">Beschrijving</p>
                            <p class="whitespace-pre-line">{{ melding.beschrijving ?? '-' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="font-semibold">Locatie</p>
                            <p>{{ melding.locatie_omschrijving ?? 'Niet opgegeven' }}</p>
                            <p class="text-sm text-gray-500">
                                Lat/Lng: {{ melding.latitude ?? '-' }}, {{ melding.longitude ?? '-' }}
                                <span v-if="melding.latitude && melding.longitude">
                                    •
                                    <a 
                                        :href="`https://www.google.com/maps/search/?api=1&query=${melding.latitude},${melding.longitude}`" 
                                        target="_blank" 
                                        rel="noopener noreferrer"
                                        class="text-blue-600 hover:text-blue-800"
                                    >
                                        Open in Google Maps
                                    </a>
                                </span>
                            </p>
                        </div>

                        <div class="md:col-span-2">
                            <p class="font-semibold">Bijlagen</p>
                            <div v-if="melding.foto_urls && melding.foto_urls.length > 0" class="grid gap-3 md:grid-cols-3 mt-2">
                                <div v-for="(foto, index) in melding.foto_urls" :key="index" class="border rounded overflow-hidden">
                                    <a :href="foto" target="_blank" rel="noopener noreferrer" class="block w-full h-full">
                                        <img :src="foto" alt="Bijlage {{ index + 1 }}" class="w-full h-32 object-cover" />
                                    </a>
                                </div>
                            </div>
                            <p v-else class="text-gray-500 mt-2">Geen bijlagen beschikbaar.</p>
                        </div>
                    </div>

                    <div class="mt-6 border-t pt-4">
                        <h3 class="text-lg font-semibold mb-3">Statusbeheer</h3>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select v-model="selectedStatus" class="w-full border-gray-300 rounded-lg">
                                    <option v-for="(label, key) in statusTexts" :key="key" :value="key">{{ label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Interne notitie</label>
                                <textarea v-model="interneNotities" rows="3" class="w-full border-gray-300 rounded-lg"></textarea>
                            </div>
                        </div>
                        <div v-if="selectedStatus === 'afgewezen'" class="mt-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reden van afwijzing</label>
                            <textarea v-model="afgewezenReden" rows="3" class="w-full border-red-300 rounded-lg"></textarea>
                        </div>

                        <div class="mt-3 flex gap-3 items-center">
                            <button @click.prevent="updateStatus" :disabled="updatingStatus" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                                {{ updatingStatus ? 'Bijwerken...' : 'Status bijwerken' }}
                            </button>
                            <p class="text-sm text-green-600" v-if="updateMessage">{{ updateMessage }}</p>
                            <p class="text-sm text-red-600" v-if="updateError">{{ updateError }}</p>
                        </div>
                    </div>

                    <div class="mt-6 border-t pt-4 text-sm text-gray-700">
                        <p><strong>Verwerkt door:</strong> {{ gemaakteDoor }}</p>
                        <p><strong>Verwerkt op:</strong> {{ formatDate(melding.verwerkt_op) }}</p>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
