var timespan = navigator.userAgent.indexOf("Firefox") > 0 ? 15 : 10;
var AdConfig = false;


function addEvent(obj, eventType, func)
{
	if (obj.addEventListener)
	{
		obj.addEventListener(eventType, func, false);
	}
	else if (obj.attachEvent)
	{
		obj.attachEvent("on" + eventType, func);
	}
}


function AdConfigInit()
{
	AdConfig = new Object();
	AdConfig.Left = 0;
	AdConfig.Top = 0;
	AdConfig.Width = 0;
	AdConfig.Height = 0;
	AdConfig.Scroll = function()
	{
		if (document.documentElement && document.documentElement.scrollLeft)
		{
		   AdConfig.Left = document.documentElement.scrollLeft;
		}
		else if (document.body)
		{
		   AdConfig.Left = document.body.scrollLeft;
		}

		if (document.documentElement && document.documentElement.scrollTop)
		{
		   AdConfig.Top = document.documentElement.scrollTop;
		}
		else if (document.body)
		{
		   AdConfig.Top = document.body.scrollTop;
		}
	}
	AdConfig.Resize = function()
	{
		if (document.documentElement && document.documentElement.clientHeight && document.body && document.body.clientHeight)
		{
			AdConfig.Width = (document.documentElement.clientWidth > document.body.clientWidth) ? document.body.clientWidth : document.documentElement.clientWidth;
			AdConfig.Height = (document.documentElement.clientHeight > document.body.clientHeight) ? document.body.clientHeight : document.documentElement.clientHeight;
		}
		else if (document.documentElement && document.documentElement.clientHeight)
		{
			AdConfig.Width = document.documentElement.clientWidth;
			AdConfig.Height = document.documentElement.clientHeight;
		}
		else if (document.body)
		{
			AdConfig.Width = document.body.clientWidth;
			AdConfig.Height = document.body.clientHeight;
		}
	}

	AdConfig.Scroll();
	AdConfig.Resize();
	addEvent(window, "scroll", AdConfig.Scroll);
	addEvent(window, "resize", AdConfig.Resize);
}

function AdPopup(id, title, content, width, height, top, side, autoClose)
{
	var popup = window.open("", "win" + id, "width=" + width + ", height=" + height + ", top=" + top + ", left=" + side);
	popup.document.write("<html><head><title>");
	popup.document.write(title);
	popup.document.write("</title><body style='margin:0;cursor:pointer;overflow:hidden;'");
	if (autoClose)
	{
		popup.document.write(" onclick='window.close();'");
	}
	popup.document.write(">");
	popup.document.write(content);
	popup.document.write("</body></html>");
}

function AdFloat(obj, width, height, top, side, autoClose)
{
	var directX = 1;
	var directY = 1;

	obj.Move = function()
	{
		if (side + width >= AdConfig.Left + AdConfig.Width)
		{
			side = AdConfig.Left + AdConfig.Width - width;
			directX = -1;
		}
		else if (side <= AdConfig.Left)
		{
			side = AdConfig.Left;
			directX = 1;
		}

		if (top + height >= AdConfig.Top + AdConfig.Height)
		{
			top = AdConfig.Top + AdConfig.Height - height;
			directY = -1;
		}
		else if (top <= AdConfig.Top)
		{
			top = AdConfig.Top;
			directY = 1;
		}

		side += directX;
		top += directY;
		obj.style.left = side + "px";
		obj.style.top = top + "px";
	}

	var interval = window.setInterval(obj.Move, timespan);
	obj.onmouseover = function()
	{
		window.clearInterval(interval);
	}
	obj.onmouseout = function()
	{
		interval = window.setInterval(obj.Move, timespan);
	}
	if (autoClose)
	{
		obj.onclick = function()
		{
			window.clearInterval(interval);
			obj.style.display = "none";
		}
	}
}

function AdHangLeft(obj, top, autoClose)
{
	obj.Move = function()
	{
		var t = parseInt(obj.style.top, 10);
		if (t + 5 < AdConfig.Top + top)
		{
			obj.style.top = (t + 5) + "px";
		}
		else if (t - 5 > AdConfig.Top + top)
		{
			obj.style.top = (t - 5) + "px";
		}
	}

	var interval = window.setInterval(obj.Move, timespan);
	if (autoClose)
	{
		obj.onclick = function()
		{
			window.clearInterval(interval);
			obj.style.display = "none";
		}
	}
}

function AdHangRight(obj, top, autoClose)
{
	obj.Move = function()
	{
		var t = parseInt(obj.style.top, 10);
		if (t + 5 < AdConfig.Top + top)
		{
			obj.style.top = (t + 5) + "px";
		}
		else if (t - 5 > AdConfig.Top + top)
		{
			obj.style.top = (t - 5) + "px";
		}
	}

	var interval = window.setInterval(obj.Move, timespan);
	if (autoClose)
	{
		obj.onclick = function()
		{
			window.clearInterval(interval);
			obj.style.display = "none";
		}
	}
}

function AdPrepare(id, title, url, mode, pic, width, height, top, side, autoClose)
{
	if (!AdConfig)
		AdConfigInit();

	var content = AdContent(url, pic, width, height);
	if (mode == "popup")
	{
		AdPopup(id, title, content, width, height, top, side, autoClose);
		return;
	}

	document.writeln("<span id='adver_" + id + "' style='z-index:99999; width:" + width + "px; height:" + height + "px; top:" + top + "px; " + (mode == "hangR" ? "right" : "left") + ":" + side + "px; cursor:pointer; position:absolute; overflow:hidden;'>" + content + "</span>");

	var obj = document.getElementById("adver_" + id);
	switch(mode)
	{
		case "float":
			AdFloat(obj, width, height, top, side, autoClose);
			break;
		case "hangL":
			AdHangLeft(obj, top, autoClose);
			break;
		case "hangR":
			AdHangRight(obj, top, autoClose);
			break;
	}
}

function AdContent(url, pic, width, height)
{
	var picType = pic.substr(pic.lastIndexOf(".") + 1).toLowerCase();
	var content;

	if (picType == "swf")
	{
		content = "<object classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' codebase='http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0' width='" + width + "' height='" + height + "'><param name='movie' value='" + pic + "'><param name='quality' value='high'><param name='wmode' value='transparent'><embed src='" + pic + "' width='" + width + "' height='" + height + "' quality='high' pluginspage='http://www.macromedia.com/go/getflashplayer' type='application/x-shockwave-flash' wmode='transparent'></embed></object>";

		if (url != "")
			content = "<span style='z-index:1;position:absolute;'><a href='" + url + "' target='_blank'><img src='js/blank.gif' width='" + width + "' height='" + height + "' /></a><span style='position:absolute; top:0; right:0;'><img src='js/closed.jpg' /></span></span>" + content;
		else
			content = "<span style='z-index:1;position:absolute;'><img src='js/blank.gif' width='" + width + "' height='" + height + "' /></span>" + content;
	}
	else
	{
		content = "<img src='" + pic + "' width='" + width + "' height='" + height + "' border='0' />";
		if (url != "")
			content = "<a href='" + url + "' target='_blank'>" + content + "</a><span style='position:absolute; top:0; right:0;'><img src='js/closed.jpg' /></span></span>";
	}

	return content;
}