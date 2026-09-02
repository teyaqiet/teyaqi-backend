@extends('admin.layouts.main')

@section('title', __('Profile'))

@section('content')
    <x-page-header title="{{ __('Profile') }}" subtitle="{{ __('Manage your personal account information and settings') }}" icon="ik ik-file-text"
                    :breadcrumbs="['Home' => route('admin.dashboard'), 'Pages' => null, 'Profile' => null]" />

    @php
        $selectClass = 'w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/25';
        $user = auth()->user();
    @endphp

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-xs font-medium text-emerald-700 shadow-sm">
            <div class="flex items-center gap-2">
                <i class="ik ik-check-circle text-base"></i>
                <span>{{ session('status') }}</span>
            </div>
        </div>
    @endif

    <div class="w-full grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left Profile Summary Card --}}
        <div class="lg:col-span-1">
            <x-card>
                <div class="text-center">
                    <img src="{{ $user->avatar ?? asset('img/user.jpg') }}" class="mx-auto h-32 w-32 rounded-full object-cover ring-4 ring-indigo-50" alt="{{ $user->name }}">
                    <h4 class="mt-4 text-lg font-bold text-gray-900">{{ $user->name }}</h4>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">{{ $user->roles->first()?->name ? ucfirst($user->roles->first()->name) : __('Administrator') }}</p>
                    <div class="mt-4 flex justify-center gap-6 text-sm">
                        <a href="javascript:void(0)" class="text-gray-600 hover:text-indigo-600"><i class="ik ik-user"></i> <span class="font-medium">254</span></a>
                        <a href="javascript:void(0)" class="text-gray-600 hover:text-indigo-600"><i class="ik ik-image"></i> <span class="font-medium">54</span></a>
                    </div>
                </div>
                <hr class="my-5 border-gray-100">
                <div class="space-y-3 text-sm">
                    <div>
                        <span class="block text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ __('Email address') }}</span>
                        <span class="font-medium text-gray-800">{{ $user->email }}</span>
                    </div>
                    <div>
                        <span class="block text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ __('Phone') }}</span>
                        <span class="font-medium text-gray-800">{{ $user->phone ?? __('(123) 456 7890') }}</span>
                    </div>
                    <div>
                        <span class="block text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ __('Address') }}</span>
                        <span class="font-medium text-gray-800">{{ $user->address ?? __('71 Pilgrim Avenue Chevy Chase, MD 20815') }}</span>
                    </div>
                </div>
                <div class="mt-5 overflow-hidden rounded-xl border border-gray-100 shadow-sm">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d248849.886539092!2d77.49085452149588!3d12.953959988118836!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3bae1670c9b44e6d%3A0xf8dfc3e8517e4fe0!2sBengaluru%2C+Karnataka!5e0!3m2!1sen!2sin!4v1542005497600" width="100%" height="200" style="border:0" allowfullscreen></iframe>
                </div>
                <div class="mt-5">
                    <span class="block text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ __('Social Profile') }}</span>
                    <div class="mt-2 flex gap-2">
                        <button class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-sm hover:bg-indigo-500 transition"><i class="fab fa-facebook-f text-xs"></i></button>
                        <button class="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-500 text-white shadow-sm hover:bg-sky-400 transition"><i class="fab fa-twitter text-xs"></i></button>
                        <button class="flex h-9 w-9 items-center justify-center rounded-xl bg-pink-600 text-white shadow-sm hover:bg-pink-500 transition"><i class="fab fa-instagram text-xs"></i></button>
                    </div>
                </div>
            </x-card>
        </div>

        {{-- Right Tabs Container --}}
        <div class="lg:col-span-2" x-data="{ tab: 'timeline' }">
            <x-card no-padding>
                <div class="flex gap-1 border-b border-gray-100 px-6 pt-4">
                    @foreach (['timeline' => __('Timeline'), 'profile' => __('Profile'), 'setting' => __('Setting')] as $key => $label)
                        <button @click="tab = '{{ $key }}'"
                                :class="tab === '{{ $key }}' ? 'border-indigo-600 text-indigo-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 font-medium'"
                                class="border-b-2 px-4 py-2 text-xs uppercase tracking-wider transition">{{ $label }}</button>
                    @endforeach
                </div>

                <div class="p-6">
                    {{-- Timeline Tab --}}
                    <div x-show="tab === 'timeline'" class="space-y-6">
                        <div class="flex gap-4">
                            <img src="{{ asset('img/users/1.jpg') }}" alt="" class="h-10 w-10 rounded-full object-cover">
                            <div class="flex-1">
                                <a href="javascript:void(0)" class="font-semibold text-gray-900 text-sm">{{ $user->name }}</a> <span class="text-xs text-gray-400">5 minutes ago</span>
                                <p class="text-xs text-gray-600 mt-0.5">assign a new task <a href="javascript:void(0)" class="text-indigo-600 font-medium">Design weblayout</a></p>
                                <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    @foreach (['img2.jpg', 'img3.jpg', 'img4.jpg', 'img5.jpg'] as $img)
                                        <img src="{{ asset('img/big/'.$img) }}" class="rounded-xl object-cover shadow-sm" alt="">
                                    @endforeach
                                </div>
                                <div class="mt-3 flex gap-4 text-xs font-medium">
                                    <a href="javascript:void(0)" class="text-indigo-600 hover:underline">2 comments</a>
                                    <a href="javascript:void(0)" class="text-pink-600 hover:underline flex items-center gap-1"><i class="fa fa-heart text-pink-500"></i> 5 Love</a>
                                </div>
                            </div>
                        </div>
                        <hr class="border-gray-100">
                        <div class="flex gap-4">
                            <img src="{{ asset('img/users/2.jpg') }}" alt="" class="h-10 w-10 rounded-full object-cover">
                            <div class="flex-1">
                                <a href="javascript:void(0)" class="font-semibold text-gray-900 text-sm">{{ $user->name }}</a> <span class="text-xs text-gray-400">15 minutes ago</span>
                                <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-4">
                                    <img src="{{ asset('img/big/img6.jpg') }}" alt="" class="rounded-xl object-cover shadow-sm md:col-span-1 h-24 w-full">
                                    <div class="md:col-span-3">
                                        <p class="text-xs text-gray-600 leading-relaxed">{{ __('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec odio. Praesent libero. Sed cursus ante dapibus diam.') }}</p>
                                        <button type="button" class="mt-3 inline-flex items-center rounded-xl bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Design weblayout</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Profile Details Tab --}}
                    <div x-show="tab === 'profile'" x-cloak style="display:none;" class="space-y-6">
                        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                            <div><strong class="text-xs uppercase tracking-wider text-gray-400">{{ __('Full Name') }}</strong><p class="mt-1 text-sm font-medium text-gray-800">{{ $user->name }}</p></div>
                            <div><strong class="text-xs uppercase tracking-wider text-gray-400">{{ __('Mobile') }}</strong><p class="mt-1 text-sm font-medium text-gray-800">{{ $user->phone ?? '(123) 456 7890' }}</p></div>
                            <div><strong class="text-xs uppercase tracking-wider text-gray-400">{{ __('Email') }}</strong><p class="mt-1 text-sm font-medium text-gray-800">{{ $user->email }}</p></div>
                            <div><strong class="text-xs uppercase tracking-wider text-gray-400">{{ __('Location') }}</strong><p class="mt-1 text-sm font-medium text-gray-800">{{ $user->location ?? 'London' }}</p></div>
                        </div>
                        <hr class="border-gray-100">
                        <div class="space-y-3 text-xs text-gray-600 leading-relaxed">
                            <p>{{ __('Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus.') }}</p>
                            <p>{{ __('Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industrys standard dummy text ever since the 1500s.') }}</p>
                        </div>
                        <h4 class="mt-6 text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Skill Set') }}</h4>
                        <hr class="my-3 border-gray-100">
                        <div class="space-y-3">
                            @foreach ([[__('Wordpress'), 80, 'bg-emerald-500'], [__('HTML 5'), 90, 'bg-indigo-600'], [__('jQuery'), 50, 'bg-amber-500'], [__('Photoshop'), 70, 'bg-pink-500']] as [$skill, $pct, $bar])
                                <div>
                                    <h6 class="flex justify-between text-xs font-semibold text-gray-700 mb-1"><span>{{ $skill }}</span> <span>{{ $pct }}%</span></h6>
                                    <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                                        <div class="h-full rounded-full {{ $bar }}" style="width: {{ $pct }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Settings Tab Form --}}
                    <div x-show="tab === 'setting'" x-cloak style="display:none;">
                        <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-4">
                            @csrf
                            @method('PUT')

                            <div>
                                <label for="name" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-600">{{ __('Full Name') }}</label>
                                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required class="{{ $selectClass }}">
                            </div>
                            <div>
                                <label for="email" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-600">{{ __('Email') }}</label>
                                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required class="{{ $selectClass }}">
                            </div>
                            <div>
                                <label for="password" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-600">{{ __('New Password') }} <span class="lowercase text-gray-400 font-normal">(leave blank to keep current)</span></label>
                                <input type="password" name="password" id="password" class="{{ $selectClass }}" placeholder="••••••••">
                            </div>
                            <div>
                                <label for="phone" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-600">{{ __('Phone No') }}</label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone ?? '') }}" class="{{ $selectClass }}" placeholder="123 456 7890">
                            </div>
                            <div>
                                <label for="address" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-600">{{ __('Address / Bio') }}</label>
                                <textarea name="address" id="address" rows="3" class="{{ $selectClass }}">{{ old('address', $user->address ?? '') }}</textarea>
                            </div>
                            <div>
                                <label for="location" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-600">{{ __('Select Country / Location') }}</label>
                                <select name="location" id="location" class="{{ $selectClass }}">
                                    @foreach(['London', 'India', 'Usa', 'Canada', 'Thailand'] as $country)
                                        <option value="{{ $country }}" {{ old('location', $user->location ?? 'London') === $country ? 'selected' : '' }}>{{ __($country) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="pt-2">
                                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-semibold text-white shadow-lg shadow-indigo-600/20 hover:bg-indigo-500 transition">
                                    {{ __('Update Profile') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
@endsection