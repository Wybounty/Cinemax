<x-mail::message>
# {{ __('Réservation confirmée') }}

{{ __('Bonjour :name,', ['name' => $reservation->contact_name ?? __('spectateur')]) }}

{{ __('Merci pour votre réservation chez :brand. Votre paiement simulé est confirmé.', ['brand' => config('app.name')]) }}

**{{ $reservation->seance?->film?->title ?? '—' }}**

{{ $reservation->seance?->start_at?->locale('fr')->translatedFormat('l j F Y') }}
· {{ $reservation->seance?->start_at?->format('H:i') }} – {{ $reservation->seance?->end_at?->format('H:i') }}

{{ __('Salle') }} : {{ $reservation->seance?->salle?->name ?? '—' }}

| | |
|---|---|
| {{ __('Référence') }} | **{{ $reservation->reference }}** |
| {{ __('Places') }} | {{ $reservation->quantity }} |
| {{ __('Montant') }} | {{ number_format((float) $reservation->total_amount, 2, ',', ' ') }} € |

{{ __('Vos billets numériques de démonstration sont en pièce jointe (PDF). Chaque place possède son QR code scannable contenant uniquement le code du billet.') }}

@if ($reservation->billets->isNotEmpty())
**{{ __('Codes billet') }}**

@foreach ($reservation->billets as $billet)
- `{{ $billet->ticket_code }}`
@endforeach
@endif

@if ($reservation->user_id !== null)
<x-mail::button :url="url('/dashboard/mes-reservations/'.$reservation->id)">
{{ __('Voir dans mon espace') }}
</x-mail::button>
@endif

{{ __('Bonne séance,') }}<br>
{{ config('app.name') }}
</x-mail::message>
