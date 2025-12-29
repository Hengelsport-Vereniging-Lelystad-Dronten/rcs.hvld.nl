<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import WysiwygInput from '@/Components/WysiwygInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    faq: Object
});

const isEdit = !!props.faq;

const form = useForm({
    question: props.faq ? props.faq.question : '',
    answer: props.faq ? props.faq.answer : '',
    order: props.faq ? props.faq.order : 0,
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
                {{ isEdit ? 'Vraag Bewerken' : 'Nieuwe Vraag Toevoegen' }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <form @submit.prevent="submit">
                        <div class="mb-4">
                            <InputLabel for="question" value="Vraag" />
                            <TextInput id="question" type="text" class="mt-1 block w-full" v-model="form.question" required autofocus />
                            <InputError class="mt-2" :message="form.errors.question" />
                        </div>

                        <div class="mb-4">
                            <InputLabel for="order" value="Volgorde (lager is hoger in lijst)" />
                            <TextInput id="order" type="number" class="mt-1 block w-full" v-model="form.order" required />
                            <InputError class="mt-2" :message="form.errors.order" />
                        </div>

                        <div class="mb-6">
                            <InputLabel for="answer" value="Antwoord" />
                            <WysiwygInput id="answer" class="mt-1 block w-full" v-model="form.answer" />
                            <InputError class="mt-2" :message="form.errors.answer" />
                        </div>

                        <div class="flex items-center justify-end">
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                {{ isEdit ? 'Opslaan' : 'Aanmaken' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
