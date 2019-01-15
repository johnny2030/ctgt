//	登陆
		$(".loginbtn").click(function(e){
//			$('#LoginModal').modal('hide'); //模态框关闭
			e.preventDefault();
		})
	//	注册
		$(".regbtn").click(function(e){
			$(".tab_main").hide();
			$(".success").show();
			e.preventDefault();
		})
	//	注册成功
		$(".success_btn").click(function(e){
			$('#LoginModal').modal('hide')
			$(".tab_main").show();
			$(".success").hide();
			e.preventDefault();
		})
	//	去注册
		$(".go_reg").click(function(e){
			$(".LoginCenterModel .nav-tabs > li  a").eq(1).click();
			e.preventDefault();
		})
	//	去登录
		$(".go_login").click(function(e){
			$(".LoginCenterModel .nav-tabs > li  a").eq(0).click();
			e.preventDefault();
		})
