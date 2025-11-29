<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { defineProps } from 'vue';

const props = defineProps({
    user: {
        type: Object,
        default: null, // Null bij create
    },
    roles: Array, // Lijst van beschikbare rollen
});

const isEdit = !!props.user;

const form = useForm({
    name: props.user ? props.user.name : '',
    email: props.user ? props.user.email : '',
    password: '',
    role: props.user ? props.user.role : props.roles[0],
});

const submit = () => {
    if (isEdit) {
        // Gebruik PUT voor update
        form.put(route('beheer.users.update', props.user.id));
    } else {
        // Gebruik POST voor store
        form.post(route('beheer.users.store'));
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Gebruiker Bewerken' : 'Nieuwe Gebruiker'" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ isEdit ? 'Gebruiker Bewerken: ' + form.name : 'Nieuwe Gebruiker Toevoegen' }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <form @submit.prevent="submit">
                        
                        <div class="mb-4">
                            <InputLabel for="name" value="Naam" />
                            <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required autofocus />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div class="mb-4">
                            <InputLabel for="email" value="E-mail" />
                            <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div class="mb-4">
                            <InputLabel for="role" value="Rol" />
                            <select id="role" v-model="form.role" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option v-for="role in roles" :key="role" :value="role">
                                    {{ role }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.role" />
                        </div>

                        <div class="mb-6">
                            <InputLabel for="password" :value="isEdit ? 'Wachtwoord (Laat leeg om niet te wijzigen)' : 'Wachtwoord'" />
                            <TextInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" :required="!isEdit" autocomplete="new-password" />
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                {{ isEdit ? 'Gebruiker Opslaan' : 'Gebruiker Aanmaken' }}
                            </PrimaryButton>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>