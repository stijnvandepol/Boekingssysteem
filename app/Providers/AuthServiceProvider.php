<?php

namespace App\Providers;

use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\Resource;
use App\Policies\AvailabilityBlockPolicy;
use App\Policies\BookingPolicy;
use App\Policies\ResourcePolicy;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Resource::class => ResourcePolicy::class,
        AvailabilityBlock::class => AvailabilityBlockPolicy::class,
        Booking::class => BookingPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('admin', function (User $user): bool {
            return $user->role === 'admin';
        });
    }
}
