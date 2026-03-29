<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    meldingen: Object,
    filters: {
        type: Object,
        default: () => ({ status: 'all', categorie: 'all', per_page: 15 }),
    },
});

const selectedStatus = ref(props.filters.status ?? 'all');
const selectedCategorie = ref(props.filters.categorie ?? 'all');
const selectedPerPage = ref(props.filters.per_page ?? 15);

const statusTexts = {
    nieuw: 'Nieuw',
    in_behandeling: 'In behandeling',
    afgehandeld: 'Afgehandeld',
    afgewezen: 'Afgewezen',
};

const formatDate = (value) => (value ? new Date(value).toLocaleString('nl-NL') : '-');

const applyFilters = () => {
    const query = {};

    if (selectedStatus.value && selectedStatus.value !== 'all') {
        query.status = selectedStatus.value;
    }

    if (selectedCategorie.value && selectedCategorie.value !== 'all') {
        query.categorie = selectedCategorie.value;
    }

    const searchParams = new URLSearchParams(query).toString();
    window.location.href = `${route('beheer.overlast-meldingen.index')}${searchParams ? '?' + searchParams : ''}`;
};

const clearFilters = () => {
    selectedStatus.value = 'all';
    selectedCategorie.value = 'all';
    window.location.href = route('beheer.overlast-meldingen.index');
};
</script>

<template>
    <Head title="Overlastmeldingen Beheer" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Overlastmeldingen Beheer</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Overzicht van meldingen ({{ meldingen.data.length }} / {{ meldingen.total }})</h3>
                        <div class="flex flex-wrap items-center gap-3">
                            <select v-model="selectedStatus" @change="applyFilters" class="border rounded px-2 py-1">
                                <option value="all">Alle statussen</option>
                                <option value="nieuw">Nieuw</option>
                                <option value="in_behandeling">In behandeling</option>
                                <option value="afgehandeld">Afgehandeld</option>
                                <option value="afgewezen">Afgewezen</option>
                            </select>
                            <select v-model="selectedCategorie" @change="applyFilters" class="border rounded px-2 py-1">
                                <option value="all">Alle categorieën</option>
                                <option value="vissterfte">Vissterfte</option>
                                <option value="onjuist_gedrag_vissers">Onjuist gedrag vissers</option>
                                <option value="dierenmishandeling">Dierenmishandeling</option>
                                <option value="illegale_visserij">Illegale visserij</option>
                                <option value="vervuiling">Vervuiling</option>
                                <option value="overig">Overig</option>
                            </select>
                            <select v-model="selectedPerPage" @change="applyFilters" class="border rounded px-2 py-1">
                                <option value="5">5 p/pagina</option>
                                <option value="10">10 p/pagina</option>
                                <option value="15">15 p/pagina</option>
                                <option value="25">25 p/pagina</option>
                                <option value="50">50 p/pagina</option>
                            </select>
                            <button @click="applyFilters" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">Filter</button>
                            <button @click="clearFilters" class="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Reset</button>
                        </div>
                    </div>

                    <div v-if="meldingen.length === 0" class="text-gray-600">Er zijn nog geen overlastmeldingen.</div>

                    <div v-else class="space-y-3">
                        <div v-for="melding in meldingen.data" :key="melding.id" class="border rounded-lg p-4 hover:shadow transition">
                            <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-2">
                                <div>
                                    <p class="font-semibold text-gray-900">#{{ melding.id }} • {{ melding.categorie || 'Onbekend' }}</p>
                                    <p class="text-sm text-gray-500">Status: {{ statusTexts[melding.status] ?? melding.status }}</p>
                                </div>
                                <div class="text-sm text-gray-700">
                                    Status: <span class="font-medium">{{ statusTexts[melding.status] ?? melding.status }}</span>
                                </div>
                            </div>

                            <p class="mt-2 text-sm text-gray-600">Locatie: {{ melding.locatie_omschrijving || 'Niet opgegeven' }}</p>
                            <p class="mt-1 text-sm text-gray-600">Lat/Lng: {{ melding.latitude ?? '-' }}, {{ melding.longitude ?? '-' }}</p>
                            <p class="mt-1 text-xs text-gray-500">Aangemaakt: {{ formatDate(melding.created_at) }}</p>

                            <div class="mt-3 flex justify-end gap-2">
                                <Link :href="route('beheer.overlast-meldingen.show', melding.id)" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">Bekijk</Link>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4">
                        <nav class="flex items-center justify-center space-x-2" aria-label="Pagination">
                            <a
                                v-for="link in meldingen.links"
                                :key="link.label"
                                :href="link.url"
                                v-html="link.label"
                                :class="[{ 'px-3 py-1 rounded border': true, 'bg-blue-600 text-white': link.active, 'bg-white text-gray-700 hover:bg-gray-100': !link.active, 'opacity-50 cursor-not-allowed': !link.url }]
                                "
                                :aria-current="link.active ? 'page' : null"
                                class="text-sm"
                            ></a>
                        </nav>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
