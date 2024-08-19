<?php

namespace App\Providers;

use App\Mixins\CollectionAirportFilter;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Collection::mixin(new CollectionAirportFilter);

        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new MailMessage)
                ->subject('Verify Email Address')
                ->line('Thank you for registering an account with Where2Fly! To complete the registration process, please verify your email address by clicking the button below.')
                ->action('Verify Email Address', $url)
                ->line('If you did not create this account, no further action is required and your account will be deleted if not verified after a few days.');
        });
    }
}
