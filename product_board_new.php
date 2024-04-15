<?php

/**
 * New User Administration Screen.
 *
 * @package WordPress
 * @subpackage Administration
 */

/** WordPress Administration Bootstrap */
require_once(dirname(__FILE__) . '/admin.php');

/**
 * Filters whether to enable user auto-complete for non-super admins in Multisite.
 *
 * @since 3.4.0
 *
 * @param bool $enable Whether to enable auto-complete for non-super admins. Default false.
 */





 


require_once(ABSPATH . 'wp-admin/admin-header.php');

$user_id = (int) $user_id;
$user_id = get_current_user_id();
$current_user = wp_get_current_user();
$current_user;
$login =  (array) $current_user;
$w = (array) $login['data'];
$current_loggedin_user = $w['user_login'];

include('connection.php');

include('path_config.php');

$user_type_query = mysqli_query($conn, "SELECT * FROM wp_users where ID='$user_id'");
while ($user_row = mysqli_fetch_assoc($user_type_query)) {
	$user_type = $user_row['user_type'];
}

if ($user_type == "exhibitor" && $user_id != '') {

	$get = mysqli_query($conn, "SELECT event_id,booth_id FROM exhibitors where user_id='$user_id'");

	while ($row1 = mysqli_fetch_assoc($get)) {
		$event_id = $row1['event_id'];
		$booth_id = $row1['booth_id'];

		$event = mysqli_query($conn, "SELECT event_id,event_title FROM event_management ");
		$option = $exhibitorEventId = '';

		while ($row = mysqli_fetch_assoc($event)) {
			$exhibitorEventId = $row['event_id'];
			$option .= '<option value = "' . $row['event_id'] . '">' . $row['event_title'] . '</option>';
		}
	}
} else {
	$get = mysqli_query($conn, "SELECT event_id,event_title FROM event_management where event_status='Live' ORDER BY event_id ASC");
	$option = '';
	while ($row1 = mysqli_fetch_assoc($get)) {
		$option .= '<option value = "' . $row1['event_id'] . '">' . $row1['event_title'] . '</option>';
	}
}
$booth1 = "SELECT * FROM booth_config";
$booth_result1 = mysqli_query($conn, $booth1);
while ($fetch = mysqli_fetch_array($booth_result1)) {

	$event_id = $fetch['event_id'];

	$booth = "SELECT * FROM event_management where event_id='$event_id'";
	$booth_result = mysqli_query($conn, $booth);
	while ($fetch = mysqli_fetch_array($booth_result)) {

		$event_title = $fetch['event_title'];
	}
}
$total_booths = mysqli_num_rows($booth_result1);

$get_currency = mysqli_query($conn, "SELECT * from config_currency ");
$option1 = '';
while ($row1 = mysqli_fetch_assoc($get_currency)) {
	$option1 .= '<option value = "' . $row1['currency_code'] . '">' . $row1['currency_code'] . ' - ' . $row1['currency'] . '</option>';
}

//YOL
global $wpdb;
$getExhibitor = $wpdb->get_row($wpdb->prepare("SELECT * FROM exhibitors where user_id='$user_id'"));
$exhibitorId = $getExhibitor->exhibitor_id;
$productImportBoothId = $getExhibitor->booth_id;

$getBoothProductUploadLimit = $wpdb->get_row("SELECT product_allowed FROM booths WHERE booth_id='$productImportBoothId'");
$booth_product_allowed = $getBoothProductUploadLimit->product_allowed;

//get current count
$getCount = $wpdb->get_results("SELECT * FROM product_board WHERE booth_id='$productImportBoothId'");
$product_count = $wpdb->num_rows;

$upload_flag = "";
// if($product_count>=$booth_product_allowed){
// 	$upload_flag="limit_reached";
// }	 //YOL	
?>
<!doctype html>
<html>

<head>
	<link rel="stylesheet" href="../wp-admin/datatable/css/style.css">
	<link rel="stylesheet" href="../wp-admin/datatable/css/hexafair-style.css">
	<link href="css/bootstrap.min.css" rel="stylesheet">
	<script src="<?= PLUGIN_CONTENTBOX_ASSETS_URL; ?>js/jquery.min.js"></script>
	<link href="<?= PLUGIN_DIGITALCARD_ASSETS_URL; ?>css/custom.css" rel="stylesheet">

	<style>
		.import_btn {
			float: right;
			background-color: #ffb300;
			color: #000;
			text-decoration: none;
			padding: 5px 10px;
			border-radius: 5px;
			font-weight: bold;
			margin-top: 5px;
		}

		.top-right-buttons-part {
			display: flex;
			float: right;
			gap: 5px;
		}

		.card {
			min-width: unset !important;
			max-width: unset !important;
		}
	</style>
</head>

<body>
	<div id="" aria-label="Main content"> <!-- YOL -->
		<h1 class="wp-heading-inline">Add New Product
			<div class="top-right-buttons-part">
				<a href="product_import_csv.php" class="import_btn">Product Import</a>
				<a href="admin.php?page=contents-management" class="back-btn mt-auto">Back</a>
			</div>
		</h1>
	</div>
	<?php if ($upload_flag == "limit_reached") { //YOL
	?>
		<div class="container main-cont-prop">
			<div class="col-md-12">
				<div class="col-md-12">
					<center><span style="color: red;">Product addition Limit reached for this Exhibitor.</span></center>
				</div>
			</div>
		</div>
	<?php } else if ($upload_flag == "") {  //YOL
	?>

		<div class="tabcontent " style="padding:5px;">
			<div class="col-md-11 reg-nn-frm" id="exhi-reg-new">
				<div class="card ">
					<form action="product_board_upload.php" enctype="multipart/form-data" method="post" id="myForm">
						<div class="row">
							<div class="col-sm-6">
								<div class="form-group mb-3">
									<label class="mb-2">
										Title <span class="mandatoryfield"></span>
									</label>
									<input class="form-control" name="product_title" id="product_title" />
								    	<span class="error-message" id="title-error"></span>
								    
								</div>
							</div>
						</div>

						<!-- <div class="row"> -->
						<!-- <div class="row-lg-9"> -->
						<div class="form-group mb-3">
							<label class="mb-2">Description
								<span class="mandatoryfield"></span><span class="smallwarning"> (Maximum 5000 characters allowed)</span>

							</label>
							<!-- <textarea class="form-control " name="product_description"  id="product_description" maxlength="500" style="width:500px;margin-top: 0px; margin-bottom: 0px; height: 76px;"></textarea> -->
							<textarea id="asset_desc" name="product_description" class="form-control"></textarea>
							<span class="error-message" id="description-error"></span>
							<p style="color: rgb(191, 23, 19);" id="error-desc"></p>

						</div>
						<!-- </div> -->

						<!-- </div> -->


						<div class="row">
							<div class="col-sm-6">
								<div class="form-group mb-3">
									<label class="mb-2">Product Image <span class="mandatoryfield"></span><span style="color: rgb(191, 23, 19);" id="error-message" CLASS="pl-2  "></span></label>
									<input class="form-control" id="product_image" accept="image/*" name="product_image" type="file" onchange="validateFile(this)" />
									<span class="error-message" id="image-error"></span>
									<p class="smallwarning">*Allowed File Types - jpeg,jpg,png,gif,bmp,tif,tiff,raw,webp
										<br>*Maximum File Size Allowed - 20MB
									</p>
								</div>
							</div>
							<div class="col-sm-6">
								<div class="form-group mb-3">
									<label class="mb-2"> 3D Model<span style="color: rgb(191, 23, 19);" id="error-logo-message" CLASS="pl-2  "></span></label>
									<input class="form-control" id="product_3d_model" onchange="validateFile(this)" name="product_3d_model" type="file" />
							       <span class="error-message" id="3d-model-error"></span>
									
									<p class="smallwarning">*Allowed File Types - glb
										<br>*Maximum File Size Allowed - 20MB
									</p>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-sm-6">
								<div class="form-group mb-3">
									<label class="mb-2">Buy Now URL</label>
									<input class="form-control " name="buynowurl" id="buynowurl" />
								</div>
							</div>



							<div class="col-sm-6">
								<div class="form-group mb-3">
									<label class="mb-2">
										Status
									</label>

									<select required id="data" name="status">
										<option value="active" selected="selected">Active</option>
										<option value="inactive">Inactive</option>
									</select>

								</div>
							</div>
						</div><br>


						<div class="input-group mb-3">
							<div class="form-group mb-3">
								<label class="mb-2">Price </label>
								<div class="currency">
									<select id="currency_code" name="currency_code">
									<option value="" selected disabled>Select Currency</option>
									<?php echo $option1; ?>
									</select>
									<input class="form-control " name="price" id="price" placeholder="Enter the amount" />
								</div>
							</div>

							<div class="col-sm-6">
								<div class="form-group mb-3" id="sku">
									<label class="mb-2"> SKU</label>
									<input class="form-control " name="product_sku" id="product_sku" />
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col-sm-12">
								<div class=" submit-button"> <button class="btn bg-violet" name="submit" type="submit" id="submit" value="Submit">Submit</button></div>
					</form>
				</div>

			</div>
		</div>	
		</div>
		</div>
		</div>
	<?php
	}  ?>

	<style type="text/css">
		.reg-nn-frm {
			background-color: #fff;
			padding: 25px 25px;
			margin: 0 44px;
			display: contents;
		}

		.bg-violet {
			background-color: #873CFF;
			color: #fff;
		}

		.mandatoryfield {
			content: "*";
			color: red
		}

		input[type="file"]::file-selector-button {
			border-radius: 4px;
			cursor: pointer;
			background-color: #873CFF;
			border: 1px solid rgba(0, 0, 0, 0.16);
			box-shadow: 0px 1px 0px rgba(0, 0, 0, 0.05);
			transition: background-color 200ms;
			color: #fff;
			;
		}

		input[type="file"] {
			padding-left: 12px !important;
		}

		/* file upload button active state */
		input[type="file"]::file-selector-button:active {
			background-color: #6a3fc7;
		}

		input {
			width: 80% !important;
		}

		#data {
			width: 80% !important;
		}

		#currency_code {
			width: 30%;
			/* min-width: 0px; */
		}

		#price {
			width: 58% !important;

		}

		.currency {
			display: flex;
		}

		#sku {
			margin-left: 70px;
			width: 98% !important;
		}

		#descrip {
			height: 50px;
		}
	</style>
	<script type="text/javascript">
		$('document').ready(function() {
			$('#submit').on("click", function() {

				var title = document.getElementById("product_title").value.trim();
				var description = document.getElementById("asset_desc").value.trim();
				var productImage = document.getElementById("product_image").value.trim();
				var product3dmodel = document.getElementById("product_3d_model").value.trim();
				var titleError = document.getElementById("title-error");
				var descriptionError = document.getElementById("description-error");
				var productImageError = document.getElementById("image-error");
				var product3dmodelError = document.getElementById("3d-model-error");

				// Reset error messages
				titleError.textContent = "";
				descriptionError.textContent = "";
				productImageError.textContent = "";
				product3dmodelError.textContent = "";

				var isValid = true;

				if (title === "") {
					titleError.textContent = "Please enter a title";
					isValid = false;
				}

				if (description === "") {
					descriptionError.textContent = "Please enter a description";
					isValid = false;
				} else if (description.length > 5000) {
					descriptionError.textContent = "Maximum 5000 characters only allowed....!";
					isValid = false;
				}

				if (productImage === "") {
					productImageError.textContent = "Please select a product image";
					isValid = false;
				} else if (productImage.size > 20 * 1024 * 1024) {
					productImageError.textContent = "Maximum file size is allowed 20MB";
					isValid = false;
				}

				
				if (product3dmodel.size > 15 * 1024 * 1024) {
                 product3dmodelError.textContent = "Maximum file size allowed is 20MB";
                 isValid = false;
                    }


				return isValid;


				ckeditor = $("#cke_asset_desc iframe").contents().find("body");
				description = $("#cke_asset_desc iframe").contents().find("body").text();
				if (description.length > 5000) {
					// const errmsg = "Description length should not be more than 5000 characters.Description length is  " + description.length;
					// console.log('len: ' + description.length);
					$('#error-desc').text(errmsg);
					return false;
				}
				let titleid = $('#title').val();
				if (description.length == 0) {
					const errmsg = " Description is required....!";
					// console.log('len: ' + cke_text.length);
					$('#error-desc').text(errmsg);
					return false;
				}

				var fileName = name.files[0].name;

				var size = name.files[0].size;

				var ext = fileName.split('.').pop().toLowerCase();

				//To check document type

				var image_type = new Array();

				var str = "<?php echo $image_str; ?>";

				image_type = str.split(',');

				var image_size = <?php echo $image_size ?>;

				if ($.inArray(ext, image_type) == -1) {
					$("span.error1").remove();

					$("span.error").remove();

					$('#product_image').after('<span class="error" style="color:red">Invalid Product image Format! Product image Format Must Be jpg,jpeg,png.</span>');

					return false;
				}

				//To check document size

				if (size > image_size) {

					$("span.error1").remove();


					$("span.error").remove();

					$('#product_image').after('<span class="error" style="color:red">Maximum Product image Size Limit is 1MB.</span>');

					return false;
				}




				var file_exist_error = $('#ajaxerror').text();
				if (file_exist_error == "Sorry...product already exist in this booth") {
					return result;
				}
			});
			if ($("textarea#asset_desc").length > 0) {
				CKEDITOR.replace('asset_desc', {
					allowedContent: true,
					basicEntities: false,
					width: '100%',
					height: '20rem',
					removePlugins: 'about',
				});
				CKEDITOR.dtd.$removeEmpty['i'] = false;
				CKEDITOR.instances['asset_desc'].on('change', function() {
					CKEDITOR.instances['asset_desc'].updateElement()
				});

			}


			//descryption above
		});
	</script>
	<script src="../wp-admin/ckeditor-basic/ckeditor.js"></script>
</body>

</html>

<?php
include(ABSPATH . 'wp-admin/admin-footer.php');
