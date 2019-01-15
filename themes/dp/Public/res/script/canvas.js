var canvas,ctx;
	var vertexes = [];
	var diffPt = [];var autoDiff = 1000;
	var verNum = 250;
	var canvasW = window.innerWidth+40;
	var addListener = function( e, str, func ) {
		if( e.addEventListener ) {
			e.addEventListener( str, func, false );
		}else if( e.attachEvent ) {
			e.attachEvent( "on" + str, func );
		}else {
			
		}
	};
	
	
	addListener( window, "load", init );
	
	function resize(){
		canvasW = document.getElementById('container').offsetWidth + 40;	
		initCanvas(canvasW,800);//如果图片太多显示不全，可以把数字调大
			var cW = canvas.width;
			var cH = canvas.height;
			for(var i = 0;i < verNum;i++)
				vertexes[i] = new Vertex(cW / (verNum -1) * i , cH / 10,cH/10);
			initDiffPt();
		var win_3 = window.innerWidth/3;

	}
	function init(){
		resize();
		var FPS =30;
		var interval = 1000 / FPS >> 0;
		var timer = setInterval( update, interval );
//		if ( window.addEventListener ) addListener( window, "DOMMouseScroll", wheelHandler );
//		addListener( window, "mousewheel", wheelHandler );
		addListener(window,"resize",resize);
		
		canvas.onmousedown=function(e)
		{
			
				
		var mouseX,mouseY;
				if (e) {
					mouseX = e.pageX;
					mouseY = e.pageY;
				}else {
				mouseX = event.x + document.body.scrollLeft;
				mouseY = event.y + document.body.scrollTop;
				}
				
				
				if(window.innerHeight - mouseY < 50 && window.innerHeight - mouseY> -50)
					//diffPt[150] = autoDiff;
					console.log(1);
					{
					autoDiff = 1000;
					if(mouseX<canvas.width-2){
						xx = 1 + Math.floor((verNum - 2) * mouseX / canvas.width);
						
						diffPt[xx] = autoDiff;
					}
					
					}
		}
	}
	
//	var wheelHandler = function( e ) {
//			var s = ( e.detail ) ? -e.detail : e.wheelDelta;
//			s > 0 ? ( dd > 15 ? dd-- :  dd=dd) : ( dd < 50 ? dd++ : dd=dd );
//	};
	
	function initDiffPt(){
		for(var i=0;i<verNum;i++)
		   diffPt[i]= 0;
	}
	var xx = 150;
	var dd = 15;
	
	function update(){
		//ctx.rect(50,20,280,620);
		//ctx.stroke();
		//ctx.clip();
		ctx.clearRect(0, 0, canvas.width, canvas.height);
		autoDiff -= autoDiff*0.9;
		diffPt[xx] = autoDiff;
		//左侧
		//差分，使得每个点都是上一个点的下一次的解，由于差分函数出来的解是一个曲线，且每次迭代后，曲线相加的结果形成了不断地波浪
			for(var i=xx-1;i>0;i--)
				{
				    var d = xx-i;
					if(d > dd)d=dd;
					diffPt[i] -= (diffPt[i]-diffPt[i+1])*(1-0.01*d);
				}
		//右侧
			for(var i=xx+1;i<verNum;i++)
				{
				    var d = i-xx;
					if(d > dd)d=dd;
					diffPt[i] -= (diffPt[i]-diffPt[i-1])*(1-0.01*d);
				}
		
		//更新点Y坐标
		for(var i = 0;i < vertexes.length;i++){
			vertexes[i].updateY(diffPt[i]);
		}

		draw();
		
	}
	var color1="#ffcb33";
	var color2 = "#ffcc33";
	function draw(){
		ctx.beginPath();
		ctx.moveTo(0,canvas.height);
		ctx.fillStyle=color1;
		ctx.lineTo(vertexes[0].x,vertexes[0].y);
		for(var i = 1;i < vertexes.length;i++){
			ctx.lineTo(vertexes[i].x,vertexes[i].y);
		}
		ctx.lineTo(canvas.width,canvas.height);
		ctx.lineTo(0,canvas.height);
		ctx.fill();

		ctx.beginPath();
		ctx.moveTo(0,canvas.height);
		ctx.fillStyle=color2;
		ctx.lineTo(vertexes[0].x+15,vertexes[0].y+5);
		for(var i = 1;i < vertexes.length;i++){
			ctx.lineTo(vertexes[i].x+15,vertexes[i].y+5);
		}
		ctx.lineTo(canvas.width,canvas.height);
		ctx.lineTo(0,canvas.height);
		ctx.fill();
		
		
//		获取数据
		var nameimg=$(".naming img");
		var Cooperative=$(".cooperative_partner img");
		var Associated=$(".associated_media img");

		var footer_top=[
		{'name':'独家冠名','en':'Exclusive Title','img':nameimg},
		{'name':'合作单位','en':'Cooperative Partner','img':Cooperative},
		{'name':'合作媒体','en':'Associated Media','img':Associated}
		];
		
		
		var img=$(".naming img");
		var imgheight=0;//图片行数*高度
		
		for (var i=0;i<footer_top.length;i++) {
			
			
			//中文标题
			ctx.fillStyle="#333";
			ctx.font="22px 微软雅黑";
			ctx.textBaseline="center";
			if(i==0){
				ctx.fillText(footer_top[i].name,canvas.width/2-530,canvas.height/10+140+i*80);
			}else{
				imgheight+=parseInt(footer_top[i-1].img.length/7+1)*55;
				ctx.fillText(footer_top[i].name,canvas.width/2-530,canvas.height/10+140+i*90+imgheight);
			}

			ctx.fillStyle="#333";
			ctx.font="14px 微软雅黑";
			ctx.textBaseline="center";
			if(i==0){
				ctx.fillText("",canvas.width/2-346,canvas.height/10+190);
			}
			//英文标题
			ctx.fillStyle="#fff";
			ctx.font="14px 微软雅黑";
			ctx.textBaseline="center";

			if(i==0){
			ctx.fillText(footer_top[i].en,canvas.width/2-436,canvas.height/10+140);
			}else{
				ctx.fillText(footer_top[i].en,canvas.width/2-436,canvas.height/10+140+i*90+imgheight);
			}
			
			
			//图片
			if(i==0){
				for (var j=0 ;j<footer_top[i].img.length;j++) {
						ctx.drawImage(footer_top[i].img[j],canvas.width/2-530+(j%6)*184,canvas.height/10+164,174,46);
				}
			}else{
				for (var j=0 ;j<footer_top[i].img.length;j++) {
					ctx.drawImage(footer_top[i].img[j],canvas.width/2-530+(j%6)*184,canvas.height/10+164+i*90+imgheight+parseInt(j/6)*55,174,46);
				}
			}
			
			
			
		}
		
	}
	function initCanvas(width,height){
		canvas = document.getElementById("canvas");
		canvas.width = width;
		canvas.height = height;
		ctx = canvas.getContext("2d");
		
	}
		
	function Vertex(x,y,baseY){
		this.baseY = baseY;
		this.x = x;
		this.y = y;
		this.vy = 0;
		this.targetY = 0;
		this.friction = 0.15;
		this.deceleration = 0.95;
	}
		
	Vertex.prototype.updateY = function(diffVal){
		this.targetY = diffVal + this.baseY;
		this.vy += this.targetY - this.y
		this.y += this.vy * this.friction;
		this.vy *= this.deceleration;
	}
