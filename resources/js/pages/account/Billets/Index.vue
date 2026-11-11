<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import SiteContainer from '@/components/SiteContainer.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { show as billetShow } from '@/routes/account/billets';

type BilletRow = {
    id: number;
    ticket_code: string;
    status: string;
    status_label: string;
    film_title: string;
    film_poster_url: string | null;
    seance_date_formatted: string;
    seance_time_range: string;
    reservation_reference: string;
    issued_at_formatted: string;
};

defineProps<{
    billets: BilletRow[];
}>();
</script>

<template>
    <Head title="Mes billets" />

    <div
        class="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
    >
        <SiteHeader />
        <main class="flex flex-1 flex-col pt-20 pb-16">
            <SiteContainer>
                <h1 class="mb-8 text-3xl font-semibold">Mes billets</h1>

                <ul class="grid gap-4 sm:grid-cols-2">
                    <li
                        v-for="billet in billets"
                        :key="billet.id"
                        class="rounded-xl border p-4"
                    >
                        <p class="font-medium">{{ billet.film_title }}</p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ billet.seance_date_formatted }} ·
                            {{ billet.seance_time_range }}
                        </p>
                        <p class="mt-2 font-mono text-xs">
                            {{ billet.ticket_code }}
                        </p>
                        <div
                            class="mt-4 flex items-center justify-between gap-2"
                        >
                            <Badge variant="outline">{{
                                billet.status_label
                            }}</Badge>
                            <Button as-child size="sm">
                                <Link :href="billetShow({ billet: billet.id })">
                                    Ouvrir
                                </Link>
                            </Button>
                        </div>
                    </li>
                </ul>

                <p
                    v-if="billets.length === 0"
                    class="py-16 text-center text-muted-foreground"
                >
                    Aucun billet pour le moment.
                </p>
            </SiteContainer>
        </main>
    </div>
</template>
