<div
    x-show="showMessageModal"
    x-cloak
    class="fixed inset-0 z-[110] flex items-center justify-center bg-black/50 p-4"
    x-transition.opacity
>
    <div
        class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl"
        @click.outside="closeMessageModal()"
        x-transition
    >

        {{-- Content --}}
        <div class="p-6">

            <div class="flex items-start gap-4">

                {{-- Message Icon --}}
                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
                    :class="messageIconClass()"
                >

                    {{-- Success --}}
                    <template x-if="messageModal.type === 'success'">
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.8"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m5.25 12.75 4.5 4.5 9-10.5"
                            />
                        </svg>
                    </template>

                    {{-- Error --}}
                    <template x-if="messageModal.type === 'error'">
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.8"
                            stroke="currentColor"
                        >
                            <circle
                                cx="12"
                                cy="12"
                                r="8.25"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m9 9 6 6m0-6-6 6"
                            />
                        </svg>
                    </template>

                    {{-- Warning --}}
                    <template x-if="messageModal.type === 'warning'">
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.8"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 3.75 21 19.5H3L12 3.75Z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 9v4.5m0 3h.008"
                            />
                        </svg>
                    </template>

                    {{-- Info --}}
                    <template x-if="messageModal.type !== 'success'
                        && messageModal.type !== 'error'
                        && messageModal.type !== 'warning'">
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.8"
                            stroke="currentColor"
                        >
                            <circle
                                cx="12"
                                cy="12"
                                r="8.25"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 10.5v5.25m0-8.25h.008"
                            />
                        </svg>
                    </template>

                </div>

                {{-- Message --}}
                <div class="min-w-0 flex-1">

                    <h2
                        class="text-lg font-semibold text-gray-900"
                        x-text="messageModal.title"
                    >
                    </h2>

                    <p
                        class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-500"
                        x-text="messageModal.message"
                    ></p>

                </div>

            </div>

        </div>

        {{-- Footer --}}
        <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50 p-5">

            {{-- Cancel --}}
            <button
                x-show="messageModal.showCancel"
                type="button"
                @click="closeMessageModal()"
                class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                x-text="messageModal.cancelText"
            >
                Cancel
            </button>

            {{-- Confirm / OK --}}
            <button
                type="button"
                @click="messageModal.onConfirm
                    ? confirmMessage()
                    : closeMessageModal()"
                class="rounded-lg px-4 py-2.5 text-sm font-medium text-white transition"
                :class="{
                    'bg-gray-900 hover:bg-gray-800':
                        messageModal.type === 'warning' ||
                        messageModal.type === 'info',

                    'bg-green-600 hover:bg-green-700':
                        messageModal.type === 'success',

                    'bg-red-600 hover:bg-red-700':
                        messageModal.type === 'error'
                }"
                x-text="messageModal.confirmText"
            >
                OK
            </button>

        </div>

    </div>
</div>