<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    types: Array,
});

const page = usePage();
// Haal flash message op, controleer op 'success' of 'message'
const flashSuccess = page.props.flash.success || page.props.flash.message; 

// Functie voor het verwijderen van een Type
const destroy = (typeId, typeCode) => {
    // Aangepaste modal/confirm is wenselijk i.v.m. de Canvas omgeving, maar we houden
    // voor nu de browser confirm, want het is een beheer actie.
    if (confirm(`Weet u zeker dat u overtredingstype ${typeCode} wilt verwijderen? Dit verwijdert mogelijk gekoppelde overtredingen!`)) {
        router.delete(route('beheer.overtreding_types.destroy', typeId), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Overtreding Types Beheer" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Overtreding Types Beheer</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Flash Message -->
                <div v-if="flashSuccess" class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg shadow">
                    {{ flashSuccess }}
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Officiële Overtreding Codes ({{ types.length }})</h3>
                        <Link :href="route('beheer.overtreding_types.create')" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            + Nieuw Type
                        </Link>
                    </div>

                    <!-- HEADERS: Alleen zichtbaar op desktop (md:) -->
                    <div class="hidden md:grid grid-cols-5 gap-4 px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-200 rounded-t-lg">
                        <div class="col-span-1">Code</div>
                        <div class="col-span-3">Omschrijving</div>
                        <div class="col-span-1 text-right">Acties</div>
                    </div>

                    <!-- DATARIJEN: De kern van de responsieve weergave -->
                    <div class="divide-y divide-gray-200 border-b border-gray-200">
                        <div 
                            v-for="type in types" 
                            :key="type.id" 
                            class="p-4 md:py-3 md:px-4 hover:bg-gray-50 transition duration-150"
                        >
                            
                            <!-- Op MD: 5 kolommen. Op SM: 1 kolom met gestapelde velden. -->
                            <div class="grid grid-cols-1 md:grid-cols-5 gap-y-3 md:gap-4 items-center">
                                
                                <!-- CODE (col-span-1 op md) -->
                                <div class="md:col-span-1">
                                    <p class="text-xs font-medium text-gray-500 uppercase mb-1 md:hidden">Code</p>
                                    <span class="font-medium text-gray-900">{{ type.code }}</span>
                                </div>
                                
                                <!-- OMSCHRIJVING (col-span-3 op md) -->
                                <div class="md:col-span-3">
                                    <p class="text-xs font-medium text-gray-500 uppercase mb-1 md:hidden">Omschrijving</p>
                                    <span class="text-sm text-gray-700">{{ type.omschrijving }}</span>
                                </div>

                                <!-- ACTIES (col-span-1 op md) -->
                                <div class="md:col-span-1 flex justify-end space-x-4 pt-3 md:pt-0 border-t md:border-t-0 border-gray-100">
                                    <Link :href="route('beheer.overtreding_types.edit', type.id)" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                        Bewerk
                                    </Link>
                                    <button @click="destroy(type.id, type.code)" class="text-red-600 hover:text-red-900 text-sm font-medium">
                                        Verwijder
                                    </button>
                                </div>

                            </div>
                        </div>
                        
                        <p v-if="types.length === 0" class="p-4 text-gray-500">Er zijn nog geen overtreding types gedefinieerd.</p>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>