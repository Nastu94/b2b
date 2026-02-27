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

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function create(array $input): User
    {
        // 1) VALIDAZIONE BASE + CAMPI VENDOR
        Validator::make($input, [
            // Jetstream
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),

            // Terms (se attivi)
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature()
                ? ['accepted', 'required']
                : ['sometimes'],

            // Vendor core
            'account_type' => ['required', Rule::in(['COMPANY', 'PRIVATE'])],
            'category_id' => ['required', 'integer', 'exists:categories,id'],

            // Dati fiscali minimi (condizionali)
            'company_name' => ['nullable', 'string', 'max:255'],
            'vat_number' => ['nullable', 'string', 'max:50'],
            'legal_entity_type' => ['nullable', 'string', 'max:50'],

            'tax_code' => ['nullable', 'string', 'max:50'],

            // Sede legale minima
            'legal_city' => ['required', 'string', 'max:255'],
            'legal_postal_code' => ['required', 'string', 'max:20'],
            'legal_address_line1' => ['required', 'string', 'max:255'],
        ])->after(function ($validator) use ($input) {
            $type = $input['account_type'] ?? null;

            if ($type === 'COMPANY') {
                // Azienda: ragione sociale + P.IVA obbligatorie
                if (empty(trim((string)($input['company_name'] ?? '')))) {
                    $validator->errors()->add('company_name', 'La ragione sociale è obbligatoria per un account azienda.');
                }
                if (empty(trim((string)($input['vat_number'] ?? '')))) {
                    $validator->errors()->add('vat_number', 'La partita IVA è obbligatoria per un account azienda.');
                }
            }

            if ($type === 'PRIVATE') {
                // Privato: codice fiscale obbligatorio
                if (empty(trim((string)($input['tax_code'] ?? '')))) {
                    $validator->errors()->add('tax_code', 'Il codice fiscale è obbligatorio per un account privato.');
                }
            }
        })->validate();

        // 2) CREA USER
        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);

        // 3) ASSEGNA RUOLO VENDOR
        $user->assignRole('vendor');

        // 4) CREA VENDOR ACCOUNT
        $vendor = VendorAccount::create([
            'user_id' => $user->id,
            'category_id' => (int) $input['category_id'],
            'account_type' => $input['account_type'],

            // fiscali
            'company_name' => $input['account_type'] === 'COMPANY' ? ($input['company_name'] ?? null) : null,
            'vat_number' => $input['account_type'] === 'COMPANY' ? ($input['vat_number'] ?? null) : null,
            'legal_entity_type' => $input['account_type'] === 'COMPANY' ? ($input['legal_entity_type'] ?? null) : null,

            'tax_code' => $input['account_type'] === 'PRIVATE' ? ($input['tax_code'] ?? null) : null,

            // sede legale minima
            'legal_country' => 'IT',
            'legal_city' => $input['legal_city'],
            'legal_postal_code' => $input['legal_postal_code'],
            'legal_address_line1' => $input['legal_address_line1'],

            // operativa (per ora uguale)
            'operational_same_as_legal' => true,

            // stato
            'status' => 'ACTIVE',
            'activated_at' => now(),
        ]);

        // 5) GEOCODING: salva legal_lat/lng e, se operativa=legale, copia su operativa
        try {
            $coords = app(GeocodingService::class)->geocodeItaly([
                'address_line1' => $vendor->legal_address_line1,
                'address_line2' => $vendor->legal_address_line2,
                'postal_code'   => $vendor->legal_postal_code,
                'city'          => $vendor->legal_city,
                'region'        => $vendor->legal_region,
                'country'       => $vendor->legal_country ?? 'IT',
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
            // non blocchiamo registrazione: log utile per debug
            report($e);
        }

        return $user;
    }
}