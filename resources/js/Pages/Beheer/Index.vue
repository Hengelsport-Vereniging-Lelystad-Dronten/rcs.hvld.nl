<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Beheer" />

    <AuthenticatedLayout>
        <template v-slot:header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Beheer</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Content Container: structuur gespiegeld aan Bestand 1 -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        
                        <!-- Sectietitel met flexibele opmaak -->
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-bold text-gray-800">Beheermodules</h3>
                            <!-- Hier kan een knop worden toegevoegd indien nodig -->
                        </div>

                        <div class="space-y-8 border-t border-gray-200 pt-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-3">Actieve taak</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                    <Link :href="route('beheer.overlast-meldingen.index')" class="block p-4 rounded-lg border border-red-200 hover:border-red-300 hover:bg-red-50 transition duration-150">
                                        <div class="flex items-center gap-3">
                                            <span class="text-2xl">🆘</span>
                                            <div>
                                                <h4 class="text-base font-semibold text-gray-900">Overlastmeldingen</h4>
                                                <p class="text-sm text-gray-600">Behandel meldingen uit het publiek actief.</p>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-3">Systeembeheer</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                    <Link :href="route('beheer.users.index')" class="block p-4 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-gray-50 transition duration-150">
                                        <div class="flex items-center gap-3">
                                            <span class="text-2xl">🧑‍💻</span>
                                            <div>
                                                <h4 class="text-base font-semibold text-gray-900">Gebruikers</h4>
                                                <p class="text-sm text-gray-600">Aanmaken, bewerken en rollen toewijzen.</p>
                                            </div>
                                        </div>
                                    </Link>

                                    <Link :href="route('beheer.waters.index')" class="block p-4 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-gray-50 transition duration-150">
                                        <div class="flex items-center gap-3">
                                            <span class="text-2xl">💧</span>
                                            <div>
                                                <h4 class="text-base font-semibold text-gray-900">Wateren</h4>
                                                <p class="text-sm text-gray-600">Beheer viswateren in het RCS.</p>
                                            </div>
                                        </div>
                                    </Link>

                                    <Link :href="route('beheer.overtreding_types.index')" class="block p-4 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-gray-50 transition duration-150">
                                        <div class="flex items-center gap-3">
                                            <span class="text-2xl">🚨</span>
                                            <div>
                                                <h4 class="text-base font-semibold text-gray-900">Overtreding Types</h4>
                                                <p class="text-sm text-gray-600">Beheer overtredingcodes.</p>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-3">Inhoud</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                    <Link :href="route('beheer.faqs.index')" class="block p-4 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-gray-50 transition duration-150">
                                        <div class="flex items-center gap-3">
                                            <span class="text-2xl">❓</span>
                                            <div>
                                                <h4 class="text-base font-semibold text-gray-900">FAQ</h4>
                                                <p class="text-sm text-gray-600">Beheer veelgestelde vragen.</p>
                                            </div>
                                        </div>
                                    </Link>

                                    <Link :href="route('beheer.auditlog.index')" class="block p-4 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-gray-50 transition duration-150">
                                        <div class="flex items-center gap-3">
                                            <span class="text-2xl">📜</span>
                                            <div>
                                                <h4 class="text-base font-semibold text-gray-900">Audit Log</h4>
                                                <p class="text-sm text-gray-600">Bekijk systeemacties.</p>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-3">Rapportage & export</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                    <Link :href="route('beheer.reports.index')" class="block p-4 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-gray-50 transition duration-150">
                                        <div class="flex items-center gap-3">
                                            <span class="text-2xl">📊</span>
                                            <div>
                                                <h4 class="text-base font-semibold text-gray-900">Rapporten</h4>
                                                <p class="text-sm text-gray-600">Genereer statistieken.</p>
                                            </div>
                                        </div>
                                    </Link>

                                    <Link :href="route('beheer.export-overtredingen.index')" class="block p-4 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-gray-50 transition duration-150">
                                        <div class="flex items-center gap-3">
                                            <span class="text-2xl">📤</span>
                                            <div>
                                                <h4 class="text-base font-semibold text-gray-900">Export Overtredingen</h4>
                                                <p class="text-sm text-gray-600">Maak export-PDF’s.</p>
                                            </div>
                                        </div>
                                    </Link>

                                    <Link :href="route('beheer.exports.index')" class="block p-4 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-gray-50 transition duration-150">
                                        <div class="flex items-center gap-3">
                                            <span class="text-2xl">📁</span>
                                            <div>
                                                <h4 class="text-base font-semibold text-gray-900">Export Overzicht</h4>
                                                <p class="text-sm text-gray-600">Bekijk eerdere exports.</p>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>