<?php
namespace app\services;

class ParserWriter
{
	const RESULT_CSV_PATH = __DIR__ . '../web/csv/parsed.csv';

	public function resetFile()
	{
		$handle = fopen(self::RESULT_CSV_PATH, "w+");
		fclose($handle);
	}

	public function writeData($data)
	{
		$handle = fopen(self::RESULT_CSV_PATH, "a+");
		fputscsv($handle, $data);
		fclose($handle);	
	}
}