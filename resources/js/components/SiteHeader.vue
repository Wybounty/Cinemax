<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { CircleUser, Ticket, User } from '@lucide/vue';
import { computed } from 'vue';
import SiteContainer from '@/components/SiteContainer.vue';
import { Button } from '@/components/ui/button';
import { index as billetsIndex } from '@/routes/account/billets';
import { index as reservationsIndex } from '@/routes/dashboard/my-reservations';
import { dashboard, film, home, login } from '@/routes';

const auth = computed(() => usePage().props.auth);

const navLinks = [
    { label: 'Films', href: film().url },
    { label: 'Horaires', href: '#' },
    { label: 'Salles', href: '#' },
];
</script>

<template>
    <header
        class="fixed inset-x-0 top-0 z-50 w-full border-b border-[#19140035] bg-[#FDFDFC]/95 backdrop-blur-sm dark:border-[#3E3E3A] dark:bg-[#0a0a0a]/95"
    >
        <SiteContainer class="flex h-14 items-center justify-between gap-4">
            <Link
                :href="home()"
                class="shrink-0 text-lg font-semibold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
            >
                Cinemax
            </Link>

            <nav
                class="hidden flex-1 items-center justify-center gap-8 md:flex"
                aria-label="Navigation principale"
            >
                <a
                    v-for="item in navLinks"
                    :key="item.label"
                    :href="item.href"
                    class="text-sm text-[#1b1b18]/70 transition-colors hover:text-[#1b1b18] dark:text-[#EDEDEC]/70 dark:hover:text-[#EDEDEC]"
                >
                    {{ item.label }}
                </a>
            </nav>

            <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                <Button as-child size="sm" class="hidden sm:inline-flex">
                    <Link :href="film()">
                        <Ticket class="size-4" aria-hidden="true" />
                        Films à l’affiche
                    </Link>
                </Button>

                <Link
                    v-if="auth.user"
                    :href="reservationsIndex()"
                    class="hidden text-sm text-[#1b1b18]/70 hover:text-[#1b1b18] dark:text-[#EDEDEC]/70 dark:hover:text-[#EDEDEC] sm:inline"
                >
                    Mes réservations
                </Link>
                <Link
                    v-if="auth.user"
                    :href="billetsIndex()"
                    class="hidden text-sm text-[#1b1b18]/70 hover:text-[#1b1b18] dark:text-[#EDEDEC]/70 dark:hover:text-[#EDEDEC] sm:inline"
                >
                    Mes billets
                </Link>

                <Link
                    v-if="auth.user"
                    :href="dashboard()"
                    class="inline-flex size-9 items-center justify-center rounded-md text-[#1b1b18] transition-colors hover:bg-[#19140035] dark:text-[#EDEDEC] dark:hover:bg-[#3E3E3A]"
                    aria-label="Mon compte"
                >
                    <User class="size-5" />
                </Link>
                <Link
                    v-else
                    :href="login()"
                    class="inline-flex size-9 items-center justify-center rounded-md text-[#1b1b18] transition-colors hover:bg-[#19140035] dark:text-[#EDEDEC] dark:hover:bg-[#3E3E3A]"
                    aria-label="Se connecter"
                >
                    <CircleUser class="size-5" />
                </Link>
            </div>
        </SiteContainer>
    </header>
</template>
