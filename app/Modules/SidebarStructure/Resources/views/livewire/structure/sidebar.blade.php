<x-tree.list>
    @foreach ($structures as $structure)
        <x-tree.item :model="$structure">{{ $structure->name }}</x-tree.item>
    @endforeach
</x-tree.list>
