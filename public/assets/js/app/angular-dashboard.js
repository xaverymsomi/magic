
var app = angular.module("mxdashboard.modal", ['angularUtils.directives.dirPagination']);

app.controller("AdminDashboardCtrl", function ($scope, $http) {
    $('#loader').css('display', '');

    var url = app_url + '/dashboard/fetch_dashboard_admin_data';
    fetchData(url);
    setInterval(function () {
        fetchData(url)
    }, 120000);

    function fetchData(url) {
        $http({
            method: 'GET',
            url: url
        }).then(function successCallback(response) {

            updateSuperDash(response);
        }, function errorCallback(response) {
            console.log('Fetch Super Data Error: ', response);
        });
    }
    let updateSuperDash = function (e) {
        let data = e.data;

        $scope.drawSummaryLineChart();
    }


    $scope.drawSummaryLineChart = function () {}

});

