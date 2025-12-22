<script setup>
// Dashboard.vue
// Pagina met overzichtskaarten (KPI's) en recente rondes; gebruikt door ingelogde gebruikers.
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';

defineProps({
    stats: Object, // De statistieken van de controller
    recentRondes: Array, // De laatste rondes
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Knop voor Nieuwe Ronde -->
                <div class="mb-6 flex justify-center md:justify-start">
                    <Link :href="route('controles.create')">
                        <PrimaryButton class="w-full md:w-auto text-center justify-center text-lg py-3 px-6">
                            Nieuwe Ronde Starten
                        </PrimaryButton>
                    </Link>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    
                    <!-- KPI Kaart: Totaal Overtredingen -->
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 rounded-xl">
                        <p class="text-sm font-medium text-gray-500">Totaal Overtredingen</p>
                        <p class="text-3xl font-bold text-red-600 mt-1">{{ stats.totalOvertredingen }}</p>
                    </div>

                    <!-- KPI Kaart: Actieve Rondes -->
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 rounded-xl">
                        <p class="text-sm font-medium text-gray-500">Actieve Rondes</p>
                        <p class="text-3xl font-bold text-amber-500 mt-1">{{ stats.activeRondes }}</p>
                    </div>

                    <!-- KPI Kaart: Aantal Controleurs -->
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 rounded-xl">
                        <p class="text-sm font-medium text-gray-500">Actieve Controleurs</p>
                        <p class="text-3xl font-bold text-indigo-600 mt-1">{{ stats.totalControleuren }}</p>
                    </div>

                    <!-- KPI Kaart: Meest Voorkomende Overtreding -->
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 rounded-xl">
                        <p class="text-sm font-medium text-gray-500">Top Overtreding</p>
                        <template v-if="stats.topOvertreding">
                            <p class="text-xl font-bold text-gray-800 mt-1 leading-tight">{{ stats.topOvertreding.omschrijving }}</p>
                            <p class="text-sm text-gray-600 mt-1">Geregistreerd: {{ stats.topOvertreding.count }}x</p>
                        </template>
                        <p v-else class="text-xl text-gray-500 mt-1">Nog geen data</p>
                    </div>

                </div>

                <!-- Recent Afgeronde Rondes & Meest Gecontroleerd Water -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 rounded-xl">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">Laatst Afgeronde Rondes</h3>
                        <ul class="divide-y divide-gray-200">
                            <li v-for="ronde in recentRondes" :key="ronde.id" class="py-3 flex justify-between items-center">
                                <div>
                                    <p class="font-medium text-gray-800">{{ ronde.water.naam }}</p>
                                    <p class="text-sm text-gray-500">
                                        Door: {{ ronde.user.name }} - Afgerond: {{ new Date(ronde.eind_tijd).toLocaleDateString() }}
                                    </p>
                                </div>
                                <!-- Link naar de detailpagina van de controle ronde -->
                                <a :href="route('controles.show', ronde.id)" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium p-1 rounded-md hover:bg-indigo-50 transition duration-150">Bekijk</a>
                            </li>
                            <li v-if="recentRondes.length === 0" class="py-3 text-gray-500 text-center">Geen afgeronde rondes gevonden.</li>
                        </ul>
                    </div>

                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 rounded-xl">
                         <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">Top Water Locatie</h3>
                        <template v-if="stats.topWater">
                            <p class="text-3xl font-bold text-teal-600">{{ stats.topWater.naam }}</p>
                            <p class="text-md text-gray-600 mt-1">
                                Totaal gecontroleerd: <span class="font-bold text-teal-700">{{ stats.topWater.count }}x</span>
                            </p>
                            <p class="text-sm text-gray-500 mt-4">
                                Dit is het water dat het meest gecontroleerd is door de controleurs.
                            </p>
                        </template>
                        <p v-else class="text-gray-500">Nog geen data om een top locatie te bepalen.</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
