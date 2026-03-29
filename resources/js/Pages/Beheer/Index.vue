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

                        <!-- Compacte lijststructuur: py-2 en divide-y voor strakke rijen -->
                        <div class="divide-y divide-gray-200 border-t border-gray-200">
                            
                            <!-- 1. Gebruikers Beheer -->
                            <div class="py-2 px-4 hover:bg-gray-50 transition duration-150 cursor-pointer">
                                <Link :href="route('beheer.users.index')" class="text-blue-600 hover:text-blue-800 font-bold text-base block">
                                    🧑‍💻 Gebruikers Beheer
                                </Link>
                                <p class="text-sm text-gray-500">Aanmaken, bewerken en rollen toewijzen van controleurs en beheerders.</p>
                            </div>
                            
                            <!-- 2. Wateren Beheer -->
                            <div class="py-2 px-4 hover:bg-gray-50 transition duration-150 cursor-pointer">
                                <Link :href="route('beheer.waters.index')" class="text-blue-600 hover:text-blue-800 font-bold text-base block">
                                    💧 Wateren Beheer
                                </Link>
                                <p class="text-sm text-gray-500">Toevoegen en aanpassen van de viswateren in het RCS.</p>
                            </div>
                            
                            <!-- 3. Strafmaten Beheer (NIEUW) -->
                            <div class="py-2 px-4 hover:bg-gray-50 transition duration-150 cursor-pointer">
                                <Link :href="route('beheer.strafmaten.index')" class="text-blue-600 hover:text-blue-800 font-bold text-base block">
                                    ⚖️ Strafmaten Beheer
                                </Link>
                                <p class="text-sm text-gray-500">Definieer en beheer de sancties en strafmaten die bij overtredingen toegepast kunnen worden.</p>
                            </div>

                            <!-- 4. FAQ Beheer (NIEUW) -->
                            <div class="py-2 px-4 hover:bg-gray-50 transition duration-150 cursor-pointer">
                                <Link :href="route('beheer.faqs.index')" class="text-blue-600 hover:text-blue-800 font-bold text-base block">
                                    ❓ FAQ Beheer
                                </Link>
                                <p class="text-sm text-gray-500">Beheer de veelgestelde vragen en antwoorden die zichtbaar zijn op de uitlegpagina.</p>
                            </div>

                            <!-- 4. Overtreding Types Beheer -->
                            <div class="py-2 px-4 hover:bg-gray-50 transition duration-150 cursor-pointer">
                                <Link :href="route('beheer.overtreding_types.index')" class="text-blue-600 hover:text-blue-800 font-bold text-base block">
                                    🚨 Overtreding Types Beheer
                                </Link>
                                <p class="text-sm text-gray-500">Definieer en beheer de types overtredingen die tijdens controles vastgelegd kunnen worden.</p>
                            </div>

                            <!-- 5. Overlastmeldingen Beheer -->
                            <div class="py-2 px-4 hover:bg-gray-50 transition duration-150 cursor-pointer">
                                <Link :href="route('beheer.overlast-meldingen.index')" class="text-blue-600 hover:text-blue-800 font-bold text-base block">
                                    🆘 Overlastmeldingen Beheer
                                </Link>
                                <p class="text-sm text-gray-500">Bekijken, filteren en verwerken van ingediende overlastmeldingen uit het publiek formulier.</p>
                            </div>

                            <!-- 6. Periodieke Rapporten -->
                            <div class="py-2 px-4 hover:bg-gray-50 transition duration-150 cursor-pointer">
                                <Link :href="route('beheer.reports.index')" class="text-blue-600 hover:text-blue-800 font-bold text-base block">
                                    📊 Periodieke Rapporten
                                </Link>
                                <p class="text-sm text-gray-500">Genereer en bekijk wekelijks, maandelijks, en kwartaalrapporten met statistieken en analyses.</p>
                            </div>

                            <!-- 7. Export Overtredingen -->
                            <div class="py-2 px-4 hover:bg-gray-50 transition duration-150 cursor-pointer">
                                <Link :href="route('beheer.export-overtredingen.index')" class="text-blue-600 hover:text-blue-800 font-bold text-base block">
                                    📤 Export Overtredingen
                                </Link>
                                <p class="text-sm text-gray-500">Exporteer actieve overtredingen naar PDF voor externe partijen zoals Sportvisunie.</p>
                            </div>

                            <!-- 8. Audit Log -->
                            <div class="py-2 px-4 hover:bg-gray-50 transition duration-150 cursor-pointer">
                                <Link :href="route('beheer.auditlog.index')" class="text-blue-600 hover:text-blue-800 font-bold text-base block">
                                    📜 Audit Log
                                </Link>
                                <p class="text-sm text-gray-500">Bekijk een read-only log van alle belangrijke acties die in het systeem zijn uitgevoerd.</p>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>