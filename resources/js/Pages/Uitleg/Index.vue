<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Uitleg & Info" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Uitleg & Informatie</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Kaart: FAQ -->
                    <Link :href="route('uitleg.faq')" class="block p-6 bg-white overflow-hidden shadow-sm sm:rounded-lg hover:bg-gray-50 transition">
                        <h3 class="text-lg font-bold text-indigo-600 mb-2">❓ Veelgestelde Vragen (FAQ)</h3>
                        <p class="text-gray-600">
                            Antwoorden op praktische vragen over controles, bevoegdheden en procedures.
                        </p>
                    </Link>

                    <!-- Kaart: Overtredingen -->
                    <Link :href="route('uitleg.overtredingen')" class="block p-6 bg-white overflow-hidden shadow-sm sm:rounded-lg hover:bg-gray-50 transition">
                        <h3 class="text-lg font-bold text-red-600 mb-2">⚠️ Overtredingen & Maatregelen</h3>
                        <p class="text-gray-600">
                            Overzicht van alle overtredingstypes met bijbehorende standaard- en recidivemaatregelen.
                        </p>
                    </Link>

                    <!-- Kaart: Waterkaart -->
                    <Link :href="route('uitleg.kaart')" class="block p-6 bg-white overflow-hidden shadow-sm sm:rounded-lg hover:bg-gray-50 transition">
                        <h3 class="text-lg font-bold text-blue-600 mb-2">🗺️ Waterkaart</h3>
                        <p class="text-gray-600">
                            Interactieve kaart van de wateren, grenzen en specifieke aandachtspunten.
                        </p>
                    </Link>

                    <!-- Kaart: Handleidingen -->
                    <Link :href="route('uitleg.handleidingen')" class="block p-6 bg-white overflow-hidden shadow-sm sm:rounded-lg hover:bg-gray-50 transition">
                        <h3 class="text-lg font-bold text-green-600 mb-2">📚 Handleidingen & Documenten</h3>
                        <p class="text-gray-600">
                            Links naar handleidingen, protocollen en formulieren (Google Drive).
                        </p>
                    </Link>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>