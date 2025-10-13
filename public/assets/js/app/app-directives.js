var app = angular.module("app");

app.directive('mabrexFilter', function () {
    return {
        scope: {
            mxCurrentPage: '=', // current active data page
            mxSelected: '=',
            mxLocation: '=',
            mxTitle: '=',
            mxCurrentLink: '=',
            mxPageSize: '=', // number of records per page
            mxSearchTerm: '=',
            mxTotalRecords: '=',
            mxTableColumns: '=',
            mxSortColumn: '=',
            mxSortOrder: '=',
            mxColumnLabel: '=',
            mxHasRange: '=',
            mxSearchRange: '=',
            mxFilterables: '=',
            mxFilterable: '=',
            mxFilterableLabel: '=',
            mxFilter: '=',
            mxFilterLabel: '=',
            mxFiltersCollection: '=',
        },
        template:
            '<div class="row" ng-controller="menuController" style="border-bottom: #ccc solid 1px; padding-bottom: 10px;">' +
            '<div class="col-md-4">' +
            '<div class="btn-toolbar"  title="">' +
            '<div class="btn-group">' +
            '<a href="#" class="btn btn-link btn-sm disabled">Records Per Page</a>' +
            '</div>' +
            '<div class="btn-group">' +
            '<a href="#" class="btn btn-default btn-sm" ng-disabled="mxSelected==25" ng-click="pageSize=25; getTable(mxLocation, mxTitle, mxCurrentLink, mxCurrentPage, 25, mxSearchTerm, mxSortColumn, mxSortOrder, mxFilterable, mxFilter)">25</a>' +
            '<a href="#" class="btn btn-default btn-sm" ng-disabled="mxSelected==50 || mxTotalRecords <= 25" ng-click="pageSize=50; getTable(mxLocation, mxTitle, mxCurrentLink, mxCurrentPage, 50, mxSearchTerm, mxSortColumn, mxSortOrder, mxFilterable, mxFilter)">50</a>' +
            '<a href="#" class="btn btn-default btn-sm" ng-disabled="mxSelected==75 || mxTotalRecords <= 50" ng-click="pageSize=75; getTable(mxLocation, mxTitle, mxCurrentLink, mxCurrentPage, 75, mxSearchTerm, mxSortColumn, mxSortOrder, mxFilterable, mxFilter)">75</a>' +
            '<a href="#" class="btn btn-default btn-sm" ng-disabled="mxSelected==100 || mxTotalRecords <= 75" ng-click="pageSize=100; getTable(mxLocation, mxTitle, mxCurrentLink, mxCurrentPage, 100, mxSearchTerm, mxSortColumn, mxSortOrder, mxFilterable, mxFilter)">100</a>' +
            '</div>' +
            '<div class="btn-group">' +
            '<div class="btn-group">' +
            '<a href="#" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">Order By ({{mxColumnLabel}}) <span class="caret"></span></a>' +
            '<ul class="dropdown-menu">' +
            '<li ng-repeat="n in mxTableColumns"><a href="#" ng-click="getTable(mxLocation, mxTitle, mxCurrentLink, mxCurrentPage, mxPageSize, mxSearchTerm, n.column, mxSortOrder, mxFilterable, mxFilter)">{{n.label}}</a></li>' +
            '</ul>' +
            '</div>' +
            '<div class="btn-group">' +
            '<a href="#" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">Direction ({{mxSortOrder}}) <span class="caret"></span></a>' +
            '<ul class="dropdown-menu">' +
            '<li><a href="#" ng-click="getTable(mxLocation, mxTitle, mxCurrentLink, mxCurrentPage, mxPageSize, mxSearchTerm, mxSortColumn, \'ASC\', mxFilterable, mxFilter)"><i class="fa fa-sort-alpha-asc"></i> Ascending</a></li>' +
            '<li><a href="#" ng-click="getTable(mxLocation, mxTitle, mxCurrentLink, mxCurrentPage, mxPageSize, mxSearchTerm, mxSortColumn, \'DESC\', mxFilterable, mxFilter)"><i class="fa fa-sort-alpha-desc"></i> Descending</a></li>' +
            '</ul>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '<div class="col-md-4">' +
            '<div class="btn-group">' +
            '<a href="#" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">Filter By ({{mxFilterableLabel}}) <span class="caret"></span></a>' +
            '<ul class="dropdown-menu">' +
            '<li ng-repeat="n in mxFilterables"><a href="#" ng-click="setFilterable(n)">{{n.label}}</a></li>' +
            '</ul>' +
            '</div>' +
            '<div class="btn-group">' +
            '<a href="#" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">With Value ({{mxFilterLabel}}) <span class="caret"></span></a>' +
            '<ul class="dropdown-menu">' +
            '<li ng-repeat="n in mxFiltersCollection[mxFilterable]"><a href="#" ng-click="getTable(mxLocation, mxTitle, mxCurrentLink, mxCurrentPage, mxPageSize, mxSearchTerm, mxSortColumn, mxSortOrder, mxFilterable, n.value)">{{n.label}}</a></li>' +
            '</ul>' +
            '</div>' +
            '</div>' +
            '<div class="col-md-4">' +

            '<div class="col-md-6" ng-if="typeof(mxHasRange) !== \'undefined\' && mxHasRange == true" ng-init="initiateSearchRange(mxSearchRange, mxHasRange)">' +
            '<div class="input-group input-group-sm">' +
            '<span class="input-group-addon">Date Range</span>' +
            '<input class="form-control" date-range-picker options="opts" ng-model="searchRange" />' +
            '</div>' +
            '</div>' +

            '<form ng-submit="loadPage(mxLocation, mxTitle, mxCurrentLink)">' +
            '<div ng-class="{\'col-md-6\': mxHasRange == true}" class="input-group" ng-init="searchTerm=mxSearchTerm">' +
            '<input ng-model="searchTerm" class="form-control input-sm" placeholder="Search records..." title="Enter criteria to search">' +
            '<span class="input-group-btn">' +
            '<button class="btn btn-default btn-sm" title="Search records"><i class="fa fa-search"></i> Search</button>' +
            '<button ng-if="searchTerm != \'\'" class="btn btn-danger btn-sm" ng-click="clearFilter(); loadPage(mxLocation, mxTitle, mxCurrentLink)" title="Clear filter"><i class="fa fa-remove"></i></button>' +
            '</span>' +
            '</div>' +
            '</form>' +
            '</div>' +
            '</div>'
    };
});

app.directive('mabrexPager', function () {
    return {
        scope: {
            mxCurrentPage: '=', // current active data page
            mxPages: '=', // total number of data pages
            mxPageButtons: '=', // number of buttons to display - default and minimum is 10
            mxFiltered: '=', // number of returned filtered records
            mxTotal: '=', // number of total records in the table
            mxPageLocation: '=', // page location - e.g /customer/index
            mxPageTitle: '=', // page title
            mxPageCurrentLink: '=', // page link id
            mxPageSize: '=', // number of records per page
            mxPageSearchTerm: '=', // search term
            mxReturned: '=', // number of returned records
            mxSortColumn: '=', // current sort column
            mxSortOrder: '=', // current sort direction
            mxHasRange: '=',
            mxSearchRange: '=', // current sort direction
            mxFilterables: '=',
            mxFilterable: '=',
            mxFilterableLabel: '=',
            mxFilter: '=',
            mxFilterLabel: '=',
            mxFiltersCollection: '=',
        },
        template:
            '<div class="row" style="border-top: #ccc solid 1px; padding-top: 10px;">' +
            '<div class="col-md-6">' +
            '<p ng-cloak><em>Showing record {{(mxCurrentPage - 1) * mxPageSize + 1}} to {{(mxCurrentPage - 1) * mxPageSize + mxReturned}} of {{mxFiltered}} - Page {{mxCurrentPage}} of {{mxPages}} pages</em></p>' +
            '</div>' +
            '<div class="col-md-6" ng-controller="menuController" ng-init="setPagesList(mxPages); initiateSearchRange(mxSearchRange,mxHasRange); ">' +
            '<ul class="pagination pagination-sm pull-right" style="margin-top: 0">' +
            '<li ng-if="mxCurrentPage > 1" class="{{mxCurrentPage == 1 ? \'disabled\' : \'\'}}"><a href="#" ng-click="getTable(mxPageLocation, mxPageTitle, mxPageCurrentLink, (mxCurrentPage - 2), mxPageSize, mxPageSearchTerm, mxSortColumn, mxSortOrder, mxFilterable, mxFilter)" title="Previous"><i class="fa fa-angle-double-left"></i></a></li>' +
            '<li ng-if="mxPages <= mxPageButtons" ng-repeat="n in pagesList" class="{{n == mxCurrentPage ? \'active\' : \'\'}}"><a href="#" ng-click="getTable(mxPageLocation, mxPageTitle, mxPageCurrentLink, (n-1), mxPageSize, mxPageSearchTerm, mxSortColumn, mxSortOrder, mxFilterable, mxFilter)">{{n}}</a></li>' +
            '<li ng-if="mxPages > mxPageButtons && mxCurrentPage <= 5" ng-repeat="n in [1,2,3,4,5]" class="{{n == mxCurrentPage ? \'active\' : \'\'}}"><a href="#" ng-click="getTable(mxPageLocation, mxPageTitle, mxPageCurrentLink, (n-1), mxPageSize, mxPageSearchTerm, mxSortColumn, mxSortOrder, mxFilterable, mxFilter)">{{n}}</a></li>' +
            '<li ng-if="mxPages > mxPageButtons && mxCurrentPage > 5" ng-repeat="n in [1,2,3]"><a href="#" ng-click="getTable(mxPageLocation, mxPageTitle, mxPageCurrentLink, (n - 1),mxPageSize, mxPageSearchTerm, mxSortColumn, mxSortOrder, mxFilterable, mxFilter)">{{n}}</a></li>' +
            '<li ng-if="mxPages > mxPageButtons && mxCurrentPage > 5"><a href="#" class="disabled">...</a></li>' +
            '<li ng-if="mxPages > mxPageButtons && mxCurrentPage > 5" ng-repeat="n in [1,0]" class="{{mxCurrentPage - n == mxCurrentPage ? \'active\' : \'\'}}"><a href="#" ng-click="getTable(mxPageLocation, mxPageTitle, mxPageCurrentLink, (mxCurrentPage - n - 1), mxPageSize, mxPageSearchTerm, mxSortColumn, mxSortOrder, mxFilterable, mxFilter)">{{mxCurrentPage - n}}</a></li>' +
            '<li ng-if="mxPages > mxPageButtons && (mxCurrentPage + 1) == mxPages"><a href="#" ng-click="getTable(mxPageLocation, mxPageTitle, mxPageCurrentLink, mxCurrentPage, mxPageSize, mxPageSearchTerm, mxSortColumn, mxSortOrder, mxFilterable, mxFilter)">{{mxCurrentPage + 1}}</a></li>' +
            '<li ng-if="mxPages > mxPageButtons && (mxCurrentPage + 2) < mxPages && mxCurrentPage >= 6"><a href="#" ng-click="getTable(mxPageLocation, mxPageTitle, mxPageCurrentLink, mxCurrentPage, mxPageSize, mxPageSearchTerm, mxSortColumn, mxSortOrder, mxFilterable, mxFilter)">{{mxCurrentPage + 1}}</a></li>' +
            '<li ng-if="mxPages > mxPageButtons && (mxCurrentPage + 2) < mxPages && mxCurrentPage < 6"><a href="#" ng-click="getTable(mxPageLocation, mxPageTitle, mxPageCurrentLink, 5, mxPageSize, mxPageSearchTerm, mxSortColumn, mxSortOrder, mxFilterable, mxFilter)">6</a></li>' +
            '<li ng-if="mxPages > mxPageButtons && (mxCurrentPage + 2) < mxPages && mxCurrentPage < 6"><a href="#" ng-click="getTable(mxPageLocation, mxPageTitle, mxPageCurrentLink, 6, mxPageSize, mxPageSearchTerm, mxSortColumn, mxSortOrder, mxFilterable, mxFilter)">7</a></li>' +
            '<li ng-if="mxPages > mxPageButtons && (mxCurrentPage + 2) < mxPages"><a href="#" class="disabled">...</a></li>' +
            '<li ng-if="mxPages > mxPageButtons && (mxCurrentPage + 2) <= mxPages" ng-repeat="n in [1,0]"><a href="#" ng-click="getTable(mxPageLocation, mxPageTitle, mxPageCurrentLink, (mxPages - n - 1), mxPageSize, mxPageSearchTerm, mxSortColumn, mxSortOrder, mxFilterable, mxFilter)">{{mxPages - n}}</a></li>' +
            '<li ng-if="mxCurrentPage < mxPages" class="{{mxCurrentPage == mxPages ? \'disabled\' : \'\'}}"><a href="#" ng-click="getTable(mxPageLocation, mxPageTitle, mxPageCurrentLink, mxCurrentPage, mxPageSize, mxPageSearchTerm, mxSortColumn, mxSortOrder, mxFilterable, mxFilter)" title="Next"><i class="fa fa-angle-double-right"></i></a></li>' +
            '</ul>' +
            '</div>' +
            '</div>'
    };
});


app.directive('validFile', function () {
    return {
        require: 'ngModel',
        link: function (scope, el, attrs, ngModel) {
            //change event is fired when file is selected
            el.bind('change', function () {
                scope.$apply(function () {
                    ngModel.$setViewValue(el.val());
                    ngModel.$render();
                });
            });
        }
    }
});
