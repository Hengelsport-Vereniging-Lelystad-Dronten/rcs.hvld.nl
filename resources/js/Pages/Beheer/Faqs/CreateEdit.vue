<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import WysiwygInput from '@/Components/WysiwygInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    faq: {
        type: Object,
        default: null,
    },
});

const isEdit = !!props.faq;

const form = useForm({
    question: props.faq ? props.faq.question : '',
    answer: props.faq ? props.faq.answer : '',
    order: props.faq ? props.faq.order : 0,
    is_active: props.faq ? !!props.faq.is_active : true,
});

const submit = () => {
    if (isEdit) {
        form.put(route('beheer.faqs.update', props.faq.id));
    } else {
        form.post(route('beheer.faqs.store'));
    }
};
</script>

<template>
    <Head :title="isEdit ? 'FAQ Bewerken' : 'Nieuwe FAQ'" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ isEdit ? 'FAQ Item Bewerken' : 'Nieuw FAQ Item Toevoegen' }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 gap-6">
                            
                            <!-- Vraag -->
                            <div>
                                <InputLabel for="question" value="Vraag" />
                                <TextInput id="question" type="text" class="mt-1 block w-full" v-model="form.question" required autofocus placeholder="Bijv: Hoe vraag ik een VISpas aan?" />
                                <InputError class="mt-2" :message="form.errors.question" />
                            </div>

                            <!-- Antwoord (WYSIWYG) -->
                            <div>
                                <InputLabel for="answer" value="Antwoord" />
                                <WysiwygInput id="answer" class="mt-1 block w-full" v-model="form.answer" />
                                <InputError class="mt-2" :message="form.errors.answer" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Volgorde -->
                                <div>
                                    <InputLabel for="order" value="Volgorde" />
                                    <TextInput id="order" type="number" class="mt-1 block w-full" v-model="form.order" required />
                                    <p class="text-xs text-gray-500 mt-1">Items met een lager nummer worden eerst getoond.</p>
                                    <InputError class="mt-2" :message="form.errors.order" />
                                </div>

                                <!-- Status -->
                                <div class="flex items-center pt-6">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" v-model="form.is_active" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                        <span class="ml-2 text-sm font-medium text-gray-700">Item is Actief (Zichtbaar op website)</span>
                                    </label>
                                </div>
                            </div>

                        </div>

                        <div class="flex items-center justify-end mt-8 pt-4 border-t border-gray-100">
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                {{ isEdit ? 'Wijzigingen Opslaan' : 'FAQ Aanmaken' }}
                            </PrimaryButton>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>