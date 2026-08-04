<?php

namespace Tests\Feature;

use App\Models\VendorAccount;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Services\CommissionResolver;

class CommissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_commission_rate_is_used()
    {
        $categoryExplicit = Category::create([
            'name' => 'Artisti',
            'slug' => 'artisti-e-performer',
            'commission_rate' => 12.00
        ]);

        $categoryDefault = Category::create([
            'name' => 'Animazione',
            'slug' => 'animazione-bambini',
            'commission_rate' => 20.00
        ]);

        $vendor1 = VendorAccount::create([
            'user_id' => \App\Models\User::factory()->create()->id,
            'company_name' => 'Vendor 1',
            'status' => 'active',
            'payment_model' => 'COMMISSION',
            'custom_commission_rate' => null,
            'category_id' => $categoryExplicit->id
        ]);

        $vendor2 = VendorAccount::create([
            'user_id' => \App\Models\User::factory()->create()->id,
            'company_name' => 'Vendor 2',
            'status' => 'active',
            'payment_model' => 'COMMISSION',
            'custom_commission_rate' => null,
            'category_id' => $categoryDefault->id
        ]);

        $vendor3 = VendorAccount::create([
            'user_id' => \App\Models\User::factory()->create()->id,
            'company_name' => 'Vendor 3',
            'status' => 'active',
            'payment_model' => 'COMMISSION',
            'custom_commission_rate' => 18.00,
            'category_id' => $categoryDefault->id
        ]);

        $resolver = new CommissionResolver();

        $res1 = $resolver->resolve($vendor1);
        $this->assertEquals(12.00, $res1['commission_rate']);

        $res2 = $resolver->resolve($vendor2);
        $this->assertEquals(20.00, $res2['commission_rate']);

        $res3 = $resolver->resolve($vendor3);
        $this->assertEquals(18.00, $res3['commission_rate']);
    }
}
