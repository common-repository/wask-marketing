<?php 
	require_once(plugin_dir_path(__FILE__)."../include/activation.php");
	require_once(plugin_dir_path(__FILE__)."../include/user.php");
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
				<span style="position: absolute;" class="dashicons dashicons-megaphone"></span>
				<h2 style="padding-left:30px">Create Target Smart Audience</h2>
				<br>
				<table>
					<tbody>
						<tr height="50">
							<td>
								<label>Facebook Ad Account: </label>
							</td>
							<td>
								<select name="ad_account" id="ad_account">
									<?php 
										if(isset($facebook_act->facebook_accounts)){
											foreach($facebook_act->facebook_accounts as $account)
												echo "<option value='".$account->id."' data-token='".$account->token."'>".$account->name."</option>";
										}
									?>
								</select>
							</td>
						</tr>

						<tr height="50">
							<td>
								<label>Target Audience Name: </label>
							</td>
							<td>
								<input type="text" name="audience_name" id="audience_name" placeholder="e-Commerce Audience">
								<span style="color:red"></span>
							</td>
						</tr>

						<tr height="50">
							<td></td>
							<td>
								<input type="button" name="custom_button" id="custom_button" class="button button-primary" style="width: 250px" value="CREATE SMART AUDIENCE">
							</td>
						</tr>
					</tbody>
				</table>
				
				<div id='status'></div>
			</section>
		<?php }?>

		<hr>
		<div style="width: 800px">
			<h2>FAQ</h2>
			<h4>What can you do with WASK?</h4>
			<p>With WASK attachment, you can create target audience similar to your customers by using the e-mails of customers who are currently on your site or, you can create target audience to your current customers to remarketing.</p>

			<h4>Why is the target audience important ?</h4>
			<p>Metrics such as age, gender, and interests of your customers that sell from your site is so important. With WASK attachment, you can create the audience most interested in your products and create new  advertisements.</p>

			<h4>How WASK do this?</h4>
			<p>WASK attachment finds your audience on your site and thanks to its smart algorithms and create target audience quickly.</p>

			<h4>How can I create advertisement to target audience ?</h4>
			<p>You can Show your Facebook and Instagram advertisements to created target audience. You can create target audience with WASK attachment and you can advertise to target audience with using WASK software.</p>
		</div>
	</div>
</div>

<script>
	const subscription = <?php echo $subscription ?>;

	jQuery("#custom_button").on("click", function(){
		var account = jQuery("#ad_account").find("option:selected");
		var audience_name = jQuery("#audience_name");

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
		else if(account.length == 0){
			jQuery("#ad_account").addClass("border-danger");
		}
		else if(audience_name.val().length < 5){
			audience_name.addClass("border-danger");
			audience_name.parent().children("span").html("You must enter at least 5 characters");
		}
		else{
			jQuery(this).attr("disabled", "disabled");

			jQuery("#status").html("<br><br><div class='loader'></div>&nbsp;&nbsp;&nbsp;<b>Creating your Audience</b>.");

			setTimeout(function(){
				jQuery.ajax({
			        url: "<?php echo admin_url( 'admin-ajax.php' );?>",
			        type: "post",
			        data: {
			        	id: account.val(),
			        	audience_name: audience_name.val(),
			        	token: account.data("token"),
			        	action: "create_custom_audience"
			        } ,
			        success: function (response) {
			        	response = JSON.parse(response);

			        	if(typeof response.error !== "undefined"){
			        		jQuery("#status").html("<br><br><span style='color:red;'><b>"+response.error.message+"</b></span>");
			        	}
			        	else if(typeof response.tos !== "undefined"){
			        		jQuery("#status").html("<br><br><span style='color:red;'><b>You must accept terms of service.</b></span> <a href='https://business.facebook.com/ads/manage/customaudiences/tos/?act="+account.val().replace("act_","")+"' target='_blank'>Click to accept the terms of service</a>");
			        	}
			        	else if(response.success){
			        		jQuery("#status").html("<br><br><span style='color:green;'><b>Your custom audience created successfully.</b></span>");

			        		setTimeout(function(){
			        			window.location.href = location.protocol + '//' + location.host + location.pathname + "?page=audience_list";
			        		}, 2000);
			        	}

			           	jQuery("#custom_button").removeAttr("disabled");
			        },
			        error: function(jqXHR, textStatus, errorThrown) {
			           jQuery("#status").html("<br><br><span style='color:red;'><b>Somethings went wrong.</b></span>");
			           jQuery("#custom_button").removeAttr("disabled");
			        }
			    });
			}, 500);
		}
	});

	jQuery("#audience_name").on("keyup", function(){
		if(jQuery(this).val().length >= 5){
			jQuery(this).removeClass("border-danger");
			jQuery(this).parent().children("span").html("");
		}
	});
</script>
