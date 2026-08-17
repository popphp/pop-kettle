<script setup>
import { ref } from 'vue';

const count = ref(0);
</script>

<template>
    <div class="p-8">
        <h1 class="text-3xl font-bold">It works!</h1>
        <button class="mt-4 px-4 py-2 bg-blue-600 text-white rounded" @click="count++">
            Count: {{ count }}
        </button>
    </div>
</template>
