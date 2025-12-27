<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    vispasnummer: String,
    violations: Array,
});
</script>

<template>
    <Head :title="'Recidive Dossier: ' + vispasnummer" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Dossier Vispasnummer: <span class="text-blue-600">{{ vispasnummer }}</span>
                </h2>
                <div class="flex items-center space-x-4">
                    <a :href="route('beheer.reports.recidivist.pdf', vispasnummer)" target="_blank" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Download PDF
                    </a>
                    <Link :href="route('beheer.reports.index')" class="text-sm text-gray-600 hover:text-gray-900 underline">
                        &larr; Terug
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Overtredingen Historie</h3>
                    
                    <div v-if="violations.length === 0" class="text-gray-500 italic">
                        Geen overtredingen gevonden voor dit nummer.
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Datum</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Water</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Overtreding</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Maatregel</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Controleur</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="violation in violations" :key="violation.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ new Date(violation.date).toLocaleDateString('nl-NL') }}
                                        <span class="text-xs text-gray-500 block">{{ new Date(violation.date).toLocaleTimeString('nl-NL', {hour: '2-digit', minute:'2-digit'}) }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ violation.water }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        <div class="font-medium">{{ violation.type }}</div>
                                        <div v-if="violation.details" class="text-xs text-gray-500 italic mt-1">{{ violation.details }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ violation.measure }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ violation.controller }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>