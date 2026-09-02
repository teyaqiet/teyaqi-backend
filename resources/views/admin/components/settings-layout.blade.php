@props(['active' => 'general'])

@php

$sections = [

    'general' => [
        'label' => __('General'),
        'icon' => 'ik ik-sliders',
    ],

    'game' => [
        'label' => __('Game'),
        'icon' => 'ik ik-play',
    ],

    'rewards' => [
        'label' => __('Rewards'),
        'icon' => 'ik ik-gift',
    ],

    'lives' => [
        'label' => __('Lives'),
        'icon' => 'ik ik-heart',
    ],

    'streak' => [
        'label' => __('Streak'),
        'icon' => 'ik ik-zap',
    ],

    'leaderboard' => [
        'label' => __('Leaderboard'),
        'icon' => 'ik ik-award',
    ],

    'ai' => [
        'label' => __('AI'),
        'icon' => 'ik ik-cpu',
    ],

    'telegram' => [
        'label' => __('Telegram'),
        'icon' => 'ik ik-send',
    ],

    'notifications' => [
        'label' => __('Notifications'),
        'icon' => 'ik ik-bell',
    ],

    'maintenance' => [
        'label' => __('Maintenance'),
        'icon' => 'ik ik-tool',
    ],

    'feature_flags' => [
        'label' => __('Feature Flags'),
        'icon' => 'ik ik-toggle-right',
    ],

    'security' => [
        'label' => __('Security'),
        'icon' => 'ik ik-shield',
    ],

    'system' => [
        'label' => __('System'),
        'icon' => 'ik ik-server',
    ],

    'about' => [
        'label' => __('About'),
        'icon' => 'ik ik-info',
    ],

];

@endphp


<div class="grid grid-cols-1 gap-6 lg:grid-cols-4">


    {{-- Sidebar --}}
    <aside class="lg:sticky lg:top-20 lg:self-start">


        <nav 
            class="space-y-1"
            aria-label="{{ __('Settings sections') }}"
        >


            @foreach($sections as $key => $section)


                @if(config("settings.$key"))


                <a
                    href="{{ route(
                        'admin.settings.index',
                        ['group'=>$key]
                    ) }}"

                    @class([

                        'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',

                        'bg-primary-50 text-primary-700'
                            => $active === $key,

                        'text-gray-600 hover:bg-gray-100'
                            => $active !== $key,

                    ])
                >


                    <i 
                        class="
                        {{ $section['icon'] }}

                        {{
                            $active === $key
                            ? 'text-primary-600'
                            : 'text-gray-400'
                        }}
                        "
                    ></i>


                    {{ $section['label'] }}


                </a>


                @endif


            @endforeach


        </nav>


    </aside>




    {{-- Content --}}
    <div class="min-w-0 space-y-6 lg:col-span-3">


        {{ $slot }}


    </div>


</div>