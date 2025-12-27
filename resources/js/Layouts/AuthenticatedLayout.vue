<script setup>
// AuthenticatedLayout.vue
// Hoofdlayout voor ingelogde gebruikers. Bevat de globale navigatie,
// het gebruikersmenu en de plaats waar child-pages hun `header` en
// hoofdcontent plaatsen via slots.
//
// Deze component centraliseert autorisatie-afhankelijke links (bijv. Beheer)
// en toont een banner als de app in een ontwikkelomgeving draait.
// ====================================================================
// IMPORTS
// Hier importeren we alle benodigde componenten en functionaliteit.
// ====================================================================
import { ref, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
// Link: Inertia component voor client-side navigatie.
// usePage: Haalt de globale props op, waaronder de ingelogde gebruiker.
import { Link, usePage } from '@inertiajs/vue3';

// ====================================================================
// STATE MANAGEMENT
// ====================================================================

// Reactieve variabele om de staat van het mobiele navigatiemenu (Hamburger Menu) te beheren.
const showingNavigationDropdown = ref(false);

// Gebruik usePage om de auth user op te halen uit de Inertia props.
// Deze data is essentieel voor het tonen van de naam/rol en autorisatie.
const user = usePage().props.auth.user;

// Haal de applicatie-omgeving op (dev, local, production)
const appEnv = computed(() => usePage().props.app_env);

// Bepaal of we in een ontwikkelomgeving zitten.
// De banner wordt getoond als de omgeving 'dev' of 'local' is.
const isDevelopment = computed(() => appEnv.value === 'dev' || appEnv.value === 'local');

// ====================================================================
// AUTORISATIE LOGICA
// ====================================================================

// Controleer of de gebruiker de 'Beheerder' rol heeft.
// Dit is momenteel de ENIGE rol die toegang krijgt tot de 'Beheer' link in de navigatie.
const canViewManagementDashboard = user.role === 'Beheerder';

// NB: De oorspronkelijke code bevatte een ongebruikte variabele `canViewManagementDashboard`
// die de rol 'Coördinator' meenam. Aangezien de navigatie alleen checkt op 'Beheerder', 
// is deze logica vereenvoudigd in deze comments en in de template (`v-if="user.role === 'Beheerder'"`).
// Als 'Coördinator' ook toegang moet hebben, moet de `v-if` in de template worden aangepast.
</script>

<template>
    <!-- De hoofdcontainer voor de gehele lay-out -->
    <div>
        <!-- ONTWIKKELOMGEVING BANNER -->
        <div v-if="isDevelopment" class="bg-yellow-400 text-black text-center p-2 font-bold text-sm z-50 relative">
            Let op: Je werkt in de '{{ appEnv }}' omgeving.
        </div>

        <div class="min-h-screen bg-gray-100">
            <!-- Navigatiebalk (Header) -->
            <nav class="border-b border-gray-100 bg-white">
                <!-- Primaire Navigatie Menu (Desktop) -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo sectie (link naar dashboard) -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')">
                                    <ApplicationLogo
                                        class="block h-9 w-auto fill-current text-gray-800"
                                    />
                                </Link>
                            </div>

                            <!-- Desktop Navigatie Links -->
                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">

                                <!-- 1. Dashboard Link (voor iedereen) -->
                                <NavLink
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard') && !route().current('beheer.index')"
                                >
                                    Dashboard
                                </NavLink>

                                <!-- 2. Start Nieuwe Controle Link (voor iedereen) -->
                                <NavLink
                                    :href="route('controles.create')"
                                    :active="route().current('controles.create')"
                                >
                                    Start Nieuwe Controle
                                </NavLink>

                                <!-- 3. Controles Overzicht Link (voor iedereen) -->
                                <NavLink
                                    :href="route('controles.index')"
                                    :active="route().current('controles.index')"
                                >
                                    Controle Overzicht
                                </NavLink>

                                <!-- 5. Rapportages Link (Autorisatie via v-if) -->
                                <!-- ALLEEN zichtbaar als de gebruiker de rol 'Beheerder' heeft -->
                                <NavLink
                                    v-if="user.role === 'Beheerder'"
                                    :href="route('beheer.reports.index')"
                                    :active="route().current('beheer.reports.index')"
                                >
                                    Rapportages
                                </NavLink>

                                <!-- 5. Audit Log Link (Autorisatie via v-if) -->
                                <!-- ALLEEN zichtbaar als de gebruiker de rol 'Beheerder' heeft -->
                                <NavLink
                                    v-if="user.role === 'Beheerder'"
                                    :href="route('beheer.auditlog.index')"
                                    :active="route().current('beheer.auditlog.index')"
                                >
                                    Audit Log
                                </NavLink>

                                <!-- 4. Beheer sectie (Autorisatie via v-if) -->
                                <!-- ALLEEN zichtbaar als de gebruiker de rol 'Beheerder' heeft -->
                                <NavLink
                                    v-if="user.role === 'Beheerder'"
                                    :href="route('beheer.index')"
                                    :active="route().current('beheer.index')"
                                >
                                    Beheer
                                </NavLink>
                            </div>
                        </div>

                        <!-- Gebruikersinstellingen en Uitloggen (Desktop) -->
                        <div class="hidden sm:ms-6 sm:flex sm:items-center">
                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                            >
                                                <!-- Toon naam en rol van de ingelogde gebruiker -->
                                                {{ $page.props.auth.user.name }} ({{ $page.props.auth.user.role }})

                                                <!-- Dropdown pijl SVG -->
                                                <svg
                                                    class="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')">
                                            Profiel
                                        </DropdownLink>
                                        <!-- Uitloggen: Gebruikt een POST-verzoek -->
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                        >
                                            Uitloggen
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger Menu Knop (Mobiel) -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="
                                    showingNavigationDropdown =
                                        !showingNavigationDropdown
                                "
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                            >
                                <!-- Toont ofwel de lijnen (niet open) of het kruis (wel open) -->
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex':
                                                !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex':
                                                showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsief Navigatie Menu (Mobiel) -->
                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="sm:hidden"
                >
                    <div class="space-y-1 pb-3 pt-2">

                        <!-- 1. Dashboard Link (Mobiel) -->
                        <ResponsiveNavLink
                            :href="route('dashboard')"
                            :active="route().current('dashboard') && !route().current('beheer.index')"
                        >
                            Dashboard
                        </ResponsiveNavLink>

                        <!-- 2. Start Nieuwe Controle Link (Mobiel) -->
                        <ResponsiveNavLink
                            :href="route('controles.create')"
                            :active="route().current('controles.create')"
                        >
                            Start Nieuwe Controle
                        </ResponsiveNavLink>

                        <!-- 3. Controles Overzicht Link (Mobiel) -->
                        <ResponsiveNavLink
                            :href="route('controles.index')"
                            :active="route().current('controles.index')"
                        >
                            Controle Overzicht
                        </ResponsiveNavLink>

                        <!-- 4. Beheer sectie (Autorisatie - Mobiel) -->
                        <ResponsiveNavLink
                            v-if="user.role === 'Beheerder'"
                            :href="route('beheer.index')"
                            :active="route().current('beheer.index')"
                        >
                            Beheer
                        </ResponsiveNavLink>

                        <!-- 5. Rapportages Link (Mobiel) -->
                        <ResponsiveNavLink
                            v-if="user.role === 'Beheerder'"
                            :href="route('beheer.reports.index')"
                            :active="route().current('beheer.reports.index')"
                        >
                            Rapportages
                        </ResponsiveNavLink>

                        <!-- 5. Audit Log Link (Mobiel) -->
                        <ResponsiveNavLink
                            v-if="user.role === 'Beheerder'"
                            :href="route('beheer.auditlog.index')"
                            :active="route().current('beheer.auditlog.index')"
                        >
                            Audit Log
                        </ResponsiveNavLink>

                    </div>

                    <!-- Responsieve Instellingen Opties (Mobiel) -->
                    <div class="border-t border-gray-200 pb-1 pt-4">
                        <div class="px-4">
                            <div
                                class="text-base font-medium text-gray-800"
                            >
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="text-sm font-medium text-gray-500">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">
                                Profiel
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                Uitloggen
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading (de header die in de child component wordt gedefinieerd) -->
            <header
                class="bg-white shadow"
                v-if="$slots.header"
            >
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content (de hoofdinhoud van de child component) -->
            <main>
                <slot />
            </main>

            <!-- HVLD Watermerk -->
            <div class="fixed inset-0 flex items-center justify-center pointer-events-none z-0">
                <ApplicationLogo class="h-96 w-auto opacity-5" />
            </div>
        </div>
    </div>
</template>