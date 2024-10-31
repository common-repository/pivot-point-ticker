<?php
$symbols       = @$_REQUEST['symbols'];
$fontColor     = @$_REQUEST['fontColor'];
$oddRowColor   = @$_REQUEST['oddRowColor'];
$evenRowColor  = @$_REQUEST['evenRowColor'];
$borderColor   = @$_REQUEST['borderColor'];
	
$arr = @explode(",", $symbols);
@array_walk($arr, create_function('&$val', '$val = trim($val);'));
$symbols       = implode(",", $arr);
	
$symbols = strtoupper(@implode(",", array_slice(explode(",", $symbols), 0, 10)));
//echo $symbols; exit;

if ( !file_exists("symbols-data.txt") ) {
	file_put_contents("symbols-data.txt", "");
}

$symbolsDataContent = file_get_contents("symbols-data.txt");
$symbolsData        = array();
$arr                = explode("|", $symbolsDataContent);
$lastUpdate         = (int)@$arr[1];
if ( count( array_diff(explode(',', $symbols), explode(',', $arr[2])) ) > 0 ) {
	$lastUpdate = 0;
}
if ( time() > ($lastUpdate + (60*60)) ) {
	$fileContent = array();
	$regex  = '/';
	$regex .= '<tr>';
		$regex .= '<td class="yfnc_tabledata1" nowrap align="right">([^<]+)<\/td>';
		$regex .= '<td class="yfnc_tabledata1" align="right">([^<]+)<\/td>';
		$regex .= '<td class="yfnc_tabledata1" align="right">([^<]+)<\/td>';
		$regex .= '<td class="yfnc_tabledata1" align="right">([^<]+)<\/td>';
		$regex .= '<td class="yfnc_tabledata1" align="right">([^<]+)<\/td>';
		$regex .= '<td class="yfnc_tabledata1" align="right">([^<]+)<\/td>';
		$regex .= '<td class="yfnc_tabledata1" align="right">([^<]+)<\/td>';
	$regex .= '<\/tr>';
	$regex .= '/is';
	foreach (explode(",", $symbols) AS $s) {
		$content = file_get_contents("http://finance.yahoo.com/q/hp?s=" . $s);
		preg_match($regex, $content, $m);	
		if ( count($m) ) {
			$fileContent[] = "{$s}=" . str_replace(",", '', $m[1]) . "," . str_replace(",", '', $m[2]) . "," . str_replace(",", '', $m[3]) . "," . str_replace(",", '', $m[4]) . "," . str_replace(",", '', $m[5]);
		}
	}
	if ( count($fileContent) ) {
		file_put_contents("symbols-data.txt", implode(';', $fileContent) . "|" . time() . "|" . $symbols);
	}
}
$symbolsDataContent = file_get_contents("symbols-data.txt");
$symbolsDataContent = explode("|", $symbolsDataContent);
$symbolsInfo        = array();
foreach ( explode(';', $symbolsDataContent[0]) AS $sData) {
	$arr = explode("=", $sData);
	if ( !in_array($arr[0], explode(",", $symbols))  ) {
		continue;
	}
	$arrPrices = explode(",", $arr[1]);
	$Open = @$arrPrices[1];
	$High = @$arrPrices[2];
	$Low = @$arrPrices[3];
	$Close = @$arrPrices[4];
	$date = date("m/d", strtotime($arrPrices[0]));
	
	$Pivot  = ($High + $Low + $Close)/3;
	$R3     = $High + 2*($Pivot - $Low);
	$R2     = $Pivot + ($High - $Low);
	$R1     = (2 * $Pivot) - $Low;
	$S1     = (2 * $Pivot) - $High;
	$S2     = $Pivot - ($High - $Low);
	$S3     = $Low - 2*($High - $Pivot);
	
	$symbolsInfo[$arr[0]] = array(
		'R3' => @$R3, 
		'R2' => @$R2, 
		'R1' => @$R1, 
		'Pivot' => @$Pivot, 
		'S1' => @$S1, 
		'S2' => @$S2, 
		'S3' => @$S3, 
		'data' => $date
	); 
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
			<!--<meta http-equiv="refresh" content="60" />-->
			<link rel="stylesheet" type="text/css" href="pivot.css" media="screen" />
			<script type='text/javascript' src='../colorpicker/js/jquery.js'></script>
			<script type='text/javascript' src='../colorpicker/js/jquery.animate.color.js'></script>
		</head>
		<body TOPMARGIN="0" LEFTMARGIN="0" MARGINHEIGHT="0" MARGINWIDTH="0" style="padding:0px; margin:0px; background-color:white;">
			<div id="container" style="border:1px  solid #<?= $borderColor ?>">
				<?php
				$i=0;
				foreach ( $symbolsInfo AS $s => $row) {
					?>
					<div id="slide-<?= $i ?>" style="background-color:#FFFFFF;width:100%; height:100%; color:#<?= $fontColor ?>; display:<?= ($i!=0 ? "none" : "") ?>">
						<h2 class="fadee" style="overflow:hidden; white-space:nowrap;"><?= htmlspecialchars($s) ?> Pivot Points</h2>
						<div id="datatable-div" style="padding:2px;" >
						<table class="datatable" id="datatable" cellpadding="0" cellspacing="0" style="width:100%;color:#<?= $fontColor ?>" >
							<tbody>
								<tr class="even"> 
									<td width="45%" style="background-color:#<?= $evenRowColor ?>">R3</td>
									<td style="background-color:#<?= $evenRowColor ?>" class="fadee"><?= number_format($row['R3'], 1, '.', '') ?></td>
								</tr>
								<tr class="odd"> 
									<td width="45%" style="background-color:#<?= $oddRowColor ?>">R2</td>
									<td style="background-color:#<?= $oddRowColor ?>" class="fadee"><?= number_format($row['R2'], 1, '.', '') ?></td>
								</tr>
								<tr class="even"> 
									<td width="45%" style="background-color:#<?= $evenRowColor ?>">R1</td>
									<td style="background-color:#<?= $evenRowColor ?>" class="fadee"><?= number_format($row['R1'], 1, '.', '') ?></td>
								</tr>
								<tr class="odd"> 
									<td width="45%" style="background-color:#<?= $oddRowColor ?>"><b>Pivot</b></td>
									<td style="background-color:#<?= $oddRowColor ?>" class="fadee"><b><?= number_format($row['Pivot'], 1, '.', '') ?></b></td>
								</tr>
								<tr class="even"> 
									<td width="45%" style="background-color:#<?= $evenRowColor ?>">S1</td>
									<td style="background-color:#<?= $evenRowColor ?>" class="fadee"><?= number_format($row['S1'], 1, '.', '') ?></td>
								</tr>
								<tr class="odd"> 
									<td width="45%" style="background-color:#<?= $oddRowColor ?>">S2</td>
									<td style="background-color:#<?= $oddRowColor ?>" class="fadee"><?= number_format($row['S2'], 1, '.', '') ?></td>
								</tr>
								<tr class="even"> 
									<td width="45%" style="background-color:#<?= $evenRowColor ?>">S3</td>
									<td style="background-color:#<?= $evenRowColor ?>" class="fadee"><?= number_format($row['S3'], 1, '.', '') ?></td>
								</tr>
								<tr class="odd"> 
									<td width="45%" style="background-color:#<?= $oddRowColor ?>">Date</td>
									<td style="background-color:#<?= $oddRowColor ?>" class="fadee"><?= $row['data'] ?></td>
								</tr>
							</tbody> 
						</table> 
						</div>
					</div>	
					<?php
					$i++;
				}
				?>
			</div>
		<script type='text/javascript'>
		var symbolsCount = <?= (int)count($symbolsInfo) ?>;
		jQuery(document).ready(function() {
			var iframeWidth  = <?= @$_GET['iw'] ?>-2;
			var iframeHeight = <?= @$_GET['ih'] ?>-2;
				
			jQuery("#container").css("width",  iframeWidth + "px");
			jQuery("#container").css("height", iframeHeight + "px");
			if ( symbolsCount > 1 ) {
				setTimeout('setToggleFade()', 7000);
			}
		});
		function setToggleFade() {
			toggleFade();
			setInterval('toggleFade()', 12000);
		}
		
		var idxSlide = 0;
		function toggleFade() {
			jQuery("#slide-" + idxSlide).fadeOut(4000, function(){
				idxSlide++;
				if (idxSlide == symbolsCount){ 
					idxSlide = 0;
				}
				jQuery("#slide-" + idxSlide).fadeIn(1000, function(){
				});
			});
		}
			
		</script>
		
		</body>
		</html>
