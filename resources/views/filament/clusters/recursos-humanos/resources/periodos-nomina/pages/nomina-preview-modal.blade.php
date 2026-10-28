<div class="space-y-4">
    <div>
        <h3 class="text-base font-semibold text-gray-950">{{ $title }}</h3>
        <p class="text-sm text-gray-500">{{ $subtitle }}</p>
    </div>
    <iframe
        src="{{ $url }}"
        title="{{ $title }}"
        class="h-[65vh] min-h-[28rem] w-full rounded-lg border border-gray-200 bg-white"
    ></iframe>
</div>
