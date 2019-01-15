function setHeight(){
		var contentHeight=document.documentElement.clientHeight-400;
		// alert(document.documentElement.clientHeight);
		// alert(contentHeight);
		var height = $('.orderConfirmContent').height(); 
		if(height<contentHeight){
		$('.orderConfirmContent').css('height',contentHeight+'px');
		}
	}
$(function(){
	
	setHeight();
	setTimeout('setHeight()',300);
	
})

