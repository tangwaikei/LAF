(function(){
    var isNum=/^\d*$/;
    var isDate=/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|1[0-9]|2[0-9]|3[0-1])$/;
    //消息控件
    Messenger.options={extraClasses:'messenger-fixed messenger-on-bottom',theme:'flat'}
    //AngularJS模块
    var LAF=angular.module("LAF",["ngRoute","ngCookies"]);
    //自定义markdown指令
    LAF.directive('markdown',function(){
        return {
            restrict:'EA',
            require:'?ngModel',
            link:function(scope, element, attrs, ngModel){
                scope.$watch((function(){
                    return ngModel.$modelValue;
                }),function(newValue){
                    element.html(marked(newValue||'',{
                        sanitize: true
                    }));
                });
            }
        };
    });
    //Angular JS核心配置
    LAF.config(function($routeProvider,$locationProvider){
        $routeProvider.
        when("/add",{controller:add,templateUrl: "views/add.html"}).
        when("/list",{controller:list,templateUrl: "views/list.html"}).
        when("/user",{controller:user,templateUrl: "views/user.html"}).
        when("/details",{controller:details,templateUrl: "views/details.html"}).
        otherwise({redirectTo: "/list"});
    });
    //主控
    var mainCtrl=function($scope,$rootScope,$location,$cookies){
        $rootScope.loading=false;
        //返回上级
        $scope.$watch(function(){return $location.url();},function(newUrl,oldUrl){
            //记录上一级地址
            if($scope.lastPage==$location.url())
                $scope.lastPage='/list';
            else if($scope.lastUrl){
                var length=$scope.lastUrl.indexOf('?')>0? $scope.lastUrl.indexOf('?'):$scope.lastUrl.length;
                var path=$scope.lastUrl.substr(0,length)
                if(path!=$location.path())
                    $scope.lastPage=$scope.lastUrl;
            }else $scope.lastPage=oldUrl;
            if($scope.lastPage==$location.url())
                $scope.lastPage='/list';
            $scope.lastUrl=$location.url();
            //从COOKIES中获取权限
            $rootScope.rank=$cookies.rank || 0;
        });
    }
    //添加
    var add=function($scope,$rootScope,$http,$location){
        $('.ui.radio.checkbox').checkbox();
        //提交
        $scope.addSubmit=function(){
            var hasError=$scope.addDisabled=false;
            $scope.titleError=$scope.dateError=$scope.linkmenError=$scope.phoneError=$scope.qqError=false;
            var type=$("input[name='type']:checked").val();
            var title=$("input[name='title']").val();
            var date=$("input[name='date']").val();
            var where=$("input[name='where']").val();
            var linkmen=$("input[name='linkmen']").val();
            var studentId=$("input[name='studentId']").val();
            var phone=$("input[name='phone']").val();
            var qq=$("input[name='qq']").val();
            if(!type in [1,2,3])
                hasError=$scope.typeError=true;
            if(title.length==0||title.length>30)
                hasError=$scope.titleError=true;
            if(date.length==0||!isDate.test(date))
                hasError=$scope.dateError=true;
            if(where.length==0||where.length>30)
                hasError=$scope.whereError=true;
            if(linkmen.length==0||linkmen.length>30)
                hasError=$scope.linkmenError=true;
            if(studentId&&(studentId.length<6||studentId.length>15))
                hasError=$scope.studentIdError=true;
            if(phone&&(phone.length!=11||!isNum.test(phone)))
                hasError=$scope.phoneError=true;
            if(qq&&(qq.length<5||qq.length>11||!isNum.test(qq)))
                hasError=$scope.qqError=true;
            if(type!=1&&!phone&&!qq)
                hasError=$scope.contactError=true;
            if(!hasError&&!$scope.addDisabled){
                $scope.addDisabled=true;
                $('#addForm').submit();
            }
        }
    }
    //列表
    var list=function($scope,$rootScope,$http,$location){
        $rootScope.loading=true;
        var params=$location.search();
        params.fun='list';
        $scope.type=params.type? params.type:0;
        $scope.key=$location.search().key || "";
        $("input[name='key']").val($scope.key);
        $http.get('index.php',{
            params:params
        }).success(function(data){
            if(data.code==0){
                $scope.data=data.data;
                $scope.info=data.info;
            }else Messenger().post({message: data.msg,showCloseButton: true});
            $rootScope.loading=false;
        });
        $scope.listSubmit=function(){
            var key=$("input[name='key']").val();
            $location.search({fun:'list',key:key});
            return false;
        }
    }
    //用户
    var user=function($scope,$rootScope,$http,$location){
        if(!$rootScope.rank)
            $location.path("/list");
        $rootScope.loading=true;
        var params=$location.search();
        params.fun='user';
        params.user=true;
        $scope.type=params.type? params.type:0;
        $http.get('index.php',{
            params:params
        }).success(function(data){
            if(data.code==0){
                $scope.data=data.data;
                $scope.info=data.info;
            }else Messenger().post({message: data.msg,showCloseButton: true});
            $rootScope.loading=false;
        });
    }
    //详情
    var details=function($scope,$rootScope,$http,$location){
        $rootScope.loading=true;
        var params=$location.search();
        params.fun="details";
        $scope.id=params.id;
        $http.get('index.php',{
            params:params
        }).success(function(data){
            if(data.code==0){
                var color={'1':'teal','2':'green','3':'blue'};
                var colour={'1':'#00B5AD','2':'#A1CF64','3':'#6ECFF5'};
                data.data.color=color[data.data.type];
                data.data.colour=colour[data.data.type];
                $scope.data=data.data;
                $scope.info=data.info;
            }else Messenger().post({message: data.msg,showCloseButton: true});
            $rootScope.loading=false;
        });
        //评论
        $scope.length=5;$scope.start=0;
        $scope.commentError=$scope.addDisabled=false;
        $scope.addComment=function(){
            var comment=$("textarea").val();
            if(comment.length>0&&comment.length<140){
                $scope.addDisabled=true;
                $http.get('index.php',{
                    params:{fun:'comment',id:$scope.id,comment:comment}
                }).success(function(data){
                    if(data.code==0){
                        $scope.start=0;
                        $("textarea").val("");
                        $scope.addDisabled=false;
                        $scope.data.comments.splice(0,0,data.data);
                        $('html,body').animate({scrollTop:$('#comments').offset().top},'slow');
                    }else Messenger().post({message: data.msg,showCloseButton: true});
                });
            }else $scope.commentError=true;
        }
    }
    //分页
    LAF.filter('paging',function(){ 
        return function(items,start,len){
            if(typeof items!="object")
                return items;
            start=parseInt(start);
            len=parseInt(len);
            var end=(start+len)<items.length? (start+len):items.length;
            return items.slice(start,end);
        }
    })
    //排序
    LAF.filter("sortBy",function(){
        return function(items,name,by){
            items=_.sortBy(items,function(item){
                return item[name];
            });
            return by? items:items.reverse();
        }
    })
    //函数
    LAF.controller("add",add);
    LAF.controller("list",list);
    LAF.controller("user",user);
    LAF.controller("details",details);
    LAF.controller("mainCtrl",mainCtrl);
})();