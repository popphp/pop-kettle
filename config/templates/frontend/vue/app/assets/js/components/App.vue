<script setup>
import { ref } from 'vue';

const count = ref(0);
</script>

<template>
    <div class="inline-flex items-center gap-4 bg-white border border-slate-200 rounded-lg px-5 py-3">
        <span class="text-sm text-slate-500">Vue is working</span>
        <button
            type="button"
            class="px-3 py-1.5 text-sm font-semibold text-white bg-orange-700 rounded-md hover:bg-orange-800 transition"
            @click="count++"
        >
            Count: {{ count }}
        </button>
    </div>
</template>
