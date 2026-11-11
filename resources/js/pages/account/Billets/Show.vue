<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import SiteContainer from '@/components/SiteContainer.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import { Badge } from '@/components/ui/badge';
import { index as billetsIndex } from '@/routes/account/billets';

type BilletDetail = {
    id: number;
    ticket_code: string;
    status: string;
    status_label: string;
    film_title: string;
    seance_date_formatted: string;
    seance_time_range: string;
    salle_name: string;
    reservation_reference: string;
    issued_at_formatted: string;
    qr_svg: string;
    qr_payload: string;
};

defineProps<{
    billet: BilletDetail;
    film_title: string;
    reservation_reference: string;
}>();
</script>

<template>
    <Head :title="`Billet ${billet.ticket_code}`" />

    <div
        class="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
    >
        <SiteHeader />
        <main class="flex flex-1 flex-col pt-20 pb-16">
            <SiteContainer class="max-w-lg">
                <Link
                    :href="billetsIndex()"
                    class="text-sm text-muted-foreground hover:underline"
                >
                    ← Retour à mes billets
                </Link>

                <div
                    class="mt-6 rounded-2xl border-2 border-dashed p-6 text-center shadow-sm"
                >
                    <p class="text-xs uppercase tracking-wide text-muted-foreground">
                        Grand Cinéma
                    </p>
                    <h1 class="mt-2 text-xl font-semibold">{{ film_title }}</h1>
                    <p class="mt-2 text-sm">
                        {{ billet.seance_date_formatted }}
                    </p>
                    <p class="text-sm font-medium">
                        {{ billet.seance_time_range }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        {{ billet.salle_name }}
                    </p>

                    <div
                        class="mx-auto mt-6 flex size-48 items-center justify-center rounded-lg bg-white p-2"
                        v-html="billet.qr_svg"
                    />

                    <p class="mt-4 font-mono text-sm">{{ billet.ticket_code }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Réservation {{ reservation_reference }}
                    </p>
                    <Badge class="mt-4" variant="outline">{{
                        billet.status_label
                    }}</Badge>
                </div>
            </SiteContainer>
        </main>
    </div>
</template>
