<?php 
	require_once(plugin_dir_path(__FILE__)."../include/activation.php");
	require_once(plugin_dir_path(__FILE__)."../include/user.php");

	if($activation){
		$account_id = isset($_GET["account_id"]) ? sanitize_text_field($_GET["account_id"]) : $facebook_act->facebook_accounts[0]->id;
		$token = isset($_GET["token"]) ? sanitize_text_field($_GET["token"]) : $facebook_act->facebook_accounts[0]->token;

		/* Get Custom Audiences */
		$url = constant("facebook_api") . $account_id . "/customaudiences";
		$url .= "?fields=id,name,delivery_status,approximate_count_lower_bound,approximate_count_upper_bound,subtype,time_created";
		$url .= "&filtering=[{'field':'subtype','operator':'IN','value':['LOOKALIKE','CUSTOM']}]";
		$url .= "&limit=50";
		$url .= "&access_token=".$token;

		$response = wp_remote_get($url);
		$custom_audiences = json_decode(wp_remote_retrieve_body($response));

		if(isset($custom_audiences->error)){
			if(isset($custom_audiences->error->error_user_msg)){
				echo "<div class='error'><p><b>".$custom_audiences->error->error_user_msg."</b></p></div>";
			}
			else if(isset($custom_audiences->error->message)){
				echo "<div class='error'><p><b>".$custom_audiences->error->message."</b></p></div>";
			}
		}
		/* Get Custom Audiences */
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
			<span style="position: absolute;" class="dashicons dashicons-menu"></span>
			<h2 style="padding-left:30px">Target Audience List</h2>
			<br>
			<table>
				<tbody>
					<tr height="50">
						<td><b>Facebook Ad Account:</b></td>
						<td>
							<select name="ad_account" id="ad_account">
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
				</tbody>
			</table>
			<table class="table table-hover">
				<tbody>
					<tr>
						<th>#</th>
						<th>Audience Name</th>
						<th style="min-width: 80px">Created Date</th>
						<th>Type</th>
						<th>Size</th>
						<th>Status</th>
						<th style="min-width: 250px">Action</th>
					</tr>
				</tbody>
				<tbody>
					<?php 
						if(isset($custom_audiences->data)){
							foreach($custom_audiences->data as $key=>$audience){
								$status = "";
								$status_color = "";
								$create_new_ad_button = "";
								$create_lookalike_button = "";
								$delete_button = "";

								if($audience->subtype == "LOOKALIKE"){
									if($audience->delivery_status->code == 200){
										$status = "Your lookalike audience is ready. You can create new ad.";
										$status_color = "green";

										$create_new_ad_button = "<input type='button' class='button button-primary create_new_ad' value='Create New Ad' data-id='".$audience->id."'>";

										$delete_button = "<input type='button' class='button button-primary delete_audience' value='Delete Audience' data-id='".$audience->id."'>";
									}
									else if($audience->delivery_status->code == 300){
										$status = "Your lookalike audience is creating. You can see after 15-20 min.";
										$status_color = "orange";

										$create_new_ad_button = "<input type='button' class='button button-primary' value='Create New Ad' disabled>";

										$delete_button = "<input type='button' class='button button-primary' value='Delete Audience' disabled>";
									}
								}
								else if($audience->subtype == "CUSTOM"){
									if($audience->delivery_status->code == 200){
										$status = "Your custom audience is ready. Now you can create a looklike.";
										$status_color = "green";

										$create_new_ad_button = "<input type='button' class='button button-primary create_new_ad' value='Create New Ad' data-id='".$audience->id."'>";

										$create_lookalike_button = "<input type='button' class='button button-primary create_lookalike' value='Create Lookalike' data-id='".$audience->id."'>";
									}
									else if($audience->delivery_status->code == 300){
										$status = "Your custom audience is creating. You can see after 15-20 min.";
										$status_color = "orange";

										$create_new_ad_button = "<input type='button' class='button button-primary' value='Create New Ad' disabled>";

										$create_lookalike_button = "<input type='button' class='button button-primary' value='Create Lookalike' disabled>";
									}
								}
					?>
								<tr>
									<td><?php echo $key+1 ?></td>
									<td><?php echo $audience->name ?></td>
									<td><?php echo date("Y-m-d", $audience->time_created) ?></td>
									<td><?php echo $audience->subtype ?></td>
									<td><?php echo $audience->approximate_count_upper_bound ?></td>
									<td style="color:<?php echo $status_color ?>">
										<?php echo $status?>		
									</td>
									<td>
										<?php echo $create_new_ad_button . $create_lookalike_button . $delete_button?>
									</td>
								</tr>
					<?php 
							}
						}
					?>
				</tbody>
			</table>
		</section>

		<?php }?>
	</div>
</div>

<script type="text/javascript">
	const subscription = <?php echo $subscription ?>;

	jQuery("#ad_account").on("change", function(){
		var account_id = jQuery(this).find("option:selected").val();
		var account_token = jQuery(this).find("option:selected").data("token");

		window.location.href = location.protocol + '//' + location.host + location.pathname + "?page=audience_list&account_id=" + account_id + "&token=" + account_token;
	});

	jQuery(".create_lookalike").on("click", function(){
		var audience_id = jQuery(this).data("id");

		if(!subscription){
			swal.fire({
				title: "You must be subscribed to use the features of this page.",
				showConfirmButton: true,
				showCancelButton: true,
				confirmButtonText: "I want to subscribe now",
				cancelButtonText: "Cancel"
			}).then((result)=>{
				if(result.value)
					window.open("https://app.wask.co?upgrade=true");
			});
		}
		else{

			var html = `
				<div style="text-align:left">
					<table>
						<tbody>
							<tr height="50">
								<td valign="bottom"><b>Name: </b></td>
								<td valign="bottom"><input type="text" id="name"></td>
							</tr>

							<tr>
								<td></td>
								<td><span id="name-danger" style="color:red;font-size:13px"></span></td>
							</tr>

							<tr height="50">
								<td valign="bottom"><b>Country: </b></td>
								<td valign="bottom">
									<select id="country">
			`;

			facebook_countries.forEach((e)=>{html += `<option value="`+e.key+`">`+e.name+`</option>`;});
			
			html +=`			
									</select>
								</td>
							</tr>

							<tr height="50">
								<td valign="bottom"><b>Ratio: </b></td>
								<td valign="bottom">
									<select id="ratio">
										<option value="0.01">1 %</option>
										<option value="0.02">2 %</option>
										<option value="0.03">3 %</option>
										<option value="0.04">4 %</option>
										<option value="0.05">5 %</option>
										<option value="0.06">6 %</option>
										<option value="0.07">7 %</option>
										<option value="0.08">8 %</option>
										<option value="0.09">9 %</option>
										<option value="0.10">10 %</option>
									</select>
								</td>
							</tr>

							<tr>
								<td></td>
								<td><span style="font-size:13px">* low ratio increases the quality of lookalike</span></td>
							</tr>
						</tbody>
					</table>
				</div>
			`;

			swal.fire({
				html: html,
				showConfirmButton: true,
				showCancelButton: true,
				confirmButtonText: "Create",
				cancelButtonText: "Cancel",
				preConfirm: ()=>{
					var name = jQuery("#name");

					if(name.val().length < 5){
						name.addClass("border-danger");
						jQuery("#name-danger").html("You must enter at least 5 characters");
						return false;
					}
				}
			}).then((result)=>{
				if(result.value){
					var name = jQuery("#name").val();
					var country = jQuery("#country").find("option:selected").val();
					var ratio = jQuery("#ratio").find("option:selected").val();
					var account_id = jQuery("#ad_account").find("option:selected").val();
					var token = jQuery("#ad_account").find("option:selected").data("token");

					create_lookalike_audience(audience_id, name, country, ratio, account_id, token);

					swal.fire({
						allowOutsideClick: false,
						allowEscapeKey: false,
						showConfirmButton: false,
						showCancelButton: false
					});
					swal.showLoading();
				}
			});

			jQuery("#name").on("keyup", function(){
				if(jQuery(this).val().length >= 5){
					jQuery(this).removeClass("border-danger");
					jQuery("#name-danger").html("");
				}
			});
		}
	});

	jQuery(".create_new_ad").on("click", function(){
		var audience_id = jQuery(this).data("id");
		var account_id = jQuery("#ad_account").val();

		if(!subscription){
			swal.fire({
				title: "You must be subscribed to use the features of this page.",
				showConfirmButton: true,
				showCancelButton: true,
				confirmButtonText: "I want to subscribe now",
				cancelButtonText: "Cancel"
			}).then((result)=>{
				if(result.value)
					window.open("https://app.wask.co?upgrade=true");
			});
		}
		else{
			window.open("https://app.wask.co/create-facebook-instagram-ad?custom_audience_id="+audience_id+"&account_id="+account_id);
		}
	});

	jQuery(".delete_audience").on("click", function(){
		var audience_id = jQuery(this).data("id");
		var token = jQuery("#ad_account").find("option:selected").data("token");

		swal.fire({
			title: "Your audience will be deleted! Do you want to countinue?",
			showConfirmButton: true,
			showCancelButton: true,
			confirmButtonText: "Delete",
			cancelButtonText: "Cancel"
		}).then((result)=>{
			if(result.value){
				delete_audience(audience_id, token);

				swal.fire({
					allowOutsideClick: false,
					allowEscapeKey: false,
					showConfirmButton: false,
					showCancelButton: false
				});
				swal.showLoading();
			}
		});
	});

	function create_lookalike_audience(audience_id, name, country, ratio, account_id, token){
		setTimeout(function(){
			jQuery.ajax({
				url: "<?php echo admin_url( 'admin-ajax.php' );?>",
				type: "post",
				data: {
					audience_id: audience_id,
					account_id: account_id,
					audience_name: name,
					country: country,
					ratio: ratio,
					token: token,
					action: "create_lookalike_audience"
				} ,
				success: function (response) {
					response = JSON.parse(response);

					if(typeof response.error !== "undefined"){
						swal.fire({
							icon: "error",
							title: response.error.message
						});
					}
					else if(response.success){
						swal.fire({
							icon: "success",
							title: "Your lookalike audience created successfully"
						}).then(()=>{
							window.location.reload();
						});
					}

					jQuery("#lookalike_button").removeAttr("disabled");
				},
				error: function(jqXHR, textStatus, errorThrown) {
					swal.fire({
						icon: "error",
						title: "Somethings went wrong"
					});
				}
			});
		}, 500);
	}

	function delete_audience(audience_id, token){
		setTimeout(function(){
			jQuery.ajax({
				url: "<?php echo admin_url( 'admin-ajax.php' );?>",
				type: "post",
				data: {
					audience_id: audience_id,
					token: token,
					action: "delete_audience"
				} ,
				success: function (response) {
					response = JSON.parse(response);

					if(typeof response.error !== "undefined"){
						swal.fire({
							icon: "error",
							title: response.error.message
						});
					}
					else if(response.success){
						swal.fire({
							icon: "success",
							title: "Your audience deleted successfully"
						}).then(()=>{
							window.location.reload();
						});
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
					swal.fire({
						icon: "error",
						title: "Somethings went wrong"
					});
				}
			});
		}, 500);
	}
</script>