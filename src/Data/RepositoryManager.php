<?php

namespace PragmaRX\Tracker\Data;

use PragmaRX\Support\Config;
use PragmaRX\Support\GeoIp\GeoIp;
use PragmaRX\Tracker\Data\Repositories\Balance;
use PragmaRX\Tracker\Data\Repositories\Image;
use PragmaRX\Tracker\Data\Repositories\Stats;
use PragmaRX\Tracker\Data\Repositories\Tier;
use PragmaRX\Tracker\Support\MobileDetect;
use PragmaRX\Tracker\Data\Repositories\Log;
use PragmaRX\Tracker\Data\Repositories\Agent;
use PragmaRX\Tracker\Support\CrawlerDetector;
use PragmaRX\Tracker\Data\Repositories\Device;
use PragmaRX\Tracker\Data\Repositories\Cookie;
use PragmaRX\Tracker\Data\Repositories\Domain;
use PragmaRX\Tracker\Data\Repositories\Referer;
use PragmaRX\Tracker\Data\Repositories\Session;
use Illuminate\Routing\Router as IlluminateRouter;
use Illuminate\Session\Store as IlluminateSession;
use PragmaRX\Tracker\Data\Repositories\GeoIpRepository;

class RepositoryManager implements RepositoryManagerInterface
{
    /**
     * @var Domain
     */
    private $domainRepository;
    /**
     * @var Referer
     */
    private $refererRepository;
    /**
     * @var GeoIP
     */
    private $geoIp;

    private $geoIpRepository;

    private $userAgentParser;

    /**
     * @var CrawlerDetector
     */
    private $crawlerDetector;

    private $balanceRepository;

    private $statsRepository;

    private $tierRepository;

    private $imageRepository;

    public function __construct(
        GeoIp $geoIp,
        MobileDetect $mobileDetect,
        $userAgentParser,
        IlluminateSession $session,
        Config $config,
        Session $sessionRepository,
        Log $logRepository,
        Agent $agentRepository,
        Device $deviceRepository,
        Cookie $cookieRepository,
        Domain $domainRepository,
        Referer $refererRepository,
        GeoIpRepository $geoIpRepository,
        CrawlerDetector $crawlerDetector,
        Balance $balanceRepository,
        Stats $statsRepository,
        Tier $tierRepository,
        Image $imageRepository
    ) {

        $this->mobileDetect = $mobileDetect;

        $this->userAgentParser = $userAgentParser;

        $this->session = $session;

        $this->config = $config;

        $this->geoIp = $geoIp;

        $this->sessionRepository = $sessionRepository;

        $this->logRepository = $logRepository;

        $this->agentRepository = $agentRepository;

        $this->deviceRepository = $deviceRepository;

        $this->cookieRepository = $cookieRepository;

        $this->domainRepository = $domainRepository;

        $this->refererRepository = $refererRepository;

        $this->geoIpRepository = $geoIpRepository;

        $this->crawlerDetector = $crawlerDetector;

        $this->balanceRepository = $balanceRepository;

        $this->statsRepository = $statsRepository;

        $this->tierRepository = $tierRepository;

        $this->imageRepository = $imageRepository;

    }

    public function checkSessionData($newData, $currentData) {
        if ($newData && $currentData && $newData !== $currentData) {
            $newData = $this->updateSessionData($newData);
        }

        return $newData;
    }

    public function createLog($data) {
        return $this->logRepository->createLog($data);
    }

    public function updateLog($model, $data) {
        return $this->logRepository->update($model, $data);
    }

    public function findOrCreateAgent($data) {
        return $this->agentRepository->findOrCreate($data, ['name']);
    }

    public function findOrCreateDevice($data) {
        return $this->deviceRepository->findOrCreate($data, ['kind', 'model', 'platform', 'platform_version']);
    }

    public function getTier($geoipId) {
        return $this->tierRepository->getTier($geoipId);
    }

    public function findOrCreateSession($data) {
        return $this->sessionRepository->findOrCreate($data, ['uuid']);
    }

    public function getAgentId() {
        return $this->findOrCreateAgent($this->getCurrentAgentArray());
    }

    public function getAllSessions() {
        return $this->sessionRepository->all();
    }

    public function getCookieId() {
        return $this->cookieRepository->getId();
    }

    public function getLogById($id) {
        return $this->logRepository->getLogById($id);
    }

    public function getCurrentAgentArray() {
        return [
            'name' => $this->getCurrentUserAgent()
                ?: 'Other',

            'browser' => $this->userAgentParser->userAgent->family,

            'browser_version' => $this->userAgentParser->getUserAgentVersion(),
        ];
    }

    public function getCurrentDeviceProperties() {
        if ($properties = $this->getDevice()) {
            $properties['platform'] = $this->getOperatingSystemFamily();

            $properties['platform_version'] = $this->getOperatingSystemVersion();
        }

        return $properties;
    }

    public function getCurrentUserAgent() {
        return $this->userAgentParser->originalUserAgent;
    }

    /**
     * @return array
     */
    private function getDevice() {
        try {
            return $this->mobileDetect->detectDevice();
        }
        catch (\Exception $e) {
            return null;
        }
    }

    public function getDomainId($domain) {
        return $this->domainRepository->findOrCreate(
            ['name' => $domain],
            ['name']
        );
    }

    public function getGeoIpId($clientIp) {
        $id = null;
        if ($geoIpData = $this->geoIp->searchAddr($clientIp)) {
            $id = $this->geoIpRepository->findOrCreate(
                $geoIpData,
                ['latitude', 'longitude']
            );
        }

        return $id;
    }

    public function getTierId($geoipId) {
       return $this->tierRepository->getId($geoipId);

    }

    public function getLastSessions($minutes, $results) {
        return $this->sessionRepository->last($minutes, $results);
    }

    /**
     * @return mixed
     */
    private function getOperatingSystemFamily() {
        try {
            return $this->userAgentParser->operatingSystem->family;
        }
        catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return mixed
     */
    private function getOperatingSystemVersion() {
        try {
            return $this->userAgentParser->getOperatingSystemVersion();
        }
        catch (\Exception $e) {
            return null;
        }
    }

    public function getRefererId($referer) {
        if ($referer) {
            $url = parse_url($referer);

            $parts = explode(".", $url['host']);

            $domain = array_pop($parts);

            if (sizeof($parts) > 0) {
                $domain = array_pop($parts) . "." . $domain;
            }

            $domain_id = $this->getDomainId($domain);

            return $this->refererRepository->store($referer, $url['host'], $domain_id);
        }
    }

    public function getSessionId($sessionInfo, $updateLastActivity) {
        return $this->sessionRepository->getCurrentId($sessionInfo, $updateLastActivity);
    }

    public function getImageIdAndUserId($uuid) {
        return $this->imageRepository->getImageIdAndUserId($uuid);
    }

    public function getSessionLog($uuid, $results = true) {
        $session = $this->sessionRepository->findByUuid($uuid);

        return $this->logRepository->bySession($session->id, $results);
    }

    public function isRobot() {
        return $this->crawlerDetector->isRobot();
    }

    public function pageViews($minutes, $uniqueOnly) {
        return $this->logRepository->pageViews($minutes, $uniqueOnly);
    }

    public function pageViewsByCountry($minutes, $results) {
        return $this->logRepository->pageViewsByCountry($minutes, $results);
    }

    public function pageViewsByRouteName($userid, $uniqueOnly) {
        return $this->logRepository->pageViewsByRouteName($userid, $uniqueOnly);
    }

    public function referersForUser($userid) {
        return $this->logRepository->referersForUser($userid);
    }

    public function countriesForUser($userid) {
        return $this->logRepository->countriesForUser($userid);
    }

    public function tiersForUser($userid) {
        return $this->logRepository->tiersForUser($userid);
    }

    public function statsForUser($userId) {
        return $this->statsRepository->statsForUser($userId);
    }

    public function viewsAndEarningsForUser($userid) {
        return $this->logRepository->viewsAndEarningsForUser($userid);
    }

    public function isIpUnique($userid, $clientIp) {
        return $this->logRepository->isIpUnique($userid, $clientIp);
    }

    public function getRateForGeoipId($geoipId) {
        return $this->geoIpRepository->getRateForGeoipId($geoipId);
    }

    public function setSessionData($data) {
        $this->sessionRepository->setSessionData($data);
    }

    public function updateSessionData($data) {
        return $this->sessionRepository->updateSessionData($data);
    }

    public function users($minutes, $results) {
        return $this->sessionRepository->users($minutes, $results);
    }

    public function updateBalanceForUser($userid, $amount) {
        $this->balanceRepository->updateBalanceForUser($userid, $amount);
    }

    public function updateStatsForImage($imageId, $userId, $tierId, $amount) {
        $this->statsRepository->updateStatsForImage($imageId, $userId, $tierId, $amount);
        $this->imageRepository->updateImageViewsAndEarnings($imageId, $amount);
    }

}
