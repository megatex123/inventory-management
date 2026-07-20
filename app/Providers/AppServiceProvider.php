<?php

namespace App\Providers;

use App\Models\MenuItem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
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
       Schema::defaultStringLength(191);

       View::composer('welcome', function ($view) {
           $view->with('sidebarMenu', MenuItem::whereNull('parent_id')
               ->where('is_active', true)
               ->orderBy('sort_order')
               ->get());
       });
    }
}
