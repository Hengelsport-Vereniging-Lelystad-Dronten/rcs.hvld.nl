<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
// usePage is nodig om flash messages op te halen
import { computed } from 'vue'; 

const props = defineProps({
    waters: Array,
    // Flash messages komen vaak via usePage().props.flash binnen, maar de prop 
    // 'message' kan ook direct vanuit de controller worden meegegeven.
    message: String, 
});

const page = usePage();

// Haal de flash message op, of gebruik de direct meegegeven prop.
const flashMessage = computed(() => {
    return props.message || page.props.flash.success || page.props.flash.message;
});

// Functie voor het verwijderen van een Water
const destroy = (waterId) => {
    // Aangepaste modal/confirm is wenselijk i.v.m. de Canvas omgeving, maar we houden
    // voor nu de browser confirm, want het is een beheer actie.
    if (confirm('Weet u zeker dat u dit water wilt verwijderen? Dit kan gevolgen hebben voor gekoppelde controlerondes!')) {
        router.delete(route('beheer.waters.destroy', waterId), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Wateren Beheer" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Wateren Beheer</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Flash Message -->
                <div v-if="flashMessage" class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg shadow">
                    {{ flashMessage }}
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Overzicht Wateren ({{ waters.length }})</h3>
                        <Link :href="route('beheer.waters.create')" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            + Nieuw Water
                        </Link>
                    </div>

                    <!-- HEADERS: Alleen zichtbaar op desktop (md:) -->
                    <div class="hidden md:grid grid-cols-5 gap-4 px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-200 rounded-t-lg">
                        <div class="col-span-1">Naam</div>
                        <div class="col-span-3">Beschrijving</div>
                        <div class="col-span-1 text-right">Acties</div>
                    </div>

                    <!-- DATARIJEN: De kern van de responsieve weergave -->
                    <div class="divide-y divide-gray-200 border-b border-gray-200">
                        <div 
                            v-for="water in waters" 
                            :key="water.id" 
                            class="p-4 md:py-3 md:px-4 hover:bg-gray-50 transition duration-150"
                        >
                            
                            <!-- Op MD: 5 kolommen. Op SM: Gestapelde velden. -->
                            <div class="grid grid-cols-1 md:grid-cols-5 gap-y-3 md:gap-4 items-start">
                                
                                <!-- NAAM (col-span-1 op md) -->
                                <div class="md:col-span-1">
                                    <p class="text-xs font-medium text-gray-500 uppercase mb-1 md:hidden">Naam</p>
                                    <span class="font-medium text-gray-900">{{ water.naam }}</span>
                                </div>
                                
                                <!-- BESCHRIJVING (col-span-3 op md) -->
                                <div class="md:col-span-3">
                                    <p class="text-xs font-medium text-gray-500 uppercase mb-1 md:hidden">Beschrijving</p>
                                    <!-- De beschrijving is nu beperkt in hoogte op desktop, maar goed leesbaar op mobiel. -->
                                    <div class="text-sm text-gray-700 max-h-24 overflow-hidden md:max-h-full" v-html="water.beschrijving"></div>
                                </div>

                                <!-- ACTIES (col-span-1 op md) -->
                                <div class="md:col-span-1 flex justify-end space-x-4 pt-3 md:pt-0 border-t md:border-t-0 border-gray-100 w-full md:w-auto">
                                    <Link :href="route('beheer.waters.edit', water.id)" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                        Bewerk
                                    </Link>
                                    <button @click="destroy(water.id)" class="text-red-600 hover:text-red-900 text-sm font-medium">
                                        Verwijder
                                    </button>
                                </div>

                            </div>
                        </div>
                        
                        <p v-if="waters.length === 0" class="p-4 text-gray-500">Er zijn nog geen wateren gedefinieerd.</p>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>