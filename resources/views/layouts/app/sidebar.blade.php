<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('portfolio.admin.panel')" class="grid">
                    <flux:sidebar.item icon="identification" :href="route('dashboard.content')" :current="request()->routeIs('dashboard.content')" wire:navigate>
                        {{ __('portfolio.admin.content') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="squares-2x2" :href="route('dashboard.projects')" :current="request()->routeIs('dashboard.projects')" wire:navigate>
                        {{ __('portfolio.admin.projects') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="briefcase" :href="route('dashboard.experiences')" :current="request()->routeIs('dashboard.experiences')" wire:navigate>
                        {{ __('portfolio.admin.experience') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="chart-bar" :href="route('dashboard.skills')" :current="request()->routeIs('dashboard.skills')" wire:navigate>
                        {{ __('portfolio.admin.skills') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('portfolio.admin.publishing')" class="grid">
                    <flux:sidebar.item icon="academic-cap" :href="route('dashboard.courses')" :current="request()->routeIs('dashboard.courses')" wire:navigate>
                        {{ __('portfolio.admin.courses') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="book-open-text" :href="route('dashboard.guides')" :current="request()->routeIs('dashboard.guides')" wire:navigate>
                        {{ __('portfolio.admin.guides') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="inbox"
                        :href="route('dashboard.messages')"
                        :current="request()->routeIs('dashboard.messages')"
                        :badge="\App\Models\ContactMessage::query()->unread()->count() ?: null"
                        wire:navigate
                    >
                        {{ __('portfolio.admin.messages') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="globe-alt" :href="route('home')">
                    {{ __('portfolio.admin.view_site') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
