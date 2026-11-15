{{-- Flash messages and the verification notice from AlertHelper ($alerts); shell/Alerts.jsx is its React twin --}}
@foreach($alerts['messages'] as $message)
    <div class="alert alert-{{ $message['variant'] }}" role="alert">
        @if($message['variant'] === 'danger')
            <i class="fa-sharp fa-lg fa-exclamation-circle"></i>
        @endif
        {{ $message['text'] }}
    </div>
@endforeach

@if($alerts['verification'])
    <div class="alert alert-warning" role="alert">
        Your account is awaiting email verification.

        <form method="POST" action="{{ $alerts['verification']['action'] }}">
            @csrf
            <button class="btn btn-sm btn-primary text-secondary mt-2">Resend verification email to {{ $alerts['verification']['email'] }}</button>
        </form>
    </div>
@endif
