@component('mail::message')
# Nuovo messaggio cliente su Party Legacy

Hai ricevuto un nuovo messaggio da parte di un cliente sulla piattaforma.

Accedi al gestionale per leggerlo e rispondere:

@component('mail::button', ['url' => route('vendor.conversations')])
Vai alle Conversazioni
@endcomponent

Grazie,<br>
Il team di Party Legacy
@endcomponent
