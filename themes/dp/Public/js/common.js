//滚动
function marquee(i, direction)
{
	var obj = document.getElementById("marquee" + i);
	var obj1 = document.getElementById("marquee" + i + "_1");
	var obj2 = document.getElementById("marquee" + i + "_2");

	if (direction == "up")
	{
		if (obj2.offsetTop - obj.scrollTop <= 0)
		{
			obj.scrollTop -= (obj1.offsetHeight + 20);
		}
		else
		{
			var tmp = obj.scrollTop;
			obj.scrollTop++;
			if (obj.scrollTop == tmp)
			{
				obj.scrollTop = 1;
			}
		}
	}
	else
	{
		if (obj2.offsetWidth - obj.scrollLeft <= 0)
		{
			obj.scrollLeft -= obj1.offsetWidth;
		}
		else
		{
			obj.scrollLeft++;
		}
	}
}

function marqueeStart(i, direction)
{
	var obj = document.getElementById("marquee" + i);
	var obj1 = document.getElementById("marquee" + i + "_1");
	var obj2 = document.getElementById("marquee" + i + "_2");

	obj2.innerHTML = obj1.innerHTML;
	var marqueeVar = window.setInterval("marquee("+ i +", '"+ direction +"')", 20);
	obj.onmouseover = function(){window.clearInterval(marqueeVar);}
	obj.onmouseout = function(){marqueeVar = window.setInterval("marquee("+ i +", '"+ direction +"')", 20);}
}

//屏蔽鼠标右键
function disabledRightButton()
{
	document.oncontextmenu = function(e){return false;}
	document.onselectstart = function(e){return false;}
	if (navigator.userAgent.indexOf("Firefox") > -1)
	{
		document.writeln("<style>body {-moz-user-select: none;}</style>");
	}
}

//设为首页
function setHomePage()
{
	if(document.all)
	{
		var obj = document.links(0);
		if (obj)
		{
			obj.style.behavior = 'url(#default#homepage)';
			obj.setHomePage(window.location.href);
		}
  	}
	else
	{
		if(window.netscape)
		{
			try
			{
				netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");
			}
			catch (e)
			{
				window.alert("此操作被浏览器拒绝，请通过浏览器菜单完成此操作！");
			}
		}
   	}
}

//加入收藏
function addFavorite()
{
	var url		= document.location.href;
	var title	= document.title;
	if (document.all)
	{
		window.external.addFavorite(url,title);
	}
	else if (window.sidebar)
	{
		window.sidebar.addPanel(title, url,"");
	}
}