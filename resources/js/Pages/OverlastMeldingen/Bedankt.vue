<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Bedankt voor uw melding" />

    <div class="min-h-screen bg-gradient-to-b from-blue-50 to-gray-100 flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-lg shadow-lg text-center">
            
            <!-- Success Icon -->
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100">
                <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <!-- Main Message -->
            <div>
                <h2 class="text-3xl font-extrabold text-gray-900">
                    Bedankt voor uw melding!
                </h2>
                
                <p class="mt-4 text-sm text-gray-700">
                    We hebben uw melding over <strong>sportvisserij en dierenwelzijn</strong> in goede orde ontvangen.
                </p>

                <div class="mt-6 p-4 bg-blue-50 border-l-4 border-blue-500 rounded">
                    <p class="text-xs text-blue-900">
                        <strong>📋 Volgende stap:</strong> Onze medewerkers zullen uw melding beoordelen en verwerken. Dit kan enkele dagen duren. 
                        U hoeft niet per direct iets te doen.
                    </p>
                </div>

                <p class="mt-6 text-xs text-gray-600">
                    Heeft u contact informatie opgegeven? Dan kunnen we u bereiken voor vervolgvragen.
                </p>
            </div>

            <!-- Action Links -->
            <div class="mt-8 space-y-3">
                <p class="text-sm text-gray-600 mb-4">Wat wilt u vervolgens doen?</p>
                
                <Link
                    href="/visplanner"
                    class="block w-full text-center px-4 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition"
                >
                    🗺️ Bekijk de waterkaart
                </Link>

                <a
                    href="https://www.hvld.nl"
                    target="_blank"
                    class="block w-full text-center px-4 py-3 border-2 border-blue-600 text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition"
                >
                    Ga naar HVLD.nl →
                </a>
            </div>

            <!-- Contact Info -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <p class="text-xs text-gray-600">
                    Vragen over uw melding? Neem contact op met de administratie van HVLD.
                </p>
            </div>

        </div>
    </div>
</template>
