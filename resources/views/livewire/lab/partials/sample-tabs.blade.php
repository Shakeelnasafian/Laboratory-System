<div class="flex flex-wrap gap-2 mb-6">
    <a href="{{ route('lab.samples.collection') }}" class="px-4 py-2 rounded-lg text-sm {{ request()->routeIs('lab.samples.collection') ? 'bg-blue-600 text-white' : 'bg-white border text-gray-700' }}">
        Collection Queue
    </a>
    <a href="{{ route('lab.samples.receive') }}" class="px-4 py-2 rounded-lg text-sm {{ request()->routeIs('lab.samples.receive') ? 'bg-blue-600 text-white' : 'bg-white border text-gray-700' }}">
        Sample Receive
    </a>
    <a href="{{ route('lab.samples.rejected') }}" class="px-4 py-2 rounded-lg text-sm {{ request()->routeIs('lab.samples.rejected') ? 'bg-blue-600 text-white' : 'bg-white border text-gray-700' }}">
        Recollect Queue
    </a>
</div>