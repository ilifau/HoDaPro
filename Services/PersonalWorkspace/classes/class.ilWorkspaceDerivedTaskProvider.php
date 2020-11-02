<?php
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Workspace derived task provider
 *
 * @author jesus.copado@fau.de
 */

class ilWorkspaceDerivedTaskProvider implements ilDerivedTaskProvider
{

	/**
	 * @inheritDoc
	 */
	public function getTasks(int $user_id): array
	{
		$tasks = [];

		$tasks[] = $this->task_service->derived()->factory()->task(
			"title",
			123,
			1234,
			1000
		);

		return $tasks;
	}

	/**
	 * @inheritDoc
	 */
	public function isActive(): bool
	{
		return true;
	}
}