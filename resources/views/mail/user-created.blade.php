<x-mail::message>
  # Bonjour {{ $user_created['firstname'] }},

  Votre compte a été créé avec succès.
  Vous pouvez créer votre mot de passe en cliquant sur le bouton ci-dessous.

  <x-mail::button :url="''">
    Button Text
  </x-mail::button>

  Merci,<br>
  {{ config('app.name') }}
</x-mail::message>
