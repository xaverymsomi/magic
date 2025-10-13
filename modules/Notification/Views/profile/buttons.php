<?php
/*
 * This file is part of the Mabrex package.
 * It is strictly a property of Rahisi Solution Ltd..
 *
 * (c) 2022
 *
 */
 ?>

<?php if (count($this->buttons) > 0) { ?>
	<div class="text-center">
		<div class="profile-buttons-group">
			<?php
			foreach ($this->buttons as $button) {
				$params = '';
				echo '<button ng-disabled="' . $button['disabled'] . '" class="' . $button['cssclass'] . '" ng-click="'.$button['function'].'(' . $button['controller'] . ',\'' . $button['action'] . '\',[' . $button['params'] . '])" ng-show="' . $button['show'] . '">' . $button['label'] . '</button>';
			}
			?>
		</div>
	</div>
<?php } ?>