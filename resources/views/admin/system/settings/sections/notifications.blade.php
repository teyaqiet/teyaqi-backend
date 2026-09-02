<x-card>

<x-slot:header>
{{ __('Notification Settings') }}
</x-slot:header>


@include(
    'admin.system.settings.sections._fields',
    [
        'settings'=>$settings
    ]
)


</x-card>