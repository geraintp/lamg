<?php

namespace App\Providers;

use App\Mail\MailjetTransport;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Statamic\Facades\GlobalSet;
use Statamic\Fieldtypes\Section;
use Statamic\Statamic;

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
        // Statamic::script('app', 'cp');
        // Statamic::style('app', 'cp');

        Section::makeSelectableInForms();

        View::composer(['layout', 'errors/404'], function ($view) {
            if ($view['response_code'] == '404') {
                $entry = GlobalSet::find('configuration')->inCurrentSite()->error_404_entry;
                $view->with($entry->toAugmentedArray());
            }
        });

        app(MailManager::class)->extend('mailjet-api', function () {
            $key = config('services.mailjet.key');
            $secret = config('services.mailjet.secret');
    
            return new MailjetTransport($key, $secret);
        });
    }
}
