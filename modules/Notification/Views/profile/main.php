<?php
/*
 * This file is part of the Mabrex package.
 * It is strictly a property of Rahisi Solution Ltd..
 *
 * (c) 2022
 *
 */
 ?>

<div class="panel panel-default" style="background-color: whitesmoke">
	<div class="panel-body">
		<h5 class="text-center" style="font-size: 2.0em; color: white; font-weight: 600; margin-top: 10px; padding: 10px; border-radius: 4px; background-color: {{ initial_tab_data.color }}">
			{{ initial_tab_data['State'] }}
		</h5>
		<div class="row" style="margin-top: 20px;">
			<div class="col-md-12 col-lg-12">
				<div class="panel panel-default">
					<div class="panel-body">
						<h5 class="text-center" style="font-size: 1.5em; color: green; text-transform: uppercase; font-weight: 600; opacity: 0.6;">
							 Notification Details
						</h5><hr />

						<div class="row">
							<div class="col-md-12">
								<div class="panel panel-default">
									<div class="panel-body">
										<h5 class="text-left" style="font-size: 1.2em; text-transform: uppercase; opacity: 0.7"> <i class="fa fa-id-badge" style="font-size: 1.3em; opacity: 0.6"></i> Notification Information</h5>
										<hr />

                                        <div class="row">
                                            <div class="col-md-6 col-lg-6">
                                                <h5 style="font-size: 1.35em; float: left; opacity: 0.6;">Notification Type</h5>
                                            </div>
                                            <div class="col-md-6 col-lg-6">
                                                <h5 style="font-size: 1.4em; color: {{ initial_tab_data['notification_type_color'] }}; font-weight: 600; float: right;">{{ initial_tab_data['Notification Type'] }}</h5>
                                            </div>
                                        </div>
                                        <hr style="margin: 0; opacity: 0.6;"/>

                                        <div class="row">
                                            <div class="col-md-6 col-lg-6">
                                                <h5 style="font-size: 1.35em; float: left; opacity: 0.6;">Message</h5>
                                            </div>
                                            <div class="col-md-6 col-lg-6">
                                                <h5 style="font-size: 1.4em; color: #292929; font-weight: 600; float: right;">{{ initial_tab_data['Message'] }}</h5>
                                            </div>
                                        </div>
                                        <hr style="margin: 0; opacity: 0.6;"/>

                                        <div class="row">
                                            <div class="col-md-6 col-lg-6">
                                                <h5 style="font-size: 1.35em; float: left; opacity: 0.6;">From Date</h5>
                                            </div>
                                            <div class="col-md-6 col-lg-6">
                                                <h5 style="font-size: 1.4em; font-weight: 600; float: right;">{{ initial_tab_data['From Date'] }}</h5>
                                            </div>
                                        </div>
                                        <hr style="margin: 0; opacity: 0.6;"/>

                                        <div class="row">
                                            <div class="col-md-6 col-lg-6">
                                                <h5 style="font-size: 1.35em; float: left; opacity: 0.6;">To Date</h5>
                                            </div>
                                            <div class="col-md-6 col-lg-6">
                                                <h5 style="font-size: 1.4em; font-weight: 600; float: right;">{{ initial_tab_data['To Date'] }}</h5>
                                            </div>
                                        </div>
                                        <hr style="margin: 0; opacity: 0.6;"/>

                                        <div class="row">
                                            <div class="col-md-6 col-lg-6">
                                                <h5 style="font-size: 1.35em; float: left; opacity: 0.6;">Added By</h5>
                                            </div>
                                            <div class="col-md-6 col-lg-6">
                                                <h5 style="font-size: 1.4em; font-weight: 600; float: right;">{{ initial_tab_data['Added By'] }}</h5>
                                            </div>
                                        </div>
                                        <hr style="margin: 0; opacity: 0.6;"/>

                                        <div class="row">
                                            <div class="col-md-6 col-lg-6">
                                                <h5 style="font-size: 1.35em; float: left; opacity: 0.6;">Added Date</h5>
                                            </div>
                                            <div class="col-md-6 col-lg-6">
                                                <h5 style="font-size: 1.4em; font-weight: 600; float: right;">{{ initial_tab_data['Added Date'] }}</h5>
                                            </div>
                                        </div>
                                        <hr style="margin: 0; opacity: 0.6;"/>

									</div>
								</div>
							</div>
						</div>

					</div>
				</div>
			</div>
		</div>
	</div>
</div>
