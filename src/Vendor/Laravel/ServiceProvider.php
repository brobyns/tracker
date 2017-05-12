<?php

namespace PragmaRX\Tracker\Vendor\Laravel;

use PragmaRX\Tracker\Data\Repositories\Balance;
use PragmaRX\Tracker\Data\Repositories\Earnings;
use PragmaRX\Tracker\Data\Repositories\Stats;
use PragmaRX\Tracker\Data\Repositories\Tier;
use PragmaRX\Tracker\Tracker;
use PragmaRX\Support\PhpSession;
use PragmaRX\Support\GeoIp\GeoIp;
use PragmaRX\Tracker\Support\MobileDetect;
use PragmaRX\Tracker\Data\Repositories\Log;
use PragmaRX\Tracker\Data\RepositoryManager;
use PragmaRX\Tracker\Data\Repositories\Path;
use PragmaRX\Tracker\Data\Repositories\Route;
use PragmaRX\Tracker\Services\Authentication;
use PragmaRX\Tracker\Support\CrawlerDetector;
use PragmaRX\Tracker\Support\UserAgentParser;
use PragmaRX\Tracker\Data\Repositories\Agent;
use PragmaRX\Tracker\Data\Repositories\Device;
use PragmaRX\Tracker\Data\Repositories\Cookie;
use PragmaRX\Tracker\Data\Repositories\Domain;
use PragmaRX\Tracker\Data\Repositories\Referer;
use PragmaRX\Tracker\Data\Repositories\Session;
use PragmaRX\Tracker\Data\Repositories\RoutePath;
use PragmaRX\Tracker\Data\Repositories\Connection;
use PragmaRX\Tracker\Data\Repositories\SystemClass;
use PragmaRX\Tracker\Data\Repositories\GeoIpRepository;
use PragmaRX\Support\ServiceProvider as PragmaRXServiceProvider;
use PragmaRX\Tracker\Vendor\Laravel\Artisan\Tables as TablesCommand;

class ServiceProvider extends PragmaRXServiceProvider {

	protected $packageVendor = 'pragmarx';

	protected $packageName = 'tracker';

	protected $packageNameCapitalized = 'Tracker';

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

	private $userChecked = false;

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

	    $this->registerAuthentication();

        $this->registerRepositories();

        $this->registerTracker();

        $this->registerTablesCommand();

        $this->registerExecutionCallback();

        $this->registerUserCheckCallback();

        $this->registerDatatables();

        $this->commands('tracker.tables.command');
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
        $this->app->singleton('tracker', function ($app)
        {
            $app['tracker.loaded'] = true;

            return new Tracker(
                                    $app['tracker.config'],
                                    $app['tracker.repositories'],
                                    $app['request'],
                                    $app['router'],
                                    $app['log'],
                                    $app
                                );
        });
    }

    public function registerRepositories()
    {
        $this->app->singleton('tracker.repositories', function ($app)
        {
            try
            {
                $uaParser = new UserAgentParser($app->make('path.base'));
            }
            catch (\Exception $exception)
            {
                $uaParser = null;
            }

            $sessionModel = $this->instantiateModel('session_model');

            $logModel = $this->instantiateModel('log_model');

            $agentModel = $this->instantiateModel('agent_model');

            $deviceModel = $this->instantiateModel('device_model');

            $cookieModel = $this->instantiateModel('cookie_model');

	        $pathModel = $this->instantiateModel('path_model');

	        $domainModel = $this->instantiateModel('domain_model');

	        $refererModel = $this->instantiateModel('referer_model');

	        $refererSearchTermModel = $this->instantiateModel('referer_search_term_model');

	        $routeModel = $this->instantiateModel('route_model');

	        $routePathModel = $this->instantiateModel('route_path_model');

	        $geoipModel = $this->instantiateModel('geoip_model');

	        $connectionModel = $this->instantiateModel('connection_model');

	        $systemClassModel = $this->instantiateModel('system_class_model');

			$earningModel = $this->instantiateModel('earnings_model');

			$balanceModel = $this->instantiateModel('balance_model');

			$statsModel = $this->instantiateModel('stats_model');

			$tierModel = $this->instantiateModel('tier_model');

	        $logRepository = new Log($logModel);

	        $connectionRepository = new Connection($connectionModel);

			$systemClassRepository = new SystemClass($systemClassModel);

	        $routeRepository = new Route(
		        $routeModel,
		        $app['tracker.config']
	        );

	        $crawlerDetect = new CrawlerDetector(
		        $app['request']->headers->all(),
		        $app['request']->server('HTTP_USER_AGENT')
	        );

	        return new RepositoryManager(
	            new Geoip(),

	            new MobileDetect,

	            $uaParser,

	            $app['tracker.authentication'],

	            $app['session.store'],

	            $app['tracker.config'],

                new Session($sessionModel,
                            $app['tracker.config'],
                            new PhpSession()),

                $logRepository,

                new Path($pathModel),

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

                $routeRepository,

                new RoutePath($routePathModel),

                new GeoIpRepository($geoipModel),

	            $connectionRepository,

	            $systemClassRepository,

		        $crawlerDetect,

				new Earnings($earningModel),

				new Balance($balanceModel),

				new Stats($statsModel),

				new Tier($tierModel)
            );
        });
    }

    public function registerAuthentication()
    {
        $this->app->singleton('tracker.authentication', function ($app)
        {
            return new Authentication($app['tracker.config'], $app);
        });
    }

	private function registerTablesCommand()
	{
        $this->app->singleton('tracker.tables.command', function ($app)
		{
			return new TablesCommand();
		});
	}

	private function registerExecutionCallback()
	{
		$me = $this;

		$this->app['events']->listen('Illuminate\Routing\Events\RouteMatched', function($event) use ($me)
		{
			$me->getTracker()->routerMatched($me->getConfig('log_routes'));
		});
	}

	private function instantiateModel($modelName)
	{
		$model = $this->getConfig($modelName);

		if ( ! $model)
		{
			$message = "Tracker: Model not found for '$modelName'.";

			$this->app['log']->error($message);

			throw new \Exception($message);
		}

        $model = new $model;

        $model->setConfig($this->app['tracker.config']);

        if ($connection = $this->getConfig('connection'))
        {
            $model->setConnection($connection);
        }

		return $model;
	}

	private function registerDatatables()
	{
		$this->registerServiceProvider('Bllim\Datatables\DatatablesServiceProvider');

		$this->registerServiceAlias('Datatable', 'Bllim\Datatables\Facade\Datatables');
	}

	/**
	 * Get the current package directory.
	 *
	 * @return string
	 */
	public function getPackageDir()
	{
		return __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..';
	}

	private function registerUserCheckCallback()
	{
		$me = $this;

		$this->app['events']->listen('router.before', function($object = null) use ($me)
		{
			if ($me->tracker &&
				! $me->userChecked &&
				$me->getConfig('log_users') &&
				$me->app->resolved($me->getConfig('authentication_ioc_binding'))
			)
			{
				$me->userChecked = $me->getTracker()->checkCurrentUser();
			}
		});
	}

	public function getTracker()
	{
		if ( ! $this->tracker)
		{
			$this->tracker = $this->app['tracker'];
		}

		return $this->tracker;
	}

	public function getRootDirectory()
	{
		return __DIR__.'/../..';
	}

	private function getAppUrl()
	{
		return $this->app['request']->url();
	}

}
