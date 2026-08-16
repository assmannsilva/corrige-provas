<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { FileText, Menu } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import {
    Avatar,
    AvatarFallback,
    AvatarImage,
} from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
import AppMobileNav from '@/components/layout/AppMobileNav.vue';
import { getInitials } from '@/composables/useInitials';

const page = usePage();
const auth = computed(() => page.props.auth);

const navItems = [
    { title: 'Provas', href: '/exams', icon: FileText },
];
</script>

<template>
    <header class="sticky top-0 z-50 border-b bg-background">
        <div class="flex h-14 items-center px-4">
            <Link href="/exams" class="flex items-center gap-x-2">
                <AppLogo />
            </Link>

            <nav class="ml-8 hidden items-center gap-1 md:flex">
                <Button
                    v-for="item in navItems"
                    :key="item.title"
                    variant="ghost"
                    size="sm"
                    as-child
                >
                    <Link :href="item.href">
                        <component :is="item.icon" class="mr-2 size-4" />
                        {{ item.title }}
                    </Link>
                </Button>
            </nav>

            <div class="ml-auto flex items-center gap-2">
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="ghost" class="relative size-8 rounded-full">
                            <Avatar class="size-8">
                                <AvatarImage
                                    v-if="auth.user?.avatar"
                                    :src="auth.user.avatar"
                                    :alt="auth.user.name"
                                />
                                <AvatarFallback>
                                    {{ getInitials(auth.user?.name) }}
                                </AvatarFallback>
                            </Avatar>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56">
                        <UserMenuContent :user="auth.user" />
                    </DropdownMenuContent>
                </DropdownMenu>

                <AppMobileNav :items="navItems">
                    <Button variant="ghost" size="icon" class="md:hidden">
                        <Menu class="size-5" />
                    </Button>
                </AppMobileNav>
            </div>
        </div>
    </header>
</template>
