<?php
namespace FreePBX\modules\Dpviz;
use FreePBX\modules\Backup as Base;

class Restore Extends Base\RestoreBase
{
	public function runRestore()
	{
		$configs = $this->getConfigs();
		if (isset($configs['tables'])) {
			$this->importTables($configs['tables']);
		}
		if (isset($configs['kvstore'])) {
			$this->importKVStore($configs['kvstore']);
		}
	}

	public function processLegacy($pdo, $data, $tables, $unknownTables)
	{
		$this->restoreLegacyDatabase($pdo);
	}
}
