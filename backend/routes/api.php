<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\DistributorRetailerLinkController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\FcmTokenController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\IntegrationController;
use App\Http\Controllers\Api\LabelController;
use App\Http\Controllers\Api\NotificationSettingController;
use App\Http\Controllers\Api\OfferController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ResetPasswordController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ShippingController;
use App\Http\Controllers\Api\SupportTicketController;
use App\Http\Controllers\Api\UserNotificationController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\WishlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| B2B Kırtasiye API Routes
| All routes are prefixed with /api
|
*/

// Public routes with auth rate limiting
Route::prefix('auth')->middleware('throttle:auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify-vergi-no', [AuthController::class, 'verifyVergiNo']);
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->middleware('throttle:5,1');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset']);
});

// Public location routes (city/district lookup)
Route::prefix('locations')->group(function () {
    Route::get('/cities', [\App\Http\Controllers\Api\LocationController::class, 'cities']);
    Route::get('/cities/{city}/districts', [\App\Http\Controllers\Api\LocationController::class, 'districts']);
});

// Payment callbacks (must be public for gateway callbacks)
Route::post('/payments/callback/{gateway}', [PaymentController::class, 'callback'])
    ->middleware('throttle:payment-callbacks');

// Transfer callback (public webhook from PayTR)
Route::post('/payments/transfer-callback', [PaymentController::class, 'transferCallback'])
    ->middleware('throttle:payment-callbacks');

// Public Campaign Routes - MUST be defined before auth middleware group
// to prevent {campaign} wildcard from matching "active"
Route::get('/campaigns/active', [CampaignController::class, 'active']);

// Protected routes (requires authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::put('/update-profile', [AuthController::class, 'updateProfile']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/deactivate-account', [AuthController::class, 'deactivateAccount']);
        Route::post('/fcm-token', [FcmTokenController::class, 'store']);
    });

    // Document routes
    Route::prefix('documents')->group(function () {
        Route::get('/', [DocumentController::class, 'index']);
        Route::post('/upload', [DocumentController::class, 'upload'])->middleware('throttle:uploads');
        Route::delete('/{document}', [DocumentController::class, 'destroy']);
        Route::get('/status', [DocumentController::class, 'status']);
    });

    // Offer routes (for sellers)
    Route::prefix('offers')->group(function () {
        Route::get('/', [OfferController::class, 'index']);
        Route::post('/', [OfferController::class, 'store']);
        Route::get('/{offer}', [OfferController::class, 'show']);
        Route::put('/{offer}', [OfferController::class, 'update']);
        Route::delete('/{offer}', [OfferController::class, 'destroy']);
        Route::post('/{offer}/toggle-status', [OfferController::class, 'toggleStatus']);
    });

    // My offers (seller's own offers)
    Route::get('/my-offers', [OfferController::class, 'myOffers']);

    // Seller offers (for sellers to view retailer offers after link approval)
    Route::get('/sellers/{id}/offers', [OfferController::class, 'getSellerOffers']);

    // Cart routes (higher rate limit for frequent +/- operations)
    Route::prefix('cart')->middleware('throttle:cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/items', [CartController::class, 'addItem']);
        Route::put('/items/{item}', [CartController::class, 'updateItem']);
        Route::delete('/items/{item}', [CartController::class, 'removeItem']);
        Route::post('/validate', [CartController::class, 'validate']);
        Route::delete('/', [CartController::class, 'clear']);
    });

    // Address routes
    Route::apiResource('user/addresses', \App\Http\Controllers\Api\UserAddressController::class);

    // Order routes
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/seller', [OrderController::class, 'sellerOrders']);
        Route::post('/', [OrderController::class, 'store'])->middleware('throttle:orders');
        Route::get('/{order}', [OrderController::class, 'show']);
        Route::put('/{order}/status', [OrderController::class, 'updateStatus']);
        Route::put('/{order}/cancel', [OrderController::class, 'cancel']);
        Route::put('/{order}/confirm-delivery', [OrderController::class, 'confirmDelivery']);
    });

    // Payment routes
    Route::prefix('payments')->group(function () {
        Route::get('/config', [PaymentController::class, 'config']);
        Route::post('/initialize', [PaymentController::class, 'initialize'])->middleware('throttle:payments');
        Route::get('/{order}/checkout', [PaymentController::class, 'checkout']);
        Route::post('/{order}/refund', [PaymentController::class, 'refund']);
        Route::post('/status-query', [PaymentController::class, 'statusQuery']);
        Route::post('/{order}/transfer', [PaymentController::class, 'transferToSellers']);
        Route::get('/returned-transfers', [PaymentController::class, 'returnedPayments']);
        Route::post('/process', [PaymentController::class, 'processDirectPayment'])->middleware('throttle:payments');
        Route::post('/bin-query', [PaymentController::class, 'binQuery']);
        Route::get('/installments', [PaymentController::class, 'installmentRates']);
        Route::get('/saved-cards', [PaymentController::class, 'savedCards']);
        Route::delete('/saved-cards/{ctoken}', [PaymentController::class, 'deleteSavedCard']);
    });

    // Wallet routes
    Route::prefix('wallet')->group(function () {
        Route::get('/', [WalletController::class, 'index']);
        Route::get('/transactions', [WalletController::class, 'transactions']);
        Route::get('/bank-accounts', [WalletController::class, 'bankAccounts']);
        Route::post('/bank-accounts', [WalletController::class, 'addBankAccount']);
        Route::put('/bank-accounts/{bankAccount}', [WalletController::class, 'updateBankAccount']);
        Route::delete('/bank-accounts/{bankAccount}', [WalletController::class, 'deleteBankAccount']);
        Route::post('/bank-accounts/{bankAccount}/default', [WalletController::class, 'setDefaultBankAccount']);
        Route::get('/payout-requests', [WalletController::class, 'payoutRequests']);
        Route::post('/payout-requests', [WalletController::class, 'createPayoutRequest'])
            ->middleware('throttle:payout-requests');
        Route::get('/settlements', [WalletController::class, 'settlements']);
        Route::get('/settlements/{date}/details', [WalletController::class, 'settlementDetails']);
        Route::get('/settlements/{date}/pdf', [WalletController::class, 'settlementPdf']);
    });

    // Shipping routes
    Route::prefix('shipping')->group(function () {
        Route::get('/config', [ShippingController::class, 'config']);
        Route::post('/calculate', [ShippingController::class, 'calculate']);
        Route::post('/options', [ShippingController::class, 'getOptions']);
        Route::post('/orders/{order}/shipment', [ShippingController::class, 'createShipment']);
        Route::get('/orders/{order}/track', [ShippingController::class, 'track']);
        Route::post('/orders/{order}/test-label', [ShippingController::class, 'generateTestLabel']);
        Route::get('/orders/{order}/label', [ShippingController::class, 'downloadLabel']);
        Route::get('/orders/{order}/shipping-detail', [ShippingController::class, 'shippingDetail']);
    });

    // Label routes
    Route::prefix('labels')->group(function () {
        Route::get('/orders/{order}', [LabelController::class, 'generate']);
        Route::post('/orders/{order}/shipment', [LabelController::class, 'createShipment']);
        Route::get('/orders/{order}/track', [LabelController::class, 'track']);
    });

    // Settings routes
    Route::prefix('settings')->group(function () {
        // Fee info (platform commission/tax rates)
        Route::get('/fee-info', function () {
            $feeService = app(\App\Services\FeeCalculationService::class);

            return response()->json($feeService->getRates());
        });

        // ERP Integrations (Updated URI to match Frontend)
        Route::prefix('integrations')->group(function () {
            Route::get('/', [IntegrationController::class, 'index']);
            Route::post('/', [IntegrationController::class, 'store']);
            Route::post('/{integration}/sync', [IntegrationController::class, 'sync']);
            Route::get('/{integration}/test', [IntegrationController::class, 'testConnection']);
            Route::delete('/{integration}', [IntegrationController::class, 'destroy']);

            // Order/Invoice ERP sync routes
            Route::post('/orders/{order}/sync', [IntegrationController::class, 'syncOrder']);
            Route::post('/orders/{order}/invoice', [IntegrationController::class, 'createInvoice']);

            // KolaySoft specific test endpoints
            Route::get('/kolaysoft/prefix-list', [IntegrationController::class, 'kolaysoftPrefixList']);
            Route::get('/kolaysoft/credit-count', [IntegrationController::class, 'kolaysoftCreditCount']);
        });

        // Notification Settings
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationSettingController::class, 'index']);
            Route::post('/', [NotificationSettingController::class, 'update']);
        });
    });

    // User Notification routes (in-app notifications)
    Route::prefix('notifications')->group(function () {
        Route::get('/', [UserNotificationController::class, 'index']);
        Route::get('/unread-count', [UserNotificationController::class, 'unreadCount']);
        Route::post('/{id}/read', [UserNotificationController::class, 'markAsRead']);
        Route::post('/read-all', [UserNotificationController::class, 'markAllAsRead']);
    });

    // Wishlist routes
    Route::prefix('wishlist')->group(function () {
        Route::get('/', [WishlistController::class, 'index']);
        Route::post('/toggle', [WishlistController::class, 'toggle']);
    });

    // Seller Dashboard routes
    Route::prefix('seller')->group(function () {
        Route::get('/stats', [\App\Http\Controllers\Api\SellerController::class, 'stats']);
        Route::get('/recent-orders', [\App\Http\Controllers\Api\SellerController::class, 'recentOrders']);
        Route::get('/products', [\App\Http\Controllers\Api\SellerController::class, 'products']);
        Route::get('/orders', [\App\Http\Controllers\Api\SellerController::class, 'orders']);
        Route::get('/orders/{order}', [\App\Http\Controllers\Api\SellerController::class, 'orderDetail']);

        // Satıcı bazlı kargo ayarları (override). NULL değer = global default kullanılır.
        Route::get('/shipping-settings', [\App\Http\Controllers\Api\SellerShippingSettingsController::class, 'show']);
        Route::patch('/shipping-settings', [\App\Http\Controllers\Api\SellerShippingSettingsController::class, 'update']);
    });

    // Invoice routes
    Route::prefix('invoices')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\InvoiceController::class, 'index']);
        Route::get('/commission-summary', [\App\Http\Controllers\Api\InvoiceController::class, 'commissionSummary']);
        Route::get('/{invoice}', [\App\Http\Controllers\Api\InvoiceController::class, 'show']);
        Route::get('/{invoice}/download', [\App\Http\Controllers\Api\InvoiceController::class, 'download']);
        Route::get('/orders/{order}/pdf', [\App\Http\Controllers\Api\InvoiceController::class, 'downloadPdf']);
        Route::post('/orders/{order}', [\App\Http\Controllers\Api\InvoiceController::class, 'createForOrder']);
        Route::post('/orders/{order}/erp', [\App\Http\Controllers\Api\InvoiceController::class, 'createForOrderViaErp']);
        Route::post('/orders/{order}/upload', [\App\Http\Controllers\Api\InvoiceController::class, 'uploadInvoiceFile']);
        Route::post('/{invoice}/sync-erp', [\App\Http\Controllers\Api\InvoiceController::class, 'syncToErp']);
    });

    // Legal & Compliance Routes
    Route::prefix('legal')->group(function () {
        Route::post('/approve', [\App\Http\Controllers\Api\LegalController::class, 'approveContract']);
        Route::get('/contract/b2b', [\App\Http\Controllers\Api\LegalController::class, 'generateSalesContract']);
    });

    // Per-order sales contract PDF
    Route::get('/orders/{orderId}/sales-contract', [\App\Http\Controllers\Api\LegalController::class, 'generateSalesContract']);

    // Contract routes (Uyelik Sozlesmesi)
    Route::get('/contracts/registration/download', [\App\Http\Controllers\Api\LegalController::class, 'downloadRegistrationContract']);
    Route::post('/contracts/registration/upload', [\App\Http\Controllers\Api\LegalController::class, 'uploadSignedContract']);

    // Distributor-Retailer Link Routes (Tedarikçi-Kırtasiyeci Bağlantısı)
    Route::prefix('distributor-retailer-links')->group(function () {
        // For sellers (distributors) - to send requests
        Route::get('/retailers', [DistributorRetailerLinkController::class, 'listRetailers']);
        Route::post('/request', [DistributorRetailerLinkController::class, 'sendRequest']);
        Route::get('/my-requests', [DistributorRetailerLinkController::class, 'mySentRequests']);
        Route::get('/approved-retailer-ids', [DistributorRetailerLinkController::class, 'approvedRetailerIds']);
        Route::delete('/request/{link}', [DistributorRetailerLinkController::class, 'cancelRequest']);

        // For retailers - to manage incoming requests
        Route::get('/incoming', [DistributorRetailerLinkController::class, 'myReceivedRequests']);
        Route::get('/pending-count', [DistributorRetailerLinkController::class, 'pendingCount']);
        Route::post('/{link}/approve', [DistributorRetailerLinkController::class, 'approveRequest']);
        Route::post('/{link}/reject', [DistributorRetailerLinkController::class, 'rejectRequest']);
        Route::delete('/{link}/revoke', [DistributorRetailerLinkController::class, 'revokeLink']);
    });

    // Campaign routes (for sellers)
    Route::prefix('campaigns')->group(function () {
        Route::get('/', [CampaignController::class, 'index']);
        Route::post('/', [CampaignController::class, 'store']);
        Route::get('/{campaign}', [CampaignController::class, 'show']);
        Route::put('/{campaign}', [CampaignController::class, 'update']);
        Route::delete('/{campaign}', [CampaignController::class, 'destroy']);
        Route::post('/{campaign}/toggle-status', [CampaignController::class, 'toggleStatus']);
    });

    // Coupon routes (for sellers)
    Route::prefix('coupons')->group(function () {
        Route::get('/', [CouponController::class, 'index']);
        Route::post('/', [CouponController::class, 'store']);
        Route::post('/apply', [CouponController::class, 'apply']);
        Route::post('/remove', [CouponController::class, 'remove']);
        Route::delete('/{coupon}', [CouponController::class, 'destroy']);
        Route::post('/{coupon}/toggle-status', [CouponController::class, 'toggleStatus']);
    });

    // Review routes
    Route::prefix('reviews')->group(function () {
        Route::post('/', [ReviewController::class, 'store']);
        Route::get('/seller', [ReviewController::class, 'sellerReviews']);
        Route::get('/my-reviews', [ReviewController::class, 'myReviews']);
        Route::get('/reviewable', [ReviewController::class, 'reviewableItems']);
        Route::post('/{review}/reply', [ReviewController::class, 'reply']);
    });

    // Support Ticket routes (Destek Talepleri)
    Route::prefix('support-tickets')->group(function () {
        Route::get('/', [SupportTicketController::class, 'index']);
        Route::post('/', [SupportTicketController::class, 'store']);
        Route::get('/{supportTicket}', [SupportTicketController::class, 'show']);
        Route::post('/{supportTicket}/messages', [SupportTicketController::class, 'addMessage']);
        Route::put('/{supportTicket}/close', [SupportTicketController::class, 'close']);
    });

    // Return Request routes (Iade Talepleri)
    Route::prefix('returns')->group(function () {
        Route::get('/reasons', [\App\Http\Controllers\Api\ReturnRequestController::class, 'reasons']);
        Route::get('/my-requests', [\App\Http\Controllers\Api\ReturnRequestController::class, 'myRequests']);
        Route::get('/seller-requests', [\App\Http\Controllers\Api\ReturnRequestController::class, 'sellerRequests']);
        Route::get('/order/{order}', [\App\Http\Controllers\Api\ReturnRequestController::class, 'orderRequests']);
        Route::post('/', [\App\Http\Controllers\Api\ReturnRequestController::class, 'store']);
        Route::post('/{returnRequest}/approve', [\App\Http\Controllers\Api\ReturnRequestController::class, 'approve']);
        Route::post('/{returnRequest}/reject', [\App\Http\Controllers\Api\ReturnRequestController::class, 'reject']);
    });
});

// Public Legal Routes
Route::get('/legal/items/{slug}', [\App\Http\Controllers\Api\LegalController::class, 'getDocument']);

// CMS Routes (Public for frontend consumption)
Route::prefix('cms')->middleware('cache.headers:600')->group(function () {
    Route::get('/layout', [\App\Http\Controllers\Api\CmsController::class, 'layout']);
    Route::get('/homepage', [\App\Http\Controllers\Api\CmsController::class, 'homepage']);
    Route::get('/banners/{location}', [\App\Http\Controllers\Api\CmsController::class, 'banners']);
    Route::get('/featured-sections', [\App\Http\Controllers\Api\CmsController::class, 'featuredSections']);
    Route::get('/random-products', [\App\Http\Controllers\Api\CmsController::class, 'randomProducts']);
    Route::get('/pages-by-group/{group}', [\App\Http\Controllers\Api\CmsController::class, 'pagesByGroup']);
    Route::get('/pages/{slug}', [\App\Http\Controllers\Api\CmsController::class, 'page']);
});

// Landing Page Content (Public)
Route::get('/landing-content', [\App\Http\Controllers\Api\CmsController::class, 'landingContent'])
    ->middleware('cache.headers:600');

// Brand Routes (Public for frontend consumption)
Route::prefix('brands')->middleware('cache.headers:600')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\BrandController::class, 'index']);
    Route::get('/featured', [\App\Http\Controllers\Api\BrandController::class, 'featured']);
    Route::get('/{slug}', [\App\Http\Controllers\Api\BrandController::class, 'show']);
});

// Category Routes (Public for frontend consumption)
Route::prefix('categories')->middleware('cache.headers:60')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/slug/{slug}', [CategoryController::class, 'showBySlug']);
    Route::get('/{category}', [CategoryController::class, 'show']);
});

// Blog Routes (Public for frontend consumption)
Route::prefix('blog')->group(function () {
    Route::get('/posts', [BlogController::class, 'index']);
    Route::get('/posts/random', [BlogController::class, 'random']);
    Route::get('/posts/{slug}', [BlogController::class, 'show']);
    Route::get('/categories', [BlogController::class, 'categories']);
});

// Product Routes (Public for frontend consumption)
Route::prefix('products')->middleware('cache.headers:300')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/search', [ProductController::class, 'search'])->middleware('throttle:search');
    Route::get('/{product}', [ProductController::class, 'show']);
    Route::get('/{product}/offers', [ProductController::class, 'offers']);
});

// Barcode decode from uploaded image (zbarimg + Tesseract OCR fallback)
Route::post('/barcode/decode', [\App\Http\Controllers\Api\BarcodeController::class, 'decode'])
    ->middleware('throttle:120,1');

// Public Review Routes
Route::get('/reviews/product/{productId}', [ReviewController::class, 'productReviews']);

// Health Check
Route::get('/health', [\App\Http\Controllers\Api\HealthCheckController::class, 'index']);

// Webhook routes with signature verification
Route::post('/webhooks/{provider}', [WebhookController::class, 'handle'])
    ->middleware(['webhook.verify', 'throttle:webhooks']);
