<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    types: Array
});
</script>

<template>
    <Head title="Overtredingen & Maatregelen" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Overtredingen & Maatregelen</h2>
                <Link :href="route('uitleg.index')" class="text-sm text-gray-600 hover:text-gray-900">← Terug naar overzicht</Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <p class="mb-6 text-gray-600">
                            Hieronder vindt u een overzicht van de gedefinieerde overtredingen en de bijbehorende richtlijnen voor afhandeling.
                        </p>

                        <div class="space-y-6">
                            <div v-for="type in types" :key="type.id" class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900">
                                            <span class="text-red-600 mr-2">{{ type.code }}</span>
                                            <span v-html="type.omschrijving"></span>
                                        </h3>
                                    </div>
                                </div>
                                <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div class="bg-gray-100 p-3 rounded">
                                        <span class="block font-semibold text-gray-500 text-xs uppercase">Standaard Maatregel</span>
                                        <span class="font-medium text-gray-800">{{ type.default_strafmaat ? type.default_strafmaat.omschrijving : 'Geen standaard gedefinieerd' }}</span>
                                    </div>
                                    <div class="bg-red-50 p-3 rounded border border-red-100">
                                        <span class="block font-semibold text-red-500 text-xs uppercase">Bij Recidive (Herhaling)</span>
                                        <span class="font-medium text-red-800">{{ type.recidive_strafmaat ? type.recidive_strafmaat.omschrijving : 'Geen escalatie gedefinieerd' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>