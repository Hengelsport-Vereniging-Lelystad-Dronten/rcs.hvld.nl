<script setup>
// ====================================================================
// IMPORTS
// Hier importeren we alle benodigde componenten en functionaliteit.
// ====================================================================
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'; // De hoofd lay-out van de applicatie
import InputError from '@/Components/InputError.vue';               // Component voor het tonen van validatiefouten
import InputLabel from '@/Components/InputLabel.vue';               // Component voor het label van invoervelden
import PrimaryButton from '@/Components/PrimaryButton.vue';         // Component voor de primaire actieknop
import TextInput from '@/Components/TextInput.vue';                 // Component voor een standaard tekstinvoerveld
import { Head, useForm } from '@inertiajs/vue3';                  // Head voor paginatitel; useForm voor formulierstate
import { defineProps } from 'vue';                                // Vue functie om props te definiëren

// ====================================================================
// PROPS DEFINITIE
// ====================================================================
const props = defineProps({
    // 'type' bevat de data van het te bewerken Overtreding Type. 
    // Is 'null' wanneer een nieuw type wordt aangemaakt.
    type: {
        type: Object,
        default: null, // Is null bij het aanmaken (Create)
    },
    // NIEUW: De volledige lijst van beschikbare Strafmaten.
    strafmaten: {
        type: Array, 
        required: true,
    }
});

// ====================================================================
// LOGICA VOOR BEWERKEN VS. AANMAKEN
// ====================================================================

// Bepaalt of we aan het bewerken (true) of aan het aanmaken (false) zijn.
const isEdit = !!props.type; // !!props.type is true als 'type' een object is

// ====================================================================
// FORMULIER STATE MANAGEMENT (useForm)
// ====================================================================

// Hulpvariabele om de standaard ID te bepalen voor een nieuw type.
// Gebruikt de ID van de eerste strafmaat in de lijst, of null als de lijst leeg is.
const defaultStrafmaatId = props.strafmaten.length > 0 ? props.strafmaten[0].id : null;

// Initialiseer het Inertia formulier.
// Bij bewerken (`isEdit` is true): gebruik bestaande waarden van `props.type`.
// Bij aanmaken (`isEdit` is false): gebruik lege strings of de berekende standaard ID.
const form = useForm({
    code: props.type ? props.type.code : '',
    omschrijving: props.type ? props.type.omschrijving : '',
    // NIEUW: De ID van de standaard gekozen strafmaat.
    default_strafmaat_id: props.type ? props.type.default_strafmaat_id : defaultStrafmaatId,
});

// ====================================================================
// SUBMIT FUNCTIE
// ====================================================================

/**
 * Verstuurt het formulier naar de juiste Inertia route (store of update).
 */
const submit = () => {
    if (isEdit) {
        // UPDATE bestaand type: Gebruik form.put() om een PUT-verzoek te sturen.
        // De route is 'beheer.overtreding_types.update' met het ID van het te bewerken type.
        form.put(route('beheer.overtreding_types.update', props.type.id));
    } else {
        // NIEUW type aanmaken: Gebruik form.post() om een POST-verzoek te sturen.
        // De route is 'beheer.overtreding_types.store'.
        form.post(route('beheer.overtreding_types.store'));
    }
};
</script>

<template>
    <!-- Dynamische paginatitel op basis van bewerkingsmodus -->
    <Head :title="isEdit ? 'Overtreding Type Bewerken' : 'Nieuw Overtreding Type'" />

    <!-- Gebruikt de algemene geauthenticeerde lay-out -->
    <AuthenticatedLayout>
        <template #header>
            <!-- Dynamische header tekst -->
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ isEdit ? 'Overtreding Type Bewerken: ' + form.code : 'Nieuw Overtreding Type Toevoegen' }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <!-- Formulier: roept de submit functie aan bij verzenden -->
                    <form @submit.prevent="submit">
                        
                        <!-- Veld: Overtreding Code -->
                        <div class="mb-4">
                            <InputLabel for="code" value="Overtreding Code (Uniek, bijv. 01, 1A)" />
                            <TextInput
                                id="code"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.code"
                                required
                                autofocus
                            />
                            <!-- Toont eventuele validatiefout voor de code -->
                            <InputError class="mt-2" :message="form.errors.code" />
                        </div>

                        <!-- Veld: Volledige Omschrijving (Textarea) -->
                        <div class="mb-4">
                            <InputLabel for="omschrijving" value="Volledige Omschrijving" />
                            <textarea
                                id="omschrijving"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                v-model="form.omschrijving"
                                required
                                rows="3"
                            ></textarea>
                            <!-- Toont eventuele validatiefout voor de omschrijving -->
                            <InputError class="mt-2" :message="form.errors.omschrijving" />
                        </div>

                        <!-- NIEUW VELD: Standaard Strafmaat Dropdown -->
                        <div class="mb-6">
                            <InputLabel for="default_strafmaat_id" value="Standaard Maatregel (Default)" />
                            <select
                                id="default_strafmaat_id"
                                v-model="form.default_strafmaat_id"
                                required
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            >
                                <!-- Vult de opties met de doorgestuurde strafmaten prop -->
                                <option 
                                    v-for="strafmaat in props.strafmaten" 
                                    :key="strafmaat.id" 
                                    :value="strafmaat.id"
                                >
                                    {{ strafmaat.omschrijving }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.default_strafmaat_id" />
                        </div>


                        <!-- Submit Knop -->
                        <div class="flex items-center justify-end mt-4">
                            <!-- Deactiveer de knop tijdens het verwerken (`form.processing`) -->
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                <!-- Dynamische knoptekst op basis van bewerkingsmodus -->
                                {{ isEdit ? 'Opslaan' : 'Type Aanmaken' }}
                            </PrimaryButton>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>