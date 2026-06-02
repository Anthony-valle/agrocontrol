<?php

namespace App\Providers;

use App\Models\Cultivo;
use App\Models\Lote;
use App\Models\Notificaciones;
use App\Policies\CultivoPolicy;
use App\Policies\LotePolicy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerRestoredVendorAutoload();
        $excelConfig = base_path('vendor/maatwebsite/excel/config/excel.php');
        if (is_file($excelConfig)) {
            $this->mergeConfigFrom($excelConfig, 'excel');
        }

        $this->app->bind(\Maatwebsite\Excel\Cache\CacheManager::class, function ($app) {
            return new \Maatwebsite\Excel\Cache\CacheManager($app);
        });

        $this->app->singleton(\Maatwebsite\Excel\Transactions\TransactionManager::class, function ($app) {
            return new \Maatwebsite\Excel\Transactions\TransactionManager($app);
        });

        $this->app->bind(\Maatwebsite\Excel\Transactions\TransactionHandler::class, function ($app) {
            return $app->make(\Maatwebsite\Excel\Transactions\TransactionManager::class)->driver();
        });

        $this->app->bind(\Maatwebsite\Excel\Files\TemporaryFileFactory::class, function () {
            return new \Maatwebsite\Excel\Files\TemporaryFileFactory(
                config('excel.temporary_files.local_path', config('excel.exports.temp_path', storage_path('framework/laravel-excel'))),
                config('excel.temporary_files.remote_disk')
            );
        });

        $this->app->bind(\Maatwebsite\Excel\Files\Filesystem::class, function ($app) {
            return new \Maatwebsite\Excel\Files\Filesystem($app->make('filesystem'));
        });

        $this->app->singleton('excel', function ($app) {
            return new \Maatwebsite\Excel\Excel(
                $app->make(\Maatwebsite\Excel\Writer::class),
                $app->make(\Maatwebsite\Excel\QueuedWriter::class),
                $app->make(\Maatwebsite\Excel\Reader::class),
                $app->make(\Maatwebsite\Excel\Files\Filesystem::class)
            );
        });

        $this->app->alias('excel', \Maatwebsite\Excel\Excel::class);
        $this->app->alias('excel', \Maatwebsite\Excel\Exporter::class);
        $this->app->alias('excel', \Maatwebsite\Excel\Importer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Cultivo::class, CultivoPolicy::class);
        Gate::policy(Lote::class, LotePolicy::class);

        view()->composer('shared.header', function ($view) {
            $view->with('notificacionesCampana', $this->loadBellNotifications());
        });
    }

    private function loadBellNotifications(): Collection
    {
        if (!Auth::check()) {
            return collect();
        }

        $user = Auth::user();
        $isSuperUser = $user && method_exists($user, 'isSuperUser') && $user->isSuperUser();
        $isCompra = $user && method_exists($user, 'hasRole') && $user->hasRole('compra');

        if (!$isSuperUser && !$isCompra) {
            return collect();
        }

        return Notificaciones::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->take(10)
            ->get();
    }

    private function registerRestoredVendorAutoload(): void
    {
        $vendorPath = base_path('vendor');
        $psr4Prefixes = [
            'Maatwebsite\\Excel\\' => $vendorPath . '/maatwebsite/excel/src',
            'PhpOffice\\PhpSpreadsheet\\' => $vendorPath . '/phpoffice/phpspreadsheet/src/PhpSpreadsheet',
            'Composer\\Semver\\' => $vendorPath . '/composer/semver/src',
            'Composer\\Pcre\\' => $vendorPath . '/composer/pcre/src',
            'ZipStream\\' => $vendorPath . '/maennchen/zipstream-php/src',
            'Matrix\\' => $vendorPath . '/markbaker/matrix/classes/src',
            'Complex\\' => $vendorPath . '/markbaker/complex/classes/src',
        ];

        $psr0Prefixes = [
            'HTMLPurifier' => $vendorPath . '/ezyang/htmlpurifier/library',
        ];

        spl_autoload_register(static function (string $class) use ($psr4Prefixes, $psr0Prefixes): bool {
            foreach ($psr4Prefixes as $prefix => $baseDir) {
                if (!str_starts_with($class, $prefix)) {
                    continue;
                }

                $relativeClass = substr($class, strlen($prefix));
                $file = $baseDir . '/' . str_replace('\\', '/', $relativeClass) . '.php';

                if (is_file($file)) {
                    require_once $file;
                    return true;
                }
            }

            foreach ($psr0Prefixes as $prefix => $baseDir) {
                if (!str_starts_with($class, $prefix)) {
                    continue;
                }

                $file = $baseDir . '/' . str_replace(['\\', '_'], '/', $class) . '.php';

                if (is_file($file)) {
                    require_once $file;
                    return true;
                }
            }

            return false;
        }, true, true);

        $htmlPurifierBootstrap = $vendorPath . '/ezyang/htmlpurifier/library/HTMLPurifier.composer.php';
        if (is_file($htmlPurifierBootstrap)) {
            require_once $htmlPurifierBootstrap;
        }
    }
}
