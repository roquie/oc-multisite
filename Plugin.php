<?php
namespace Roquie\Multisite;

use App;
use BackendAuth;
use Cache;
use Config;
use Event;
use Flash;
use Request;
use Roquie\Multisite\Models\Setting;
use System\Classes\PluginBase;

/**
 * Multisite Plugin Information File
 * Plugin icon is used with Creative Commons (CC BY 4.0) Licence
 * Icon author: http://pixelkit.com/
 */
class Plugin extends PluginBase
{

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'roquie.multisite::lang.details.title',
            'description' => 'roquie.multisite::lang.details.description',
            'author'      => 'Roquie',
            'icon'        => 'icon-cubes'
        ];
    }

    public function registerSettings()
    {
        return [
            'multisite' => [
                'label'       => 'roquie.multisite::lang.details.title',
                'description' => 'roquie.multisite::lang.details.description',
                'category'    => 'system::lang.system.categories.cms',
                'icon'        => 'icon-cubes',
                'url'         => Backend::url('roquie/multisite/settings'),
                'order'       => 500,
                'keywords'    => 'multisite domains themes'
            ]
        ];
    }

    public function boot()
    {
        $backendUri     = Config::get('cms.backendUri');
        $requestUrl     = Request::url();
        $currentHostUrl = Request::getHost();

        /*
         * Get domain to theme bindings from cache, if it's not there, load them from database,
         * save to cache and use for theme selection.
         */
        $binds = Cache::rememberForever('roquie_multisite_settings', function () {
            try {
                $cacheableRecords = Setting::generateCacheableRecords();
            } catch (\Illuminate\Database\QueryException $e) {
                if (BackendAuth::check()) {
                    Flash::error(trans('roquie.multisite:lang.flash.db-error'));
                }
                return null;
            }
            return $cacheableRecords;
        });

        /*
         * Oooops something went wrong, abort.
         */
        if ($binds === null) {
            return;
        }

        /*
         * Check if this request is in backend scope and is using domain,
         * that is protected from using backend
         */
        foreach ($binds as $domain => $bind) {
            if (preg_match('/\\' . $backendUri . '/', $requestUrl) && preg_match('/' . $currentHostUrl . '/i', $domain) && $bind['is_protected']) {
                return App::abort(401, 'Unauthorized.');
            }
        }

        /*
         * If current request is in backend scope, do not check cms themes
         * Allows for current theme changes in October Theme Selector
         */
        if (preg_match('/\\' . $backendUri . '/', $requestUrl)) {
            return;
        }

        /*
         * Listen for CMS activeTheme event, change theme according to binds
         * If there's no match, let CMS set active theme
         */
        Event::listen('cms.theme.getActiveTheme', function () use ($binds, $currentHostUrl) {
            foreach ($binds as $domain => $bind) {
                if ($currentHostUrl === parse_url($domain, PHP_URL_HOST)) {
                    Config::set('app.url', $domain);
                    return $bind['theme'];
                }
            }
        });
    }
}
