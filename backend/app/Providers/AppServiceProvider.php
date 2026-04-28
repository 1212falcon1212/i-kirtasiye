<?php

namespace App\Providers;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\HomepageSection;
use App\Models\Invoice;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Models\Review;
use App\Models\SellerDocument;
use App\Models\SellerWallet;
use App\Models\SupportTicket;
use App\Models\UserAddress;
use App\Models\UserNotification;
use App\Observers\BannerObserver;
use App\Observers\HomepageSectionObserver;
use App\Observers\OfferObserver;
use App\Policies\BrandPolicy;
use App\Policies\CampaignPolicy;
use App\Policies\CartPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\CouponPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\OfferPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ReturnRequestPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\SupportTicketPolicy;
use App\Policies\UserAddressPolicy;
use App\Policies\UserNotificationPolicy;
use App\Policies\WalletPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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
        // Force HTTPS in production
        if (config('app.env') === 'production' || str_starts_with(config('app.url'), 'https')) {
            URL::forceScheme('https');
        }

        $this->configurePolicies();
        $this->configureRateLimiting();

        // Register model observers for automatic cache invalidation
        Offer::observe(OfferObserver::class);
        Banner::observe(BannerObserver::class);
        HomepageSection::observe(HomepageSectionObserver::class);
    }

    /**
     * Configure the authorization policies for the application.
     */
    protected function configurePolicies(): void
    {
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Offer::class, OfferPolicy::class);
        Gate::policy(SellerDocument::class, DocumentPolicy::class);
        Gate::policy(SellerWallet::class, WalletPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(SupportTicket::class, SupportTicketPolicy::class);
        Gate::policy(ReturnRequest::class, ReturnRequestPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Cart::class, CartPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Brand::class, BrandPolicy::class);
        Gate::policy(Coupon::class, CouponPolicy::class);
        Gate::policy(Campaign::class, CampaignPolicy::class);
        Gate::policy(Review::class, ReviewPolicy::class);
        Gate::policy(UserAddress::class, UserAddressPolicy::class);
        Gate::policy(UserNotification::class, UserNotificationPolicy::class);
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Default API rate limit: 60 requests per minute
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Stricter limit for auth endpoints: 10 attempts per minute
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // File upload limit: 20 uploads per minute
        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        // Search/heavy operations: 30 per minute
        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // Payment callbacks: 30 per minute per IP (for payment gateway callbacks)
        RateLimiter::for('payment-callbacks', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        // Webhooks: 60 per minute per IP (for external service webhooks)
        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Payments: 30 per minute per user (payment operations)
        RateLimiter::for('payments', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // Order creation: 10 per minute per user (prevent bulk order spam)
        RateLimiter::for('orders', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Cart operations: 120 per minute per user (frequent +/- clicks)
        RateLimiter::for('cart', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // Payout requests: 5 per hour per user (financial operations)
        RateLimiter::for('payout-requests', function (Request $request) {
            return Limit::perHour(5)->by($request->user()?->id ?: $request->ip());
        });
    }
}
