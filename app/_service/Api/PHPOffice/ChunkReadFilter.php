<?php
namespace Gajija\service\Api\PHPOffice ;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ChunkReadFilter implements IReadFilter
{
	private $startRow = 0;
	private $endRow = 0;
	/* public function __construct($startRow, $endRow, $columns)
	{
		$this->startRow = $startRow;
		$this->endRow = $endRow;
		//$this->columns = $columns;
	} */
	/**
	 * Set the list of rows that we want to read.
	 *
	 * @param mixed $startRow
	 * @param mixed $chunkSize
	 */
	public function setRows($startRow, $chunkSize)
	{
		$this->startRow = $startRow;
		$this->endRow = $startRow + $chunkSize;
	}
	public function readCell($column, $row, $worksheetName = '')
	{
		//  Only read the heading row, and the rows that are configured in $this->_startRow and $this->_endRow
		if (($row == 1) || ($row >= $this->startRow && $row < $this->endRow)) {
		//if ( $row >= $this->startRow && $row < $this->endRow ) {
			/* if (in_array($column, $this->columns)) {
				return true;
			} */
			return true;
		}
		return false;
	}
}
