{{-- ============================================================
    COMMON NODE SETTINGS
============================================================= --}}

<div class="space-y-5">

    {{-- Node Name --}}

    <div>

        <label
            class="block text-xs font-medium text-gray-700 mb-2"
        >
            Node Name
        </label>

        <input
            type="text"
            x-model="node.name"
            @input="markDirty()"
            class="w-full rounded-lg border-gray-300 text-sm focus:border-gray-900 focus:ring-gray-900"
            placeholder="Enter node name"
        >

    </div>


    {{-- Description --}}

    <div>

        <label
            class="block text-xs font-medium text-gray-700 mb-2"
        >
            Description
        </label>

        <textarea
            x-model="node.description"
            @input="markDirty()"
            rows="3"
            class="w-full rounded-lg border-gray-300 text-sm focus:border-gray-900 focus:ring-gray-900"
            placeholder="Describe what this node does..."
        ></textarea>

    </div>


    {{-- Component Type --}}

    <div>

        <label
            class="block text-xs font-medium text-gray-700 mb-2"
        >
            Component
        </label>

        <div class="px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-600">
            <span x-text="nodeLabel(node.type)"></span>
        </div>

    </div>

</div>