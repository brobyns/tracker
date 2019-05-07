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
    )
    {
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

    public function getDomainId($domain)
    {
        return $this->dataRepositoryManager->getDomainId($domain);
    }

    protected function getGeoIpId()
    {
        return $this->dataRepositoryManager->getGeoIpId($this->getClientIp());
    }

    protected function isProxy()
    {
        $client = new GuzzleHttp\Client();
        $response = $client->request('GET', 'http://www.shroomery.org/ythan/proxycheck.php', [
            'query' => ['ip' => $this->getClientIp()]
        ]);

        if ($response->getStatusCode() === 200) {
            $stringBody = (string)$response->getBody();
            return $stringBody === 'Y';
        }
        return false;
    }

    /**
     * @return array
     */
    protected function getLogData($image)
    {
        $clientIp = $this->getClientIp();
        $isUnique = $this->isIpUnique($image->user_id, $clientIp);

        return [
            'session_id' => $this->getSessionId(true),
            'image_id' => $image->id,
            'user_id' => $image->user_id,
            'referer_id' => $this->getRefererId(),
            'geoip_id' => $this->getGeoIpId(),
            'client_ip' => $clientIp,
            'is_adblock' => true,
            'is_real' => false,
            'is_proxy' => $this->isProxy(),
            'is_confirmed' => false,
            'is_unique' => $isUnique
        ];
    }

    protected function getRefererId()
    {
        $refererId = $this->dataRepositoryManager->getRefererId($this->request->headers->get('referer'));
        return ($refererId == null) ? 1 : $refererId;
    }

    /**
     * @return array
     */
    protected function makeSessionData()
    {
        $sessionData = [
            'user_id' => $this->request->user() ? $this->request->user() : null,
            'device_id' => $this->getDeviceId(),
            'client_ip' => $this->getClientIp(),
            'geoip_id' => $this->getGeoIpId(),
            'agent_id' => $this->getAgentId(),
            'referer_id' => $this->getRefererId(),
            'cookie_id' => $this->getCookieId(),
            'is_robot' => $this->isRobot(),

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

    public function getImageIdAndUserId($uuid)
    {
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

    public function pageViewsByRouteName($userid, $uniqueOnly)
    {
        return $this->dataRepositoryManager->pageViewsByRouteName($userid, $uniqueOnly);
    }

    public function referersForUser($userid, $startDate, $endDate)
    {
        return $this->dataRepositoryManager->referersForUser($userid, $startDate, $endDate);
    }

    public function countriesForUser($userid, $startDate, $endDate)
    {
        return $this->dataRepositoryManager->countriesForUser($userid, $startDate, $endDate);
    }

    public function tiersForUser($userid, $startDate, $endDate)
    {
        return $this->dataRepositoryManager->tiersForUser($userid, $startDate, $endDate);
    }

    public function statsForUser($userId, $startDate, $endDate)
    {
        return $this->dataRepositoryManager->statsForUser($userId, $startDate, $endDate);
    }

    public function viewsAndEarningsForUser($userid)
    {
        return $this->dataRepositoryManager->viewsAndEarningsForUser($userid);
    }

    public function isIpUnique($userid, $clientIp)
    {
        return $this->dataRepositoryManager->isIpUnique($userid, $clientIp);
    }

    public function getRateForGeoipId($geoipId)
    {
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
        $uuid = basename($this->request->path());
        $logId = null;
        $key = null;

        $image = $this->getImageIdAndUserId($uuid);

        if (!is_null($image)) {
            $log = $this->getLogData($image);
            $logId = $this->dataRepositoryManager->createLog($log);
            $key = $this->hashLogData($uuid, $logId);
        }

        return compact('logId', 'uuid', 'key');
    }

    public function getClientIp()
    {
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip); // just to be safe
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
    }

    public function confirmView(Request $request)
    {
        $logData = $this->parseObfuscatedLogData($request->get('log'));
        $clientHash = $logData['key'];
        $serverHash = $this->hashLogData($request->get('uuid'), $logData['logId']);

        $log = $this->dataRepositoryManager->getLogById($logData['logId']);
        $image = $this->dataRepositoryManager->getImage($log->image_id);
        if (!$log->is_confirmed && ($clientHash == $serverHash)) {

            $this->dataRepositoryManager->updateLog($log,
                ['is_adblock' => $logData['isAdblock'], 'is_real' => $logData['isReal'], 'is_confirmed' => true]);

            if ($log->is_unique && !$log->is_proxy && !$logData['isAdblock'] && $logData['isReal']) {
                $tier = $this->dataRepositoryManager->getTier($log->geoip_id);
                $this->dataRepositoryManager->updateStatsForImage($log->image_id, $log->user_id, $tier->id, $tier->rate);
                $this->dataRepositoryManager->updateBalanceForUser($log->user_id, $tier->rate);
            }
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

    private function hashLogData($uuid, $logId)
    {
        $secret = config('tracker.tracker_secret');
        $stringToHash = $uuid . $secret . $logId;
        return \hash('sha512', $stringToHash);
    }

    private function parseObfuscatedLogData($data)
    {
        $isReal = (int) $data[0] > 4;
        $isAdblock = (int) $data[129] > 5;
        $key = substr($data, 1, 128);
        $logId = substr($data, 130);

        return compact('isReal', 'isAdblock', 'key', 'logId');
    }
}
