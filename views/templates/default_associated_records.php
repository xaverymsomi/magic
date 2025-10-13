<div class="table scrolled-div" style="height: 20vh;">
    <table class="table table-striped table-condensed table-hover table-bordered">
        <thead>
        <tr class="bottom-border-color-orange primary">
            <th colspan="{{table_headers.length}}" style="font-size: 12pt; color:#f26e09; text-transform: uppercase;">
                <?= $this->caller ?>
            </th>
        </tr>
        </thead>
        <thead class="thead-red" ng-if="associated_records.length > 0">
        <tr>
            <th ng-repeat="header in table_headers track by $index" ng-if="hiddens.indexOf(header) < 0">{{header}}</th>
            <th ng-if="associated_actions.length > 0">Actions</th>
        </tr>
        </thead>
        <tbody>
        <tr ng-repeat="record in associated_records">
            <td ng-repeat="(key, value) in record" ng-if="hiddens.indexOf(key) < 0">

                <div ng-switch="formatters[key] != undefined">
                    <div ng-switch-when="true">
                        <div ng-switch="formatters[key]['format']">
                            <div ng-switch-when="number">
                                <strong
                                    ng-style="formatters[key]['color'] != undefined && {'color' : formatters[key]['color']}">
                                    {{value | number}}
                                </strong>
                            </div>
                            <div ng-switch-when="label">
                            <span class="label"
                                  ng-style="
                                  formatters[key]['labels'] == undefined && {'background' : 'black'} ||
                                  formatters[key]['labels'] != undefined && {'background' : formatters[key]['labels'][value]}
                                        ">{{value}}
                            </span>
                            </div>
                            <div ng-switch-when="date">
                                {{reFormatDate(value) | date: formatters[key]['type'] }}
                            </div>
                            <div ng-switch-default>
                                <strong
                                    ng-style="formatters[key]['color'] != undefined && {'color' : formatters[key]['color']}">
                                    {{value}}
                                </strong>
                            </div>
                        </div>
                    </div>

                    <div ng-switch-default>
                        <span>{{value}}</span>
                    </div>

                </div>

            </td>
            <td ng-if="associated_actions.length > 0">
                <?php
                foreach ($this->actions as $button) {
                    ?>
                    <a
                        href="#"
                        ng-click="<?= $button['function'] ?>"
                        class="<?= $button['cssclass'] ?> associated_records_action"
                        ng-class="{disabledLink: <?= $button['disabled'] ?>}">
                        <i class="<?= $button['icon'] ?>"></i></a>
                    <?php
                }
                ?>
            </td>
        </tr>
        </tbody>
    </table>
</div>