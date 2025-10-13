<?php

namespace Libs;

use Core\database\QueryBuilder as DB;

class ApiSystemAmount
{
	public static function getAmount($invoice_type): array
	{
		$db = new DB();
		$price = [];

		$sql = $db->select(
			"SELECT mx_invoice_type_price.dbl_price          AS price,
                   mx_invoice_type_price.opt_mx_currency_id AS currency
            FROM mx_invoice_type_price
                     JOIN mx_currency ON mx_invoice_type_price.opt_mx_currency_id = mx_currency.id
            WHERE opt_mx_invoice_type_id = :invoice_type
              AND opt_mx_state_id = 1",
			[':invoice_type' => $invoice_type]
		);

		if (count($sql) <= 0) {
			$price['amount'] = 0.00;
			$price['currency'] = null;
		} else {
			// Calculate the amount and round it to the nearest whole number (zero decimal places)
			$calculated_price = $sql[0]['price'];
			$price['amount'] = round($calculated_price, 0); // Rounds to nearest integer
			$price['currency'] = $sql[0]['currency'];
		}

		return $price;
	}

}