(function(){
    var isNum = /^\d*$/;
    var isDate = /^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|1[0-9]|2[0-9]|3[0-1])$/;
    //消息控件
    Messenger.options = {extraClasses: 'messenger-fixed messenger-on-bottom', theme: 'flat'}
    //AngularJS模块
    var LAF = angular.module("LAF", ["ngRoute", "ngCookies"]);
    //自定义markdown指令
    LAF.directive('markdown', function() {
        return {
            restrict: 'EA',
            require: '?ngModel',
            link: function(scope, element, attrs, ngModel) {
                scope.$watch((function() {
                    return ngModel.$modelValue;
                }), function(newValue) {
                    element.html(marked(newValue || '', {
                        sanitize: true
                    }));
                });
            }
        };
    });
    //注入配置
    LAF.config(function($routeProvider, $locationProvider) {
        $routeProvider.
        when("/edit", {controller: edit, templateUrl: "views/edit.html?20150117"}).
        when("/list", {controller: list, templateUrl: "views/list.html?20150117"}).
        when("/user", {controller: user, templateUrl: "views/user.html?20150117"}).
        when("/details", {controller: details, templateUrl: "views/details.html?20150117-2"}).
        otherwise({redirectTo: "/list"});
    });
    //主控
    var mainCtrl = function($scope, $rootScope, $location, $cookies) {
        $rootScope.loading = false;
        //用于记录返回上级应该返回到哪个页面
        $scope.$watch(function(){
            return $location.url();
        }, function(newUrl, oldUrl){
            //记录上一级地址
            if ($rootScope.lastPage == $location.url()) {
                $rootScope.lastPage = '/list';
            } else if($rootScope.lastUrl) {
                var length = $rootScope.lastUrl.indexOf('?') > 0 ? $rootScope.lastUrl.indexOf('?'): $rootScope.lastUrl.length;
                var path = $rootScope.lastUrl.substr(0, length)
                if (path != $location.path()) {
                    $rootScope.lastPage = $rootScope.lastUrl;
                }
            } else $rootScope.lastPage = oldUrl;
            //当前地址等于上个页面地址 将上个页面地址改为首页地址
            if ($rootScope.lastPage == $location.url()) {
                $rootScope.lastPage = '/list';
            }
            $rootScope.lastUrl = $location.url();
            //从COOKIES中获取权限
            $rootScope.rank = $cookies.rank || 0;
        });
    }
    //添加
    var edit = function($scope, $rootScope, $http, $location) {
        //semantic的checkbox 声明语句
        $('.ui.radio.checkbox').checkbox();
        var id = $location.search().id || '';
        if (id != '') {
            //从服务器获取数据
            params = {fun: 'details', id: id};
            $rootScope.loading = true;
            $http.get('index.php', {
                params: params
            }).success(function(data) {
                if (data.code == 0) {
                    for (item in data.data) {
                        $scope[item] = data.data[item];
                    }
                    $scope.img = $scope.picture;
                    $scope.picture = '';
                } else {
                    //错误弹窗
                    Messenger().post({
                        message: data.msg || '服务器错误!',
                        showCloseButton: true
                    });
                }
                $rootScope.loading = false;
            });
        } else {
            $scope.type = 2;
            $scope.title = $scope.date = $scope.where = $scope.linkmen = '';
            $scope.studentId = $scope.phone = $scope.qq = $scope.details = '';
        }
        //提交
        $scope.addSubmit = function() {
            var hasError = $scope.addDisabled = false;
            $scope.titleError = $scope.dateError = false;
            $scope.linkmenError = $scope.phoneError = $scope.qqError = false;
            $scope.date = $("input[name='date']").val();
            //判断类型
            if (!$scope.type in [1,2,3]) {
                hasError = $scope.typeError = true;
            }
            //判断标题
            if (($scope.title.length == 0) || ($scope.title.length > 30)) {
                hasError = $scope.titleError = true;
            }
            //判断日期
            if (($scope.date.length == 0) || (!isDate.test($scope.date))) {
                hasError = $scope.dateError = true;
            }
            //判断来源
            if (($scope.where.length == 0) || ($scope.where.length > 30)) {
                hasError = $scope.whereError = true;
            }
            //判断联系人
            if (($scope.linkmen.length == 0) || ($scope.linkmen.length > 30)) {
                hasError = $scope.linkmenError = true;
            }
            //判断学号
            if ($scope.studentId && (($scope.studentId.length < 6) || ($scope.studentId.length > 15))) {
                hasError = $scope.studentIdError = true;
            }
            //判断电话
            if ($scope.phone && (($scope.phone.length != 11) || !isNum.test($scope.phone))) {
                hasError = $scope.phoneError = true;
            }
            //判断QQ
            if ($scope.qq && (($scope.qq.length < 5) || ($scope.qq.length > 11) || !isNum.test($scope.qq))) {
                hasError = $scope.qqError = true;
            }
            //判断电话与QQ
            if ($scope.type != 1 && !$scope.phone && !$scope.qq) {
                hasError = $scope.contactError = true;
            }
            //提交
            if (!hasError && !$scope.addDisabled) {
                $scope.addDisabled = true;
                $('#editForm').submit();
            }
        }
    }
    //列表
    var list = function($scope, $rootScope, $http, $location) {
        $rootScope.loading = true;
        var params = $location.search();
        params.fun = 'list';
        $scope.type = params.type? params.type: 0;
        $scope.key = $location.search().key || "";
        $("input[name='key']").val($scope.key);
        //获取页面内容
        $http.get('index.php', {
            params:params
        }).success(function(data) {
            if (data.code == 0) {
                $scope.data = data.data;
                $scope.info = data.info;
            } else {
                //错误弹窗
                Messenger().post({
                    message: data.msg || '服务器错误!',
                    showCloseButton: true
                });
            }
            $rootScope.loading = false;
        });
        //搜索
        $scope.listSubmit = function() {
            var key = $("input[name='key']").val();
            $location.search({
                fun: 'list',
                key: key
            });
            return false;
        }
    }
    //用户
    var user = function($scope, $rootScope, $http, $location) {
        if (!$rootScope.rank) {
            $location.path("/list");
        }
        $rootScope.loading = true;
        var params = $location.search();
        params.fun = 'user';
        params.user = true;
        $scope.type = params.type? params.type: 0;
        //获取用户发表的内容
        $http.get('index.php', {
            params: params
        }).success(function(data) {
            if (data.code == 0) {
                $scope.data = data.data;
                $scope.info = data.info;
            } else {
                //错误弹窗
                Messenger().post({
                    message: data.msg || '服务器错误!',
                    showCloseButton: true
                });
            }
            $rootScope.loading = false;
        });
    }
    //详情
    var details = function($scope, $rootScope, $http, $location) {
        $rootScope.loading = true;
        var params = $location.search();
        params.fun = "details";
        $scope.id = params.id;
        //获取页面内容
        $http.get('index.php', {
            params: params
        }).success(function(data) {
            if (data.code == 0) {
                //页面颜色
                var color = {'1': 'teal', '2': 'green', '3': 'blue'};
                var colour = {'1': '#00B5AD', '2': '#A1CF64', '3': '#6ECFF5'};
                data.data.color = color[data.data.type];
                data.data.colour = colour[data.data.type];
                $scope.data = data.data;
                $scope.info = data.info;
            } else {
                //错误弹窗
                Messenger().post({
                    message: data.msg || '服务器错误!',
                    showCloseButton: true
                });
            }
            $rootScope.loading = false;
        });
        //删除
        $scope.del = function() {
            if (confirm("删除是不可恢复的，你确认要删除吗？")) {
                $rootScope.loading=true;
                params = {fun: 'del', id:$scope.id}
                $http.get('index.php', {
                    params: params
                }).success(function(data) {
                    if (data.code == 0) {
                        //跳转到上个页面
                        $location.url($rootScope.lastPage);
                    }
                    //错误或正确均弹窗
                    Messenger().post({
                        message: data.msg || '服务器错误!',
                        showCloseButton: true
                    });
                    $rootScope.loading = false;
                })
            }
        }
        //评论
        $scope.length = 5;
        $scope.start = 0;
        $scope.commentError = $scope.addDisabled = false;
        $scope.addComment = function() {
            var comment = $("textarea").val();
            //验证评论内容
            if ((comment.length > 0) && (comment.length < 140)) {
                $scope.addDisabled = true;
                //get参数
                params = {fun: 'comment', id: $scope.id, comment: comment};
                //提交
                $http.get('index.php', {
                    params: params
                //成功返回
                }).success(function(data) {
                    //正常
                    if (data.code == 0) {
                        $scope.start = 0;
                        $("textarea").val("");
                        $scope.addDisabled = false;
                        $scope.data.comments.splice(0, 0, data.data);
                        //滚动到评论位置
                        $('html,body').animate({scrollTop: $('#comments').offset().top}, 'slow');
                    //异常
                    } else {
                        //错误弹窗
                        Messenger().post({
                            message: data.msg || '服务器错误!',
                            showCloseButton: true
                        });
                    }
                });
            //错误返回
            } else {
                $scope.commentError=true;
            }
        }
    }
    //分页
    LAF.filter('paging', function() { 
        return function(items, start, len) {
            //非object类型直接返回
            if (typeof items != "object") {
                return items;
            }
            //分页起始位置和分页长度
            start = parseInt(start);
            len = parseInt(len);
            var end = (start + len) < items.length ? (start + len): items.length;
            //顺序或倒叙
            return items.slice(start, end);
        }
    })
    //排序
    LAF.filter("sortBy", function() {
        return function(items, name, by) {
            items = _.sortBy(items, function(item) {
                return item[name];
            });
            return by ? items: items.reverse();
        }
    })
    //注入angular
    LAF.controller("edit", edit);
    LAF.controller("list", list);
    LAF.controller("user", user);
    LAF.controller("details", details);
    LAF.controller("mainCtrl", mainCtrl);
})();