<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import SiteContainer from '@/components/SiteContainer.vue';
import SiteHeader from '@/components/SiteHeader.vue';
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div
        class="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a]"
    >
        <SiteHeader />
        <main class="flex flex-1 flex-col py-6 lg:justify-center lg:py-8">
            <SiteContainer class="flex flex-1 flex-col" />
        </main>
    </div>
</template>
