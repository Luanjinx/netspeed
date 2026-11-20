<?php

namespace Tests\Feature;

use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Odps\Pages\CreateOdp;
use App\Filament\Resources\Odps\Pages\EditOdp;
use App\Models\Customer;
use App\Models\Odp;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentEditPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_and_odp_edit_pages_render_without_multiple_root_element_errors(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $customer = Customer::first();
        if ($customer) {
            Livewire::test(EditCustomer::class, ['record' => $customer->getKey()])
                ->assertSuccessful();
        }

        Livewire::test(CreateCustomer::class)
            ->assertSuccessful();

        $odp = Odp::first();
        if ($odp) {
            Livewire::test(EditOdp::class, ['record' => $odp->getKey()])
                ->assertSuccessful();
        }

        Livewire::test(CreateOdp::class)
            ->assertSuccessful();
    }
}
