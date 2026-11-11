<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Clapperboard } from '@lucide/vue';
import SiteContainer from '@/components/SiteContainer.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import { Button } from '@/components/ui/button';
import { show as filmShow } from '@/routes/film';

type CatalogFilm = {
    id: number;
    title: string;
    poster_url: string | null;
};

defineProps<{
    films: CatalogFilm[];
}>();
</script>

<template>
    <Head title="Films à l'affiche" />

    <div
        class="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
    >
        <SiteHeader />
        <main class="flex flex-1 flex-col pt-20 pb-12">
            <SiteContainer>
                <header class="mb-10">
                    <h1
                        class="text-3xl font-semibold tracking-tight md:text-4xl"
                    >
                        À l'affiche
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm text-[#1b1b18]/70 dark:text-[#EDEDEC]/70">
                        Découvrez la programmation Grand Cinéma et consultez
                        les séances à venir pour chaque film.
                    </p>
                </header>

                <ul
                    class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4"
                >
                    <li
                        v-for="film in films"
                        :key="film.id"
                        class="group flex flex-col"
                    >
                        <Link
                            :href="filmShow({ film: film.id })"
                            class="flex flex-col outline-none focus-visible:ring-2 focus-visible:ring-[#1b1b18]/30 dark:focus-visible:ring-[#EDEDEC]/40"
                        >
                            <div
                                class="relative mb-4 aspect-[2/3] overflow-hidden rounded-xl border border-[#19140035] bg-[#19140008] shadow-sm dark:border-[#3E3E3A] dark:bg-[#141414]"
                            >
                                <img
                                    v-if="film.poster_url"
                                    :src="film.poster_url"
                                    :alt="`Affiche de ${film.title}`"
                                    class="size-full object-cover transition-transform duration-300 group-hover:scale-[1.02]"
                                />
                                <div
                                    v-else
                                    class="flex size-full items-center justify-center"
                                >
                                    <Clapperboard
                                        class="size-12 text-[#1b1b18]/30 dark:text-[#EDEDEC]/30"
                                    />
                                </div>
                            </div>
                            <h2
                                class="text-lg font-medium leading-snug transition-colors group-hover:text-[#1b1b18] dark:group-hover:text-white"
                            >
                                {{ film.title }}
                            </h2>
                        </Link>
                        <Button
                            as-child
                            class="mt-4 w-full"
                            variant="outline"
                        >
                            <Link :href="filmShow({ film: film.id })">
                                Voir les séances
                            </Link>
                        </Button>
                    </li>
                </ul>

                <p
                    v-if="films.length === 0"
                    class="py-16 text-center text-[#1b1b18]/60 dark:text-[#EDEDEC]/60"
                >
                    Aucun film publié pour le moment.
                </p>
            </SiteContainer>
        </main>
    </div>
</template>
