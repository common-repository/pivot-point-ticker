<?php
/*
Plugin Name: Pivot Point Ticker
Plugin URI: http://www.livecharts.co.uk/marketnewswidget.php
Description: Pivot Point Ticker.
Author: Livecharts
Author URI: http://livecharts.co.uk
Version: 1.0
Date: 2011-09-09
*/

/*  Copyright 2009  Livecharts  (email : lee@livecharts.co.uk)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


register_activation_hook( __FILE__, 'lcwpivots_plugin_activate' );
register_deactivation_hook ( __FILE__, 'lcwpivots_plugin_deactivate' );
add_action ('admin_menu', 'lcwpivots_add_option_page');
function lcwpivots_plugin_activate(){
	delete_option("lcwpivots_options");
}
function lcwpivots_plugin_deactivate(){
	delete_option("lcwpivots_options");
}

function lcwpivots_add_option_page() {
	$options_page = add_options_page('Pivot Points Ticker Options', 'Pivot Points Ticker', 8, __FILE__, 'lcwpivots_options_page');
	add_action ('admin_head-'. $options_page, 'lcwpivots_head');	
	
	$options                    = array();
	$options['width']           = "140";
	$options['symbols']         = "";
	$options['fontColor']       = "#444444";
	$options['oddRowColor']     = "#FFFFFF";
	$options['evenRowColor']    = "#F6F6F6";
	$options['borderColor']     = "#ECEDE8";
	$options['activelink']      = lcwpivots_getActiveLink();;
	$options['links_inactive']  = "1";
	add_option('lcwpivots_options', $options, 'Options for the Pivot Points Ticker plugin');
	
}

function lcwpivots_getActiveLink() {
	$defaultLink = "http://www.livecharts.co.uk|Live Charts";
		
	$fp = fsockopen("www.livecharts.co.uk", 80, $errno, $errstr, 30);
	if ( !$fp ) {
		return $defaultLink;
	} 
	else {
		$content = '';
		$out = "GET /widgets/pivotpoints/services.php?func=active-link HTTP/1.1\r\n";
		$out .= "Host: www.livecharts.co.uk\r\n";
		$out .= "Connection: Close\r\n\r\n";
		fwrite($fp, $out);
		while (!feof($fp)) {
			$content .= fgets($fp, 128);
		}
		fclose($fp);
		$arr = explode("##", $content);
		$resp = $arr[1];
			
		$arr = explode("\n", $resp);
		
		$resp = $arr[0];
		return $resp;
	}
}


function lcwpivots_head() {
	$baseUrl = lcwpivots_getbaseurl();
	?>
	<link rel="stylesheet" href="<?php echo $baseUrl ?>/colorpicker/css/colorpicker.css" type="text/css" media="screen" />
	<script type="text/javascript" src="<?php echo $baseUrl ?>/colorpicker/js/jquery.js"></script>
	<script type="text/javascript" src="<?php echo $baseUrl ?>/colorpicker/js/colorpicker.js"></script>
	<?php
}

/* UTIL FUNCTIONS */
function lcwpivots_slash_clean($post){
	//if ( get_magic_quotes_gpc() ) {
		return stripslashes($post);
	//}
	//return $post;
}
function lcwpivots_getbaseurl() {
	return rtrim(get_bloginfo('siteurl'), "/") . "/wp-content/plugins/lcw_pivot_points";
}
function get_pivot_points(){
	$options      = get_option('lcwpivots_options');
	$width        = $options['width'];
	$symbols      = $options['symbols'];
	$fontColor    = str_replace("#", "", $options['fontColor']);
	$oddRowColor  = str_replace("#", "", $options['oddRowColor']);
	$evenRowColor = str_replace("#", "", $options['evenRowColor']);
	$borderColor  = str_replace("#", "", $options['borderColor']);
	$height       = 185;
	$activeLink   = explode("|", $options["activelink"]);
	
$template = '
<div id="lcw_pivots_container">
	<iframe src="{IFRAME_SRC}" id="lcw_pivots_iframe" style="width:{IFRAME_WIDTH}px; height:{IFRAME_HEIGHT}px; border:0px; padding:0px; margin:0px;" frameborder="0" marginheight="0" marginwidth="0"></iframe>
';
if ( !$options['links_inactive'] ) {
	$template .= '
	<div style="padding:2px 0px 0px 5px; font-size:11px; font-family: Arial,Helvetica,Sans-serif;">
		<a onmouseover="this.style.color=\'#f20208\'" onmouseout="this.style.color=\'#1065D3\'" style="font-size:11px; color:#1065D3; text-decoration:none;" href="{ACTIVE_LINK_URL}" title="{ACTIVE_LINK_TITLE}">{ACTIVE_LINK_LABEL}</a>
	</div>
	';
}	
$template .= '
	<script type="text/javascript" src="http://www.livecharts.co.uk/widgets/pivotpoints/services.php?func=js&wp=y"></script>
</div>
';
	
	
	$iframeSRC    = lcwpivots_getbaseurl() . "/data/widget-iframe.php?symbols=" . $symbols;
	$iframeSRC   .= "&fontColor={$fontColor}&oddRowColor={$oddRowColor}&evenRowColor={$evenRowColor}"; 
	$iframeSRC   .= "&borderColor={$borderColor}&iw={$width}&ih={$height}";
	
	$search = array(
		"{IFRAME_SRC}",
		"{IFRAME_WIDTH}",
		"{IFRAME_HEIGHT}",
		"{ACTIVE_LINK_URL}",
		"{ACTIVE_LINK_TITLE}",
		"{ACTIVE_LINK_LABEL}"
	);
	$replace = array(
		$iframeSRC,
		$width,
		$height,
		$activeLink[0],
		$activeLink[1],
		$activeLink[1]
	);
		
	$code = str_replace($search, $replace, $template);
	return $code;
}

function lcwpivots_genoptions($options, $selected) {
	foreach ($options AS $val => $label) {
		?>
		<option <?php echo ($val == $selected ? ' selected="selected"' : '') ?> value="<?php echo $val ?>"><?php echo $label ?></option>
		<?php
	}
}
/* END UTIL FUNCTIONS */

function lcwpivots_options_page(){
	if ( count($_POST) ) {
		$options      = get_option('lcwpivots_options');
		
		$ops                    = array();
		$ops['width']           = lcwpivots_slash_clean($_POST['widget_width']);
		$ops['symbols']         = lcwpivots_slash_clean($_POST['widget_symbols']);
		$ops['fontColor']       = lcwpivots_slash_clean($_POST['widget_fontColor']);
		$ops['oddRowColor']     = lcwpivots_slash_clean($_POST['widget_oddRowColor']);
		$ops['evenRowColor']    = lcwpivots_slash_clean($_POST['widget_evenRowColor']);
		$ops['borderColor']     = lcwpivots_slash_clean($_POST['widget_borderColor']);
		$ops['links_inactive']  = lcwpivots_slash_clean($_POST['widget_links_inactive']);
		$ops['activelink']      = lcwpivots_slash_clean($options['activelink']);
		update_option("lcwpivots_options", $ops);
		echo '<div class="updated"><p>Plugin settings saved.</p></div>';
	}
	$options      = get_option('lcwpivots_options');
	?>
<div class='wrap'>
<h2>Pivot Points Ticker Options</h2>	
<form method="post" id="frmOptions">	
<table cellpadding="0" cellspacing="0" border="0" style="border: 0px solid red; width:800px;">
	<tr>
		<td width="300">
			Width <small><em>(px)</em></small>
		</td>
		<td>
			<input type="text" style="width:100%;" value="<?php echo htmlspecialchars($options['width']) ?>" id="widget_width" name="widget_width" />
			<input type="hidden" value="<?php echo htmlspecialchars($options['fontColor']) ?>" id="widget_fontColor" name="widget_fontColor" />
			<input type="hidden" value="<?php echo htmlspecialchars($options['oddRowColor']) ?>" id="widget_oddRowColor" name="widget_oddRowColor" />
			<input type="hidden" value="<?php echo htmlspecialchars($options['evenRowColor']) ?>" id="widget_evenRowColor" name="widget_evenRowColor" />
			<input type="hidden" value="<?php echo htmlspecialchars($options['borderColor']) ?>" id="widget_borderColor" name="widget_borderColor" />
		</td>
	</tr>
	<tr>
		<td valign="top">
			Symbols <small><em>(separated by commas, up to 10)</em></small>
		</td>
		<td>
			<input type="text" style="width:100%;" value="<?php echo htmlspecialchars($options['symbols']) ?>" id="widget_symbols" name="widget_symbols" />
<small>
<em>
Widget bases Pivot Points for stocks and indices on Yahoo historical data. Symbols are in Yahoo format as follows:<br />
&nbsp;&nbsp;US Stocks: GOOG | GE | AMZN<br />
&nbsp;&nbsp;UK Stocks: BARC.L | BP.L<br />
&nbsp;&nbsp;German Stocks: DBK.DE | ALV.DE<br />
&nbsp;&nbsp;Indices: ^FTSE | ^DJI | ^NDX |^GDAXI
</em>
</small>
		</td>
	</tr>
	<tr>
		<td>
			Font color
		</td>
		<td>
			<div id="cp-fontColor" class="colorpickerButton">
				<div style="background-color: <?php echo $options['fontColor'] ?>;"></div>
			</div>
			<script type="text/javascript">
			$('#cp-fontColor').ColorPicker({
				color: "<?php echo $options['fontColor'] ?>",
				onShow: function (colpkr) {
					$(colpkr).fadeIn(500);
					return false;
				},
				onHide: function (colpkr) {
					$(colpkr).fadeOut(500);
					//$("#cp-fontColor").ColorPickerSetColor(wc_fontColor);
					return false;
				},
				onChange: function (hsb, hex, rgb) {
					$('#cp-fontColor div').css('backgroundColor', "#" + hex);
					$("#widget_fontColor").val("#" + hex);
				}
			});
			</script>
		</td>
	</tr>
	<tr>
		<td>
			Odd row color
		</td>
		<td>
			<div id="cp-oddRowColor" class="colorpickerButton">
				<div style="background-color: <?php echo $options['oddRowColor'] ?>;"></div>
			</div>
			<script type="text/javascript">
			$('#cp-oddRowColor').ColorPicker({
				color: "<?php echo $options['oddRowColor'] ?>",
				onShow: function (colpkr) {
					$(colpkr).fadeIn(500);
					return false;
				},
				onHide: function (colpkr) {
					$(colpkr).fadeOut(500);
					//$("#cp-oddRowColor").ColorPickerSetColor(wc_oddRowColor);
					return false;
				},
				onChange: function (hsb, hex, rgb) {
					$('#cp-oddRowColor div').css('backgroundColor', "#" + hex);
					$("#widget_oddRowColor").val("#" + hex);
				}
			});
			</script>
		</td>
	</tr>
	<tr>
		<td>
			Even row color
		</td>
		<td>
			<div id="cp-evenRowColor" class="colorpickerButton">
				<div style="background-color: <?php echo $options['evenRowColor'] ?>;"></div>
			</div>
			<script type="text/javascript">
			$('#cp-evenRowColor').ColorPicker({
				color: "<?php echo $options['evenRowColor'] ?>",
				onShow: function (colpkr) {
					$(colpkr).fadeIn(500);
					return false;
				},
				onHide: function (colpkr) {
					$(colpkr).fadeOut(500);
					//$("#cp-evenRowColor").ColorPickerSetColor(wc_evenRowColor);
					return false;
				},
				onChange: function (hsb, hex, rgb) {
					$('#cp-evenRowColor div').css('backgroundColor', "#" + hex);
					$("#widget_evenRowColor").val("#" + hex);
				}
			});
			</script>
		</td>
	</tr>
	<tr>
		<td>
			Border color
		</td>
		<td>
			<div id="cp-borderColor" class="colorpickerButton">
				<div style="background-color: <?php echo $options['borderColor'] ?>;"></div>
			</div>
			<script type="text/javascript">
			$('#cp-borderColor').ColorPicker({
				color: "<?php echo $options['borderColor'] ?>",
				onShow: function (colpkr) {
					$(colpkr).fadeIn(500);
					return false;
				},
				onHide: function (colpkr) {
					$(colpkr).fadeOut(500);
					//$("#cp-borderColor").ColorPickerSetColor(wc_borderColor);
					return false;
				},
				onChange: function (hsb, hex, rgb) {
					$('#cp-borderColor div').css('backgroundColor', "#" + hex);
					$("#widget_borderColor").val("#" + hex);
				}
			});
			</script>
		</td>
	</tr>
	
	<tr>
		<td>
			De-activate link ?
		</td>
		<td>
			<select name="widget_links_inactive" id="widget_links_inactive">
			<?php
			lcwpivots_genoptions(array("0" => "No", "1" => "Yes"), @$options['links_inactive']);
			?>
			</select>
		</td>
	</tr>
	
	<tr>
		<td colspan="2" align="left" style="padding-top: 10px; padding-bottom: 10px;">
			<input type="button" value="Apply" onclick="onUpdate()">
		</td>
	</tr>
</table>
</form>
<h2>Preview</h2>
<div style="border:1px solid #DDDDDD; padding: 8px; width:500px;">
	<?php  echo get_pivot_points() ?>
</div>
</div>

<script type="text/javascript">
	function onUpdate() {
		$("#frmOptions").submit();
	}
</script>
	<?php
}
function lcwpivots_sidebar_init() {
    function lcwpivots_sidebar($args) {
        extract($args);
        echo $before_widget;
        echo $before_title . $after_title;
        echo get_pivot_points();
        echo $after_widget;
    }
    
	if ( function_exists('wp_register_sidebar_widget') ) {
		wp_register_sidebar_widget('lcw_pivots', 'Pivot Points Ticker', 'lcwpivots_sidebar');
	}
	else if ( function_exists('register_sidebar_widget') ) {
		register_sidebar_widget('Pivot Points Ticker', 'lcwpivots_sidebar');	
	}
	else {
		return;
	}
    
}

add_action('plugins_loaded', 'lcwpivots_sidebar_init');
?>