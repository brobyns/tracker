<?php

namespace PragmaRX\Tracker\Vendor\Laravel;

use PragmaRX\Tracker\Data\Repositories\Balance;
use PragmaRX\Tracker\Data\Repositories\Earnings;
use PragmaRX\Tracker\Data\Repositories\Image;
use PragmaRX\Tracker\Data\Repositories\Stats;
use PragmaRX\Tracker\Data\Repositories\Tier;
use PragmaRX\Tracker\Tracker;
use PragmaRX\Support\PhpSession;
use PragmaRX\Support\GeoIp\GeoIp;
use PragmaRX\Tracker\Support\MobileDetect;
use PragmaRX\Tracker\Data\Repositories\Log;
use PragmaRX\Tracker\Data\RepositoryManager;
use PragmaRX\Tracker\Support\CrawlerDetector;
use PragmaRX\Tracker\Support\UserAgentParser;
use PragmaRX\Tracker\Data\Repositories\Agent;
use PragmaRX\Tracker\Data\Repositories\Device;
use PragmaRX\Tracker\Data\Repositories\Cookie;
use PragmaRX\Tracker\Data\Repositories\Domain;
use PragmaRX\Tracker\Data\Repositories\Referer;
use PragmaRX\Tracker\Data\Repositories\Session;
use PragmaRX\Tracker\Data\Repositories\GeoIpRepository;
use PragmaRX\Support\ServiceProvider as PragmaRXServiceProvider;
use PragmaRX\Tracker\Vendor\Laravel\Artisan\Tables as TablesCommand;
use PragmaRX\Tracker\Vendor\Laravel\Artisan\UpdateGeoIp;
use PragmaRX\Tracker\Repositories\Message as MessageRepository;

class ServiceProvider extends PragmaRXServiceProvider
{

    protected $packageVendor = 'pragmarx';

    protected $packageName = 'tracker';

    protected $packageNameCapitalized = 'Tracker';

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    private $tracker;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        parent::register();

        $this->registerRepositories();

        $this->registerTracker();

        $this->registerTablesCommand();

        $this->registerUpdateGeoIpCommand();

        $this->registerMessageRepository();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('tracker');
    }

    /**
     * Takes all the components of Tracker and glues them
     * together to create Tracker.
     *
     * @return void
     */
    private function registerTracker()
    {
        $this->app->singleton('tracker', function ($app) {
            $app['tracker.loaded'] = true;

            return new Tracker(
                $app['tracker.config'],
                $app['tracker.repositories'],
                $app['request'],
                $app['router'],
                $app['log'],
                $app,
                $app['tracker.messages']
            );
        });
    }

    public function registerRepositories()
    {
        $this->app->singleton('tracker.repositories', function ($app) {
            try {
                $uaParser = new UserAgentParser($app->make('path.base'));
            } catch (\Exception $exception) {
                $uaParser = null;
            }

            $sessionModel = $this->instantiateModel('session_model');

            $logModel = $this->instantiateModel('log_model');

            $agentModel = $this->instantiateModel('agent_model');

            $deviceModel = $this->instantiateModel('device_model');

            $cookieModel = $this->instantiateModel('cookie_model');

            $domainModel = $this->instantiateModel('domain_model');

            $refererModel = $this->instantiateModel('referer_model');

            $refererSearchTermModel = $this->instantiateModel('referer_search_term_model');

            $geoipModel = $this->instantiateModel('geoip_model');

            $earningModel = $this->instantiateModel('earnings_model');

            $balanceModel = $this->instantiateModel('balance_model');

            $statsModel = $this->instantiateModel('stats_model');

            $tierModel = $this->instantiateModel('tier_model');

            $logRepository = new Log($logModel);

            $crawlerDetect = new CrawlerDetector(
                $app['request']->headers->all(),
                $app['request']->server('HTTP_USER_AGENT')
            );

            $imageModel = $this->instantiateModel('image_model');

            return new RepositoryManager(
                new Geoip($this->getConfig('geoip_database_path')),

                new MobileDetect,

                $uaParser,

                $app['session.store'],

                $app['tracker.config'],

                new Session($sessionModel,
                    $app['tracker.config'],
                    new PhpSession()),

                $logRepository,

                new Agent($agentModel),

                new Device($deviceModel),

                new Cookie($cookieModel,
                    $app['tracker.config'],
                    $app['request'],
                    $app['cookie']),

                new Domain($domainModel),

                new Referer(
                    $refererModel,
                    $refererSearchTermModel,
                    $this->getAppUrl(),
                    $app->make('PragmaRX\Tracker\Support\RefererParser')
                ),

                new GeoIpRepository($geoipModel),

                $crawlerDetect,

                new Earnings($earningModel),

                new Balance($balanceModel),

                new Stats($statsModel),

                new Tier($tierModel),

                new Image($imageModel)
            );
        });
    }

    protected function registerUpdateGeoIpCommand()
    {
        $this->app->singleton('tracker.updategeoip.command', function ($app) {
            return new UpdateGeoIp();
        });

        $this->commands('tracker.updategeoip.command');
    }

    /**
     * Register the message repository.
     */
    protected function registerMessageRepository()
    {
        $this->app->singleton('tracker.messages', function () {
            return new MessageRepository();
        });
    }

    private function registerTablesCommand()
    {
        $this->app->singleton('tracker.tables.command', function ($app) {
            return new TablesCommand();
        });

        $this->commands('tracker.tables.command');
    }

    private function instantiateModel($modelName)
    {
        $model = $this->getConfig($modelName);

        if (!$model) {
            $message = "Tracker: Model not found for '$modelName'.";

            $this->app['log']->error($message);

            throw new \Exception($message);
        }

        $model = new $model;

        $model->setConfig($this->app['tracker.config']);

        if ($connection = $this->getConfig('connection')) {
            $model->setConnection($connection);
        }

        return $model;
    }

    /**
     * Get the current package directory.
     *
     * @return string
     */
    public function getPackageDir()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..';
    }

    public function getTracker()
    {
        if (!$this->tracker) {
            $this->tracker = $this->app['tracker'];
        }

        return $this->tracker;
    }

    public function getRootDirectory()
    {
        return __DIR__ . '/../..';
    }

    private function getAppUrl()
    {
        return $this->app['request']->url();
    }

}
