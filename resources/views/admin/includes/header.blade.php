<header class="fixed inset-x-0 top-0 z-30 h-16 border-b border-black/5 bg-topbar text-topbar-text lg:pl-60">
    <div class="flex h-full items-center justify-between px-4 sm:px-6">

        <!-- Left -->
        <div class="flex items-center gap-2">
            <button type="button" @click="sidebarOpen = ! sidebarOpen"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-topbar-text/70 hover:bg-gray-500/10 lg:hidden">
                <i class="ik ik-menu"></i>
            </button>

            <!-- Section switcher -->
            @include('admin.includes.topmenu')

            <button type="button" @click="$dispatch('open-command')"
                    class="hidden items-center gap-2 rounded-lg border border-gray-500/15 bg-gray-500/5 py-2 pl-3 pr-2 text-sm text-topbar-text/50 transition hover:bg-gray-500/10 md:flex">
                <i class="ik ik-search"></i>
                <span class="w-24 text-left lg:w-32">{{ __('Search...') }}</span>
                <kbd class="rounded border border-gray-500/20 px-1.5 py-0.5 text-[10px] font-medium">⌘K</kbd>
            </button>
            <button type="button" @click="$dispatch('open-command')" title="{{ __('Search') }}"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-topbar-text/70 hover:bg-gray-500/10 md:hidden">
                <i class="ik ik-search"></i>
            </button>

            <button type="button" onclick="document.fullscreenElement ? document.exitFullscreen() : document.documentElement.requestFullscreen()"
                    class="hidden h-9 w-9 items-center justify-center rounded-lg text-topbar-text/70 hover:bg-gray-500/10 sm:flex">
                <i class="ik ik-maximize"></i>
            </button>
        </div>

        <!-- Right -->
        <div class="flex items-center gap-1">

            <!-- Quick create -->
            <x-dropdown width="w-52">
                <x-slot:trigger>
                    <button class="flex h-9 items-center gap-1.5 rounded-lg bg-primary-500/10 px-2.5 text-sm font-medium text-primary-600 transition hover:bg-primary-500/15" title="{{ __('Quick create') }}">
                        <i class="ik ik-plus"></i><span class="hidden sm:inline">{{ __('Create') }}</span>
                    </button>
                </x-slot:trigger>
                <div class="px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ __('Quick create') }}</div>
                
                <a href="{{ route('admin.questions.create') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                    <i class="ik ik-help-circle text-gray-400"></i> {{ __('New Question') }}
                </a>
                <a href="{{ route('admin.categories.create') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                    <i class="ik ik-grid text-gray-400"></i> {{ __('New Category') }}
                </a>
                <a href="{{ route('admin.challenges.create') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                    <i class="ik ik-award text-gray-400"></i> {{ __('New Challenge') }}
                </a>
                <a href="{{ route('admin.topics.create') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                    <i class="ik ik-book-open text-gray-400"></i> {{ __('New Topic') }}
                </a>
                
                @can('manage admin users')
                    <div class="my-1 border-t border-gray-100"></div>
                    <a href="{{ route('admin.admin-users.create') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                        <i class="ik ik-user-plus text-gray-400"></i> {{ __('New Admin') }}
                    </a>
                @endcan
            </x-dropdown>

            <!-- Notifications -->
            <x-dropdown width="w-80">
                <x-slot:trigger>
                    <button class="relative flex h-9 w-9 items-center justify-center rounded-lg text-topbar-text/70 hover:bg-gray-500/10">
                        <i class="ik ik-bell"></i>
                        <span class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-accent-500 px-1 text-[10px] font-semibold text-white">3</span>
                    </button>
                </x-slot:trigger>

                <div class="border-b border-gray-100 px-4 py-2 text-sm font-semibold text-gray-700">{{ __('Notifications') }}</div>
                <div class="max-h-80 overflow-y-auto">
                    <a href="#" class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600"><i class="ik ik-check"></i></span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-gray-700">{{ __('New user registered') }}</span>
                            <span class="block truncate text-xs text-gray-500">{{ __('A new player joined Teyaqi...') }}</span>
                        </span>
                    </a>
                    <a href="#" class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600"><i class="ik ik-award"></i></span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-gray-700">{{ __('Challenge completed') }}</span>
                            <span class="block truncate text-xs text-gray-500">{{ __('Weekly challenge milestone reached') }}</span>
                        </span>
                    </a>
                    <a href="#" class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-100 text-primary-600"><i class="ik ik-cpu"></i></span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-gray-700">{{ __('AI Assistant sync') }}</span>
                            <span class="block truncate text-xs text-gray-500">{{ __('Question bank generation complete') }}</span>
                        </span>
                    </a>
                </div>
                <a href="javascript:void(0);" class="block border-t border-gray-100 px-4 py-2 text-center text-sm font-medium text-primary-600 hover:bg-gray-50">{{ __('See all activity') }}</a>
            </x-dropdown>

            <!-- Chat drawer toggle -->
            <button type="button" @click="chatOpen = true"
                    class="relative flex h-9 w-9 items-center justify-center rounded-lg text-topbar-text/70 hover:bg-gray-500/10">
                <i class="ik ik-message-square"></i>
                <span class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-green-500 px-1 text-[10px] font-semibold text-white">3</span>
            </button>

            <!-- App grid -->
            <button type="button" @click="$dispatch('open-apps-modal')"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-topbar-text/70 hover:bg-gray-500/10">
                <i class="ik ik-grid"></i>
            </button>

            <!-- Dark mode toggle -->
            <button type="button" title="{{ __('Toggle dark mode') }}"
                    x-data="{ dark: document.documentElement.classList.contains('dark') }"
                    @click="dark = ! dark; document.documentElement.classList.toggle('dark', dark); localStorage.setItem('radmin-dark', dark ? '1' : '0')"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-topbar-text/70 hover:bg-gray-500/10">
                <i :class="dark ? 'ik ik-sun' : 'ik ik-moon'"></i>
            </button>

            <!-- Theme customizer -->
            <button type="button" @click="$dispatch('open-theme')" title="{{ __('Customize theme') }}"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-topbar-text/70 hover:bg-gray-500/10">
                <i class="ik ik-droplet"></i>
            </button>

            <!-- User -->
            <x-dropdown width="w-56">
                <x-slot:trigger>
                    <button class="ml-1 flex items-center gap-2">
                        @if (auth('admin')->user()?->avatar_url)
                            <img class="h-9 w-9 rounded-full object-cover ring-2 ring-gray-500/15" src="{{ auth('admin')->user()->avatar_url }}" alt="{{ auth('admin')->user()->name }}">
                        @else
                            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white ring-2 ring-gray-500/15">
                                {{ strtoupper(substr(auth('admin')->user()->name ?? 'Admin', 0, 2)) }}
                            </div>
                        @endif
                    </button>
                </x-slot:trigger>

                <div class="border-b border-gray-100 px-4 py-2.5">
                    <p class="text-sm font-semibold text-gray-800 truncate">{{ auth('admin')->user()->name ?? 'Admin' }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ auth('admin')->user()->email ?? '' }}</p>
                </div>

                <a href="{{ route('admin.profile.index', auth('admin')->id() ?? 1) }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                    <i class="ik ik-user text-gray-400"></i> {{ __('Profile') }}
                </a>
                
                <div class="my-1 border-t border-gray-100"></div>
                
                <a href="javascript:void(0);" onclick="event.preventDefault(); document.getElementById('admin-logout-form').submit();" class="flex items-center gap-2 px-4 py-2 text-sm text-rose-600 hover:bg-rose-50 font-medium">
                    <i class="ik ik-power text-rose-500"></i> {{ __('Logout') }}
                </a>

                <form id="admin-logout-form" action="{{ route('admin.logout') }}" method="POST" class="hidden">
                    @csrf
                </form>
            </x-dropdown>
        </div>
    </div>
</header>