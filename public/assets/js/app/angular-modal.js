var app = angular.module("modal", ['ui.bootstrap']);
app.controller("modalFormCtrl", ['$scope', '$modal', '$log', function ($scope, $modal, $log) {
        $scope.form = {};
        $scope.url = "";

        $scope.showForm = function (url) {
            $scope.url = url;
            var modalInstance = $modal.open({
                templateUrl: "/views/" + url + "create.php/", //url where the form is
                controller: modalFormCtrl,
                windowClass: 'mx-modal-form',
                scope: $scope
            });
        };


        $scope.showProfile = function (url, id) {
      
            $scope.url = url;
            var modalInstancePro = $modal.open({
                templateUrl: "/" + url + "/profile/" + id, //url where the form is
                controller: modalProfileCtrl,
                windowClass: 'mx-modal-form',
                scope: $scope
            });
        };
    }]);

var modalFormCtrl = function ($scope, $modalInstance, $http, $window) {
    $scope.saveForm = function () {
        $http({
            method: 'POST',
            url: '/' + $scope. url + "save/",
            data: $scope.form, //forms user object
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        })
                .success(function (data) {
                    if (data.errors) {

                    } else {
                        $modalInstance.dismiss('cancel');
                        //$window.location.href = '' + $scope.url + "index/";
                        //console.log(data);
                    }
                });
    };


    $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
    };


};

var modalProfileCtrl = function ($scope, $modalInstance, $http, $window) {
    $scope.showForm = function (url) {
            $scope.url = url;
            var modalInstance = $modal.open({
                templateUrl: "/views/" + url + "create.php/", //url where the form is
                controller: modalFormCtrl,
                windowClass: 'mx-modal-form',
                scope: $scope
            });
        };
};