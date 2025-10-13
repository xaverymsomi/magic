<?php

namespace Modules\Report\Reports;

use Libs\Database;
use Modules\Report\Libs\ReportGenerator;

class GenerateGeneralReport
{

	protected Database $db;
	private int $report_type;
	protected string $from_date;
	protected string $to_date;
	protected int $filter_criteria;
	protected int $group_criteria;
	protected int $category;
	private string $title;
	protected int $filter_value;
	protected string $filter_name;
	private string $logo = '';

	public function init($data)
	{
		$data1 = $data;
		$this->db = new Database();

		$this->report_type = $data1['report_type'];
		$this->from_date = date('Y-m-d', strtotime($data1['from_date']));
		$this->to_date = date('Y-m-d', strtotime($data1['to_date']));
		$this->filter_criteria = $data1['filter_criteria'];
		$this->group_criteria = $data1['group_criteria'];
		$this->category = $data1['category'];
		$this->title = $data1['title'];
		$this->filter_value = $data1['filter_value'];
		$this->filter_name = $data1['filter_name'];
		$this->generateReport();
	}

	public function generateReport()
	{
		$array_data = [];
		$report = null;
		try {
			$report = new ReportGenerator();

			$array_data = $this->getGeneralReport();

			if (sizeof($array_data)) {
				$status = 200;
				$report->setArray_data($array_data);
				$this->drawPDF($array_data, $report);
			} else {
				$status = 100;
			}

			$array_data = json_decode(html_entity_decode(json_encode($array_data)), true);
			$pdf_name = strtolower(str_replace(' ', '_', $this->title)) . ".pdf";

			response([
				'status' => $status,
				'records' => $array_data,
				'pdf_name' => $pdf_name
			], true);

			// Ensure upload folder exists before saving
			$filename = "uploads/report/" . $pdf_name;
			mkdirIfNotExists(PUBLIC_PATH . "/uploads/report/");
			$report->Output($filename, 'F');
		} catch (\Exception $exc) {
			echo json_encode([
				'status' => 100,
				'records' => $report ?? 'Report instance not available',
				'error' => $exc->getMessage()
			]);
			echo $exc->getTraceAsString();
		}
	}

	private function getGeneralReport() : array
	{
		return [];
	}
	private function drawPDF($array_data, ReportGenerator $report)
	{
		$report->SetLineWidth(0.0025);
		$report->report_type($this->report_type);
		$report->setHeader(1);
		$report->SetFont('Arial', 'B', 9);
		$report->SetTextColor(106, 106, 106);
		$report->AddPage();
		$report->SetXY(60, 60);

		foreach ($array_data as $key => $value) {
			$report->Ln(5);
			$report->SetFont('Arial', 'B', 9);
			$report->SetTextColor(106, 106, 106);

			if (!empty($value)) {
				$report->ln();
				$report->writeLandscapeHeader($value[0], false);
				$report->SetFont('Arial', '', 7);
				$report->writeGeneralReportSection($value, false);
			}
		}
	}
}
