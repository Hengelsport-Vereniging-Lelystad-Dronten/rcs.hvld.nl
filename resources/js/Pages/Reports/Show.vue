<script setup>
// Reports/Show.vue
// Toont details van een gegenereerd rapport met statistieken en exportmogelijkheden.
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    report: Object,
});

const downloadAsJson = () => {
    const filename = `rapport_${report.report_type}_${new Date(report.period_start).toISOString().split('T')[0]}.json`;
    const dataStr = JSON.stringify(report.data_summary, null, 2);
    const dataUri = 'data:application/json;charset=utf-8,' + encodeURIComponent(dataStr);

    const exportFileDefaultName = filename;

    const linkElement = document.createElement('a');
    linkElement.setAttribute('href', dataUri);
    linkElement.setAttribute('download', exportFileDefaultName);
    linkElement.click();
};
</script>

<template>
    <Head title="Rapport Details" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Rapport: {{ report.report_type }}</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Rapport metadata -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Rapportdetails</h3>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <p class="text-sm text-gray-600">Rapporttype</p>
                            <p class="font-semibold">{{ report.report_type }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Periode</p>
                            <p class="font-semibold">{{ new Date(report.period_start).toLocaleDateString() }} - {{ new Date(report.period_end).toLocaleDateString() }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Gegenereerd op</p>
                            <p class="font-semibold">{{ new Date(report.generated_at).toLocaleString() }}</p>
                        </div>
                    </div>

                    <button @click="downloadAsJson" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Download als JSON
                    </button>
                </div>

                <!-- Samenvattingsstatistieken -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <p class="text-sm text-gray-600">Totaal Overtredingen</p>
                        <p class="text-3xl font-bold text-red-600">{{ report.data_summary.total_overtredingen }}</p>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <p class="text-sm text-gray-600">Totaal Controlerondes</p>
                        <p class="text-3xl font-bold text-blue-600">{{ report.data_summary.total_rondes }}</p>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <p class="text-sm text-gray-600">Ingename VISpassen</p>
                        <p class="text-3xl font-bold text-orange-600">{{ report.data_summary.vispas_ingenomen_count }}</p>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <p class="text-sm text-gray-600">Recidivegevallen</p>
                        <p class="text-3xl font-bold text-purple-600">{{ report.data_summary.recidive_count }}</p>
                    </div>
                </div>

                <!-- Top Overtredingstypes -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Overtredingstypes</h3>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Code</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Omschrijving</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Aantal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="type in report.data_summary.top_overtredingTypes" :key="type.code" class="border-b">
                                <td class="px-4 py-2 text-sm">{{ type.code }}</td>
                                <td class="px-4 py-2 text-sm">{{ type.omschrijving }}</td>
                                <td class="px-4 py-2 text-sm font-bold">{{ type.count }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Top Controleurs -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Meest Actieve Controleurs</h3>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Naam</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Rondes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="controleur in report.data_summary.top_controleurs" :key="controleur.id" class="border-b">
                                <td class="px-4 py-2 text-sm">{{ controleur.name }}</td>
                                <td class="px-4 py-2 text-sm font-bold">{{ controleur.rondes_count }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Top Wateren -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Meest Gecontroleerde Wateren</h3>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Water</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Controles</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="water in report.data_summary.top_wateren" :key="water.id" class="border-b">
                                <td class="px-4 py-2 text-sm">{{ water.naam }}</td>
                                <td class="px-4 py-2 text-sm font-bold">{{ water.control_count }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Terug link -->
                <div class="mt-6">
                    <Link href="/reports" class="text-indigo-600 hover:text-indigo-900">← Terug naar rapporten</Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
