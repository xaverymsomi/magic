<?php
/*
 * This file is part of the Mabrex package.
 * It is strictly a property of Rahisi Solution Ltd..
 *
 * (c) 2024
 *
 * Author: John M. Andrew
 * Date : 15/10/2024
 *
 * Meant to be a one point extension of the FPDF library
 * from which all other PDF generators should extend
 *
 * The reasoning is to have one place to change the code logic
 * in case the FPDF library somehow gets an update that breaks
 * all PDF generation functionality
 *
 */

namespace Libs;

use Fpdf\Fpdf;

class FpdfMask extends Fpdf
{
    protected string $doc_dir = PUBLIC_PATH . '/uploads/';

    public function __construct($orientation = 'P', $unit = 'mm', $size = 'A4')
    {
        parent::__construct($orientation, $unit, $size);
        mkdirIfNotExists($this->doc_dir);
    }
}