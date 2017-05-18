<?php

namespace PragmaRX\Tracker\Data\Repositories;

class Log extends Repository
{
	private $currentLogId = null;

	public function bySession($sessionId, $results = true)
	{
		$query = $this
					->getModel()
					->where('session_id', $sessionId)->orderBy('updated_at', 'desc');

		if ($results)
		{
			return $query->get();
		}

		return $query;
	}

	public function getLogById($id) {
	    return $this->getModel()->where('id', $id)->first();
    }

	/**
	 * @return null
	 */
	public function getCurrentLogId()
	{
		return $this->currentLogId;
	}

	/**
	 * @param null $currentLogId
	 */
	public function setCurrentLogId($currentLogId)
	{
		$this->currentLogId = $currentLogId;
	}

	public function createLog($data)
	{
		$log = $this->create($data);

		$this->setCurrentLogId($log->id);

		return $this->getCurrentLogId();
	}

	public function pageViews($minutes, $results)
	{
		 return $this->getModel()->pageViews($minutes, $results);
	}

	public function pageViewsByCountry($minutes, $results)
	{
		 return $this->getModel()->pageViewsByCountry($minutes, $results);
	}

    public function pageViewsByRouteName($userid, $uniqueOnly)
    {
        return $this->getModel()->pageViewsByRouteName($userid, $uniqueOnly);
    }

	public function referersForUser($userid)
	{
		return $this->getModel()->referersForUser($userid);
	}

	public function countriesForUser($userid) {
		return $this->getModel()->countriesForUser($userid);
	}

	public function tiersForUser($userid) {
		return $this->getModel()->tiersForUser($userid);
	}

	public function viewsAndEarningsForUser($userid) {
		return $this->getModel()->viewsAndEarningsForUser($userid);
	}

	public function isIpUnique($userid, $clientIp) {
		return $this->getModel()->isIpUnique($userid, $clientIp);
	}

	public function delete()
	{
        $this->currentLogId = null;

		$this->getModel()->delete();
	}
}
