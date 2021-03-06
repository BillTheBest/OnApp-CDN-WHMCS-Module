<?php

if( ! defined( 'WHMCS' ) ) {
	exit( 'This file cannot be accessed directly' );
}

// Getting Product Name filter selects //
////////////////////////////////////////
// TODO multiselect
$product_query = 'SELECT
					name,
					id
				FROM
					tblproducts
				WHERE
					servertype = "onappcdn"';

$product_result = full_query( $product_query );

while( $row = mysql_fetch_assoc( $product_result ) ) {
	$product_names[ $row[ 'id' ] ] = $row[ 'name' ];
}

$product_name_options = "";
foreach( $product_names as $id => $name ) {
	$selected = '';

	if( $id == $bw[ 'productid' ] ) {
		$selected = ' selected';
	}

	$product_name_options .= '<option value="' . $id . '"' . $selected . '>' . $name . '</option>';
}
// END Getting Product Name filter selects //
////////////////////////////////////////////

// Getting Client Name filter selects //
///////////////////////////////////////
$client_query  = 'SELECT
					hosting.userid,
					client.firstname,
					client.lastname
				FROM
					tblservers AS server
				LEFT JOIN
					tblhosting AS hosting
					ON hosting.server = server.id
				LEFT JOIN
					tblclients AS client
					ON hosting.userid = client.id
				WHERE
					server.type = "onappcdn" AND
					hosting.userid != ""';
$client_result = full_query( $client_query );

while( $row = mysql_fetch_assoc( $client_result ) ) {
	$client_names[ $row[ 'userid' ] ] = $row[ 'firstname' ] . ' ' . $row[ 'lastname' ];
}

$client_name_options = "";
foreach( $client_names as $id => $name ) {
	$selected = '';
	if( $id == $bw[ 'clientid' ] ) {
		$selected = ' selected';
	}

	$client_name_options .= '<option value="' . $id . '"' . $selected . '>' . $name . '</option>';
}

// END Getting Product Name filter selects //
////////////////////////////////////////////
//
// Filtering //
//////////////
if( isset( $_POST[ 'bw' ] ) ) {
	$filter_condition  = 'AND bandwidth.stat_time != ""' . PHP_EOL;
	$bw                = $_POST[ 'bw' ];
	$filter_conditions = array();

	if( isset ( $bw[ 'end' ] ) && $bw[ 'end' ] != '' ) {
		// TODO DATE_FORMAT
		$filter_conditions[ ] = "bandwidth.stat_time >= '" . onappcdn_dates_mysql( $bw[ 'end' ] ) . "'";
	}

	if( isset ( $bw[ 'start' ] ) && $bw[ 'start' ] != "" ) {
		// TODO DATE_FORMAT
		$filter_conditions[ ] = "bandwidth.stat_time <= '" . onappcdn_dates_mysql( $bw[ 'start' ] ) . "'";
	}

	if( isset ( $bw[ 'serviceid' ] ) && $bw[ 'serviceid' ] != '' ) {
		$filter_conditions[ ] = 'hosting.id = ' . $bw[ 'serviceid' ];
	}

	if( isset ( $bw[ 'clientid' ] ) && $bw[ 'clientid' ] != '' ) {
		$filter_conditions[ ] = 'client.id = ' . $bw[ 'clientid' ];
	}

	if( isset ( $bw[ 'productid' ] ) && $bw[ 'productid' ] != '' ) {
		$filter_conditions[ ] = 'product.id = ' . $bw[ 'productid' ];
	}

	foreach( $filter_conditions as $condition ) {
		$filter_condition .= ' AND ' . PHP_EOL . $condition;
	}
// rename
// TODO add field client fullname instead of first an second
// TODO add pagenator
	$query1 = "SELECT
					bandwidth.currency_rate       AS rate,
					bandwidth.cost                AS price,
					hosting.userid,
					client.firstname              AS clientfirstname,
					client.lastname               AS clientlastname,
					client.id                     AS clientid,
					onappclient.onapp_user_id,
					bandwidth.traffic AS total_bandwidth,
					hosting.id                    AS hostingid,
					bandwidth.stat_time,
					product.name                  AS productname,
					product.id                    AS productid,
					hosting.domainstatus
				FROM
					tblservers AS server
				LEFT JOIN
					tblhosting AS hosting
					ON hosting.server = server.id
				LEFT JOIN
					tblonappcdnclients AS onappclient
					ON onappclient.service_id = hosting.id
				LEFT JOIN
					tblclients AS client
					ON hosting.userid = client.id
				LEFT JOIN
					tblproducts AS product
					ON product.id = hosting.packageid
				LEFT JOIN
					tblonappcdn_billing  AS bandwidth
					ON bandwidth.hosting_id = hosting.id
				WHERE
					server.type = 'onappcdn' AND
					onappclient.onapp_user_id != ''
					$filter_condition
				GROUP BY
					bandwidth.hosting_id, bandwidth.cost
				ORDER BY
					bandwidth.stat_time DESC";
}
// End Filtering //
//////////////////

// Filter HTML //
////////////////
$reportdata[ 'title' ]       = 'CDN Usage Statistics';
$reportdata[ 'description' ] = "This report shows usage statistics of CDN Resources and billing information.<br /><br />

<div id='tab_content'>
    <form id = 'form' action='' method='post'>
        <table class='form' width='100%' border='0' cellspacing='2' cellpadding='3'>
            <tr>
                <td width='15%' class='fieldlabel'>Start Date</td>
                <td class='fieldarea'><input class='datepick' type='text' name='bw[end]' size='20' value='{$bw["end"]}'></td>
                <td class='fieldlabel'>Service Id</td>
                <td class='fieldarea'><input type='text' name='bw[serviceid]' size='20' value='{$bw["serviceid"]}'></td>
            </tr>
            <tr>
                <td class='fieldlabel'>End Date</td>
                <td class='fieldarea'><input class='datepick' type='text' name='bw[start]' size='20' value='{$bw["start"]}'></td>
                <td class='fieldlabel'>Client Name</td>
                <td class='fieldarea'>
                    <select name='bw[clientid]'>
                        <option value=''>- Any -</option>
                        $client_name_options
                    </select>
                </td>
            </tr>
            <tr>
                <td class='fieldlabel'>&nbsp;</td>
                <td class='fieldlarea'>&nbsp;</td>

                <td class='fieldlabel'>Product Name</td>
                <td class='fieldarea'>
                    <select name='bw[productid]'> <!-- TODO multiselect -->
                        <option value=''>- Any -</option>
                        $product_name_options
                    </select>
                </td>
            </tr>
        </table>
        <input id='update' type='hidden' name='update' value='' />
        <p align='center'><input type='submit' name='filter' value='Search' class='button'></p>
    </form>
  </div>
";
// END Filter HTML //
////////////////////

if( ( $bw[ 'end' ] != '' ) || ( $bw[ 'start' ] != '' ) ) {
	$reportdata[ 'tableheadings' ] = array(
		'Status', 'Service ID', 'Client Name', 'Product Name', 'Total Bandwidht', 'Cost'
	);
}
else {
	$reportdata[ 'tableheadings' ] = array(
		'Status', 'Service ID', 'Client Name', 'Product Name', 'Total Bandwidht', 'Cost', 'Paid Invoices', 'Unpaid Invoices', 'Not Invoiced'
	);
}

$result = mysql_query( $query1 );

$total               = 0;
$total_cost          = 0;
$total_paid          = 0;
$total_unpaid        = 0;
$not_invoiced_amount = 0;

$_data = array();
while( $row1 = mysql_fetch_assoc( $result ) ) {
	if( ! isset( $_data[ $row1[ 'hostingid' ] ] ) ) {
		$_data[ $row1[ 'hostingid' ] ] = $row1;
	}
	else {
		$_data[ $row1[ 'hostingid' ] ][ 'cached' ] += $row1[ 'total_bandwidth' ];
		$_data[ $row1[ 'hostingid' ] ][ 'cost' ] += $row1[ 'price' ];
	}
}

foreach( $_data as $data ) {
	$invoices_query = 'SELECT
							SUM( i.subtotal ) AS amount,
							status
						FROM
							tblinvoices AS i
						WHERE
							i.userid = ' . $data[ 'clientid' ] . '
							AND i.notes = ' . $data[ 'hostingid' ] . '
						GROUP BY
							i.notes, status
						ORDER BY
							i.date DESC';

	$invoices_result = full_query( $invoices_query );

	$invoices_data             = array();
	$invoices_data[ 'paid' ]   = 0;
	$invoices_data[ 'unpaid' ] = 0;

	while( $invoices = mysql_fetch_assoc( $invoices_result ) ) {
		if( $invoices[ 'status' ] == 'Paid' ) {
			$invoices_data[ 'paid' ] = $invoices[ 'amount' ] / $data[ 'rate' ];
		}
		else {
			$invoices_data[ 'unpaid' ] = $invoices[ 'amount' ] / $data[ 'rate' ];
		}
	}

	$not_invoiced_amount = $data[ 'cost' ] - ( $invoices_data[ 'paid' ] + $invoices_data[ 'unpaid' ] );

	$total_paid += $invoices_data[ 'paid' ];
	$total_unpaid += $invoices_data[ 'unpaid' ];
	$total_cached += $data[ 'cached' ];
	$total_non_cached += $data[ 'non_cached' ];
	$total_cost += $data[ 'cost' ];
	$total_not_invoiced += $not_invoiced_amount;

	$clientlink  = '<a href="clientssummary.php?userid=' . $data[ 'userid' ] . '">';
	$servicelink = '<a href="clientshosting.php?userid= ' . $data[ 'userid' ] . '&id=' . $data[ 'hostingid' ] . '">';;

	if( ( $bw[ 'end' ] != '' ) || ( $bw[ 'start' ] != '' ) ) {
		$reportdata[ 'tablevalues' ][ ] = array(
			$data[ 'domainstatus' ], $servicelink . $data[ 'hostingid' ] . '</a>', $clientlink . $data[ 'clientfirstname' ] . ' ' . $data[ 'clientlastname' ] . '</a>', $data[ 'productname' ], $data[ 'cached' ],	$data[ 'cost' ]
		);
	}
	else {
		$reportdata[ 'tablevalues' ][ ] = array(
			$data[ 'domainstatus' ], $servicelink . $data[ 'hostingid' ] . '</a>', $clientlink . $data[ 'clientfirstname' ] . ' ' . $data[ 'clientlastname' ] . '</a>', $data[ 'productname' ], $data[ 'cached' ], $data[ 'cost' ], $invoices_data[ 'paid' ], $invoices_data[ 'unpaid' ], $not_invoiced_amount
		);
	}
}

// Javascript //
///////////////
$javascript = '
    <script type="text/javascript" >' . PHP_EOL .
		'function update_run(){' . PHP_EOL .
		'jQuery("#update").val("run")' . PHP_EOL .
		'jQuery("#form").submit()' . PHP_EOL .
		'}' . PHP_EOL .
		'</script>
	';

echo $javascript;
// END Javascript //
///////////////////

$reportdata[ 'footertext' ] = '<a href="#" onClick="update_run();">Update Now</a>';

if( ! mysql_num_rows( $result ) < 1 ) {
	if( ( $bw[ 'end' ] != '' ) || ( $bw[ 'start' ] != '' ) ) {
		$reportdata[ 'tablevalues' ][ ] = array(
			'<b>Total</b>', '', '', '', '<b>' . $total_cached . '</b>', '<b>' . $total_cost . '</b>'
		);
	}
	else {
		$reportdata[ 'tablevalues' ][ ] = array(
			'<b>Total</b>', '', '', '', '<b>' . $total_cached . '</b>', '<b>' . $total_cost . '</b>', '<b>' . $total_paid . '</b>', '<b>' . $total_unpaid . '</b>', '<b>' . $total_not_invoiced . '</b>'
		);
	}
}

function onappcdn_dates_mysql( $date ) {
	$date = explode( '/', $date );
	$date = array_reverse( $date );
	$date = implode( '-', $date );
	return $date;
}