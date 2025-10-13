<?php
namespace Modules\Report\Libs;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use Fpdf\Fpdf;


class ReportGenerator extends FPDF {

	protected $fromdate;
	protected $todate;
	private $array_data;
	private $report_title;
	protected $report_subtitle;
	private $status;
	protected $report_total;
	private $current_printing_date;
	private $current_printing_date_output;
	private $current_printing_nature;
	private $current_printing_nature_output;
	private $current_printing_status;
	private $current_printing_status_output;
	private $default_column_width;
	private $number_of_columns;
	private $report_type;
	private $header;
	protected $report_category;
	protected $filter_criteria;
	protected $filter_value;
	private $pagesizes;
	protected $widths;
	protected $aligns;

	function setHeader($header) {
		$this->header = $header;
	}

	function setArray_data($array_data) {
		$this->array_data = $array_data;
	}
	function report_type($type) {
		$this->report_type = $type;
	}


	function Header()
	{
		if ($this->header) {
			// Get page dimensions
			$pageWidth = $this->GetPageWidth();
			$pageHeight = $this->GetPageHeight();

			// Get the current orientation (portrait or landscape)
			$orientation = $this->CurOrientation;

			// Set logo size and position
			$image = "assets/images/smz_logo.png";
			$logoWidth = 35;  // Set your desired logo width
			$logoHeight = 30; // Set your desired logo height

			// Center the logo based on orientation
			if ($orientation == 'P') { // Portrait
				$centerX = ($pageWidth - $logoWidth) / 2;
				$this->SetY(8);
				$this->SetX($centerX);
			} else { // Landscape
				$centerX = ($pageWidth - $logoWidth) / 2;
				$this->SetY(15);
				$this->SetX($centerX);
			}

			// Display logo
			if (file_exists($image)) {
				$this->Image($image, $centerX, $this->GetY(), $logoWidth, $logoHeight);
			} else {
				$this->Cell($pageWidth, 5, '[Logo Missing]', 0, 0, 'C');
			}

			// Add space between logo and text
			$this->Ln(30); // Adjust this value to ensure there's enough space between the logo and the text

			// Set text color and font
			$this->SetTextColor(47, 135, 203);
			$this->SetFont('Arial', 'B', 12);

			// Add company name
			$this->Cell($pageWidth, 5, 'MABREX SYSTEM', 0, 0, 'C');
			$this->Ln(5); // Line break after company name

			// Call ReportHeader function (if needed for additional report info)

			$this->ReportHeader($pageWidth);
		} else {
			// If no header, just draw a bottom border
			$this->Cell(280, 0, '', 'B', 1, 'C', false);
		}
	}

	function Footer() {
		$this->SetTextColor(106, 106, 106);
		// Position at 1.5 cm from bottom
		$this->SetY(-15);
		// Arial italic 8
		$this->SetFont('Arial', 'I', 8);
		// Page number
		$this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}, Printed on ' . date('m-d-Y H:i:s'), 0, 0, 'C');
	}

	function reportHeader($pageWidth)
	{
		// Initialize filter value if it's not empty
		$by_value = '';
		if (!empty($this->filter_value)) {
			$by_value = ' BY ' . $this->filter_value;
		}

		// Set fonts and colors for header content
		$this->SetFont('Arial', '', 11);
		$this->SetFillColor(224, 224, 224); // Fill color for background
		$this->SetTextColor(85, 85, 85); // Text color
		$this->SetDrawColor(196, 196, 196); // Border color

		// Set title and print filter criteria
		$this->SetFont('', 'B');

		// Print the filter criteria
		$this->Cell(0, 10, $this->filter_criteria . $by_value, 0, 1, 'L', 0);

		// Format date range if provided
		$fromTimestamp = $this->fromdate !== null ? strtotime($this->fromdate) : null;
		$toTimestamp = $this->todate !== null ? strtotime($this->todate) : null;

		// Set default report title if none is provided
		if ($this->report_title == NULL) {
			$this->report_title = 'GENERAL REPORT ';
		}

		// Call the extracted method for adding the date range
		$this->extracted($pageWidth,$by_value, $fromTimestamp, $toTimestamp);

		// Set font for next part of report header
		$this->SetFont('Arial', '', 12);
		$this->Ln(5);
	}

	function writeTransactionReport($grouping = []) {
		$this->SetLineWidth(0.0025);
		$total_amount = 0;

		$this->writeTransactionTableHeader($this->array_data);

		$this->SetFont('Arial', '', 7.5);

		if (sizeof($grouping)) {
			$total_amount = $this->writeGroupedTransactionReport($grouping);
		} else {

			$this->report_total = $this->writeUngroupedTransactionReport($this->array_data);
		}


		$extra_width = 0;
		if ($this->number_of_columns > 5) {
			$extra_width = 20;
		}

	}

	public function writeLandscapeHeader($headers, $large = true) {
		$this->SetFillColor(224, 224, 224); //fill color
		$this->SetTextColor(106, 106, 106); // text color
		$this->SetDrawColor(196, 196, 196); //border color
		$this->SetFont('Arial', 'B', 8);

		if (!$large) {
			$this->SetFont('Arial', 'B', 7.5);
		}
		// Write headers
		$keys = array_keys($headers);
		$index = 0;
		$border = '';
		$new_line = 0;
		$this->number_of_columns = count($keys);
		$this->default_column_width = 190 / $this->number_of_columns;

		foreach ($keys as $key => $val) {
			$index++;

			if ($index == 1) {
				$border = 'LTRB';
			} else {
				$border = 'TRB';
			}
			if ($index == count($keys)) {
				$new_line = 1;
			}
			$this->Cell($this->default_column_width, 8, $val, $border, $new_line, 'C', true);
		}
	}

	private function writeTransactionTableHeader($headers, $large = true) {
		$this->SetFillColor(224, 224, 224); //fill color
		$this->SetTextColor(106, 106, 106); // text color
		$this->SetDrawColor(196, 196, 196); //border color
		$this->SetFont('Arial', 'B', 10);

		if (!$large) {
			$this->SetFont('Arial', 'B', 7.5);
		}
		// Write headers
		$keys = array_keys($headers);
		$index = 0;
		$border = '';
		$new_line = 0;
		$this->number_of_columns = count($keys);
		if (in_array($this->report_type, [10, 9, 7]) && $this->report_category == 0) {
			$this->default_column_width = 190 / $this->number_of_columns;
		} elseif ($this->number_of_columns > 5) {
			// Reserve 20 for the Description column
			$this->default_column_width = 170 / $this->number_of_columns;
		} else {
			$this->default_column_width = 190 / $this->number_of_columns;
		}

		foreach ($keys as $key => $val) {
			$index++;
			//$col_size = $default_col_size;
			if ($index == 1) {
				$border = 'LTRB';
			} else {
				$border = 'TRB';
			}
			if ($index == count($keys)) {
				$new_line = 1;
			}
			if (in_array($val, ['Amount'])) {
				$this->Cell($this->default_column_width, 8, $val . '(TZS)', $border, $new_line, 'C', true);
			} elseif (in_array($val, ['COUNCIL'])) {
				$this->Cell($this->default_column_width + 20, 8, $val, $border, $new_line, 'C', true);
			} elseif (in_array($val, ['UNPAID AMOUNT'])) {
				$this->Cell($this->default_column_width + 10, 8, $val, $border, $new_line, 'C', true);
			} elseif (in_array($val, ['UNPAID BILLS'])) {
				$this->Cell($this->default_column_width + 2, 8, $val, $border, $new_line, 'C', true);
			} elseif (in_array($val, ['PAID AMOUNT'])) {
				$this->Cell($this->default_column_width + 5, 8, $val, $border, $new_line, 'C', true);
			} elseif (in_array($val, ['ALL BILLS'])) {
				$this->Cell($this->default_column_width - 4, 8, $val, $border, $new_line, 'C', true);
			} elseif (in_array($val, ['DATE'])) {
				$this->Cell($this->default_column_width - 6, 8, $val, $border, $new_line, 'C', true);
			} else {
				$this->Cell($this->default_column_width, 8, $val, $border, $new_line, 'C', true);
			}
		}
	}

	private function writeUngroupedTransactionReport($data, $summarized = false) {
		$total_amount = [0, 0];
		foreach ($data as $array) {
			$index = 0;
			$border = '';
			$new_line = 0;
			$align = 'L';

			if ($this->current_printing_date != $array['Date']) {
				// Change the current date
				$this->current_printing_date = $array['Date'];
				$this->current_printing_date_output = $this->current_printing_date;
				$this->current_printing_nature = '';
				$this->current_printing_status = '';
			} else {
				// Leave the date as it is
				$this->current_printing_date_output = '';
			}
			if ($this->current_printing_nature != $array['Nature']) {
				$this->current_printing_nature = $array['Nature'];
				$this->current_printing_nature_output = $this->current_printing_nature;
				$this->current_printing_status = '';
			} else {
				$this->current_printing_nature_output = '';
			}

			if ($this->current_printing_status != $array['Status']) {
				$this->current_printing_status = $array['Status'];
				$this->current_printing_status_output = $this->current_printing_status;
			} else {
				$this->current_printing_status_output = '';
			}

			foreach ($array as $key => $val) {
				$index++;
				$output = $val;

				if ($index == 1) {
					$border = 'LR';
				} else {
					$border = 'RB';
				}
				if ($index == count($array)) {
					$new_line = 1;
				}
				if (is_numeric($val)) {
					$align = 'C';
				} else {
					$align = 'L';
				}

//                if ($key == 'Money In' || $key == 'Money Out') {
//                    $output = number_format($val, 2);
//                    $align = 'R';
//                }
				if ($key == 'Date') {
					if ($this->current_printing_date_output != '') {
						$output = date('d M Y', strtotime($val));
						if (!$summarized) {
							$border = 'LT';
						}
					} else {
						$output = $this->current_printing_date_output;
					}
				}
				if ($key == 'Nature') {
					$output = $this->current_printing_nature_output;
					if ($output == '') {
						$border = '';
					} else {
						$border = 'LT';
					}
				}
				if ($key == 'Status') {
					$output = $this->current_printing_status_output;
					if ($output == '') {
						$border = 'LR';
					} else {
						$border = 'LRT';
					}
				}
				if ($key == 'Description') {
					$this->Cell($this->default_column_width + 20, 6, $output, $border, $new_line, $align, false);
				} else {
					$this->Cell($this->default_column_width, 6, $output, $border, $new_line, $align, false);
				}
			}
//            $total_amount[0] += $array['Money In'];
//            $total_amount[1] += $array['Money Out'];
		}
		return $total_amount;
	}

	private function writeGroupedTransactionReport($groups) {
		// Data will always be grouped by date if any grouping option is selected
		$date_amount = [0, 0];
		$this->report_total = [0, 0];
		$dates = [];
		$transactions = $this->array_data[0];
		for ($i = 0; $i < count($transactions); $i++) {
			if (!in_array($transactions[$i]['Date'], $dates)) {
				$dates[] = $transactions[$i]['Date'];
			}
		}
		if (sizeof($dates)) {
			// Start grouping data based on the given date in the array
			foreach ($dates as $date) {
				$date_data = array_filter($transactions, function($elem) use($date) {
					return $elem['Date'] == $date;
				});
				// Check if the grouping criteria has more than 1 data
				if (count($groups) >= 2) {
					// The user has requested to also group data by nature
					$natures = [];
					foreach ($date_data as $data) {
						if (!in_array($data['Nature'], $natures)) {
							$natures[] = $data['Nature'];
						}
					}
					foreach ($natures as $nature) {
						$nature_data = array_filter($date_data, function($elem) use ($nature) {
							return $elem['Nature'] == $nature;
						});
						if (count($groups) == 3) {
							// The user has requested to also group data by status
							$nature_amount = [0, 0];
							$statuses = [];
							foreach ($nature_data as $n_data) {
								if (!in_array($n_data['Status'], $statuses)) {
									$statuses[] = $n_data['Status'];
								}
							}
							foreach ($statuses as $status) {
								$status_data = array_filter($nature_data, function($elem) use ($status) {
									return $elem['Status'] == $status;
								});
								$amount = $this->writeTransactionComponent($status_data, $status, 3);
								$nature_amount[0] += $amount[0];
								$nature_amount[1] += $amount[1];
							}
							$this->writeTransactionComponentSubtotal($nature, $nature_amount, 2);
						} else {
							// Write the nature sub-report
							$amount = $this->writeTransactionComponent($nature_data, $nature, 2);
							$date_amount[0] += $amount[0];
							$date_amount[1] += $amount[1];
						}
					}
					$this->writeTransactionComponentSubtotal(date('d M Y', strtotime($date)), $date_amount, 1);
				} else {
					// Write the daily sub-report
					$this->writeTransactionComponent($date_data, date('d M Y', strtotime($date)));
				}
			}
		}
	}

	function writeGeneralReport($array_data) {

		$this->SetLineWidth(0.0025);
		$this->report_type($this->report_type);

		$this->SetFont('Arial', 'B', 9);
		$this->SetTextColor(106, 106, 106);

		$this->AddPage();
		$this->SetXY(60,60);
		foreach ($array_data as $key => $value) {


			$heading = '';
			switch ($key) {
				case 'Applicant':
					$heading = "APPLICANT SUMMARY REPORT";
//                    $_SESSION['council'] == 0 ? $heading = "MERCHANTS REGISTRATION BY COUNCIL" : $heading = "MERCHANTS REGISTRATION";
					break;
				case 'Medical':
					$heading = 'MEDICAL SUMMARY REPORT';
//                    $_SESSION['council'] == 0 ? $heading = "COUNCIL" : 'TRANSACTIONS BY BUSINESS LOCATION';
					break;
				case 'Sid':
					$heading = 'SID SUMMARY REPORT';
					break;
				case 'Billing':
					$heading = 'BILLING SUMMARY REPORT';
					break;
				default:
					break;
			}

			$this->Ln(5);
			$this->SetFont('Arial', 'B', 9);
			$this->SetTextColor(106, 106, 106);

			if (sizeof($array_data[$key])) {

				if ($key == 'Applicant') {
					$this->Cell(0, 8, $heading, '', 0, 'C', false);

				} elseif($key == 'Medical') {
					$this->Cell(0, 8, $heading, '', 0, 'C', false);

				} elseif($key == 'Billing') {
					$this->Cell(0, 8, $heading, '', 0, 'C', false);
				} else{
					$cellText = ($key == 'Sid') ? $heading . ' TOTAL APPLICATION ' : $heading;
					$this->Cell(0, 8, $cellText, '', 0, 'C', false);
				}

				$this->ln();

				$this->writeLandscapeHeader($value[0], false);
				$this->SetFont('Arial', '', 7);

				$this-> writeGeneralReportSection($value, false);;
			}

		}

		// }
	}


	function writeTestReport() {
		$this->SetLineWidth(0.0025);
		$this->SetY(35);
		$this->SetFont('Arial', 'B', 9);
		$this->SetTextColor(59, 59, 59);
		foreach ($this->array_data as $key => $value) {
			$this->SetFont('Arial', '', 7);
			$this->ln();
			$this->writeClinicalReportSection($value, false);
			$this->ln();
			$this->writePatientReportSection($value, false);
			$this->ln();
			$this->writeSampleInformationReportSection($value, false);
			$this->ln();
			$this->writeOtherInformationReportSection($value, false);
		}

		// }
	}

	private function writeClinicalReportSection($data, $large = true) {
		$total_amount = [];
		$extra_size = 0;
		$cols_count = count(array_keys($data[0]));
		for ($i = 1; $i < $cols_count; $i++) {
			$total_amount[$i] = 0;
		}
//        $this->Cell($this->default_column_width, 8, $val, $border, $new_line, 'C', true);
		foreach ($data as $array) {
			print_r($array);
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(190, 10, 'A. Clinician Information', 'TLR');
			$this->SetFont('Arial', '', 10);
			$this->Ln(7);
			$this->Cell(12, 10, 'Name:', 'L');
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(90, 10, $array['Name'], 0, 0, 'L');
			$this->SetFont('Arial', '', 10);
			$this->Cell(15, 10, 'Position:');
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(40, 10, $array['Position'], 0, 0, 'L');
			$this->SetFont('Arial', '', 10);
			$this->Cell(20, 10, 'Signature:');
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(13, 10, $array['Signature'], 'R', 0, 'L');
			$this->Ln(7);
			$this->SetFont('Arial', '', 10);
			$this->Cell(30, 10, 'Contact Details:', 'L');
			$this->Cell(60, 10, '........................................................', 0, 0, 'L');
			$this->Cell(10, 10, 'Tel(Bus):');
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(40, 10, $array['Tel(Bus)'], 0, 0, 'L');
			$this->SetFont('Arial', '', 10);
			$this->Cell(20, 10, 'Cell:');
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(30, 10, '0777780006', 'R', 0, 'L');
			$this->Ln(7);
			$this->SetFont('Arial', '', 10);
			$this->Cell(27, 10, 'Health Facility:', 'LB');
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(50, 10, $array['Health Facility'], 'B', 0, 'L');
			$this->SetFont('Arial', '', 10);
			$this->Cell(15, 10, 'District:', 'B');
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(50, 10, $array['District'], 'B', 0, 'L');
			$this->SetFont('Arial', '', 10);
			$this->Cell(15, 10, 'Region:', 'B');
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(33, 10, $array['Region'], 'BR', 0, 'L');
			$this->Ln();
			$this->SetFont('Arial', '', 10);
		}
//        $this->SetTextColor(21, 67, 96);
//        $this->SetFont('Arial', 'B', 10);
//        if (!$large) {
//            $this->SetFont('Arial', 'B', 7.5);
//        }
//        $this->SetFillColor(169, 204, 227);
//        $this->Cell($this->default_column_width + $extra_size, 8, 'TOTAL', 'LTBR', 0, 'L', false);
//        foreach ($total_amount as $amount) {
//            $this->Cell($this->default_column_width, 8, number_format($amount), 'TRB', 0, 'R', false);
//        }
//        $this->Ln();
	}

	private function writePatientReportSection($data, $large = true) {
		$total_amount = [];
		$extra_size = 0;
		$cols_count = count(array_keys($data[0]));
		for ($i = 1; $i < $cols_count; $i++) {
			$total_amount[$i] = 0;
		}
//        $this->Cell($this->default_column_width, 8, $val, $border, $new_line, 'C', true);
		foreach ($data as $array) {
			print_r($array);
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(190, 10, 'B. Patient Information', 'TLR');
			$this->SetFont('Arial', '', 10);
			$this->Ln(7);
			$this->SetFont('Arial', '', 10);
			$this->Cell(47, 10, 'Patient identification number:', 'L');
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(143, 10, "RHO87907", 'R', 0, 'L');
			$this->Ln(8);
			$this->SetFont('Arial', '', 10);
			$this->Cell(12, 10, 'Name:', 'L');
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(90, 10, "Mercedes Shaw", 0, 0, 'L');
			$this->SetFont('Arial', '', 10);
			$this->Cell(15, 10, 'Age:');
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(40, 10, '32 (Years)', 0, 0, 'L');
			$this->SetFont('Arial', '', 10);
			$this->Cell(20, 10, 'Sex:');
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(13, 10, 'M', 'R', 0, 'L');
			$this->Ln(8);
			$this->SetFont('Arial', '', 10);
			$this->Cell(15, 10, 'Address:', 'L');
			$this->Cell(175, 10, '................................................................................................................................................................................', 'R', 0, 'L');
			$this->Ln(8);
			$this->SetFont('Arial', '', 10);
			$this->Cell(35, 10, 'Occupation of patient:', 'L');
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(80, 10, 'Civil Eng.', 0, 0, 'L');
			$this->SetFont('Arial', '', 10);
			$this->Cell(20, 10, 'Nationality:');
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(55, 10, 'Kenyan', 'R', 0, 'L');
			$this->Ln(8);
			$this->SetFont('Arial', '', 10);
			$this->Cell(190, 10, 'Clinical information/history', 'LR');
			$this->Ln(8);
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(30, 10, 'Asymptomatic', 'L', 0, 'L');
			$this->Cell(160, 10, 'Yes', 'R');
//            $this->Ln(8);
//            $this->Cell(160, 10, '................................................................................................................................................................', 'R');
//            $this->Ln(8);
//            $this->Cell(190, 10, '...............................................................................................................................................................................................', 'LR');
			$this->Ln(8);
			$this->SetFont('Arial', '', 10);
			$this->Cell(80, 10, 'Regional Laboratory Technologist Phone Number:', 'LB');
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(110, 10, '+254789624531', 'BR', 0, 'L');
//            $this->Cell(110, 10, '+254789624531....................................................................................', 'BR', 0, 'L');
			$this->Ln();
		}
//        $this->SetTextColor(21, 67, 96);
//        $this->SetFont('Arial', 'B', 10);
//        if (!$large) {
//            $this->SetFont('Arial', 'B', 7.5);
//        }
//        $this->SetFillColor(169, 204, 227);
//        $this->Cell($this->default_column_width + $extra_size, 8, 'TOTAL', 'LTBR', 0, 'L', false);
//        foreach ($total_amount as $amount) {
//            $this->Cell($this->default_column_width, 8, number_format($amount), 'TRB', 0, 'R', false);
//        }
//        $this->Ln();
	}

	private function writeSampleInformationReportSection($data, $large = true) {
		$total_amount = [];
		$extra_size = 0;
		$cols_count = count(array_keys($data[0]));
		for ($i = 1; $i < $cols_count; $i++) {
			$total_amount[$i] = 0;
		}
//        $this->Cell($this->default_column_width, 8, $val, $border, $new_line, 'C', true);
		foreach ($data as $array) {
			print_r($array);
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(190, 10, 'C. Sample Information', 'TLR');
			$this->SetFont('Arial', '', 10);
			$this->Ln(7);
			$this->Cell(30, 10, 'Anatomical Site:', 'L');
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(20, 10, "Mouth", 0, 0, 'L');
			$this->SetFont('Arial', '', 10);
			$this->Cell(35, 10, 'Nature of specimen:');
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(40, 10, 'Oropharyngeal swab', 0, 0, 'L');
			$this->SetFont('Arial', '', 10);
			$this->Cell(35, 10, 'Transport Medium:');
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(30, 10, "VTM", 'R', 0, 'L');
			$this->Ln(7);
			$this->SetFont('Arial', '', 10);
			$this->Cell(50, 10, 'Condition for transportation:', 'L');
			$this->Cell(40, 10, "4 C", 0, 0, 'L');
//            $this->Cell(60, 10, '........................................................', 0, 0, 'L');
			$this->Cell(10, 10, 'Tel(Bus):');
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(40, 10, $array['Tel(Bus)'], 0, 0, 'L');
			$this->SetFont('Arial', '', 10);
			$this->Cell(20, 10, 'Cell:');
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(30, 10, $array['Cell'], 'R', 0, 'L');
			$this->Ln(7);

			$this->SetFont('Arial', '', 10);
			$this->Cell(27, 10, 'Health Facility:', 'LB');
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(50, 10, $array['Health Facility'], 'B', 0, 'L');
			$this->SetFont('Arial', '', 10);
			$this->Cell(15, 10, 'District:', 'B');
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(50, 10, $array['District'], 'B', 0, 'L');
			$this->SetFont('Arial', '', 10);
			$this->Cell(15, 10, 'Region:', 'B');
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(33, 10, $array['Region'], 'BR', 0, 'L');
			$this->SetFont('Arial', '', 10);
			$this->Ln(7);
		}
//        $this->SetTextColor(21, 67, 96);
//        $this->SetFont('Arial', 'B', 10);
//        if (!$large) {
//            $this->SetFont('Arial', 'B', 7.5);
//        }
//        $this->SetFillColor(169, 204, 227);
//        $this->Cell($this->default_column_width + $extra_size, 8, 'TOTAL', 'LTBR', 0, 'L', false);
//        foreach ($total_amount as $amount) {
//            $this->Cell($this->default_column_width, 8, number_format($amount), 'TRB', 0, 'R', false);
//        }
//        $this->Ln();
	}

	private function writeOtherInformationReportSection($data, $large = true) {
		$total_amount = [];
		$extra_size = 0;
		$cols_count = count(array_keys($data[0]));
		for ($i = 1; $i < $cols_count; $i++) {
			$total_amount[$i] = 0;
		}
//        $this->Cell($this->default_column_width, 8, $val, $border, $new_line, 'C', true);
		foreach ($data as $array) {
			print_r($array);
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(190, 10, 'NHL-QATC Laboratory Use only', '', 0, 'C');
			$this->SetFont('Arial', '', 10);
			$this->Ln();
			$this->Cell(51, 10, 'Specimen laboratory number:', '');
			$this->Cell(20, 10, '...............', 0, 0, 'L');
			$this->Cell(48, 10, 'Date Received(dd/mm/yy):');
			$this->Cell(35, 10, '...............', 0, 0, 'L');
			$this->Cell(20, 10, 'Time:');
			$this->Cell(16, 10, '...........', '', 0, 'L');
			$this->Ln();
			$this->Cell(46, 10, 'Name of receiving officer:', '');
			$this->Cell(101, 10, '.........................................................................................', 0, 0, 'L');
			$this->Cell(15, 10, 'Signature:');
			$this->Cell(28, 10, '.......................', '', 0, 'L');
			$this->Ln();

			$this->Cell(40, 10, 'Condition upon receipt:', '');
			$this->Cell(60, 10, '.......................................................', '', 0, 'L');
			$this->Cell(48, 10, 'Transportation temperature:', '');
			$this->Cell(42, 10, '.....................................', '', 0, 'L');
			$this->Ln();
			$this->Cell(190, 20, 'Comments', 'LTBR', 0, 'T');
			$this->Ln();
			$this->ln(4);
			$this->Cell(25, 5, '', 'LTBR');
			$this->Cell(15, 5, '', 'LTBR', 0, 'L');
		}
//        $this->SetTextColor(21, 67, 96);
//        $this->SetFont('Arial', 'B', 10);
//        if (!$large) {
//            $this->SetFont('Arial', 'B', 7.5);
//        }
//        $this->SetFillColor(169, 204, 227);
//        $this->Cell($this->default_column_width + $extra_size, 8, 'TOTAL', 'LTBR', 0, 'L', false);
//        foreach ($total_amount as $amount) {
//            $this->Cell($this->default_column_width, 8, number_format($amount), 'TRB', 0, 'R', false);
//        }
//        $this->Ln();
	}

	public function writeGeneralReportSection($data, $large = true) {

		$total_amount = [];
		$extra_size = 0;
		$cols_count = count(array_keys($data[0]));
		for ($i = 1; $i < $cols_count; $i++) {
			$total_amount[$i] = 0;
		}
		foreach ($data as $array) {
			$index = 0;
			$border = '';
			$new_line = 0;
			$align = 'C';

			foreach ($array as $key => $val) {
				$index++;
				$output = $val;

				if ($index == 1) {
					$border = 'LRB';
				} else {
					$border = 'RB';
				}
				if ($index == count($array)) {
					$new_line = 1;
				}
				if (is_numeric($val)) {
					$align = 'C';
				} else {
					$align = 'C';
				}

				if ($key == 'Money In' || $key == 'Money Out' || $key == 'Difference') {
					$output = number_format($val);
					$align = 'R';
				}

				if ($key == 'Region' || $key == 'Servicee' || $key == 'Agent' || $key == 'Retailer') {
					$this->Cell($this->default_column_width + 2, 6, $output, $border, $new_line, $align, false);
					$extra_size = 2;
				} else {
					$this->Cell($this->default_column_width, 6, $output, $border, $new_line, $align, false);
				}
				if (is_numeric($val) && isset($total_amount[$index - 1])) {
					$total_amount[$index - 1] += $val;
				}

			}
		}
		$this->SetTextColor(21, 67, 96);
		$this->SetFont('Arial', 'B', 10);
		if (!$large) {
			$this->SetFont('Arial', 'B', 7.5);
		}
		$this->SetFillColor(169, 204, 227);
		$this->Cell($this->default_column_width + $extra_size, 8, 'TOTAL', 'LTBR', 0, 'C', false);
		foreach ($total_amount as $amount) {
			$this->Cell($this->default_column_width, 8, number_format($amount), 'TRB', 0, 'C', false);
		}
		$this->Ln();
	}

	function writeSubscriptionsReport($grouping = []) {
		$total_amount = [];
		$extra_size = 0;
		$headers = $this->array_data[0];
//        print_r($headers);
		$this->writeTransactionTableHeader($headers);
		$this->SetFont('Arial', '', 8);

		$cols_count = count(array_keys($headers));
		for ($i = 2; $i < $cols_count; $i++) {
			$total_amount[$i] = 0;
		}
		foreach ($this->array_data as $array) {
			$index = 0;
			$border = '';
			$new_line = 0;
			$align = 'L';
			$this->CheckPageBreak(6);
			foreach ($array as $key => $val) {
				$index++;
				$output = $val;

				if ($index == 1) {
					$border = 'LRB';
				} else {
					$border = 'RB';
				}
				if ($index == count($array)) {
					$new_line = 1;
				}
				if (is_numeric($val)) {
					$align = 'R';
				} else {
					$align = 'L';
				}

				if (in_array($key, ['Description', 'COMPLETE TRANSACTION', 'FAILED TRANSACTION', 'COMPLETE AMOUNT', 'FAILED AMOUNT'])) {
					$this->Cell($this->default_column_width + 10, 6, $output, $border, $new_line, $align, false);
					$this->pagesizes = $this->pagesizes + ($this->default_column_width + 10);
				} elseif ($key == 'Amount') {
					$this->Cell($this->default_column_width, 6, number_format($output, 2), $border, $new_line, $align, false);
					$this->pagesizes = $this->pagesizes + ($this->default_column_width);
				} elseif ($key == 'COUNCIL') {
					$this->Cell($this->default_column_width + 20, 6, $output, $border, $new_line, $align, false);
					$this->pagesizes = $this->pagesizes + ($this->default_column_width + 20);
				} elseif ($key === 'UNPAID AMOUNT') {
					$this->Cell($this->default_column_width + 10, 6, $output, $border, $new_line, $align, false);
					$this->pagesizes = $this->pagesizes + ($this->default_column_width + 10);
				} elseif ($key === 'UNPAID BILLS') {
					$this->Cell($this->default_column_width + 2, 6, $output, $border, $new_line, $align, false);
					$this->pagesizes = $this->pagesizes + ($this->default_column_width + 2);
				} elseif ($key === 'PAID AMOUNT') {
					$this->Cell($this->default_column_width + 5, 6, $output, $border, $new_line, $align, false);
					$this->pagesizes = $this->pagesizes + ($this->default_column_width + 5);
				} elseif ($key === 'ALL BILLS') {
					$this->Cell($this->default_column_width - 4, 6, $output, $border, $new_line, $align, false);
				} elseif ($key === 'DATE') {
					$this->Cell($this->default_column_width - 6, 6, $output, $border, $new_line, $align, false);
				} else {
					$this->Cell($this->default_column_width, 6, $output, $border, $new_line, $align, false);
					$this->pagesizes = $this->pagesizes + ($this->default_column_width);
				}
//                if (is_numeric($val)) {
//                    $total_amount[$index - 1] += $val;
//                }
//                                    $this->Ln();
			}
		}
		$this->SetTextColor(21, 67, 96);
		$this->SetFont('Arial', 'B', 10);

		$this->SetFillColor(169, 204, 227);
		//$this->Cell($this->default_column_width * 2 + $extra_size, 8,null, 'LTBR', 0, 'L', false);
//        foreach ($total_amount as $amount) {
//            $this->Cell($this->default_column_width, 8,number_format($amount), 'TRB', 0, 'R', false);
//        }
//        $this->Ln(20);
	}

	function writeLandscapeReport($grouping = []) {
		$total_amount = [];
		$extra_size = 0;
		$headers = $this->array_data[0];
//        print_r($headers);
//        $this->writeTransactionTableHeader($headers);
		$this->SetFillColor(224, 224, 224); //fill color
		$this->SetTextColor(106, 106, 106); // text color
		$this->SetDrawColor(196, 196, 196); //border color
		$this->SetFont('Arial', 'B', 10);
		// Write headers
		$keys = array_keys($headers);
		$index = 0;
		$border = '';
		$new_line = 0;
		$this->number_of_columns = count($keys);
		if ($this->number_of_columns > 5) {
			// Reserve 20 for the Description column
			$this->default_column_width = 170 / $this->number_of_columns;
		} else {
			$this->default_column_width = 190 / $this->number_of_columns;
		}

		foreach ($keys as $key => $val) {
			$index++;
			//$col_size = $default_col_size;
			if ($index == 1) {
				$border = 'LRB';
			} else {
				$border = 'LRB';
			}
			if ($index == count($keys)) {
				$new_line = 1;
			}
			if (in_array($val, ['INSTITUTION'])) {
				$this->Cell($this->default_column_width + 65, 8, $val, $border, $new_line, 'C', true);
			} elseif (in_array($val, ['Merchant', 'Name', 'Mobile', 'Council', 'TESTED BY', 'COMPLETE TRANSACTION'])) {
				$this->Cell($this->default_column_width + 30, 8, $val, $border, $new_line, 'C', true);
			} elseif (in_array($val, ['COMPLETE AMOUNT', 'FAILED AMOUNT', 'FAILED TRANSACTION', 'TOTAL TRANSACTION'])) {
				$this->Cell($this->default_column_width + 20, 8, $val, $border, $new_line, 'C', true);
			} elseif (in_array($val, ['PAYMENT REFERENCE', 'TEST CENTER'])) {
				$this->Cell($this->default_column_width + 25, 8, $val, $border, $new_line, 'C', true);
			} elseif (in_array($val, ['AGE'])) {
				$this->Cell($this->default_column_width - 3, 8, $val, $border, $new_line, 'C', true);
			} elseif (in_array($val, ['Branch', 'Date/Time', 'Reference', 'PAID DATE', 'REFERENCE', 'Licence Type', 'Registered Date', 'Acc Number', 'Payment Type', 'Control Number'])) {
				$this->Cell($this->default_column_width + 8, 8, $val, $border, $new_line, 'C', true);
			} elseif (in_array($val, ['CONTROL NUMBER', 'PAYMENT TIME', 'TEST TYPE'])) {
				$this->Cell($this->default_column_width + 15, 8, $val, $border, $new_line, 'C', true);
			} elseif (in_array($val, ['Amount'])) {
				$this->Cell($this->default_column_width + 10, 8, $val . '(TZS)', $border, $new_line, 'C', true);
			} else {
				$this->Cell($this->default_column_width + 2, 8, $val, $border, $new_line, 'C', true);
			}
		}
		$this->SetFont('Arial', '', 8);

		$cols_count = count(array_keys($headers));
		for ($i = 2; $i < $cols_count; $i++) {
			$total_amount[$i] = 0;
		}
		foreach ($this->array_data as $array) {

			$index = 0;
			$border = '';
			$new_line = 0;
			$align = 'L';

			foreach ($array as $key => $val) {
				$index++;
				$output = $val;

				if ($index == 1) {
					$border = 'LRB';
				} else {
					$border = 'RB';
				}
				if ($index == count($array)) {
					$new_line = 1;
				}
				if (is_numeric($val)) {
					$align = 'R';
				} else {
					$align = 'L';
				}

				if ($key == 'INSTITUTION') {
					$this->Cell($this->default_column_width + 65, 6, $output, $border, $new_line, $align, false);
					$extra_size = 20;
				} elseif (in_array($key, ['Merchant', 'Name', 'Council', 'TESTED BY', 'COMPLETE TRANSACTION'])) {
					if ($key == 'Mobile') {
						$output = DataView::formatPhoneNo($output);
					}
					$this->Cell($this->default_column_width + 30, 6, $output, $border, $new_line, $align, false);
//                    $extra_size = 20;
				} elseif (in_array($key, ['COMPLETE AMOUNT', 'FAILED AMOUNT', 'FAILED TRANSACTION', 'TOTAL TRANSACTION'])) {
					$this->Cell($this->default_column_width + 20, 6, $output, $border, $new_line, $align, false);
//                    $extra_size = 20;
				} elseif (in_array($key, ['PAYMENT REFERENCE', 'TEST CENTER'])) {
					$this->Cell($this->default_column_width + 25, 6, $output, $border, $new_line, $align, false);
//                    $extra_size = 20;
				} elseif (in_array($key, ['AGE'])) {
					$this->Cell($this->default_column_width - 3, 6, $output, $border, $new_line, $align, false);
//                    $extra_size = 20;
				} elseif (in_array($key, ['Branch', 'Date/Time', 'REFERENCE', 'PAID DATE', 'Licence Type', 'Registered Date', 'Acc Number', 'Payment Type', 'Control Number'])) {
					$this->Cell($this->default_column_width + 8, 6, $output, $border, $new_line, $align, false);
//                    $extra_size = 20;
				} elseif (in_array($key, ['CONTROL NUMBER', 'PAYMENT TIME', 'TEST TYPE'])) {
					$this->Cell($this->default_column_width + 15, 6, $output, $border, $new_line, $align, false);
//                    $extra_size = 20;
				} elseif (in_array($key, ['Reason'])) {
					$this->MultiCell($this->default_column_width + 10, 6, $output, $border, $new_line, $align, false);
//                    $extra_size = 20;
				} elseif (in_array($key, ['Amount'])) {
					$this->Cell($this->default_column_width + 10, 6, number_format($output, 2), $border, $new_line, $align, false);
				} else {
					$this->Cell($this->default_column_width + 2, 6, $output, $border, $new_line, $align, false);
				}
			}
		}
		$this->SetTextColor(21, 67, 96);
		$this->SetFont('Arial', 'B', 10);

		$this->SetFillColor(169, 204, 227);
	}

	function writeComplaintReport($grouping = []) {
		$total_amount = [];
		$extra_size = 0;
		$headers = $this->array_data[0];
//        print_r($headers);
//        $this->writeTransactionTableHeader($headers);
		$this->SetFillColor(224, 224, 224); //fill color
		$this->SetTextColor(106, 106, 106); // text color
		$this->SetDrawColor(196, 196, 196); //border color
		$this->SetFont('Arial', 'B', 10);
		// Write headers
		$keys = array_keys($headers);
		$index = 0;
		$border = '';
		$new_line = 0;
		$this->number_of_columns = count($keys);
		if ($this->number_of_columns > 5) {
			// Reserve 20 for the Description column
			$this->default_column_width = 170 / $this->number_of_columns;
		} else {
			$this->default_column_width = 190 / $this->number_of_columns;
		}

		foreach ($keys as $key => $val) {
			$index++;
			//$col_size = $default_col_size;
			if ($index == 1) {
				$border = 'LRB';
			} else {
				$border = 'LRB';
			}
			if ($index == count($keys)) {
				$new_line = 1;
			}
			if (in_array($val, ['INSTITUTION'])) {
				$this->Cell($this->default_column_width + 67, 8, $val, $border, $new_line, 'C', true);
			} else {
				$this->Cell($this->default_column_width + 7, 8, $val, $border, $new_line, 'C', true);
			}
		}
		$this->SetFont('Arial', '', 8);

		$cols_count = count(array_keys($headers));
		for ($i = 2; $i < $cols_count; $i++) {
			$total_amount[$i] = 0;
		}
		foreach ($this->array_data as $array) {

			$index = 0;
			$border = '';
			$new_line = 0;
			$align = 'L';
			$this->CheckPageBreak(6);
			foreach ($array as $key => $val) {
				$index++;
				$output = $val;

				if ($index == 1) {
					$border = 'LRB';
				} else {
					$border = 'RB';
				}
				if ($index == count($array)) {
					$new_line = 1;
				}
				if (is_numeric($val)) {
					$align = 'C';
				} else {
					$align = 'L';
				}

				if ($key == 'INSTITUTION') {
					$this->Cell($this->default_column_width + 67, 6, $output, $border, $new_line, $align, false);
					$extra_size = 20;
				} elseif ($key == 'DATE') {
					$this->Cell($this->default_column_width + 7, 6, $output, $border, $new_line, 'C', false);
				} else {
					$this->Cell($this->default_column_width + 7, 6, $output, $border, $new_line, $align, false);
				}
			}
		}
		$this->SetTextColor(21, 67, 96);
		$this->SetFont('Arial', 'B', 10);

		$this->SetFillColor(169, 204, 227);
	}

	function writeStakeholderReport() {
		$this->SetTextColor(17, 120, 100);
		$this->SetFont('Arial', 'B', 10);
		$this->Cell(0, 10, strtoupper($this->report_subtitle) . ' TRANSACTIONS DETAILS', '', 1, 'C', false);

		$extra_size = 0;
		$headers = $this->array_data[0];
		$this->writeTransactionTableHeader($headers);
		$this->SetFont('Arial', '', 8);

		foreach ($this->array_data as $array) {
			$index = 0;
			$border = '';
			$new_line = 0;
			$align = 'L';

			foreach ($array as $key => $val) {
				$index++;
				$output = $val;

				if ($index == 1) {
					$border = 'LRB';
				} else {
					$border = 'RB';
				}
				if ($index == count($array)) {
					$new_line = 1;
				}
				if (is_numeric($val)) {
					$align = 'R';
				} else {
					$align = 'L';
				}

				if ($key == 'Description') {
					$this->Cell($this->default_column_width + 20, 6, $output, $border, $new_line, $align, false);
					$extra_size = 20;
				} else {
					$this->Cell($this->default_column_width, 6, $output, $border, $new_line, $align, false);
				}
			}
		}
		if (sizeof($this->array_data) <= 2) {
			$this->SetTextColor(196, 50, 50);
			$this->SetFont('Arial', 'I', 10);
			$this->Ln();
			$this->Cell(0, 10, $this->report_subtitle . ' had no transaction activity during the selected preiod. Please try another date range.', '', 1, 'C', false);
		}
	}

	function writeStatementReport() {
		$this->SetFillColor(224, 224, 224); //fill color
		$this->SetTextColor(106, 106, 106); // text color
		$this->SetDrawColor(196, 196, 196); //border color
		$this->SetFont('Arial', 'B', 10);
		$balance = $this->array_data[0]['Balance'];
		if ($this->array_data[0]['Balance'] == '' || $this->array_data[0]['Balance'] == null) {
			$balance = 0;
		}
		$this->SetTextColor(242, 111, 33); // text color
		$this->Cell(100, 8, 'Opening Balance:', 'B', 0, 'L', false);
		$this->Cell(180, 8, number_format($balance, 2) . ' TZS', 'B', 1, 'R', false);
		$this->SetTextColor(106, 106, 106); // text color
		$this->Ln(5);
		$this->Cell(40, 8, 'Date', 'LTBR', 0, 'C', false);
		$this->Cell(15, 8, 'STAN', 'TBR', 0, 'C', false);
		$this->Cell(70, 8, 'Description', 'TBR', 0, 'C', false);
		$this->Cell(25, 8, 'Source Acc', 'TBR', 0, 'C', false);
		$this->Cell(30, 8, 'Destination Acc', 'TBR', 0, 'C', false);
		$this->Cell(15, 8, 'Type', 'TBR', 0, 'C', false);
		$this->Cell(32.5, 8, 'Amount', 'TBR', 0, 'C', false);
		$this->Cell(32.5, 8, 'Balance', 'TBR', 0, 'C', false);
		$this->Cell(20, 8, 'Status', 'TBR', 1, 'C', false);
		$this->SetFont('Arial', '', 9);

		foreach ($this->array_data as $row) {
			$this->Cell(40, 6, $row['Date'], 'LBR', 0, 'C', false);
			$this->Cell(15, 6, $row['STAN'], 'BR', 0, 'C', false);
			$this->Cell(70, 6, $row['Description'], 'BR', 0, 'L', false);
			$this->Cell(25, 6, $row['Source Acc'], 'BR', 0, 'C', false);
			$this->Cell(30, 6, $row['Destination Acc'], 'BR', 0, 'C', false);
			$this->Cell(15, 6, $row['Type'], 'BR', 0, 'C', false);
			if ($row['Type'] == 'Debit') {
				$this->SetTextColor(255, 0, 0); // text color
				$this->Cell(32.5, 6, '-' . number_format($row['Amount'], 2), 'BR', 0, 'R', false);
			} else {
				$this->SetTextColor(30, 157, 222); // text color
				$this->Cell(32.5, 6, '+' . number_format($row['Amount'], 2), 'BR', 0, 'R', false);
			}
			if (($_SESSION['council'] == 30000004) && (stripos($row['Description'], "MPESA") > 0 || stripos($row['Description'], "TIGO-PESA") > 0 )) {

				$balance = $row['Balance'];
			} else {

				if ($row['Type'] == 'Debit') {
					$balance = $row['Balance'];
				} else {
					$balance = $row['Balance'];
				}
			}
			$this->SetTextColor(106, 106, 106); // text color
			$this->Cell(32.5, 6, number_format($balance, 2), 'BR', 0, 'R', false);
			$this->Cell(20, 6, $row['Status'], 'BR', 1, 'C', false);
		}
		$this->SetFont('Arial', 'B', 10);
		$this->Ln(5);
		$this->SetTextColor(242, 111, 33); // text color
		$this->Cell(100, 8, 'Closing Balance:', 'B', 0, 'L', false);
		$this->Cell(180, 8, number_format($balance, 2) . ' TZS', 'B', 1, 'R', false);
	}

	function writeAuditTrailReport() {
		$this->SetFillColor(224, 224, 224); //fill color
		$this->SetTextColor(106, 106, 106); // text color
		$this->SetDrawColor(196, 196, 196); //border color
		$this->SetFont('Arial', 'B', 10);
		$this->Cell(37.5, 8, 'Date', 'LTBR', 0, 'C', false);
		$this->Cell(20, 8, 'Section', 'TBR', 0, 'C', false);
		$this->Cell(30, 8, 'Action', 'TBR', 0, 'C', false);
		$this->Cell(85, 8, 'Affected Record', 'TBR', 0, 'C', false);
		$this->Cell(25, 8, 'Added By', 'TBR', 0, 'C', false);
		$this->Cell(25, 8, 'Attended By', 'TBR', 0, 'C', false);
		$this->Cell(37.5, 8, 'Attended Date', 'TBR', 0, 'C', false);
		$this->Cell(20, 8, 'Status', 'TBR', 1, 'C', false);
		$this->SetFont('Arial', '', 9);

		foreach ($this->array_data as $row) {
			$this->Cell(37.5, 6, $row['Date'], 'LBR', 0, 'C', false);
			$this->Cell(20, 6, $row['Section'], 'BR', 0, 'C', false);
			$this->Cell(30, 6, $row['Action'], 'BR', 0, 'C', false);
			$this->Cell(85, 6, $row['Affected Record'], 'BR', 0, 'L', false);
			$this->Cell(25, 6, $row['Added By'], 'BR', 0, 'C', false);
			$this->Cell(25, 6, $row['Attended By'], 'BR', 0, 'C', false);
			$this->Cell(37.5, 6, $row['Attended Date'], 'BR', 0, 'C', false);
			$this->Cell(20, 6, $row['Status'], 'BR', 1, 'C', false);
		}
	}

	function writeCommissionReport() {
		$this->SetFillColor(224, 224, 224); //fill color
		$this->SetTextColor(106, 106, 106); // text color
		$this->SetDrawColor(196, 196, 196); //border color
		$this->SetFont('Arial', 'B', 10);
		$this->Cell(40, 8, 'Date', 'LTBR', 0, 'C', false);
		$this->Cell(80, 8, 'Description', 'TBR', 0, 'C', false);
		$this->Cell(35, 8, 'Service', 'TBR', 0, 'C', false);
//        $this->Cell(30, 8, 'Type', 'TBR', 0, 'C', false);
		$this->Cell(45, 8, 'Amount (TZS)', 'TBR', 0, 'C', false);
		$this->Cell(40, 8, 'Commission (TZS)', 'TBR', 0, 'C', false);
		$this->Cell(40, 8, 'BCX Commission', 'TBR', 1, 'C', false);
		$this->SetFont('Arial', '', 9);
		$total_commission = 0;
		$total_amount = 0;
		$total_bcx_commission = 0;
		foreach ($this->array_data as $row) {
			$this->Cell(40, 8, $row['Date'], 'LTBR', 0, 'C', false);
			$this->Cell(80, 8, $row['Description'], 'TBR', 0, 'L', false);
			$this->Cell(35, 8, $row['Service'], 'TBR', 0, 'C', false);
//            $this->Cell(35, 8, $row['Type'], 'TBR', 0, 'C', false);
			if ($row['Type'] == 'Debit') {
				$this->SetTextColor(178, 7, 5); // text color
				$this->Cell(45, 8, "-" . number_format($row['Amount'], 2), 'TBR', 0, 'R', false);
				$this->Cell(40, 8, "-" . number_format($row['Commission'], 2), 'TBR', 0, 'R', false);
				$this->Cell(40, 8, "-" . number_format($row['BCX Commission'], 2), 'TBR', 1, 'R', false);
				$this->SetTextColor(222, 6, 39); // text color
				$total_amount -= $row['Amount'];
				$total_commission -= $row['Commission'];
				$total_bcx_commission -= $row['BCX Commission'];
				$this->SetTextColor(106, 106, 106); // text color
			} else {
				$this->SetTextColor(5, 178, 178); // text color
				$this->Cell(45, 8, "+" . number_format($row['Amount'], 2), 'TBR', 0, 'R', false);
				$this->SetTextColor(10, 163, 16); // text color
				$this->Cell(40, 8, "+" . number_format($row['Commission'], 2), 'TBR', 0, 'R', false);
				$this->SetTextColor(40, 113, 161); // text color
				$this->Cell(40, 8, "+" . number_format($row['BCX Commission'], 2), 'TBR', 1, 'R', false);
				$this->SetTextColor(10, 163, 16); // text color
				$total_amount += $row['Amount'];
				$total_commission += $row['Commission'];
				$total_bcx_commission += $row['BCX Commission'];
				$this->SetTextColor(106, 106, 106); // text color
			}
		}
		$this->SetFont('Arial', 'B', 10);
		$this->Cell(155, 8, 'TOTAL', 'LTBR', 0, 'L', false);
		$this->SetTextColor(5, 178, 178); // text color
		$this->Cell(45, 8, number_format($total_amount, 2), 'TBR', 0, 'R', false);
		$this->SetTextColor(10, 163, 16); // text color
		$this->Cell(40, 8, number_format($total_commission, 2), 'TBR', 0, 'R', false);
		$this->SetTextColor(40, 113, 161); // text color
		$this->Cell(40, 8, number_format($total_bcx_commission, 2), 'TBR', 1, 'R', false);
	}

	function WriteTableHeader($data) {
		//Calculate the height of the row
		$this->SetFont('Arial', 'B', 8);
		$this->SetTextColor(106, 106, 106); // text color
		$this->SetDrawColor(196, 196, 196);
		$this->SetFillColor(224, 224, 224); //fill color
		$nb = 0;
		for ($i = 0; $i < count($data); $i++) {
			$nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
		}
		$h = 5 * $nb;
		//Issue a page break first if needed
		$this->CheckPageBreak($h);
		//Draw the cells of the row
		for ($i = 0; $i < count($data); $i++) {
			$w = $this->widths[$i];
			$a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
			//Save the current position
			$x = $this->GetX();
			$y = $this->GetY();
			//Draw the border
			$this->Rect($x, $y, $w, $h);
			//Print the text
			$this->MultiCell($w, 5, $data[$i], 1, $a, TRUE);
			//Put the position to the right of the cell
			$this->SetXY($x + $w, $y);
		}
		//Go to the next line
		$this->Ln($h);
	}

	function WriteTableRow($data) {
		//Calculate the height of the row
		$this->SetFont('Arial', '', 8);
		$this->SetTextColor(106, 106, 106); // text color
		$this->SetDrawColor(196, 196, 196);


		$nb = 0;
		for ($i = 0; $i < count($data); $i++) {
			$nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
		}
		$h = 5 * $nb;
		//Issue a page break first if needed
		$this->CheckPageBreak($h);
		//Draw the cells of the row
		for ($i = 0; $i < count($data); $i++) {
			$w = $this->widths[$i];
			$a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
			//Save the current position
			$x = $this->GetX();
			$y = $this->GetY();
			//Draw the border
			$this->Rect($x, $y, $w, $h);
			//Print the text
			$this->MultiCell($w, 5, $data[$i], 0, $a);
			//Put the position to the right of the cell
			$this->SetXY($x + $w, $y);
		}
		//Go to the next line
		$this->Ln($h);
	}

	function NbLines($w, $txt) {


		//Computes the number of lines a MultiCell of width w will take
		$cw = &$this->CurrentFont['cw'];
		if ($w == 0) {
			$w = $this->w - $this->rMargin - $this->x;
		}
		$wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;

		if (is_string($txt)) {
			$s = str_replace("\r", '', $txt);
		} else {
			// Handle the case where $txt is not a string (optional, based on your logic)
			$s = '';
		}

		$nb = strlen($s);
		if ($nb > 0 and $s[$nb - 1] == "\n") {
			$nb--;
		}
		$sep = -1;
		$i = 0;
		$j = 0;
		$l = 0;
		$nl = 1;
		while ($i < $nb) {
			$c = $s[$i];
			if ($c == "\n") {
				$i++;
				$sep = -1;
				$j = $i;
				$l = 0;
				$nl++;
				continue;
			}
			if ($c == ' ') {
				$sep = $i;
			}
			$l += $cw[$c];
			if ($l > $wmax) {
				if ($sep == -1) {
					if ($i == $j) {
						$i++;
					}
				} else {
					$i = $sep + 1;
				}
				$sep = -1;
				$j = $i;
				$l = 0;
				$nl++;
			} else {
				$i++;
			}
		}
		return $nl;
	}

	function CheckPageBreak($h, $size = 0) {
		//If the height h would cause an overflow, add a new page immediately
		if ($this->GetY() + $h > $this->PageBreakTrigger) {
			if ($this->header == 0) {
				$this->setHeader(0);
			} else {
				$this->setHeader(1);
			}
			$this->AddPage($this->CurOrientation);
			if ($size) {
				$this->Cell($size, 0, '', 'B', 1, 'C', false);
			} else {
				$this->Cell($this->pagesizes, 0, '', 'B', 1, 'C', false);
			}
		}
	}

	/**
	 * @param string $by_value
	 * @param bool|int|null $fromTimestamp
	 * @param bool|int|null $toTimestamp
	 * @return void
	 */
	public function extracted($pageWidth,$by_value, $fromTimestamp = null, $toTimestamp = null) : void
	{
		$this->Cell($pageWidth, 10, in_array($this->report_type, [ 6 ]) ? $this->report_title . $by_value . ' as of ' . date('F d, Y', $fromTimestamp) : $this->report_title . $by_value . ' as of ' . date('F d, Y', $fromTimestamp) . ' to ' . date('F d, Y', $toTimestamp), '', 1, 'C', false);
		$this->SetFont('Arial', '', 12);
	}

	/*function drawPDF($array_data) {

		$this->SetLineWidth(0.0025);
		$this->report_type($this->report_type);

		$this->SetFont('Arial', 'B', 9);
		$this->SetTextColor(106, 106, 106);

		$this->AddPage();
		$this->SetXY(60,60);
		foreach ($array_data as $key => $value) {


			$heading = '';
			switch ($key) {
				case 'Applicant':
					$heading = "APPLICANT SUMMARY REPORT";
//                    $_SESSION['council'] == 0 ? $heading = "MERCHANTS REGISTRATION BY COUNCIL" : $heading = "MERCHANTS REGISTRATION";
					break;
				case 'Medical':
					$heading = 'MEDICAL SUMMARY REPORT';
//                    $_SESSION['council'] == 0 ? $heading = "COUNCIL" : 'TRANSACTIONS BY BUSINESS LOCATION';
					break;
				case 'Sid':
					$heading = 'SID SUMMARY REPORT';
					break;
				case 'Billing':
					$heading = 'BILLING SUMMARY REPORT';
					break;
				default:
					break;
			}

			$this->Ln(5);
			$this->SetFont('Arial', 'B', 9);
			$this->SetTextColor(106, 106, 106);

			if (sizeof($array_data[$key])) {

				if ($key == 'Applicant') {
					$this->Cell(0, 8, $heading, '', 0, 'C', false);

				} elseif($key == 'Medical') {
					$this->Cell(0, 8, $heading, '', 0, 'C', false);

				} elseif($key == 'Billing') {
					$this->Cell(0, 8, $heading, '', 0, 'C', false);
				} else{
					$cellText = ($key == 'Sid') ? $heading . ' TOTAL APPLICATION ' : $heading;
					$this->Cell(0, 8, $cellText, '', 0, 'C', false);
				}

				$this->ln();

				$this->writeLandscapeHeader($value[0], false);
				$this->SetFont('Arial', '', 7);

				$this-> writeGeneralReportSection($value, false);;
			}

		}

		// }
	}*/

}
