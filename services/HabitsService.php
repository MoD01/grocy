<?php

namespace Grocy\Services;

class HabitsService extends BaseService
{
	const HABIT_TYPE_MANUALLY = 'manually';
	const HABIT_TYPE_DYNAMIC_REGULAR = 'dynamic-regular';

	public function GetCurrent()
	{
		$sql = 'SELECT * from habits_current';
		return $this->DatabaseService->ExecuteDbQuery($sql)->fetchAll(\PDO::FETCH_OBJ);
	}

	public function GetHabitDetails(int $habitId)
	{
		if (!$this->HabitExists($habitId))
		{
			throw new \Exception('Habit does not exist');
		}

		$habit = $this->Database->habits($habitId);
		$habitTrackedCount = $this->Database->habits_log()->where('habit_id', $habitId)->count();
		$habitLastTrackedTime = $this->Database->habits_log()->where('habit_id', $habitId)->max('tracked_time');
		$nextExeuctionTime = $this->Database->habits_current()->where('habit_id', $habitId)->min('next_estimated_execution_time');
		
		$lastHabitLogRow =  $this->Database->habits_log()->where('habit_id = :1 AND tracked_time = :2', $habitId, $habitLastTrackedTime)->fetch();
		$lastDoneByUser = null;
		if ($lastHabitLogRow !== null && !empty($lastHabitLogRow))
		{
			$usersService = new UsersService();
			$users = $usersService->GetUsersAsDto();
			$lastDoneByUser = FindObjectInArrayByPropertyValue($users, 'id', $lastHabitLogRow->done_by_user_id);
		}

		return array(
			'habit' => $habit,
			'last_tracked' => $habitLastTrackedTime,
			'tracked_count' => $habitTrackedCount,
			'last_done_by' => $lastDoneByUser,
			'next_estimated_execution_time' => $nextExeuctionTime
		);
	}

	public function TrackHabit(int $habitId, string $trackedTime, $doneBy = GROCY_USER_ID)
	{
		if (!$this->HabitExists($habitId))
		{
			throw new \Exception('Habit does not exist');
		}

		$userRow = $this->Database->users()->where('id = :1', $doneBy)->fetch();
		if ($userRow === null)
		{
			throw new \Exception('User does not exist');
		}
		
		$logRow = $this->Database->habits_log()->createRow(array(
			'habit_id' => $habitId,
			'tracked_time' => $trackedTime,
			'done_by_user_id' => $doneBy
		));
		$logRow->save();

		return true;
	}

	private function HabitExists($habitId)
	{
		$habitRow = $this->Database->habits()->where('id = :1', $habitId)->fetch();
		return $habitRow !== null;
	}
}
