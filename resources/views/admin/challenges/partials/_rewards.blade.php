{{-- REWARDS & POINTS --}}

<div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">


    <div class="mb-5">

        <h2 class="font-semibold text-gray-800">
            Completion Rewards
        </h2>

        <p class="mt-1 text-xs text-gray-500">
            Configure what players receive after completing this challenge.
        </p>

    </div>





    <div class="grid gap-5 md:grid-cols-2">



        {{-- XP REWARD --}}
        <div>


            <label class="text-xs font-medium text-gray-500">
                Experience Points (XP)
            </label>



            <div class="relative mt-2">


                <input
                    type="number"
                    name="reward_xp"
                    value="{{ old(
                        'reward_xp',
                        optional($challenge ?? null)->reward_xp ?? 0
                    ) }}"
                    min="0"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 pr-14 text-sm focus:border-primary-500 focus:ring-0"
                    placeholder="100"
                >



                <span
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-gray-400"
                >
                    XP
                </span>



            </div>



            <p class="mt-1 text-xs text-gray-400">
                Amount of experience added to player progression.
            </p>



        </div>









        {{-- COINS --}}
        <div>


            <label class="text-xs font-medium text-gray-500">
                Teyaqi Coins
            </label>




            <div class="relative mt-2">


                <input
                    type="number"
                    name="reward_coins"
                    value="{{ old(
                        'reward_coins',
                        optional($challenge ?? null)->reward_coins ?? 0
                    ) }}"
                    min="0"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 pr-20 text-sm focus:border-primary-500 focus:ring-0"
                    placeholder="20"
                >




                <span
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-gray-400"
                >
                    Coins
                </span>




            </div>



            <p class="mt-1 text-xs text-gray-400">
                Virtual currency reward after completion.
            </p>



        </div>



    </div>









    {{-- FUTURE REWARD SYSTEM --}}
    <div class="mt-6 rounded-xl border border-dashed border-gray-200 bg-gray-50 p-4">


        <div class="flex items-start gap-3">



            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-100 text-primary-600">

                <i class="ik ik-gift"></i>

            </div>





            <div>


                <h3 class="text-sm font-semibold text-gray-700">
                    Future Reward Extensions
                </h3>



                <p class="mt-1 text-xs text-gray-500">
                    Future versions can add badges, avatars, boosters, streak bonuses and special rewards.
                </p>



            </div>



        </div>



    </div>



</div>