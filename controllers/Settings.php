<?php
namespace Roquie\Multisite\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use System\Classes\SettingsManager;
use Roquie\Multisite\Models\Setting;
use Cache;
use Flash;
use Lang;

/**
 * Settings Back-end Controller
 */
class Settings extends Controller
{
    /**
     * @var array
     */
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    /**
     * @var string
     */
    public $formConfig = 'config_form.yaml';

    /**
     * @var string
     */
    public $listConfig = 'config_list.yaml';

    /**
     * Settings constructor.
     */
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('Roquie.Multisite', 'multisite');
    }

    public function onDelete()
    {
        $selected = post('checked');
        Setting::destroy($selected);

        return $this->listRefresh();
    }

    public function onClearCache()
    {
        Cache::forget('roquie_multisite_settings');
        Flash::success(Lang::get('roquie.multisite::lang.flash.cache-clear'));
    }
}

