<?php 
	require_once(plugin_dir_path(__FILE__)."../include/activation.php");

	if(isset($_POST["save_code"])){
		if(!isset($_POST["non"]) || !wp_verify_nonce($_POST["non"], "non")){
			exit;
		}
		else{
			$pixelcode = sanitize_text_field($_POST["pixelcode"]);
			$analyticcode = sanitize_text_field($_POST["analyticcode"]);

			update_option("wask_facebook_pixel", $pixelcode, true);
			update_option("wask_google_analytic", $analyticcode, true);

			echo "<div class='updated'><p><b>Tracking codes Saved Successfully</b></p></div>";
		}
	}

	if($activation){
		$wask_facebook_pixel = get_option("wask_facebook_pixel");
		$wask_google_analytic = get_option("wask_google_analytic");

		if($wask_facebook_pixel == ""){
			$account_id = isset($_GET["account_id"]) ? sanitize_text_field($_GET["account_id"]) : $facebook_act->facebook_accounts[0]->id;
			$token = isset($_GET["token"]) ? sanitize_text_field($_GET["token"]) : $facebook_act->facebook_accounts[0]->token;

			/* Get Facebook Pixel */
			$url = constant("facebook_api") . $account_id . "/adspixels";
			$url .= "?fields=id,name";
			$url .= "&access_token=".$token;

			$response = wp_remote_get($url);
			$pixels = json_decode(wp_remote_retrieve_body($response));

			if(isset($pixels->error)){
				if(isset($pixels->error->error_user_msg)){
					echo "<div class='error'><p><b>".$pixels->error->error_user_msg."</b></p></div>";
				}
				else if(isset($pixels->error->message)){
					echo "<div class='error'><p><b>".$pixels->error->message."</b></p></div>";
				}
			}
			/* Get Facebook Pixel */
		}
	}
?>

<div class="wrap">
	<div class="wask">
		<?php 
			if(!$activation){
				require_once(plugin_dir_path(__FILE__)."activation.php");
			} 
			else {
		?>
			<section id="app">
				<span style="position: absolute;" class="dashicons dashicons-facebook-alt"></span>
				<h2 style="padding-left:30px">Facebook Pixel</h2> 
				<?php echo $wask_facebook_pixel == "" ? "<div class='circle orange'></div>" : "<div class='circle green'><span>Connected</span></div>";?>
				<br>

				<ul class="nav nav-tabs">
					<li data-tab="#tab1" class="active">Automatic Connection</li>
					<li data-tab="#tab2">Manuel Connection</li>
				</ul>

				<div class="tab-content">
					<div id="tab1" class="tab-pane active" >
						<table>
							<tbody>
								<tr height="50">
									<td>
										<label>Facebook Ad Account: </label>
									</td>
									<td>
										<select name="ad_account" id="ad_account" <?php echo $wask_facebook_pixel != "" ? "disabled":"" ?> >
											<?php 
												if(isset($facebook_act->facebook_accounts)){
													foreach($facebook_act->facebook_accounts as $account){
														$selected = $account->id == $account_id ? "selected":"";

														echo "<option value='".$account->id."' data-token='".$account->token."' ".$selected.">".$account->name."</option>";
													}
												}
											?>
										</select>
									</td>
								</tr>

								<tr height="50">
									<td>
										<label>Facebook Pixel: </label>
									</td>
									<td>
										<select name="pixel" id="pixel" <?php echo $wask_facebook_pixel != "" ? "disabled":"" ?>>
											<?php 
												if(isset($pixels->data)){
													foreach($pixels->data as $pixel)
														echo "<option value='".$pixel->id."'>".$pixel->name."</option>";
												}
											?>
										</select>
									</td>
								</tr>

								<tr height="50">
									<td></td>
									<td>
										<?php if($wask_facebook_pixel == ""){?>
											<input type="button" name="connect_pixel" id="connect_pixel" class="button button-primary" style=" width: 250px;" value="Connect Facebook Pixel">
										<?php }else{?>
											<input type="button" name="disconnect_pixel" id="disconnect_pixel" class="button button-primary" style="background:#f95252;border-color:#f95252; width: 250px" value="Disconnect Facebook Pixel">
										<?php }?>
									</td>
								</tr>
							</tbody>
						</table>

						<div id='status'></div>
					</div>

					<div id="tab2" class="tab-pane" >
						<form action="" method="post">
							<label>Facebook Pixel Code: </label>
							<textarea name="pixelcode" style="width: 100%;height: 125px;"><?php echo $wask_facebook_pixel?></textarea>
							<a href="https://blog.wask.co/digital-marketing/what-can-we-do-with-facebook-pixel-code" target="_blank">How can i find my pixel code</a>

							<br><br>
							<label>Google Analytic Code: </label>
							<textarea name="analyticcode" style="width: 100%;height: 125px;"><?php echo $wask_google_analytic?></textarea>

							<br><br>
							<?php wp_nonce_field('non','non'); ?>
							<input type="submit" name="save_code" id="save_code" class="button button-primary" value="Save Codes">
						</form>
					</div>
				</div>
			</section>
		<?php }?>
	</div>
</div>

<script>
	jQuery("#ad_account").on("change", function(){
		var account_id = jQuery(this).find("option:selected").val();
		var account_token = jQuery(this).find("option:selected").data("token");

		window.location.href = location.protocol + '//' + location.host + location.pathname + "?page=facebook_pixel&account_id=" + account_id + "&token=" + account_token;
	});

	jQuery("#connect_pixel").on("click", function(){
		var account = jQuery("#ad_account");
		var pixel = jQuery("#pixel");

		if(account.find("option:selected").length == 0){
			account.addClass("border-danger");
		}
		else if(pixel.find("option:selected").length == 0){
			pixel.addClass("border-danger");
		}
		else{
			jQuery(this).attr("disabled", "disabled");
			account.attr("disabled", "disabled");
			pixel.attr("disabled", "disabled");

			jQuery("#status").html("<br><br><div class='loader'></div>&nbsp;&nbsp;&nbsp;<b>Facebook pixel code adding</b>.");

			setTimeout(function(){
				jQuery.ajax({
			        url: "<?php echo admin_url( 'admin-ajax.php' );?>",
			        type: "post",
			        data: {
			        	id: pixel.find("option:selected").val(),
			        	token: account.find("option:selected").data("token"),
			        	action: "add_facebook_pixel"
			        } ,
			        success: function (response) {
			        	response = JSON.parse(response);

			        	if(typeof response.error !== "undefined"){
			        		jQuery("#status").html("<br><br><span style='color:red;'><b>Automatic connection could not be established due to the authorization problem in your Facebook account. Please try manual connection.</b></span>");

			        		jQuery("#connect_pixel").removeAttr("disabled");
				           	account.removeAttr("disabled");
				           	pixel.removeAttr("disabled");
			        	}
			        	else if(response.success){
			        		jQuery("#status").html("<br><br><span style='color:green;'><b>Your pixel code added successfully.</b></span>");

			        		setTimeout(function(){
			        			window.location.reload();
			        		}, 2000);
			        	}
			        },
			        error: function(jqXHR, textStatus, errorThrown) {
			           	jQuery("#status").html("<br><br><span style='color:red;'><b>Somethings went wrong.</b></span>");
			           	jQuery("#connect_pixel").removeAttr("disabled");
			           	account.removeAttr("disabled");
				        pixel.removeAttr("disabled");
			        }
			    });
			}, 500);
		}
	});

	jQuery("#disconnect_pixel").on("click", function(){
		jQuery.ajax({
			url: "<?php echo admin_url( 'admin-ajax.php' );?>",
			type: "post",
			data: {
				action: "remove_facebook_pixel"
			} ,
			success: function (response) {
				window.location.reload();
			},
			error: function(jqXHR, textStatus, errorThrown) {
				jQuery("#status").html("<br><br><span style='color:red;'><b>Somethings went wrong.</b></span>");
			}
		});
	});

	jQuery("ul.nav-tabs > li").on("click", function(){
		var element = jQuery(this);

		if(!element.hasClass("active")){
			jQuery("ul.nav-tabs li.active").removeClass("active");
			element.addClass("active");

			jQuery(".tab-content .tab-pane.active").removeClass("active");
			jQuery(element.data("tab")).addClass("active");
		}
	});
</script> 