<script setup>
// Beheer/Reports/Index.vue
// Toont overzicht van alle gegenereerde periodieke rapporten met genereer-formulier.
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

defineProps({
    reports: Object,
});

const reportType = ref('weekly');
const periodStart = ref('');
const periodEnd = ref('');
const loading = ref(false);

const generateReport = () => {
    loading.value = true;
    router.post(route('beheer.reports.generate'), {
        report_type: reportType.value,
        period_start: periodStart.value || null,
        period_end: periodEnd.value || null,
    }, {
        onFinish: () => {
            loading.value = false;
        },
    });
};

const downloadReport = (reportId) => {
    window.location.href = route('beheer.reports.download', reportId);
};

const deleteReport = (reportId) => {
    if (!confirm('Weet je zeker dat je dit rapport wilt verwijderen? Dit kan niet ongedaan gemaakt worden.')) return;

    router.delete(route('beheer.reports.destroy', reportId), {}, {
        onFinish: () => {
            // eenvoudige refresh naar index
            router.reload();
        }
    });
};
</script>

<template>
    <Head title="Rapporten" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Periodieke Rapporten</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Genereer nieuw rapport formulier -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Nieuw Rapport Genereren</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rapporttype</label>
                            <select v-model="reportType" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="weekly">Wekelijks</option>
                                <option value="monthly">Maandelijks</option>
                                <option value="quarterly">Kwartaal</option>
                                <option value="custom">Custom Periode</option>
                            </select>
                        </div>

                        <div v-if="reportType === 'custom'" class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Startdatum</label>
                                <input v-model="periodStart" type="date" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Einddatum</label>
                                <input v-model="periodEnd" type="date" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                        </div>

                        <div>
                            <PrimaryButton @click="generateReport" :disabled="loading">
                                {{ loading ? 'Genereren...' : 'Rapport Genereren' }}
                            </PrimaryButton>
                        </div>
                    </div>
                </div>

                <!-- Overzicht gegenereerde rapporten -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Gegenereerde Rapporten</h3>

                        <div v-if="reports.data.length === 0" class="text-gray-500 text-center py-6">
                            Geen rapporten gegenereerd. Genereer er een hierboven.
                        </div>

                        <table v-else class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rapporttype</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aangemaakt door</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periode</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gegenereerd op</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acties</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="report in reports.data" :key="report.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ report.report_type }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ report.creator ? report.creator.name : 'Systeem' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ new Date(report.period_start).toLocaleDateString() }} - {{ new Date(report.period_end).toLocaleDateString() }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ new Date(report.generated_at).toLocaleString() }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                        <Link :href="route('beheer.reports.show', report.id)" class="text-indigo-600 hover:text-indigo-900">Bekijk</Link>
                                        <button @click="downloadReport(report.id)" class="text-indigo-600 hover:text-indigo-900">Download</button>
                                        <button @click="deleteReport(report.id)" class="text-red-600 hover:text-red-900">Verwijder</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
