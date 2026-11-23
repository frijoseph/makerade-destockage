<?php

namespace App\Providers;

use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\OrderStatus;
use App\Support\Mail\MailSettings;
use App\Support\ShopSettings;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
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
        Carbon::setLocale(config('app.locale'));
        Paginator::defaultView('admin.partials.pagination');
        View::share('shop', new ShopSettings);

        RateLimiter::for('assistant', fn (Request $request): Limit => Limit::perMinute(max(1, (int) Setting::get('ai_rate_limit', 10)))->by($request->ip()));

        MailSettings::apply();

        ResetPassword::createUrlUsing(fn (User $user, string $token): string => route('password.reset', [
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ]));

        ResetPassword::toMailUsing(function (User $user, string $token): MailMessage {
            $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire');
            $url = route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()]);

            return (new MailMessage)
                ->subject('Réinitialisation de votre mot de passe — '.Setting::get('shop_name'))
                ->greeting('Bonjour '.explode(' ', $user->name)[0].',')
                ->line('Vous avez demandé à réinitialiser le mot de passe de votre compte '.Setting::get('shop_name').'.')
                ->action('Choisir un nouveau mot de passe', $url)
                ->line("Ce lien est valable {$minutes} minutes.")
                ->line('Si vous n’êtes pas à l’origine de cette demande, ignorez simplement cet e-mail : votre mot de passe reste inchangé.')
                ->salutation('À bientôt, l’équipe '.Setting::get('shop_name'));
        });

        View::composer('admin.layouts.app', function ($view): void {
            $view->with([
                'shopName' => Setting::get('shop_name'),
                'pendingOrders' => Order::query()->where('status', OrderStatus::Pending)->count(),
                'lowStockCount' => Product::query()->active()->lowStock()->count(),
                'unreadMessages' => ContactMessage::query()->whereNull('read_at')->count(),
            ]);
        });
    }
}
