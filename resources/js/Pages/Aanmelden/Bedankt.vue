<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Bedankt voor je aanmelding" />

    <div class="min-h-screen bg-gray-100 flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-3xl shadow-lg text-center">
            
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100">
                <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                Bedankt voor je aanmelding!
            </h2>
            
            <p class="mt-2 text-sm text-gray-600">
                We hebben je gegevens in goede orde ontvangen. De coördinator controleurs neemt binnenkort contact met je op voor het inplannen van een intakegesprek.
            </p>

            <div class="mt-6">
                <a href="https://www.hvld.nl" class="text-blue-600 hover:text-blue-500 font-medium">Terug naar HVLD.nl</a>
            </div>
        </div>
    </div>
</template>