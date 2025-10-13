<?php
/*
 * This file is part of the Mabrex package.
 * It is strictly a property of Rahisi Solution Ltd.
 *
 * (c) 2023
 *
 */
?>

<div id="page-content">
	<div id="data_content"
	     data-form="<?php echo htmlspecialchars(json_encode($this->data, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"
	     data-dropdowns="<?php echo htmlspecialchars(json_encode($this->dropdowns, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"></div>
	<div id="display_content">
		<div class="row">
			<div class="col-sm-12">
				<div class="modal-header" style="background:linear-gradient(to right,#000000,#626262);color: white;">
					<button ng-click="cancel()" type="button" class="close" data-dismiss="modal"
					        style="color: white; font-size: 35px;">&times;
					</button>
					<h4 class="modal-title ocean text-capitalize">
						<i class="pe pe-7s-info pe-fw pe-va pe-2x"></i><?php echo $this->title ?></h4>
				</div>
				<div class="modal-body">
					<div class="col text-center">
						<br><br><br>
						<?php
						echo '<h1 class=""><i class="' . $this->icon . ' fa-3x" aria-hidden="true"></i><h1>';
						echo '<h3 class="error p-y-16">' . $this->msg . ' </h3>';
						echo '<h4 class="error-inverse">' . $this->sub . '</h4>';
						echo '<h5>' . date('d M Y @ H:m:s') . '</h5>';
						?>
					</div>
				</div>
			</div>

		</div>

	</div>
</div>