<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\WishlistController;
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/men', [ShopController::class, 'men'])->name('shop.men');
Route::get('/women', [ShopController::class, 'women'])->name('shop.women');
Route::get('/sale', [ShopController::class, 'sale'])->name('shop.sale');
Route::get('/new-arrivals', [ShopController::class, 'newArrivals'])->name('shop.new-arrivals');
Route::get('/trending', [ShopController::class, 'trending'])->name('shop.trending');
Route::get('/product/{slug}', [ShopController::class, 'product'])->name('shop.product');
Route::get('/search', [SearchController::class, 'index'])->name('shop.search');
Route::get('/search/suggestions', [SearchController::class, 'suggestions'])->name('shop.search.suggestions');
Route::get('/track-order', [\App\Http\Controllers\TrackOrderController::class, 'index'])->name('track.order');
Route::get('/contact', [\App\Http\Controllers\ContactController::class, 'index'])->name('contact');
Route::get('/health', fn() => response()->json(['status' => 'ok', 'timestamp' => now()->toIso8601String()]));

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/cart', [\App\Http\Controllers\CartController::class, 'index'])->name('cart.index');
Route::get('/cart/api/count', [CartController::class, 'getCartCount'])->name('api.cart.count');
Route::post('/cart/api/add', [CartController::class, 'addToCartAjax'])->name('api.cart.add');
Route::post('/wishlist/api/toggle', [WishlistController::class, 'toggleWishlistAjax'])->name('api.wishlist.toggle');
Route::post('/cart/add', [\App\Http\Controllers\CartController::class, 'add'])->name('cart.add');
Route::post('/cart/remove', [\App\Http\Controllers\CartController::class, 'remove'])->name('cart.remove');

Route::middleware('auth')->group(function () {
    Route::get('/wishlist', [\App\Http\Controllers\WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/add', [\App\Http\Controllers\WishlistController::class, 'add'])->name('wishlist.add');
    Route::post('/wishlist/remove', [\App\Http\Controllers\WishlistController::class, 'remove'])->name('wishlist.remove');

    Route::get('/checkout/address', [\App\Http\Controllers\CheckoutController::class, 'address'])->name('checkout.address');
    Route::post('/checkout/address', [\App\Http\Controllers\CheckoutController::class, 'processAddress'])->name('checkout.address.process');
    Route::get('/checkout/payment', [\App\Http\Controllers\CheckoutController::class, 'payment'])->name('checkout.payment');
    Route::post('/checkout/process', [\App\Http\Controllers\CheckoutController::class, 'processOrder'])->name('checkout.process');

    Route::get('/orders', [\App\Http\Controllers\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [\App\Http\Controllers\OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{item}/cancel', [\App\Http\Controllers\OrderController::class, 'cancelItem'])->name('orders.cancel');
    
    // Admin Routes (These were unprotected, move to admin group)
    // We'll move them to the new structure below
    
    Route::get('/account/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/account/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/account/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin Routes (Unified SSO)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'auth.admin'])->group(function () {
    Route::get('/', function() {
        return redirect()->route('admin.dashboard');
    });
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    
    // Orders (Migrated from earlier)
    Route::get('/orders', [\App\Http\Controllers\Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [\App\Http\Controllers\Admin\OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{id}/status', [\App\Http\Controllers\Admin\OrderController::class, 'updateStatus'])->name('orders.status');
    Route::post('/orders/{id}/cancel', [\App\Http\Controllers\Admin\OrderController::class, 'cancelOrder'])->name('orders.cancel');

    // Products
    Route::resource('/products', \App\Http\Controllers\Admin\ProductController::class)->except(['show', 'destroy']);
    Route::post('/products/{id}/toggle', [\App\Http\Controllers\Admin\ProductController::class, 'toggleStatus'])->name('products.toggle');

    // Categories
    Route::get('/categories', [\App\Http\Controllers\Admin\CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [\App\Http\Controllers\Admin\CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{id}', [\App\Http\Controllers\Admin\CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{id}', [\App\Http\Controllers\Admin\CategoryController::class, 'destroy'])->name('categories.destroy');
    Route::post('/categories/{id}/toggle', [\App\Http\Controllers\Admin\CategoryController::class, 'toggleStatus'])->name('categories.toggle');

    // Inventory
    Route::get('/inventory', [\App\Http\Controllers\Admin\InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory/{id}/adjust', [\App\Http\Controllers\Admin\InventoryController::class, 'adjust'])->name('inventory.adjust');

    // Returns
    Route::get('/returns', [\App\Http\Controllers\Admin\ReturnController::class, 'index'])->name('returns.index');
    Route::post('/returns/{id}/status', [\App\Http\Controllers\Admin\ReturnController::class, 'updateStatus'])->name('returns.status');

    // Exchanges
    Route::get('/exchanges', [\App\Http\Controllers\Admin\ExchangeController::class, 'index'])->name('exchanges.index');
    Route::post('/exchanges/{id}/status', [\App\Http\Controllers\Admin\ExchangeController::class, 'updateStatus'])->name('exchanges.status');

    // Refunds
    Route::get('/refunds', [\App\Http\Controllers\Admin\RefundController::class, 'index'])->name('refunds.index');
    Route::post('/refunds/{id}/status', [\App\Http\Controllers\Admin\RefundController::class, 'updateStatus'])->name('refunds.status');

    // Customers
    Route::get('/customers', [\App\Http\Controllers\Admin\CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{id}', [\App\Http\Controllers\Admin\CustomerController::class, 'show'])->name('customers.show');
    Route::post('/customers/{id}/status', [\App\Http\Controllers\Admin\CustomerController::class, 'toggleStatus'])->name('customers.status');

    // Reviews
    Route::get('/reviews', [\App\Http\Controllers\Admin\ReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews/{id}/status', [\App\Http\Controllers\Admin\ReviewController::class, 'updateStatus'])->name('reviews.status');
    Route::delete('/reviews/{id}', [\App\Http\Controllers\Admin\ReviewController::class, 'destroy'])->name('reviews.destroy');

    // Banners (Homepage Management)
    Route::get('/banners', [\App\Http\Controllers\Admin\BannerController::class, 'index'])->name('banners.index');
    Route::post('/banners', [\App\Http\Controllers\Admin\BannerController::class, 'store'])->name('banners.store');
    Route::put('/banners/{id}', [\App\Http\Controllers\Admin\BannerController::class, 'update'])->name('banners.update');
    Route::delete('/banners/{id}', [\App\Http\Controllers\Admin\BannerController::class, 'destroy'])->name('banners.destroy');
    Route::post('/banners/{id}/toggle', [\App\Http\Controllers\Admin\BannerController::class, 'toggleStatus'])->name('banners.toggle');

    // Audit Logs (Super Admin Only)
    Route::middleware('role.admin:Super Admin')->group(function () {
        Route::get('/audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-logs');
    });
});
require __DIR__.'/auth.php';
