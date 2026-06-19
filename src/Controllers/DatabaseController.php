<?php

namespace TanemRahman\LaravelInstaller\Controllers;

use Illuminate\Routing\Controller;
use TanemRahman\LaravelInstaller\Helpers\DatabaseManager;
use App\Facades\ModuleFacade as Module;
use App\Models\AddOn;

class DatabaseController extends Controller
{
    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * @param DatabaseManager $databaseManager
     */
    public function __construct(DatabaseManager $databaseManager)
    {
        $this->databaseManager = $databaseManager;
    }

    /**
     * Migrate and seed the database.
     *
     * @return \Illuminate\View\View
     */
    public function database()
    {
        $response = $this->databaseManager->migrateAndSeed();
        $module_json =  Module::allModules();
        foreach ($module_json as $module) {
            $addon = AddOn::where('module',$module->name)->first();
            if(empty($addon))
            {
                $addon = new AddOn;
                $addon->module = $module->name;
                $addon->name = $module->alias;
                $addon->monthly_price = data_get($module, 'monthly_price', 0);
                $addon->yearly_price = data_get($module, 'yearly_price', 0);
                $addon->is_enable = 1;
                $addon->package_name = $module->package_name;
                $addon->save();
            }
        }

        if (count($module_json) > 0) {
            // Redirect to the FIRST available module's install screen instead of a hardcoded one,
            // so projects that don't ship a "LandingPage" module still get a valid first
            // "Add On Install Editor" step (no broken/empty screen).
            $first = collect($module_json)->first(function ($m) {
                return !empty($m) && !empty($m->name);
            });

            if ($first) {
                return redirect()->route('LaravelInstaller::default_module', ['module' => $first->name]);
            }
        }

        return redirect()->route('LaravelInstaller::final')->with(['message' => $response]);
    }
}
