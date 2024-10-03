<?php

namespace App\Providers;

use App\Models\Category;
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
        // Chia sẻ dữ liệu cho nhiều view
        View::composer(['admin.layouts.sidebar', 'client.layouts.particals.navleft'], function ($view) {
            // Lấy danh sách các danh mục từ cơ sở dữ liệu
            $parentCategories  = Category::whereNull('parent_id')->get();

            // Chia sẻ dữ liệu 'categories' với view
            $view->with('parentCategories', $parentCategories );
        });
    }
}
