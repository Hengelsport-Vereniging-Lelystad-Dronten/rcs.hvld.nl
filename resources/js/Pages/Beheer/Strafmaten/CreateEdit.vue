<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import Layout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';

// Definieer de props die deze pagina verwacht van de Controller.
const props = defineProps(['strafmaat']);

// Bepaal of we in de bewerkingsmodus zitten
const isEditing = !!props.strafmaat;

// ---- FIX VOOR DE HARNEKKIGE COMPILER FOUT ----
// Bereken de initiële waarden door de ternaire logica buiten het object te plaatsen.
let initialCode = '';
let initialOmschrijving = '';
let initialOrderId = null;

if (isEditing) {
    // Gebruik de bestaande waarden, met een fallback naar lege string/null
    initialCode = props.strafmaat.code || '';
    initialOmschrijving = props.strafmaat.omschrijving || '';
    initialOrderId = props.strafmaat.order_id || null;
}

const initialValues = {
    code: initialCode,
    omschrijving: initialOmschrijving,
    order_id: initialOrderId
};
// ----------------------------------------------

// Initialisatie van het formulier met de berekende waarden.
const form = useForm(initialValues);

// Bepaal de titel en de indienings-URL
const title = isEditing ? `Strafmaat bewerken: ${form.omschrijving}` : 'Nieuwe Strafmaat aanmaken';

// FIX: Geef de route parameter door als een object { strafmaten: id } om de Ziggy error op te lossen.
const action = isEditing 
    ? route('beheer.strafmaten.update', { strafmaten: props.strafmaat.id })
    : route('beheer.strafmaten.store');

// Bepaal de HTTP-methode
const method = isEditing ? 'put' : 'post';

// Functie voor het indienen van het formulier
const submit = () => {
    // Gebruik de Inertia-formulierinstantie om de juiste aanvraag te verzenden (POST of PUT)
    form.submit(method, action, {
        onSuccess: () => {
            // Optioneel: reset formulier of toon succesbericht
            console.log('Strafmaat succesvol opgeslagen.');
        },
        onError: (errors) => {
            console.error('Er zijn validatiefouten opgetreden:', errors);
        }
    });
};
</script>

<template>
    <Layout>
        <!-- Stel de paginatitel in voor de browser -->
        <Head :title="title" />

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ title }}</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <!-- Formulier definitie -->
                    <form @submit.prevent="submit" class="space-y-6">
                        
                        <!-- Veld: Order ID (Nieuw Veld) -->
                        <div>
                            <InputLabel for="order_id" value="Sorteervolgorde (Order ID)" />
                            <TextInput
                                id="order_id"
                                type="number"
                                class="mt-1 block w-full"
                                v-model="form.order_id"
                                required
                                min="1"
                            />
                            <InputError class="mt-2" :message="form.errors.order_id" />
                            <p class="text-sm text-gray-500 mt-1">
                                Een uniek, vast nummer om de volgorde van de strafmaat te bepalen (bijv. 1, 2, 3...).
                            </p>
                        </div>

                        <!-- Veld: Omschrijving -->
                        <div>
                            <InputLabel for="omschrijving" value="Omschrijving" />
                            <!-- Gebruikt TextInput met 'as="textarea"' voor de multiline-functionaliteit -->
                            <TextInput
                                id="omschrijving"
                                as="textarea" 
                                class="mt-1 block w-full"
                                v-model="form.omschrijving"
                                required
                                rows="3"
                            />
                            <InputError class="mt-2" :message="form.errors.omschrijving" />
                            <p class="text-sm text-gray-500 mt-1">
                                De volledige omschrijving van de strafmaat.
                            </p>
                        </div>

                        <!-- Veld: Code (Optioneel) -->
                        <div>
                            <InputLabel for="code" value="Code (optioneel)" />
                            <TextInput
                                id="code"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.code"
                            />
                            <InputError class="mt-2" :message="form.errors.code" />
                            <p class="text-sm text-gray-500 mt-1">
                                Een korte, interne code voor de strafmaat (max. 50 karakters).
                            </p>
                        </div>
                        
                        <!-- Submit knop -->
                        <div class="flex items-center justify-end">
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                {{ isEditing ? 'Opslaan en Bijwerken' : 'Aanmaken' }}
                            </PrimaryButton>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </Layout>
</template>