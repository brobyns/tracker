<?php

namespace PragmaRX\Tracker;

use Illuminate\Foundation\Application as Laravel;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface as Logger;
use Illuminate\Routing\Router;
use PragmaRX\Support\Config;
use PragmaRX\Tracker\Data\RepositoryManager as DataRepositoryManager;
use PragmaRX\Tracker\Support\Minutes;
use PragmaRX\Support\GeoIp\Updater as GeoIpUpdater;
use PragmaRX\Tracker\Repositories\Message as MessageRepository;
use GuzzleHttp;

class Tracker
{
    protected $config;

    /**
     * @var \Illuminate\Routing\Router
     */
    protected $route;

    protected $logger;
    /**
     * @var \Illuminate\Foundation\Application
     */
    protected $laravel;

    protected $sessionData;

    public function __construct(
        Config $config,
        DataRepositoryManager $dataRepositoryManager,
        Request $request,
        Router $route,
        Logger $logger,
        Laravel $laravel,
        MessageRepository $messageRepository
    ) {
        $this->config = $config;

        $this->dataRepositoryManager = $dataRepositoryManager;

        $this->request = $request;

        $this->route = $route;

        $this->logger = $logger;

        $this->laravel = $laravel;

        $this->messageRepository = $messageRepository;
    }

    public function boot()
    {
        return $this->track();
    }

    public function allSessions()
    {
        return $this->dataRepositoryManager->getAllSessions();
    }

    public function checkCurrentUser()
    {
        if (!$this->getSessionData()['user_id'] && $user_id = $this->getUserId()) {
            return true;
        }

        return false;
    }

    public function currentSession()
    {
        return $this->dataRepositoryManager->sessionRepository->getCurrent();
    }

    protected function deleteCurrentLog()
    {
        $this->dataRepositoryManager->logRepository->delete();
    }

    protected function getAgentId()
    {
        return $this->config->get('log_user_agents')
            ? $this->dataRepositoryManager->getAgentId()
            : null;
    }

    public function getConfig($key)
    {
        return $this->config->get($key);
    }

    public function getCookieId()
    {
        return $this->config->get('store_cookie_tracker')
            ? $this->dataRepositoryManager->getCookieId()
            : null;
    }

    public function getDeviceId()
    {
        return $this->config->get('log_devices')
            ? $this->dataRepositoryManager->findOrCreateDevice(
                $this->dataRepositoryManager->getCurrentDeviceProperties()
            )
            : null;
    }

    public function getDomainId($domain) {
        return $this->dataRepositoryManager->getDomainId($domain);
    }

    protected function getGeoIpId()
    {
        return $this->dataRepositoryManager->getGeoIpId($this->request->getClientIp());
    }

    protected function isProxy()
    {
        $client = new GuzzleHttp\Client();
        $response = $client->request('GET', 'http://www.shroomery.org/ythan/proxycheck.php', [
        'query' => ['ip' => $this->request->getClientIp()]
        ]);

        if($response->getStatusCode() === 200) {
            $stringBody = (string) $response->getBody();
            return $stringBody === 'Y';
        }
        return false;
    }

    /**
     * @return array
     */
    protected function getLogData()
    {
        $image = $this->getImageIdAndUserId();
        return [
            'session_id' => $this->getSessionId(true),
            'image_id'  => $image->id,
            'user_id'    => $image->user_id,
            'referer_id' => $this->getRefererId(),
            'geoip_id' => $this->getGeoIpId(),
            'is_adblock' => true,
            'is_real' => false,
            'is_proxy' => $this->isProxy(),
            'is_confirmed' => false
        ];
    }

    protected function getRefererId()
    {
        $refererId = $this->dataRepositoryManager->getRefererId($this->request->headers->get('referer'));
        return ($refererId == null)? 1 : $refererId;
    }

    /**
     * @return array
     */
    protected function makeSessionData()
    {
        $sessionData = [
            'user_id'    => $this->request->user() ? $this->request->user() : null,
            'device_id'  => $this->getDeviceId(),
            'client_ip'  => $this->request->getClientIp(),
            'geoip_id'   => $this->getGeoIpId(),
            'agent_id'   => $this->getAgentId(),
            'referer_id' => $this->getRefererId(),
            'cookie_id'  => $this->getCookieId(),
            'is_robot'   => $this->isRobot(),

            // The key user_agent is not present in the sessions table, but
            // it's internally used to check if the user agent changed
            // during a session.
            'user_agent' => $this->dataRepositoryManager->getCurrentUserAgent(),
        ];

        return $this->sessionData = $this->dataRepositoryManager->checkSessionData($sessionData, $this->sessionData);
    }

    public function getSessionId($updateLastActivity = false)
    {
        return $this->dataRepositoryManager->getSessionId(
            $this->makeSessionData(),
            $updateLastActivity
        );
    }

    public function getImageIdAndUserId() {
        $uuid = basename($this->request->path());
        return $this->dataRepositoryManager->getImageIdAndUserId($uuid);
    }

    public function isRobot()
    {
        return $this->dataRepositoryManager->isRobot();
    }

    protected function notRobotOrTrackable()
    {
        return
            !$this->isRobot() ||
            !$this->config->get('do_not_track_robots');
    }

    public function pageViews($minutes, $results = true)
    {
        return $this->dataRepositoryManager->pageViews(Minutes::make($minutes), $results);
    }

    public function pageViewsByCountry($minutes, $results = true)
    {
        return $this->dataRepositoryManager->pageViewsByCountry(Minutes::make($minutes), $results);
    }

    public function pageViewsByRouteName($userid, $uniqueOnly) {
        return $this->dataRepositoryManager->pageViewsByRouteName($userid, $uniqueOnly);
    }

    public function referersForUser($userid) {
        return $this->dataRepositoryManager->referersForUser($userid);
    }

    public function countriesForUser($userid) {
        return $this->dataRepositoryManager->countriesForUser($userid);
    }

    public function tiersForUser($userid) {
        return $this->dataRepositoryManager->tiersForUser($userid);
    }

    public function statsForUser($userId) {
        return $this->dataRepositoryManager->statsForUser($userId);
    }

    public function viewsAndEarningsForUser($userid) {
        return $this->dataRepositoryManager->viewsAndEarningsForUser($userid);
    }

    public function isIpUnique($userid, $clientIp) {
        return $this->dataRepositoryManager->isIpUnique($userid, $clientIp);
    }

    public function getRateForGeoipId($geoipId) {
        return $this->dataRepositoryManager->getRateForGeoipId($geoipId);
    }

    public function sessionLog($uuid, $results = true)
    {
        return $this->dataRepositoryManager->getSessionLog($uuid, $results);
    }

    public function sessions($minutes = 1440, $results = true)
    {
        return $this->dataRepositoryManager->getLastSessions(Minutes::make($minutes), $results);
    }

    public function onlineUsers($minutes = 3, $results = true)
    {
        return $this->sessions(3);
    }

    public function track()
    {
        $log = $this->getLogData();
        return $this->dataRepositoryManager->createLog($log);
    }

    public function confirmView(Request $request) {
        $log = $this->dataRepositoryManager->getLogById($request->get('log_id'));
        $image = $this->dataRepositoryManager->getImage($log->image_id);
        $clientIp = $this->request->getClientIp();

        if ($this->isIpUnique($log->user_id, $clientIp))
        {
            $tier = $this->dataRepositoryManager->getTier($log->geoip_id);
            $this->dataRepositoryManager->updateLog($log,
                        ['is_adblock' => $request->get('is_adblock'),
                        'is_real' => $request->get('is_real'),
                        'is_confirmed' => true]);

            $this->dataRepositoryManager->updateStatsForImage($log->image_id, $log->user_id, $tier->id, $tier->rate);
            $this->dataRepositoryManager->updateBalanceForUser($log->user_id, $tier->rate);
        }
        return $image;
    }

    public function userDevices($minutes, $user_id = null, $results = true)
    {
        return $this->dataRepositoryManager->userDevices(
            Minutes::make($minutes),
            $user_id,
            $results
        );
    }

    public function users($minutes, $results = true)
    {
        return $this->dataRepositoryManager->users(Minutes::make($minutes), $results);
    }

    /**
     * Get the messages.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getMessages()
    {
        return $this->messageRepository->getMessages();
    }
    /**
     * Update the GeoIp2 database.
     *
     * @return bool
     */
    public function updateGeoIp()
    {
        $updater = new GeoIpUpdater();
        $success = $updater->updateGeoIpFiles($this->config->get('geoip_database_path'));
        $this->messageRepository->addMessage($updater->getMessages());
        return $success;
    }
}
