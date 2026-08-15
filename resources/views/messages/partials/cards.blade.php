{{-- ─── LOOP CARD PESAN — dipakai render awal & fragment AJAX infinite scroll ─── --}}
{{-- Butuh variabel: $messages (paginator/collection) dan $myReactions (map message_id → emoji user). --}}
@foreach ($messages as $msg)
    @include('messages.partials.card', [
        'msg' => $msg,
        'mine' => $myReactions->get($msg->id, collect())->all(),
    ])
@endforeach
