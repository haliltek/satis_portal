<?php

namespace App\Providers;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
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
        // Subdirectory desteği için URL generator'ı yapılandır
        $basePath = '/b2b-gemas-project-main/bayi/public';
        
        // Request varsa, ondan bilgileri al
        if ($this->app->runningInConsole()) {
            // Console'dan çalışıyorsa, default değerleri kullan
            $rootUrl = 'http://localhost' . $basePath;
        } else {
            // Web'den çalışıyorsa, request'ten bilgileri al
            $request = $this->app->make('request');
            $protocol = $request->getScheme();
            $host = $request->getHost();
            $rootUrl = $protocol . '://' . $host . $basePath;
        }
        
        // URL generator'a root URL'i zorla
        URL::forceRootUrl($rootUrl);
        
        // Asset URL'ini de ayarla (asset() helper için)
        if (!$this->app->runningInConsole()) {
            // Asset URL'ini manuel olarak ayarla
            $assetUrl = $rootUrl;
            config(['app.asset_url' => $assetUrl]);
        }
        
        Blade::directive('money', function ($amount) {
            return "<?php echo number_format($amount, 2, ',', '.'); ?>";
        });
        Blade::directive('money2', function ($amount) {
            return "<?php echo number_format($amount, 2, ',', '.').'₺'; ?>";
        });
        Blade::directive('money3', function ($amount) {
            return "<?php echo number_format($amount, 2, ',', '.'); ?>";

        });
    }
}
