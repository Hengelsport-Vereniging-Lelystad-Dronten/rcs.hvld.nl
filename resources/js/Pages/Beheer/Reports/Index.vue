<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    // Global totals
    totals: {
        type: Object,
        default: () => ({ violations: 0, rounds: 0, active_controllers: 0 })
    },
    // Data lists
    byWater: {
        type: Array,
        default: () => []
    },
    byType: {
        type: Array,
        default: () => []
    },
    byController: {
        type: Array,
        default: () => []
    },
    byMonth: {
        type: Array,
        default: () => []
    },
    recidivism: {
        type: Array,
        default: () => []
    },
    // Filters
    filters: {
        type: Object,
        default: () => ({ start_date: '', end_date: '', user_id: '' })
    },
    // Dropdown options
    users: {
        type: Array,
        default: () => []
    }
});

const filterForm = ref({
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    user_id: props.filters.user_id || '',
});

const applyFilters = () => {
    router.get(route('beheer.reports.index'), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    filterForm.value = {
        start_date: '',
        end_date: '',
        user_id: '',
    };
    applyFilters();
};

// Helper for percentage bars
const calculatePercentage = (value, total) => {
    if (!total) return 0;
    return Math.round((value / total) * 100);
};

const maxMonthCount = computed(() => {
    if (!props.byMonth || props.byMonth.length === 0) return 1;
    return Math.max(...props.byMonth.map(m => m.count)) || 1;
});
</script>

<template>
    <Head title="Rapportages & Statistieken" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Rapportages & Statistieken</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Filters Section -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Filter Opties</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div>
                            <InputLabel for="start_date" value="Start Datum" />
                            <TextInput id="start_date" type="date" v-model="filterForm.start_date" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel for="end_date" value="Eind Datum" />
                            <TextInput id="end_date" type="date" v-model="filterForm.end_date" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel for="user_id" value="Specifieke Controleur" />
                            <select id="user_id" v-model="filterForm.user_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">-- Alle Controleurs --</option>
                                <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                            </select>
                        </div>
                        <div class="flex space-x-2">
                            <PrimaryButton @click="applyFilters">Filteren</PrimaryButton>
                            <button @click="resetFilters" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">Reset</button>
                            <a :href="route('beheer.reports.download', filterForm)" target="_blank" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                PDF
                            </a>
                        </div>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-blue-500">
                        <div class="text-gray-500 text-sm font-medium uppercase">Totaal Controles</div>
                        <div class="text-3xl font-bold text-gray-900 mt-1">{{ totals.rounds }}</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-red-500">
                        <div class="text-gray-500 text-sm font-medium uppercase">Totaal Overtredingen</div>
                        <div class="text-3xl font-bold text-gray-900 mt-1">{{ totals.violations }}</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-green-500">
                        <div class="text-gray-500 text-sm font-medium uppercase">Actieve Controleurs</div>
                        <div class="text-3xl font-bold text-gray-900 mt-1">{{ totals.active_controllers }}</div>
                    </div>
                </div>

                <!-- Grafiek: Overtredingen per Maand -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Overtredingen per Maand</h3>
                    <div v-if="byMonth.length === 0" class="text-gray-500 italic">Geen data beschikbaar voor deze periode.</div>
                    <div v-else class="flex items-end space-x-4 h-64 border-b border-gray-200 pb-2 overflow-x-auto">
                        <div v-for="(month, index) in byMonth" :key="index" class="flex flex-col items-center group relative min-w-[3rem]">
                            <!-- Tooltip -->
                            <div class="absolute bottom-full mb-2 hidden group-hover:block bg-gray-800 text-white text-xs rounded py-1 px-2 whitespace-nowrap z-10 shadow-lg">
                                {{ month.label }}: {{ month.count }} overtredingen
                            </div>
                            <!-- Bar -->
                            <div class="w-12 bg-blue-500 rounded-t hover:bg-blue-600 transition-all duration-300 relative" 
                                :style="{ height: (month.count / maxMonthCount * 100) + '%' }">
                                <span class="absolute -top-6 left-1/2 transform -translate-x-1/2 text-xs font-bold text-gray-700">{{ month.count }}</span>
                            </div>
                            <!-- Label -->
                            <div class="mt-2 text-xs text-gray-600 whitespace-nowrap">{{ month.label }}</div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    
                    <!-- Top Wateren -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Overtredingen per Water</h3>
                        <div v-if="byWater.length === 0" class="text-gray-500 italic">Geen data beschikbaar.</div>
                        <div v-else class="space-y-4">
                            <div v-for="(water, index) in byWater" :key="index">
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="font-medium text-gray-700">{{ water.name }}</span>
                                    <span class="text-gray-900 font-bold">{{ water.count }}</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="bg-blue-600 h-2.5 rounded-full" :style="{ width: calculatePercentage(water.count, totals.violations) + '%' }"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Overtreding Types -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Top Overtreding Types</h3>
                        <div v-if="byType.length === 0" class="text-gray-500 italic">Geen data beschikbaar.</div>
                        <div v-else class="space-y-4">
                            <div v-for="(type, index) in byType" :key="index">
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="font-medium text-gray-700">{{ type.code }} - {{ type.description }}</span>
                                    <span class="text-gray-900 font-bold">{{ type.count }}</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="bg-red-500 h-2.5 rounded-full" :style="{ width: calculatePercentage(type.count, totals.violations) + '%' }"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Controleur Statistieken -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Prestaties Controleurs</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Controleur</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aantal Rondes</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Geregistreerde Overtredingen</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gem. per Ronde</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="stat in byController" :key="stat.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ stat.name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ stat.rounds }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ stat.violations }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ stat.rounds > 0 ? (stat.violations / stat.rounds).toFixed(2) : '0.00' }}
                                    </td>
                                </tr>
                                <tr v-if="byController.length === 0">
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500 italic">Geen controleurs data gevonden.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recidive Gedrag -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Mogelijke Recidivisten (Top 10)</h3>
                    <p class="text-sm text-gray-500 mb-4">Onderstaande vispasnummers komen meerdere keren voor in de overtredingen database binnen de geselecteerde periode.</p>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vispasnummer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aantal Overtredingen</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Laatste Overtreding</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="(recidivist, index) in recidivism" :key="index">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                        <Link :href="route('beheer.reports.recidivist', recidivist.vispasnummer)" class="text-blue-600 hover:underline hover:text-blue-800" title="Bekijk dossier">
                                            {{ recidivist.vispasnummer }}
                                        </Link>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-bold">{{ recidivist.count }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ recidivist.last_violation_date }}</td>
                                </tr>
                                <tr v-if="recidivism.length === 0">
                                    <td colspan="3" class="px-6 py-4 text-center text-gray-500 italic">Geen recidivisten gevonden in deze selectie.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>