<?php

namespace App\Providers;

use App\Helpers\AlertHelper;
use App\Helpers\MapHintHelper;
use App\Helpers\NavigationHelper;
use App\Mixins\CollectionAirportFilter;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Collection::mixin(new CollectionAirportFilter);

        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new MailMessage)
                ->subject('Verify Email Address')
                ->line('Thank you for registering an account with Where2Fly! To complete the registration process, please verify your email address by clicking the button below.')
                ->action('Verify Email Address', $url)
                ->line('If you did not create this account, no further action is required and your account will be deleted if not verified after a few days.');
        });

        // The same navigation, alerts and map hint the React shell gets as shared props
        View::composer(['layouts.menu', 'layouts.bottomnav', 'layouts.footer'], fn ($view) => $view->with('nav', NavigationHelper::build()));
        View::composer('layouts.alerts', fn ($view) => $view->with('alerts', AlertHelper::build()));
        View::composer('parts.map', fn ($view) => $view->with('mapHint', MapHintHelper::current()));
    }
}
