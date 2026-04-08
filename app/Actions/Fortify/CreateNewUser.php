<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Models\VendorAccount;
use App\Services\GeocodingService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;
use App\Services\PrestashopProductSyncService;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function create(array $input): User
    {
        // Validazione base utente + campi vendor.
        Validator::make($input, [
            // Jetstream
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),

            // Terms Legali (Privacy e Contratto)
            'privacy_accepted' => ['accepted', 'required'],
            'contract_accepted' => ['accepted', 'required'],

            // Vendor core
            'account_type' => ['required', Rule::in(['COMPANY', 'PRIVATE'])],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'event_type_ids' => ['required', 'array', 'min:1'],
            'event_type_ids.*' => ['integer', 'exists:event_types,id'],

            // Dati fiscali minimi
            'company_name' => ['nullable', 'string', 'max:255'],
            'vat_number' => ['nullable', 'string', 'max:50'],
            'legal_entity_type' => ['nullable', 'string', 'max:50'],
            'tax_code' => ['nullable', 'string', 'max:50'],

            // Sede legale minima
            'legal_city' => ['required', 'string', 'max:255'],
            'legal_postal_code' => ['required', 'string', 'max:20'],
            'legal_address_line1' => ['required', 'string', 'max:255'],

            // Campi opzionali già presenti nel form
            'legal_country' => ['nullable', 'string', 'max:2'],
            'legal_region' => ['nullable', 'string', 'max:255'],

            // Recapiti Commerciali
            'billing_email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],

            // Immagine profilo
            'profile_image' => ['nullable', 'image', 'max:8192'], // MAX 8MB
        ])->after(function ($validator) use ($input) {
            $type = $input['account_type'] ?? null;

            // Azienda: ragione sociale + partita IVA obbligatorie.
            if ($type === 'COMPANY') {
                if (empty(trim((string) ($input['company_name'] ?? '')))) {
                    $validator->errors()->add(
                        'company_name',
                        'La ragione sociale è obbligatoria per un account azienda.'
                    );
                }

                if (empty(trim((string) ($input['vat_number'] ?? '')))) {
                    $validator->errors()->add(
                        'vat_number',
                        'La partita IVA è obbligatoria per un account azienda.'
                    );
                }
            }

            // Privato: codice fiscale obbligatorio.
            if ($type === 'PRIVATE') {
                if (empty(trim((string) ($input['tax_code'] ?? '')))) {
                    $validator->errors()->add(
                        'tax_code',
                        'Il codice fiscale è obbligatorio per un account privato.'
                    );
                }
                if (empty(trim((string) ($input['first_name'] ?? '')))) {
                    $validator->errors()->add('first_name', 'Il nome è obbligatorio per un account privato.');
                }
                if (empty(trim((string) ($input['last_name'] ?? '')))) {
                    $validator->errors()->add('last_name', 'Il cognome è obbligatorio per un account privato.');
                }
            }
        })->validate();

        // Salva eventuale immagine profilo
        $profileImagePath = null;
        if (isset($input['profile_image']) && $input['profile_image'] instanceof \Illuminate\Http\UploadedFile) {
            $file = $input['profile_image'];
            $filename = \Illuminate\Support\Str::random(40) . '.' . $file->getClientOriginalExtension();
            $profileImagePath = $file->storeAs('vendors/profiles', $filename, 'public');
        }

        // Crea user.
        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);

        // Assegna ruolo vendor.
        $user->assignRole('vendor');

        // Crea vendor account.
        $vendor = VendorAccount::create([
            'user_id' => $user->id,
            'category_id' => (int) $input['category_id'],
            'account_type' => $input['account_type'],
            
            'first_name' => $input['account_type'] === 'PRIVATE' ? ($input['first_name'] ?? null) : null,
            'last_name' => $input['account_type'] === 'PRIVATE' ? ($input['last_name'] ?? null) : null,

            'billing_email' => $input['billing_email'] ?? null,
            'phone' => $input['phone'] ?? null,

            'privacy_accepted_at' => now(),
            'contract_accepted_at' => now(),

            // Dati fiscali
            'company_name' => $input['account_type'] === 'COMPANY'
                ? ($input['company_name'] ?? null)
                : null,
            'vat_number' => $input['account_type'] === 'COMPANY'
                ? ($input['vat_number'] ?? null)
                : null,
            'legal_entity_type' => $input['account_type'] === 'COMPANY'
                ? ($input['legal_entity_type'] ?? null)
                : null,
            'tax_code' => $input['account_type'] === 'PRIVATE'
                ? ($input['tax_code'] ?? null)
                : null,

            // Sede legale minima
            'legal_country' => $input['legal_country'] ?? 'IT',
            'legal_region' => $input['legal_region'] ?? null,
            'legal_city' => $input['legal_city'],
            'legal_postal_code' => $input['legal_postal_code'],
            'legal_address_line1' => $input['legal_address_line1'],

            // Per ora manteniamo la sede operativa uguale alla legale.
            'operational_same_as_legal' => true,

            // Immagine
            'profile_image_path' => $profileImagePath,

            // Stato: PENDING di default, in attesa di approvazione Admin (Fase 2)
            'status' => 'PENDING',
            'activated_at' => null,
        ]);

        // Il prodotto ombra verrà creato solo quando l'Admin imposterà lo status su ACTIVE.

        if (!empty($input['event_type_ids'])) {
            $vendor->eventTypes()->sync($input['event_type_ids']);
        }

        // Geocoding della sede legale.
        // Se la sede operativa coincide, copiamo le stesse coordinate.
        try {
            $coords = app(GeocodingService::class)->geocodeItaly([
                'address_line1' => $vendor->legal_address_line1,
                'address_line2' => $vendor->legal_address_line2,
                'postal_code' => $vendor->legal_postal_code,
                'city' => $vendor->legal_city,
                'region' => $vendor->legal_region,
                'country' => $vendor->legal_country ?? 'IT',
            ]);

            if ($coords && ($coords['lat'] ?? null) && ($coords['lng'] ?? null)) {
                $update = [
                    'legal_lat' => $coords['lat'],
                    'legal_lng' => $coords['lng'],
                ];

                if ($vendor->operational_same_as_legal) {
                    $update['operational_lat'] = $coords['lat'];
                    $update['operational_lng'] = $coords['lng'];
                }

                $vendor->update($update);
            }
        } catch (\Throwable $e) {
            // Il geocoding non deve bloccare la registrazione.
            report($e);
        }

        // Notifica all'amministratore (Admin) della nuova registrazione
        try {
            \Illuminate\Support\Facades\Mail::to(config('mail.from.address'))
                ->send(new \App\Mail\NewVendorRegisteredAdminMail($vendor));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Impossibile inviare notifica Admin per nuovo Vendor: ' . $e->getMessage());
        }

        return $user;
    }
}