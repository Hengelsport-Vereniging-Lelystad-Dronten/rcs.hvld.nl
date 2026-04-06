<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref, reactive } from 'vue';

const props = defineProps({
    nietGeexporteerdCount: Number,
    geexporteerdCount: Number,
    nietExporterenCount: Number,
    overtredingTypes: Array,
    csrf_token: String,
});

const page = usePage();

// Haal zowel succes- als foutmeldingen op
const flashMessage = computed(() => {
    return page.props.flash.success || page.props.flash.message;
});

const flashError = computed(() => {
    return page.props.flash.error;
});

// Filters state
const filters = reactive({
    start_date: '',
    end_date: '',
    overtreding_type_id: '',
    export_status: 'wel_exporteren',
    force_re_export: false,
});

// Preview data
const previewOvertredingen = ref([]);
const selectedOvertredingen = ref([]);
const showPreview = ref(false);
const isLoadingPreview = ref(false);

// Functie voor bevestiging van reset
const confirmReset = () => {
    if (confirm('Weet u zeker dat u de export status wilt resetten voor alle overtredingen? Dit kan niet ongedaan worden gemaakt.')) {
        event.target.submit();
    }
};

// Preview functie
const loadPreview = async () => {
    isLoadingPreview.value = true;
    try {
        const response = await fetch(route('beheer.export-overtredingen.preview'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': props.csrf_token,
            },
            body: JSON.stringify(filters),
        });

        if (response.ok) {
            const data = await response.json();
            previewOvertredingen.value = data.overtredingen;
            selectedOvertredingen.value = data.overtredingen.map(o => o.id);
            showPreview.value = true;
        } else {
            alert('Fout bij het laden van de preview');
        }
    } catch (error) {
        console.error('Preview error:', error);
        alert('Fout bij het laden van de preview');
    } finally {
        isLoadingPreview.value = false;
    }
};

// Select/deselect alle overtredingen
const toggleSelectAll = () => {
    if (selectedOvertredingen.value.length === previewOvertredingen.value.length) {
        selectedOvertredingen.value = [];
    } else {
        selectedOvertredingen.value = previewOvertredingen.value.map(o => o.id);
    }
};

// Export functie met geselecteerde overtredingen
const exportSelected = () => {
    if (selectedOvertredingen.value.length === 0) {
        alert('Selecteer tenminste één overtreding om te exporteren');
        return;
    }

    // Maak een hidden form om de POST request te doen
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = route('beheer.export-overtredingen.pdf');

    // CSRF token
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = props.csrf_token;
    form.appendChild(csrfInput);

    // Filters
    Object.keys(filters).forEach(key => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = filters[key];
        form.appendChild(input);
    });

    // Geselecteerde overtredingen
    selectedOvertredingen.value.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'selected_overtredingen[]';
        input.value = id;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
};

// Update export status van een overtreding
const updateExportStatus = async (overtredingId, newStatus) => {
    try {
        const response = await fetch(route('beheer.export-overtredingen.update-status', { overtreding: overtredingId }), {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': props.csrf_token,
            },
            body: JSON.stringify({
                export_status: newStatus,
            }),
        });

        if (response.ok) {
            // Update de lokale data
            const overtreding = previewOvertredingen.value.find(o => o.id === overtredingId);
            if (overtreding) {
                overtreding.export_status = newStatus;
            }
        } else {
            alert('Fout bij het bijwerken van de export status');
        }
    } catch (error) {
        console.error('Update status error:', error);
        alert('Fout bij het bijwerken van de export status');
    }
};

// Helper functies
const formatDate = (dateString) => {
    if (!dateString) {
        return 'Geen datum';
    }

    const date = new Date(dateString);
    if (Number.isNaN(date.getTime())) {
        return 'Geen datum';
    }

    return date.toLocaleDateString('nl-NL');
};

const getOvertredingTypeName = (overtreding) => {
    if (!overtreding.overtreding_type) {
        return 'Onbekend';
    }

    if (overtreding.overtreding_type.code === '00') {
        return 'Melding (geen overtreding)';
    }

    return overtreding.overtreding_type.omschrijving || overtreding.overtreding_type.code || 'Onbekend';
};

const getLocatieName = (overtreding) => {
    if (!overtreding) {
        return 'Geen locatie';
    }

    return overtreding.resolved_locatie_naam
        || overtreding.locatie_details?.naam
        || overtreding.locatie_details?.water_naam
        || overtreding.locatie_details?.adres
        || overtreding.locatie_details?.locatie_omschrijving
        || overtreding.locatie_details?.omschrijving
        || overtreding.controle_ronde?.water?.naam
        || 'Geen locatie';
};
</script>

<template>
    <Head title="Export Overtredingen" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Export Overtredingen</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <!-- Flash Success Message -->
                <div v-if="flashMessage" class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg shadow">
                    {{ flashMessage }}
                </div>

                <!-- Flash Error Message -->
                <div v-if="flashError" class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg shadow">
                    {{ flashError }}
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Overzicht</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-blue-600">{{ nietGeexporteerdCount }}</div>
                                <div class="text-sm text-blue-800">Klaar voor export</div>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-green-600">{{ geexporteerdCount }}</div>
                                <div class="text-sm text-green-800">Reeds geëxporteerd</div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-gray-600">{{ nietExporterenCount }}</div>
                                <div class="text-sm text-gray-800">Niet exporteren (intern)</div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Filters</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Van datum</label>
                                <input
                                    v-model="filters.start_date"
                                    type="date"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tot datum</label>
                                <input
                                    v-model="filters.end_date"
                                    type="date"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Overtreding type</label>
                                <select
                                    v-model="filters.overtreding_type_id"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="">Alle types</option>
                                    <option
                                        v-for="type in overtredingTypes"
                                        :key="type.id"
                                        :value="type.id"
                                    >
                                        {{ type.code }} - {{ type.omschrijving }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Export status</label>
                                <select
                                    v-model="filters.export_status"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="wel_exporteren">Klaar voor export</option>
                                    <option value="niet_exporteren">Niet exporteren (intern)</option>
                                    <option value="geexporteerd">Reeds geëxporteerd</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex items-center mb-4">
                            <input
                                v-model="filters.force_re_export"
                                type="checkbox"
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                            <label class="ml-2 text-sm text-gray-700">Forceer her-export (inclusief eerder geëxporteerde)</label>
                        </div>
                        <button
                            @click="loadPreview"
                            :disabled="isLoadingPreview"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                        >
                            <span v-if="isLoadingPreview">Laden...</span>
                            <span v-else>Preview tonen ({{ previewOvertredingen.length }})</span>
                        </button>
                        <button
                            v-if="showPreview && selectedOvertredingen.length > 0"
                            @click="exportSelected"
                            class="ml-2 inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            Export geselecteerde ({{ selectedOvertredingen.length }})
                        </button>
                    </div>

                    <!-- Preview Tabel -->
                    <div v-if="showPreview" class="bg-white border rounded-lg overflow-hidden mb-6">
                        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                            <div class="flex items-center">
                                <input
                                    type="checkbox"
                                    :checked="selectedOvertredingen.length === previewOvertredingen.length && previewOvertredingen.length > 0"
                                    @change="toggleSelectAll"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                <span class="ml-2 text-sm font-medium text-gray-700">
                                    Selecteer alle ({{ selectedOvertredingen.length }} van {{ previewOvertredingen.length }} geselecteerd)
                                </span>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Select</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Datum</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Locatie</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Controleur</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="overtreding in previewOvertredingen" :key="overtreding.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input
                                                type="checkbox"
                                                :value="overtreding.id"
                                                v-model="selectedOvertredingen"
                                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            >
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ formatDate(overtreding.geconstateerd_op) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ getOvertredingTypeName(overtreding) }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            {{ getLocatieName(overtreding) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ overtreding.controle_ronde && overtreding.controle_ronde.user ? overtreding.controle_ronde.user.name : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <select
                                                :value="overtreding.export_status"
                                                @change="updateExportStatus(overtreding.id, $event.target.value)"
                                                class="text-xs rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            >
                                                <option value="wel_exporteren">Klaar voor export</option>
                                                <option value="niet_exporteren">Niet exporteren</option>
                                                <option value="geexporteerd">Geëxporteerd</option>
                                            </select>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="border-t pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Export Opties</h3>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
