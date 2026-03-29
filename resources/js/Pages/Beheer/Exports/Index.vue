<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    exports: Object,
    csrf_token: String,
});

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('nl-NL', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head title="Export Overzicht" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">Export Overzicht</h1>
                                <p class="mt-1 text-sm text-gray-600">
                                    Overzicht van alle gegenereerde exports die beschikbaar zijn voor download.
                                </p>
                            </div>
                            <Link
                                href="/beheer/export-overtredingen"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Nieuwe Export
                            </Link>
                        </div>
                    </div>

                    <!-- Flash Messages -->
                    <div v-if="$page.props.flash.success" class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg mx-6 mt-4">
                        {{ $page.props.flash.success }}
                    </div>
                    <div v-if="$page.props.flash.error" class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg mx-6 mt-4">
                        {{ $page.props.flash.error }}
                    </div>

                    <div class="p-6">
                        <div v-if="exports.data.length === 0" class="text-center py-12">
                            <div class="text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">Geen exports gevonden</h3>
                                <p class="mt-1 text-sm text-gray-500">Er zijn nog geen exports gegenereerd.</p>
                            </div>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bestand</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Records</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aangemaakt</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Door</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acties</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="exportItem in exports.data" :key="exportItem.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ exportItem.original_filename }}</div>
                                            <div class="text-sm text-gray-500">PDF Document</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ exportItem.export_type }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ exportItem.record_count }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ formatDate(exportItem.created_at) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ exportItem.creator?.name || 'Onbekend' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                :class="exportItem.downloaded_at ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'"
                                            >
                                                {{ exportItem.downloaded_at ? 'Gedownload' : 'Nieuw' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a
                                                :href="`/beheer/exports/${exportItem.id}/download`"
                                                class="text-blue-600 hover:text-blue-900"
                                                target="_blank"
                                            >
                                                Download
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="exports.last_page > 1" class="mt-4">
                            <div class="flex justify-between items-center">
                                <div class="text-sm text-gray-700">
                                    Toont {{ exports.from }} tot {{ exports.to }} van {{ exports.total }} resultaten
                                </div>
                                <div class="flex space-x-1">
                                    <Link
                                        v-if="exports.prev_page_url"
                                        :href="exports.prev_page_url"
                                        class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50"
                                    >
                                        Vorige
                                    </Link>
                                    <Link
                                        v-if="exports.next_page_url"
                                        :href="exports.next_page_url"
                                        class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50"
                                    >
                                        Volgende
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>