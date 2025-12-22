<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    users: Array,
});

const page = usePage();

// Haal zowel succes- als foutmeldingen op
const flashMessage = computed(() => {
    return page.props.flash.success || page.props.flash.message;
});

const flashError = computed(() => {
    return page.props.flash.error;
});


// Functie voor het verwijderen van een gebruiker
const destroy = (userId, userName) => {
    // Aangepaste modal/confirm is wenselijk i.v.m. de Canvas omgeving, maar we houden
    // voor nu de browser confirm, want het is een beheer actie.
    if (confirm(`Weet u zeker dat u gebruiker "${userName}" wilt verwijderen?`)) {
        router.delete(route('beheer.users.destroy', userId), {
            preserveScroll: true,
        });
    }
};

// Functie om de juiste kleurklasse voor de rol te bepalen
const getRoleClass = (role) => {
    switch (role) {
        case 'Beheerder':
            return 'bg-red-100 text-red-800';
        case 'Coördinator':
            return 'bg-yellow-100 text-yellow-800';
        case 'Controleur':
            return 'bg-green-100 text-green-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};
</script>

<template>
    <Head title="Gebruikers Beheer" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gebruikers Beheer</h2>
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
                    
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Overzicht van Alle Gebruikers ({{ users.length }})</h3>
                        <Link :href="route('beheer.users.create')" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            + Nieuwe Gebruiker
                        </Link>
                    </div>

                    <!-- HEADERS: Alleen zichtbaar op desktop (md:) -->
                    <!-- We gebruiken 5 kolommen voor Naam, E-mail, Rol, Laatste Login en Acties -->
                    <div class="hidden md:grid grid-cols-5 gap-4 px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-200 rounded-t-lg">
                        <div class="col-span-1">Naam</div>
                        <div class="col-span-1">E-mail</div>
                        <div class="col-span-1">Rol</div>
                        <div class="col-span-1">Laatste Login</div>
                        <div class="col-span-1 text-right">Acties</div>
                    </div>

                    <!-- DATARIJEN: De kern van de responsieve weergave -->
                    <div class="divide-y divide-gray-200 border-b border-gray-200">
                        <div 
                            v-for="user in users" 
                            :key="user.id" 
                            class="p-4 md:py-3 md:px-4 hover:bg-gray-50 transition duration-150"
                        >
                            
                            <!-- Op MD: 5 kolommen. Op SM: Gestapelde velden. -->
                            <div class="grid grid-cols-1 md:grid-cols-5 gap-y-3 md:gap-4 items-center">
                                
                                <!-- NAAM (col-span-1 op md) -->
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase mb-1 md:hidden">Naam</p>
                                    <span class="font-medium text-gray-900">{{ user.name }}</span>
                                </div>
                                
                                <!-- E-MAIL (col-span-1 op md) -->
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase mb-1 md:hidden">E-mail</p>
                                    <span class="text-sm text-gray-700">{{ user.email }}</span>
                                </div>
                                
                                <!-- ROL (col-span-1 op md) -->
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase mb-1 md:hidden">Rol</p>
                                    <span :class="getRoleClass(user.role)" class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full">
                                        {{ user.role }}
                                    </span>
                                </div>

                                <!-- LAATSTE LOGIN (col-span-1 op md) -->
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase mb-1 md:hidden">Laatste Login</p>
                                    <span class="text-sm text-gray-700">
                                        {{ user.last_login_at ? new Date(user.last_login_at).toLocaleString('nl-NL') : '-' }}
                                    </span>
                                </div>

                                <!-- ACTIES (col-span-1 op md) -->
                                <div class="flex justify-end space-x-4 pt-3 md:pt-0 border-t md:border-t-0 border-gray-100 w-full md:w-auto">
                                    <Link :href="route('beheer.users.edit', user.id)" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                        Bewerk
                                    </Link>
                                    <button @click="destroy(user.id, user.name)" class="text-red-600 hover:text-red-900 text-sm font-medium">
                                        Verwijder
                                    </button>
                                </div>

                            </div>
                        </div>
                        
                        <p v-if="users.length === 0" class="p-4 text-gray-500">Er zijn nog geen gebruikers gedefinieerd.</p>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>