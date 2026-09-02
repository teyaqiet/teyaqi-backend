<x-card>

<x-slot:header>
{{ __('Leaderboard Settings') }}
</x-slot:header>


@include(
    'admin.system.settings.sections._fields',
    [
        'settings'=>$settings
    ]
)


</x-card>