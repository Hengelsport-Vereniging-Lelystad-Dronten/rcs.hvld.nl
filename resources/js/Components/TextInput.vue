<script setup>
// TextInput.vue
// Eenvoudig tekstinput-component met autofocus-support en exposeable focus().
// Gebruikt in formulieren; bind via v-model in parent.
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <!-- Gebruik deze input wanneer je een eenvoudige tekstveld nodig hebt. -->
    <input
        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        v-model="model"
        ref="input"
    />
</template>
