<script setup>
// ====================================================================
// IMPORTS
// Hier importeren we de benodigde componenten en functionaliteit.
// ====================================================================
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
// Head: Voor het instellen van de paginatitel.
// Link: Inertia component voor client-side navigatie naar andere pagina's/routes.
import { Head, Link } from '@inertiajs/vue3';

// ====================================================================
// PROPS DEFINITIE
// Data die van de Laravel Controller via Inertia wordt doorgegeven.
// ====================================================================
// rondes: Een array met alle controle rondes, inclusief geneste relaties
//         zoals 'water' en 'user' en de telling van 'overtredingen_count'.
defineProps({
    rondes: {
        type: Array, 
        // ESSENTIEEL: Biedt een lege array als fallback (default: () => []), 
        // wat runtime fouten voorkomt als er geen data is doorgegeven.
        default: () => [], 
    },
});

// ====================================================================
// HELPER FUNCTIE
// ====================================================================

/**
 * Bepaalt de dynamische CSS-klassen (Tailwind) voor de status badge.
 * Dit zorgt ervoor dat de status visueel duidelijk is voor de gebruiker.
 * @param {string} status - De status van de ronde (bijv. 'Actief', 'Afgerond').
 * @returns {string} - De Tailwind klassenstring voor achtergrond en tekstkleur.
 */
const getStatusColor = (status) => {
    switch (status) {
        case 'Actief':
            return 'bg-green-100 text-green-800'; // Actieve rondes zijn groen
        case 'Afgerond':
            return 'bg-blue-100 text-blue-800';  // Afgeronde rondes zijn blauw
        case 'Geannuleerd':
            return 'bg-gray-200 text-gray-700';  // Geannuleerde rondes zijn grijs
        default: // Voor onbekende of andere statussen (bijv. Concept)
            return 'bg-gray-100 text-gray-800';
    }
};
</script>

<template>
    <!-- Stelt de paginatitel in (getoond in de browser tab) -->
    <Head title="Controle Rondes Overzicht" />

    <!-- Gebruikt de algemene geauthenticeerde lay-out (met navigatie en header) -->
    <AuthenticatedLayout>
        <template #header>
            <!-- Hoofdtitel van de pagina -->
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Controle Rondes Overzicht</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        
                        <!-- Sectie Header: Titel en de knop 'Start Nieuwe Ronde' -->
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-bold">Lopende en Afgesloten Rondes ({{ rondes.length }})</h3>
                            
                            <!-- Knop om te navigeren naar de aanmaakpagina voor een nieuwe ronde -->
                            <Link :href="route('controles.create')" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                ➕ Start Nieuwe Ronde
                            </Link>
                        </div>
                        
                        <!-- Flash Message Weergave (bijvoorbeeld na succesvol opslaan of verwijderen) -->
                        <!-- $page.props.flash.success is een Inertia conventie voor succesberichten -->
                        <div v-if="$page.props.flash.success" class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg">
                            {{ $page.props.flash.success }}
                        </div>

                        <!-- Tabel Header voor Desktop (hidden md:block) -->
                        <div class="hidden md:block">
                            <div class="grid grid-cols-6 gap-4 px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-200">
                                <div>Water</div>
                                <div>Controleur</div>
                                <div>Start Tijd</div>
                                <div>Overtredingen</div> 
                                <div>Status</div>
                                <div>Acties</div>
                            </div>
                        </div>

                        <!-- Lijst met Controles (v-for loop over rondes) -->
                        <div class="divide-y divide-gray-200">
                            <div 
                                v-for="ronde in rondes" 
                                :key="ronde.id" 
                                class="p-4 hover:bg-gray-50 transition duration-150"
                            >
                                <!-- Responsieve Grid: 1 kolom op mobiel (grid-cols-1), 6 kolommen op desktop (md:grid-cols-6) -->
                                <div class="grid grid-cols-1 md:grid-cols-6 gap-2 md:gap-4 items-center">
                                    <div class="md:p-2">
                                        <!-- Mobiel label (zichtbaar op mobiel, hidden md:hidden) -->
                                        <span class="md:hidden font-bold block text-sm text-gray-500">Water:</span>
                                        <!-- Naam van het gecontroleerde water -->
                                        <span class="font-medium text-gray-900">{{ ronde.water.naam }}</span>
                                    </div>
                                    <div class="md:p-2">
                                        <span class="md:hidden font-bold block text-sm text-gray-500">Controller:</span>
                                        <!-- Naam van de gebruiker die de ronde startte -->
                                        {{ ronde.user.name }}
                                    </div>
                                    <div class="md:p-2">
                                        <span class="md:hidden font-bold block text-sm text-gray-500">Start Tijd:</span>
                                        <!-- Toont de starttijd, geformatteerd naar Nederlandse locale -->
                                        {{ new Date(ronde.start_tijd).toLocaleString('nl-NL') }}
                                    </div>
                                    
                                    <div class="md:p-2">
                                        <span class="md:hidden font-bold block text-sm text-gray-500">Overtredingen:</span>
                                        <!-- Telt het aantal overtredingen (overtredingen_count komt van de backend) -->
                                        <span :class="['font-medium', {'text-red-600 font-bold': ronde.overtredingen_count > 0, 'text-gray-500': ronde.overtredingen_count === 0}]">
                                            {{ ronde.overtredingen_count }}
                                        </span>
                                    </div>

                                    <div class="md:p-2">
                                        <span class="md:hidden font-bold block text-sm text-gray-500">Status:</span>
                                        <!-- Status Badge met dynamische kleur op basis van getStatusColor() -->
                                        <span :class="['px-2 inline-flex text-xs leading-5 font-semibold rounded-full', getStatusColor(ronde.status)]">
                                            {{ ronde.status }}
                                        </span>
                                    </div>
                                    <div class="md:p-2">
                                        <!-- Link naar de detailpagina van de ronde (controles.show) -->
                                        <Link :href="route('controles.show', ronde.id)" class="text-indigo-600 hover:text-indigo-900 font-medium">Bekijk Details</Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Bericht als de lijst leeg is -->
                        <p v-if="rondes.length === 0" class="mt-4 text-gray-500">Er zijn nog geen controles uitgevoerd.</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>