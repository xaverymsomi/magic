<?php
/*
 * This file is part of the Mabrex package.
 * It is strictly a property of Rahisi Solution Ltd..
 *
 * (c) 2022
 *
 */
 ?>

<div id="page-content">
	<div id="data_content"
		data-initial="<?php echo htmlspecialchars(json_encode($this->data, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"
		data-tabs="<?php echo htmlspecialchars(json_encode($this->tabs, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"
		data-hidden-columns="<?php echo htmlspecialchars(json_encode($this->hidden_columns, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"
		data-extras="<?php echo htmlspecialchars(json_encode($this->extras, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"
	>
	</div>
	<div id="display_content">
		<div ng-controller="profileController">
			<div class="modal-header" style="background-color: silver; color: black;">
				<button ng-click="cancel()" type="button" class="close" data-dismiss="modal" style="color: white; font-size: 35px;">&times;</button>
				<h4 class="modal-title ocean text-capitalize"><i class="pe pe-7s-id pe-fw pe-va pe-2x"></i> <?php echo $this->title ?></h4>
			</div>
			<div class="modal-body">
				<div>
					<ul class="nav nav-tabs">
						<li class="active">
							<a data-toggle="tab" href="#Shehia" ng-click="getProfileRecords('Shehia', '<?php echo $this->data["row_id"] ?>')">Shehia</a>
						</li>
						<li ng-repeat="tab in tabs">
							<a data-toggle="tab" href="#{{tab}}" ng-click="getAssociatedRecords(tab, initial_tab_data.row_id)">{{tab.replace('_', ' ')}}</a>
						</li>
					</ul>
				</div>
				<div class="tab-content">
					<div class="row" style="margin-bottom: 15px;"> </div>
					<div class="row" style="margin-bottom: 15px;">
						<?php include 'buttons.php' ?>
					</div>
					<div class="tab-pane fade active in" id="Shehia">
						<div class="row profile_section">
							<?php include 'main.php' ?>
						</div>
					</div>
					<div  ng-repeat="tab in tabs" class="tab-pane fade" id="{{tab}}">
						<div class="associated_section">
							<!-- ASSOCIATED RECORDS TO BE LOADED HERE -->
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button class="btn btn-warning" ng-click="cancel()">Cancel</button>
			</div>
		</div>
	</div>
</div>
