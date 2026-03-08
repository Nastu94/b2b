<?php

namespace App\Livewire\Vendor\Pricings\Tabs;

use App\Domain\Pricing\Support\PricingConditions;
use App\Domain\Pricing\Support\PricingOptions;
use App\Domain\Pricing\Support\PricingValidation;
use App\Domain\Pricing\Support\PricingWeekdays;
use App\Models\VendorOfferingPricing;
use App\Models\VendorOfferingPricingRule;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

/**
 * Tab Livewire per la gestione delle regole di pricing.
 *
 * Responsabilità:
 * - elencare le regole del listino base
 * - creare una nuova regola
 * - modificare una regola esistente
 * - attivare/disattivare una regola
 * - eliminare una regola
 *
 * Nota:
 * questa tab richiede che il listino base esista già.
 */
class PricingRulesTab extends Component
{
    use AuthorizesRequests;

    /**
     * ID del listino base corrente.
     */
    public ?int $pricingId = null;

    /**
     * Stato del form regola.
     *
     * @var array<string, mixed>
     */
    public array $form = [];

    /**
     * ID della regola in modifica.
     */
    public ?int $editingRuleId = null;

    /**
     * Indica se il form regola è aperto.
     */
    public bool $showRuleForm = false;

    /**
     * Inizializza il componente.
     */
    public function mount(?int $pricingId = null): void
    {
        $this->pricingId = $pricingId;
        $this->form = $this->getDefaultForm();
    }

    /**
     * Restituisce il listino corrente.
     */
    public function getPricingProperty(): ?VendorOfferingPricing
    {
        if ($this->pricingId === null) {
            return null;
        }

        $vendorOfferingPricing = VendorOfferingPricing::query()->find($this->pricingId);

        if ($vendorOfferingPricing !== null) {
            $this->authorize('view', $vendorOfferingPricing);
        }

        return $vendorOfferingPricing;
    }

    /**
     * Restituisce le regole del listino ordinate per priorità.
     */
    public function getRulesProperty()
    {
        if ($this->pricing === null) {
            return collect();
        }

        return VendorOfferingPricingRule::query()
            ->where('vendor_offering_pricing_id', $this->pricing->id)
            ->ordered()
            ->get();
    }

    /**
     * Apre il form per creare una nuova regola.
     */
    public function startCreate(): void
    {
        if ($this->pricing === null) {
            return;
        }

        $this->authorize('update', $this->pricing);

        $this->editingRuleId = null;
        $this->form = $this->getDefaultForm();
        $this->showRuleForm = true;
    }

    /**
     * Apre il form per modificare una regola esistente.
     */
    public function startEdit(int $ruleId): void
    {
        $vendorOfferingPricingRule = VendorOfferingPricingRule::query()->findOrFail($ruleId);

        $this->authorize('update', $vendorOfferingPricingRule);

        $this->editingRuleId = (int) $vendorOfferingPricingRule->id;

        $this->form = [
            'name' => $vendorOfferingPricingRule->name,
            'is_active' => (bool) $vendorOfferingPricingRule->is_active,
            'priority' => (int) $vendorOfferingPricingRule->priority,
            'rule_type' => $vendorOfferingPricingRule->rule_type?->value ?? $vendorOfferingPricingRule->rule_type,
            'adjustment_type' => $vendorOfferingPricingRule->adjustment_type?->value ?? $vendorOfferingPricingRule->adjustment_type,
            'adjustment_value' => $vendorOfferingPricingRule->adjustment_value,
            'override_price' => $vendorOfferingPricingRule->override_price,
            'starts_at' => $vendorOfferingPricingRule->starts_at?->format('Y-m-d'),
            'ends_at' => $vendorOfferingPricingRule->ends_at?->format('Y-m-d'),
            'weekdays' => $vendorOfferingPricingRule->weekdays ?? [],
            'is_exclusive' => (bool) $vendorOfferingPricingRule->is_exclusive,
            'conditions' => PricingConditions::sanitize($vendorOfferingPricingRule->conditions),
            'notes_internal' => $vendorOfferingPricingRule->notes_internal,
        ];

        $this->showRuleForm = true;
    }

    /**
     * Chiude il form e resetta lo stato locale.
     */
    public function cancelEdit(): void
    {
        $this->resetValidation();
        $this->editingRuleId = null;
        $this->form = $this->getDefaultForm();
        $this->showRuleForm = false;
    }

    /**
     * Salva la regola di pricing.
     */
    public function saveRule(): void
    {
        if ($this->pricing === null) {
            return;
        }

        $rules = PricingValidation::fullPricingRuleRules();
        unset($rules['vendor_offering_pricing_id']);

        $validated = $this->validate([
            'form.name' => $rules['name'],
            'form.is_active' => $rules['is_active'],
            'form.priority' => $rules['priority'],
            'form.rule_type' => $rules['rule_type'],
            'form.adjustment_type' => $rules['adjustment_type'],
            'form.adjustment_value' => $rules['adjustment_value'],
            'form.override_price' => $rules['override_price'],
            'form.starts_at' => $rules['starts_at'],
            'form.ends_at' => $rules['ends_at'],
            'form.weekdays' => $rules['weekdays'],
            'form.weekdays.*' => $rules['weekdays.*'],
            'form.is_exclusive' => $rules['is_exclusive'],
            'form.conditions' => $rules['conditions'],
            'form.conditions.distance_km_min' => $rules['conditions.distance_km_min'],
            'form.conditions.distance_km_max' => $rules['conditions.distance_km_max'],
            'form.conditions.lead_days_min' => $rules['conditions.lead_days_min'],
            'form.conditions.lead_days_max' => $rules['conditions.lead_days_max'],
            'form.conditions.time_from' => $rules['conditions.time_from'],
            'form.conditions.time_to' => $rules['conditions.time_to'],
            'form.conditions.guests_min' => $rules['conditions.guests_min'],
            'form.conditions.guests_max' => $rules['conditions.guests_max'],
            'form.notes_internal' => $rules['notes_internal'],
        ]);

        if ($validated['form']['rule_type'] === 'OVERRIDE' && blank($validated['form']['override_price'])) {
            $this->addError('form.override_price', 'Inserisci il prezzo override per una regola di tipo override.');

            return;
        }

        if (
            in_array($validated['form']['rule_type'], ['SURCHARGE', 'DISCOUNT'], true)
            && blank($validated['form']['adjustment_type'])
        ) {
            $this->addError('form.adjustment_type', 'Seleziona il tipo valore per questa regola.');

            return;
        }

        if (
            in_array($validated['form']['rule_type'], ['SURCHARGE', 'DISCOUNT'], true)
            && blank($validated['form']['adjustment_value'])
        ) {
            $this->addError('form.adjustment_value', 'Inserisci il valore della modifica per questa regola.');

            return;
        }

        $payload = [
            'vendor_offering_pricing_id' => $this->pricing->id,
            'name' => $validated['form']['name'],
            'is_active' => $validated['form']['is_active'],
            'priority' => $validated['form']['priority'],
            'rule_type' => $validated['form']['rule_type'],
            'adjustment_type' => in_array($validated['form']['rule_type'], ['SURCHARGE', 'DISCOUNT'], true)
                ? $validated['form']['adjustment_type']
                : null,
            'adjustment_value' => in_array($validated['form']['rule_type'], ['SURCHARGE', 'DISCOUNT'], true)
                ? $validated['form']['adjustment_value']
                : null,
            'override_price' => $validated['form']['rule_type'] === 'OVERRIDE'
                ? $validated['form']['override_price']
                : null,
            'starts_at' => $validated['form']['starts_at'],
            'ends_at' => $validated['form']['ends_at'],
            'weekdays' => $validated['form']['weekdays'] ?? [],
            'is_exclusive' => $validated['form']['is_exclusive'],
            'conditions' => PricingConditions::sanitize($validated['form']['conditions'] ?? []),
            'notes_internal' => $validated['form']['notes_internal'],
        ];

        if ($this->editingRuleId === null) {
            $this->authorize('update', $this->pricing);

            $vendorOfferingPricingRule = VendorOfferingPricingRule::query()->create($payload);

            $this->dispatch('pricing-rule-created', ruleId: $vendorOfferingPricingRule->id);
        } else {
            $vendorOfferingPricingRule = VendorOfferingPricingRule::query()->findOrFail($this->editingRuleId);

            $this->authorize('update', $vendorOfferingPricingRule);

            $vendorOfferingPricingRule->update($payload);

            $this->dispatch('pricing-rule-updated', ruleId: $vendorOfferingPricingRule->id);
        }

        session()->flash('pricing_rules_success', 'Regola salvata correttamente.');

        $this->cancelEdit();
    }

    /**
     * Reagisce al cambio tipo regola, pulendo i campi non coerenti.
     */
    public function updatedFormRuleType($value): void
    {
        if ($value === 'OVERRIDE') {
            $this->form['adjustment_type'] = null;
            $this->form['adjustment_value'] = null;
        }

        if (in_array($value, ['SURCHARGE', 'DISCOUNT'], true)) {
            $this->form['override_price'] = null;
        }
    }

    /**
     * Attiva o disattiva rapidamente una regola.
     */
    public function toggleRule(int $ruleId): void
    {
        $vendorOfferingPricingRule = VendorOfferingPricingRule::query()->findOrFail($ruleId);

        $this->authorize('update', $vendorOfferingPricingRule);

        $vendorOfferingPricingRule->update([
            'is_active' => ! $vendorOfferingPricingRule->is_active,
        ]);

        $this->dispatch('pricing-rule-updated', ruleId: $vendorOfferingPricingRule->id);
    }

    /**
     * Elimina una regola.
     */
    public function deleteRule(int $ruleId): void
    {
        $vendorOfferingPricingRule = VendorOfferingPricingRule::query()->findOrFail($ruleId);

        $this->authorize('delete', $vendorOfferingPricingRule);

        $vendorOfferingPricingRule->delete();

        if ($this->editingRuleId === $ruleId) {
            $this->cancelEdit();
        }

        session()->flash('pricing_rules_success', 'Regola eliminata correttamente.');

        $this->dispatch('pricing-rule-deleted', ruleId: $ruleId);
    }

    /**
     * Valori di default del form.
     *
     * @return array<string, mixed>
     */
    protected function getDefaultForm(): array
    {
        return [
            'name' => null,
            'is_active' => true,
            'priority' => (($this->rules->max('priority') ?? 0) + 10),
            'rule_type' => 'SURCHARGE',
            'adjustment_type' => 'FIXED',
            'adjustment_value' => null,
            'override_price' => null,
            'starts_at' => null,
            'ends_at' => null,
            'weekdays' => [],
            'is_exclusive' => false,
            'conditions' => [],
            'notes_internal' => null,
        ];
    }

    /**
     * Opzioni utili per select e checkbox list.
     *
     * @return array<string, mixed>
     */
    public function getOptionsProperty(): array
    {
        return [
            'ruleTypes' => PricingOptions::ruleTypeOptions(),
            'adjustmentTypes' => PricingOptions::adjustmentTypeOptions(),
            'weekdays' => PricingWeekdays::options(),
        ];
    }

    /**
     * Indica se la regola corrente è di tipo override.
     */
    public function isOverrideRuleType(): bool
    {
        return ($this->form['rule_type'] ?? null) === 'OVERRIDE';
    }

    /**
     * Indica se la regola corrente usa adjustment classico
     * (maggiorazione o sconto).
     */
    public function usesAdjustmentFields(): bool
    {
        return in_array(($this->form['rule_type'] ?? null), ['SURCHARGE', 'DISCOUNT'], true);
    }

    /**
     * Restituisce una descrizione breve e leggibile del valore della regola.
     */
    public function ruleValueLabel(VendorOfferingPricingRule $vendorOfferingPricingRule): string
    {
        if ($vendorOfferingPricingRule->isOverrideRule()) {
            if ($vendorOfferingPricingRule->overridePriceValue() === null) {
                return 'Override senza importo';
            }

            return 'Override a ' . $this->formatMoney($vendorOfferingPricingRule->overridePriceValue());
        }

        if (
            ($vendorOfferingPricingRule->isSurchargeRule() || $vendorOfferingPricingRule->isDiscountRule())
            && $vendorOfferingPricingRule->adjustmentValue() !== null
        ) {
            $prefix = $vendorOfferingPricingRule->isSurchargeRule() ? '+' : '-';

            if ($vendorOfferingPricingRule->usesPercentAdjustment()) {
                return $prefix . $this->formatNumber($vendorOfferingPricingRule->adjustmentValue()) . '%';
            }

            return $prefix . $this->formatMoney($vendorOfferingPricingRule->adjustmentValue());
        }

        return 'Valore non definito';
    }

    /**
     * Restituisce una descrizione breve delle principali condizioni attive.
     */
    public function ruleConditionsLabel(VendorOfferingPricingRule $vendorOfferingPricingRule): string
    {
        $parts = [];

        if ($vendorOfferingPricingRule->hasDateRange()) {
            $from = $vendorOfferingPricingRule->starts_at?->format('d/m/Y');
            $to = $vendorOfferingPricingRule->ends_at?->format('d/m/Y');

            if ($from !== null && $to !== null) {
                $parts[] = 'Date: ' . $from . ' → ' . $to;
            } elseif ($from !== null) {
                $parts[] = 'Da: ' . $from;
            } elseif ($to !== null) {
                $parts[] = 'Fino al: ' . $to;
            }
        }

        if ($vendorOfferingPricingRule->hasWeekdayConstraint()) {
            $parts[] = 'Giorni selezionati';
        }

        if ($vendorOfferingPricingRule->hasDistanceCondition()) {
            $parts[] = 'Filtro distanza';
        }

        if ($vendorOfferingPricingRule->hasLeadDaysCondition()) {
            $parts[] = 'Filtro anticipo';
        }

        if ($vendorOfferingPricingRule->hasGuestsCondition()) {
            $parts[] = 'Filtro ospiti';
        }

        if ($vendorOfferingPricingRule->isExclusive()) {
            $parts[] = 'Esclusiva';
        }

        if ($parts === []) {
            return 'Nessuna condizione specifica';
        }

        return implode(' • ', $parts);
    }

    /**
     * Formatta un importo monetario in stile italiano.
     */
    private function formatMoney(float $amount): string
    {
        return number_format($amount, 2, ',', '.') . ' €';
    }

    /**
     * Formatta un numero eliminando decimali inutili.
     */
    private function formatNumber(float $value): string
    {
        if ((float) (int) $value === $value) {
            return (string) (int) $value;
        }

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    /**
     * Render del componente.
     */
    public function render(): View
    {
        return view('livewire.vendor.pricings.tabs.pricing-rules-tab');
    }
}