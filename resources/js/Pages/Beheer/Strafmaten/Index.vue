<script setup>
// ====================================================================
// IMPORTS
// Hier importeren we de benodigde componenten en functionaliteit.
// ====================================================================
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
// ref is nodig voor lokale reactieve data (modals, etc.)
import { ref } from 'vue'; 

// ====================================================================
// PROPS DEFINITIE
// Data die van de Laravel Controller via Inertia wordt doorgegeven.
// ====================================================================
const props = defineProps({
    strafmaten: {
        type: Array,
        default: () => [],
    },
    currentSortBy: {
        type: String,
        default: 'order_id', // Standaard sorteren op order_id
    },
    currentSortDirection: {
        type: String,
        default: 'asc',
    },
});

// ====================================================================
// STATE MANAGEMENT
// ====================================================================
// Modale status voor het verwijderen van bevestiging
const showConfirmDeleteModal = ref(false);
const itemToDelete = ref(null); // Slaat het object op dat verwijderd moet worden


// ====================================================================
// FLASH MESSAGE AFHANDELING
// ====================================================================
const page = usePage();
// Haal de flash message op uit de props.
const flashSuccess = page.props.flash.success || page.props.flash.message;

// ====================================================================
// SORTERING EN ACTIES
// ====================================================================

/**
 * Past de sorteervolgorde aan wanneer een kolomheader wordt aangeklikt.
 * Dit verstuurt een nieuwe request naar de backend om de data gesorteerd op te halen.
 * @param {string} column - De databasenaam van de kolom om op te sorteren.
 */
const changeSorting = (column) => {
    let newDirection = 'asc';
    let newSortBy = column;

    // Als we al op deze kolom sorteren, keer de richting om
    if (props.currentSortBy === column) {
        newDirection = props.currentSortDirection === 'asc' ? 'desc' : 'asc';
    }

    // Verstuur de nieuwe sorteerparameters via Inertia
    router.get(route('beheer.strafmaten.index'), {
        sort_by: newSortBy,
        sort_direction: newDirection
    }, {
        // Behoud de scrollpositie en vervang de geschiedenis-entry
        preserveScroll: true,
        replace: true,
    });
};

/**
 * Toont de custom modal voor verwijdering en stelt het te verwijderen item in.
 * @param {object} strafmaat - Het strafmaat object.
 */
const confirmDeletion = (strafmaat) => {
    itemToDelete.value = strafmaat;
    showConfirmDeleteModal.value = true;
};

/**
 * Voert de DELETE-actie uit voor het geselecteerde Strafmaat object.
 */
const destroy = () => {
    if (!itemToDelete.value) return; 

    router.delete(route('beheer.strafmaten.destroy', itemToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Sluit de modal na succesvolle verwijdering
            showConfirmDeleteModal.value = false;
            itemToDelete.value = null;
        }
    });
};

/**
 * Sluit de modal en reset de selectie.
 */
const closeModal = () => {
    showConfirmDeleteModal.value = false;
    itemToDelete.value = null;
};
</script>

<template>
    <!-- Stelt de paginatitel in -->
    <Head title="Strafmaten Beheer" />

    <!-- Gebruikt de algemene geauthenticeerde lay-out -->
    <AuthenticatedLayout>
        <template #header>
            <!-- Hoofdtitel van de pagina -->
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Strafmaten Beheer</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Flash Message Weergave (na successvolle actie) -->
                <div v-if="flashSuccess" class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg shadow">
                    {{ flashSuccess }}
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <!-- Sectie Header: Titel en Nieuwe Strafmaat knop -->
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Gedefinieerde Strafmaten ({{ props.strafmaten.length }})</h3>
                        
                        <div class="flex space-x-3">
                            <!-- Link naar de aanmaakpagina -->
                            <Link :href="route('beheer.strafmaten.create')" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                + Nieuwe Strafmaat
                            </Link>
                        </div>
                    </div>

                    <!-- HEADERS: De tabel-headers voor de desktop weergave -->
                    <div class="hidden md:grid grid-cols-6 gap-4 px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-200 rounded-t-lg">
                        
                        <!-- Kolom: VOLGORDE (Klikbaar voor sortering) -->
                        <div class="col-span-1 cursor-pointer select-none flex items-center hover:text-gray-700 transition duration-150" @click="changeSorting('order_id')">
                            Volgorde
                            <!-- Sorteer-indicator -->
                            <svg v-if="currentSortBy === 'order_id'" :class="{'rotate-180': currentSortDirection === 'desc'}" class="w-3 h-3 ml-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>

                        <!-- Kolom: CODE (Klikbaar voor sortering) -->
                        <div class="col-span-1 cursor-pointer select-none flex items-center hover:text-gray-700 transition duration-150" @click="changeSorting('code')">
                            Code
                            <!-- Sorteer-indicator -->
                            <svg v-if="currentSortBy === 'code'" :class="{'rotate-180': currentSortDirection === 'desc'}" class="w-3 h-3 ml-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>

                        <!-- Kolom: OMSCHRIJVING (Klikbaar voor sortering) -->
                        <div class="col-span-3 cursor-pointer select-none flex items-center hover:text-gray-700 transition duration-150" @click="changeSorting('omschrijving')">
                            Omschrijving
                            <!-- Sorteer-indicator -->
                            <svg v-if="currentSortBy === 'omschrijving'" :class="{'rotate-180': currentSortDirection === 'desc'}" class="w-3 h-3 ml-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        
                        <!-- Kolom: ACTIES (Niet Sorteerbaar) -->
                        <div class="col-span-1 text-right">Acties</div>
                    </div>

                    <!-- DATARIJEN: De loop over alle strafmaten (de prop wordt nu direct gebruikt) -->
                    <div class="divide-y divide-gray-200 border-b border-gray-200">
                        <div 
                            v-for="strafmaat in props.strafmaten"
                            :key="strafmaat.id"
                            class="p-4 md:py-3 md:px-4 hover:bg-gray-50 transition duration-150 relative"
                        >
                            
                            <!-- De Responsieve Row Container -->
                            <div class="grid grid-cols-1 md:grid-cols-6 gap-y-3 md:gap-4 items-center">
                                
                                <!-- VOLGORDE Veld -->
                                <div class="md:col-span-1 flex items-center space-x-2">
                                    <span>{{ strafmaat.order_id }}</span>
                                </div>
                                
                                <!-- CODE Veld -->
                                <div class="md:col-span-1">
                                    <!-- Mobiel Label (alleen zichtbaar op mobiel) -->
                                    <p class="text-xs font-medium text-gray-500 uppercase mb-1 md:hidden">Code</p>
                                    <!-- Toont de code, of een streepje als er geen code is -->
                                    <span class="font-medium text-gray-900">{{ strafmaat.code || '-' }}</span>
                                </div>
                                
                                <!-- OMSCHRIJVING Veld -->
                                <div class="md:col-span-3">
                                    <!-- Mobiel Label -->
                                    <p class="text-xs font-medium text-gray-500 uppercase mb-1 md:hidden">Omschrijving</p>
                                    <span class="text-sm text-gray-700">{{ strafmaat.omschrijving }}</span>
                                </div>

                                <!-- ACTIES Veld: Bewerk en Verwijder Knoppen -->
                                <div class="md:col-span-1 flex justify-end space-x-4 pt-3 md:pt-0 border-t md:border-t-0 border-gray-100">
                                    
                                    <!-- Link naar de bewerkingspagina -->
                                    <Link :href="route('beheer.strafmaten.edit', strafmaat.id)" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                        Bewerk
                                    </Link>
                                    
                                    <!-- Knop om de custom modal voor verwijdering te starten -->
                                    <button @click="confirmDeletion(strafmaat)" class="text-red-600 hover:text-red-900 text-sm font-medium">
                                        Verwijder
                                    </button>
                                </div>

                            </div>
                        </div>
                        <!-- Melding als de lijst leeg is -->
                        <p v-if="props.strafmaten.length === 0" class="p-4 text-gray-500">Er zijn nog geen strafmaten gedefinieerd.</p>
                    </div>

                </div>
            </div>
        </div>
        
        <!-- ==================================================================== -->
        <!-- CUSTOM VERWIJDER MODAL (Ter vervanging van de verboden 'confirm()') -->
        <!-- ==================================================================== -->
        <div v-if="showConfirmDeleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Strafmaat Verwijderen</h3>
                <div class="mt-2">
                    <p class="text-sm text-gray-500">
                        Weet u zeker dat u de strafmaat
                        <span class="font-bold text-red-600">"{{ itemToDelete?.omschrijving }}" (ID: {{ itemToDelete?.id }})</span>
                        wilt verwijderen? Deze actie kan niet ongedaan worden gemaakt.
                    </p>
                </div>
                <div class="mt-4 flex justify-end space-x-3">
                    <button 
                        @click="closeModal" 
                        type="button" 
                        class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition duration-150"
                    >
                        Annuleren
                    </button>
                    <button 
                        @click="destroy" 
                        type="button" 
                        class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm transition duration-150"
                    >
                        Verwijderen
                    </button>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>