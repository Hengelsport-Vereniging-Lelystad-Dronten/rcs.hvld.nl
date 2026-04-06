<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Handleidingen" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Handleidingen & Documenten</h2>
                <Link :href="route('uitleg.index')" class="text-sm text-gray-600 hover:text-gray-900">← Terug naar overzicht</Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-gray-600 mb-6">
                        Onderstaande links verwijzen naar externe documenten (Google Drive) met uitgebreide instructies, protocollen en formulieren.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Voorbeeld Links - Pas aan naar werkelijke URL's -->
                        <a href="https://drive.google.com/file/d/1LKH6iE8qa0x-ozKVHxE51iLtyYnQ4-zr/view?usp=drive_link" target="_blank" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-blue-50 hover:border-blue-300 transition group">
                            <span class="text-3xl mr-4">📘</span>
                            <div>
                                <h3 class="font-bold text-gray-900 group-hover:text-blue-700">Handboek Controleur RCS</h3>
                                <p class="text-sm text-gray-500">Volledige handleiding voor de RCS applicatie en procedures.</p>
                            </div>
                        </a>

                        <a href="https://docs.google.com/spreadsheets/d/1_FHRCVkyBZrXEv1A7OY283EbvlQQPYxY/view?usp=drive_link&ouid=104740159758276464299&rtpof=true&sd=true" target="_blank" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-blue-50 hover:border-blue-300 transition group">
                            <span class="text-3xl mr-4">📋</span>
                            <div>
                                <h3 class="font-bold text-gray-900 group-hover:text-blue-700">Kilometer declaratie</h3>
                                <p class="text-sm text-gray-500">Kilometer declaratie formulier (template).</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>