<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="/">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">

                    <x-nav-link href="/">
                        Főoldal
                    </x-nav-link>

                    <x-nav-link href="/films">
                        Films
                    </x-nav-link>

                    <x-nav-link href="/directors">
                        Directors
                    </x-nav-link>

                    <x-nav-link href="/actors">
                        Actors
                    </x-nav-link>

                </div>
            </div>

            <!-- RIGHT SIDE (LOGIN / LOGOUT) -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">

                @if(session()->has('api_token'))
                    <!-- LOGGED IN -->
                    <div class="flex items-center space-x-3">
                        <span class="text-gray-600">
                            {{ session('user_name') }}
                        </span>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="text-red-600 hover:text-red-800">
                                Kijelentkezés
                            </button>
                        </form>
                    </div>

                @else
                    <!-- NOT LOGGED IN -->
                    <x-nav-link href="{{ route('login') }}">
                        Bejelentkezés
                    </x-nav-link>
                @endif

            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500
                           hover:bg-gray-100 focus:outline-none transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open}"
                              class="inline-flex"
                              stroke-linecap="round" stroke-linejoin="round"
                              stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open}"
                              class="hidden"
                              stroke-linecap="round" stroke-linejoin="round"
                              stroke-width="2"
                              d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">

        <!-- PUBLIC NAV LINKS -->
        <div class="pt-2 pb-3 space-y-1 border-t border-gray-200">

            <x-responsive-nav-link href="/">
                Főoldal
            </x-responsive-nav-link>

            <x-responsive-nav-link href="/films">
                Films
            </x-responsive-nav-link>

            <x-responsive-nav-link href="/directors">
                Directors
            </x-responsive-nav-link>

            <x-responsive-nav-link href="/actors">
                Actors
            </x-responsive-nav-link>

        </div>

        @if(session()->has('api_token'))

            <div class="pt-4 pb-1 border-t border-gray-200">
                <div class="px-4">
                    <div class="font-medium text-base text-gray-800">
                        {{ session('user_name') }}
                    </div>
                    <div class="font-medium text-sm text-gray-500">
                        {{ session('user_email') }}
                    </div>
                </div>

                <div class="mt-3 space-y-1">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link
                            href="{{ route('logout') }}"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                            Kijelentkezés
                        </x-responsive-nav-link>
                    </form>
                </div>
            </div>

        @else

            <div class="pt-4 pb-1 border-t border-gray-200">
                <div class="px-4">
                    <x-responsive-nav-link href="{{ route('login') }}">
                        Bejelentkezés
                    </x-responsive-nav-link>
                </div>
            </div>

        @endif

    </div>
</nav>
