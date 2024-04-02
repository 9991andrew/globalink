<div>
    <p>{{ $description }}</p>

    <h3>Monster:</h3>
    @foreach ($monsters as $monster)
        <div>{{ $monster->name }} - Health: {{ $monster->health }}, Attack: {{ $monster->attack }}</div>
    @endforeach

    <h3>Items Found:</h3>
    @foreach ($items as $item)
        <div>{{ $item->name }} - Type: {{ class_basename(get_class($item)) }}, Effect: {{ $item->effect ?? 'N/A' }}</div>
    @endforeach

    <h3>Choose Your Action:</h3>
    @foreach ($actions as $action)
        <button>{{ $action }}</button>
    @endforeach
</div>